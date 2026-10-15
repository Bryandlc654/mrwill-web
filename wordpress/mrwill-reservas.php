<?php
// Copia del fragmento «MR WILL – Reservas» (Code Snippets).
// En Code Snippets se pega SIN la línea <?php inicial.
// Requiere el fragmento «MR WILL – Panel de ajustes y reseñas» (menú «mrwill» y mrwill_wa_number()).

/**
 * MR WILL – Reservas.
 *
 * - Modal de reserva en 3 pasos (fecha y hora → personas y datos → resumen) que se abre
 *   desde cualquier enlace a «#reserva» y desde los enlaces de WhatsApp que hablan de reservar.
 * - Guarda cada solicitud como «Reserva» (MR WILL → Reservas), avisa por email y ofrece
 *   enviarla también por WhatsApp.
 * - Ajustes en MR WILL → Reservas · Ajustes: horas, días cerrados, antelación y email de avisos.
 */

function mrwill_res_opt( $key ) {
	$defaults = array(
		'mrwill_res_horas'     => '13:00, 14:30, 20:00, 21:30',
		'mrwill_res_cerrados'  => array(),
		'mrwill_res_antelacion'=> 60,
		'mrwill_res_email'     => get_option( 'admin_email' ),
	);
	$value = get_option( $key, null );
	return ( $value === null || $value === '' ) ? $defaults[ $key ] : $value;
}

function mrwill_res_horas() {
	$horas = array();
	foreach ( explode( ',', (string) mrwill_res_opt( 'mrwill_res_horas' ) ) as $h ) {
		$h = trim( $h );
		if ( preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $h ) ) {
			$horas[] = str_pad( $h, 5, '0', STR_PAD_LEFT );
		}
	}
	sort( $horas );
	return array_values( array_unique( $horas ) );
}

function mrwill_res_cerrados() {
	return array_map( 'intval', (array) mrwill_res_opt( 'mrwill_res_cerrados' ) );
}

/* ---------- Tipo de contenido «Reserva» ---------- */

add_action( 'init', function () {
	register_post_type( 'mrwill_reserva', array(
		'labels'          => array(
			'name'          => 'Reservas',
			'singular_name' => 'Reserva',
			'edit_item'     => 'Ver reserva',
			'all_items'     => 'Reservas',
			'search_items'  => 'Buscar reservas',
			'not_found'     => 'Todavía no hay reservas',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'mrwill',
		'show_in_rest'    => false,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );

	register_setting( 'mrwill_reservas', 'mrwill_res_horas', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'mrwill_reservas', 'mrwill_res_cerrados', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $v ) {
			return array_values( array_intersect( array_map( 'intval', (array) $v ), range( 0, 6 ) ) );
		},
	) );
	register_setting( 'mrwill_reservas', 'mrwill_res_antelacion', array(
		'type'              => 'integer',
		'sanitize_callback' => function ( $v ) {
			return max( 1, min( 365, (int) $v ) );
		},
	) );
	register_setting( 'mrwill_reservas', 'mrwill_res_email', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email' ) );
} );

if ( ! defined( 'MRWILL_RES_ESTADOS' ) ) {
	define( 'MRWILL_RES_ESTADOS', array(
		'pendiente'  => 'Pendiente',
		'confirmada' => 'Confirmada',
		'cancelada'  => 'Cancelada',
	) );
}

/* ---------- Admin: ajustes de reservas ---------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'mrwill', 'Ajustes de reservas', 'Reservas · Ajustes', 'manage_options', 'mrwill-reservas', 'mrwill_res_settings_page' );
}, 20 );

function mrwill_res_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$dias     = array( 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo' );
	$cerrados = mrwill_res_cerrados();
	?>
	<div class="wrap">
		<h1>Ajustes de reservas</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mrwill_reservas' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mrwill_res_horas">Horas disponibles</label></th>
					<td>
						<input name="mrwill_res_horas" id="mrwill_res_horas" type="text" class="regular-text" value="<?php echo esc_attr( mrwill_res_opt( 'mrwill_res_horas' ) ); ?>">
						<p class="description">Separadas por comas, en formato 24 h (ejemplo: 13:00, 14:30, 20:00, 21:30).</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Días cerrados</th>
					<td>
						<?php foreach ( $dias as $num => $nombre ) : ?>
							<label style="margin-right:1rem"><input type="checkbox" name="mrwill_res_cerrados[]" value="<?php echo $num; ?>" <?php checked( in_array( $num, $cerrados, true ) ); ?>> <?php echo esc_html( $nombre ); ?></label>
						<?php endforeach; ?>
						<p class="description">Esos días no se podrán elegir en el calendario.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mrwill_res_antelacion">Antelación máxima</label></th>
					<td><input name="mrwill_res_antelacion" id="mrwill_res_antelacion" type="number" min="1" max="365" class="small-text" value="<?php echo (int) mrwill_res_opt( 'mrwill_res_antelacion' ); ?>"> días</td>
				</tr>
				<tr>
					<th scope="row"><label for="mrwill_res_email">Email de avisos</label></th>
					<td>
						<input name="mrwill_res_email" id="mrwill_res_email" type="email" class="regular-text" value="<?php echo esc_attr( mrwill_res_opt( 'mrwill_res_email' ) ); ?>">
						<p class="description">Aquí llega un aviso con cada nueva reserva.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Guardar cambios' ); ?>
		</form>
	</div>
	<?php
}

/* ---------- Admin: listado y ficha de reservas ---------- */

add_filter( 'manage_mrwill_reserva_posts_columns', function ( $columns ) {
	return array(
		'cb'       => $columns['cb'],
		'title'    => 'Cliente',
		'fecha'    => 'Fecha',
		'hora'     => 'Hora',
		'personas' => 'Personas',
		'telefono' => 'Teléfono',
		'estado'   => 'Estado',
		'date'     => 'Recibida',
	);
} );

add_action( 'manage_mrwill_reserva_posts_custom_column', function ( $column, $post_id ) {
	$m = function ( $k ) use ( $post_id ) {
		return get_post_meta( $post_id, 'mrwill_res_' . $k, true );
	};
	switch ( $column ) {
		case 'fecha':
			$f = $m( 'fecha' );
			echo $f ? esc_html( date_i18n( 'D j M Y', strtotime( $f ) ) ) : '—';
			break;
		case 'hora':
			echo esc_html( $m( 'hora' ) );
			break;
		case 'personas':
			echo esc_html( $m( 'personas' ) );
			break;
		case 'telefono':
			$tel = $m( 'telefono' );
			echo '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $tel ) ) . '">' . esc_html( $tel ) . '</a>';
			break;
		case 'estado':
			$e      = $m( 'estado' ) ?: 'pendiente';
			$colors = array( 'pendiente' => '#b45309', 'confirmada' => '#15803d', 'cancelada' => '#b91c1c' );
			printf( '<strong style="color:%s">%s</strong>', esc_attr( $colors[ $e ] ?? '#000' ), esc_html( MRWILL_RES_ESTADOS[ $e ] ?? $e ) );
			break;
	}
}, 10, 2 );

add_filter( 'manage_edit-mrwill_reserva_sortable_columns', function ( $cols ) {
	$cols['fecha'] = 'mrwill_res_fecha';
	return $cols;
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && $q->get( 'post_type' ) === 'mrwill_reserva' && $q->get( 'orderby' ) === 'mrwill_res_fecha' ) {
		$q->set( 'meta_key', 'mrwill_res_fecha' );
		$q->set( 'orderby', 'meta_value' );
	}
} );

add_action( 'add_meta_boxes_mrwill_reserva', function () {
	add_meta_box( 'mrwill_reserva_datos', 'Datos de la reserva', function ( $post ) {
		wp_nonce_field( 'mrwill_reserva_guardar', 'mrwill_reserva_nonce' );
		$m = function ( $k ) use ( $post ) {
			return get_post_meta( $post->ID, 'mrwill_res_' . $k, true );
		};
		$fecha = $m( 'fecha' );
		$rows  = array(
			'Fecha'       => $fecha ? date_i18n( 'l j \d\e F \d\e Y', strtotime( $fecha ) ) : '',
			'Hora'        => $m( 'hora' ),
			'Personas'    => $m( 'personas' ),
			'Nombre'      => $m( 'nombre' ),
			'Teléfono'    => $m( 'telefono' ),
			'Email'       => $m( 'email' ),
			'Comentarios' => $m( 'notas' ),
		);
		echo '<table class="widefat striped" style="max-width:640px"><tbody>';
		foreach ( $rows as $label => $value ) {
			printf( '<tr><th style="width:140px">%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( $value ?: '—' ) ) );
		}
		echo '</tbody></table>';
	}, 'mrwill_reserva', 'normal', 'high' );

	add_meta_box( 'mrwill_reserva_estado', 'Estado', function ( $post ) {
		$estado = get_post_meta( $post->ID, 'mrwill_res_estado', true ) ?: 'pendiente';
		echo '<select name="mrwill_res_estado" style="width:100%">';
		foreach ( MRWILL_RES_ESTADOS as $k => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $estado, $k, false ), esc_html( $label ) );
		}
		echo '</select>';
		$tel = preg_replace( '/\D+/', '', (string) get_post_meta( $post->ID, 'mrwill_res_telefono', true ) );
		if ( $tel ) {
			printf( '<p><a class="button" target="_blank" rel="noopener" href="%s">Escribir al cliente por WhatsApp</a></p>', esc_url( 'https://wa.me/' . $tel ) );
		}
	}, 'mrwill_reserva', 'side', 'high' );
} );

add_action( 'save_post_mrwill_reserva', function ( $post_id ) {
	if ( ! isset( $_POST['mrwill_reserva_nonce'] ) || ! wp_verify_nonce( $_POST['mrwill_reserva_nonce'], 'mrwill_reserva_guardar' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$estado = sanitize_key( $_POST['mrwill_res_estado'] ?? 'pendiente' );
	if ( isset( MRWILL_RES_ESTADOS[ $estado ] ) ) {
		update_post_meta( $post_id, 'mrwill_res_estado', $estado );
	}
} );

/* ---------- API pública: crear reserva ---------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'mrwill/v1', '/reservas', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'mrwill_res_crear',
	) );
} );

function mrwill_res_crear( WP_REST_Request $req ) {
	$p   = $req->get_json_params();
	$err = function ( $msg ) {
		return new WP_Error( 'mrwill_reserva', $msg, array( 'status' => 400 ) );
	};

	if ( ! empty( $p['empresa'] ) ) {
		return $err( 'No se pudo enviar la reserva.' );
	}

	$ip_key = 'mrwill_res_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		return new WP_Error( 'mrwill_reserva', 'Has enviado varias reservas seguidas. Inténtalo más tarde o escríbenos por WhatsApp.', array( 'status' => 429 ) );
	}

	$tz    = wp_timezone();
	$now   = new DateTime( 'now', $tz );
	$fecha = sanitize_text_field( $p['fecha'] ?? '' );
	$hora  = sanitize_text_field( $p['hora'] ?? '' );
	$dia   = DateTime::createFromFormat( '!Y-m-d', $fecha, $tz );
	if ( ! $dia || $dia->format( 'Y-m-d' ) !== $fecha ) {
		return $err( 'Elige una fecha válida.' );
	}
	$hoy = new DateTime( $now->format( 'Y-m-d' ), $tz );
	$max = ( clone $hoy )->modify( '+' . (int) mrwill_res_opt( 'mrwill_res_antelacion' ) . ' days' );
	if ( $dia < $hoy || $dia > $max || in_array( (int) $dia->format( 'w' ), mrwill_res_cerrados(), true ) ) {
		return $err( 'Esa fecha no está disponible.' );
	}
	if ( ! in_array( $hora, mrwill_res_horas(), true ) ) {
		return $err( 'Elige una hora disponible.' );
	}
	if ( $dia->format( 'Y-m-d' ) === $now->format( 'Y-m-d' ) && $hora <= $now->format( 'H:i' ) ) {
		return $err( 'Esa hora ya ha pasado. Elige otra.' );
	}

	$personas = sanitize_text_field( (string) ( $p['personas'] ?? '' ) );
	if ( ! preg_match( '/^([1-9]|10\+)$/', $personas ) ) {
		return $err( 'Indica el número de personas.' );
	}
	$nombre = trim( sanitize_text_field( $p['nombre'] ?? '' ) );
	if ( mb_strlen( $nombre ) < 2 || mb_strlen( $nombre ) > 80 ) {
		return $err( 'Escribe tu nombre.' );
	}
	$telefono = trim( sanitize_text_field( $p['telefono'] ?? '' ) );
	$digitos  = preg_replace( '/\D+/', '', $telefono );
	if ( strlen( $digitos ) < 9 || strlen( $digitos ) > 15 ) {
		return $err( 'Escribe un teléfono válido.' );
	}
	$email = sanitize_email( $p['email'] ?? '' );
	if ( ! empty( $p['email'] ) && ! is_email( $email ) ) {
		return $err( 'El email no es válido.' );
	}
	$notas = mb_substr( sanitize_textarea_field( $p['notas'] ?? '' ), 0, 500 );
	if ( empty( $p['acepto'] ) ) {
		return $err( 'Debes aceptar el uso de tus datos para gestionar la reserva.' );
	}

	$fecha_txt = date_i18n( 'l j \d\e F', $dia->getTimestamp() );
	$post_id   = wp_insert_post( array(
		'post_type'   => 'mrwill_reserva',
		'post_status' => 'publish',
		'post_title'  => sprintf( '%s · %s %s · %s pers.', $nombre, $dia->format( 'd/m' ), $hora, $personas ),
		'meta_input'  => array(
			'mrwill_res_fecha'    => $fecha,
			'mrwill_res_hora'     => $hora,
			'mrwill_res_personas' => $personas,
			'mrwill_res_nombre'   => $nombre,
			'mrwill_res_telefono' => $telefono,
			'mrwill_res_email'    => $email,
			'mrwill_res_notas'    => $notas,
			'mrwill_res_estado'   => 'pendiente',
		),
	), true );
	if ( is_wp_error( $post_id ) ) {
		return new WP_Error( 'mrwill_reserva', 'No se pudo guardar la reserva. Inténtalo de nuevo.', array( 'status' => 500 ) );
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$resumen = "Fecha: {$fecha_txt}\nHora: {$hora}\nPersonas: {$personas}\nNombre: {$nombre}\nTeléfono: {$telefono}"
		. ( $email ? "\nEmail: {$email}" : '' ) . ( $notas ? "\nComentarios: {$notas}" : '' );

	$aviso = mrwill_res_opt( 'mrwill_res_email' );
	if ( is_email( $aviso ) ) {
		wp_mail( $aviso, 'Nueva reserva: ' . $nombre . ' · ' . $dia->format( 'd/m' ) . ' ' . $hora,
			"Nueva solicitud de reserva en la web:\n\n{$resumen}\n\nGestiónala en: " . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) );
	}

	$wa = function_exists( 'mrwill_wa_number' ) ? mrwill_wa_number() : '';
	$wa = $wa !== '' ? $wa : '34600000000';
	$msg = "¡Hola MR WILL! Acabo de solicitar una reserva en la web:\n\n{$resumen}\n\n¿Me la confirmáis? ¡Gracias!";

	return array(
		'ok'       => true,
		'id'       => $post_id,
		'whatsapp' => 'https://wa.me/' . $wa . '?text=' . rawurlencode( $msg ),
	);
}

/* ---------- Frontend: modal ---------- */

add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$config = array(
		'endpoint'   => esc_url_raw( rest_url( 'mrwill/v1/reservas' ) ),
		'horas'      => mrwill_res_horas(),
		'cerrados'   => mrwill_res_cerrados(),
		'antelacion' => (int) mrwill_res_opt( 'mrwill_res_antelacion' ),
		'hoy'        => wp_date( 'Y-m-d' ),
		'ahora'      => wp_date( 'H:i' ),
	);
	?>
<div id="mw-res" class="mw-res" hidden>
	<div class="mw-res__backdrop" data-mw-close></div>
	<div class="mw-res__dialog" role="dialog" aria-modal="true" aria-labelledby="mw-res-title" tabindex="-1">
		<button type="button" class="mw-res__x" data-mw-close aria-label="Cerrar">×</button>
		<p class="mw-res__kicker" id="mw-res-step">Paso 1 de 3 · Fecha y hora</p>
		<h2 class="mw-res__title" id="mw-res-title">Reserva tu mesa en <span>Mr Will</span></h2>
		<div class="mw-res__bar"><span id="mw-res-bar"></span></div>

		<section class="mw-res__panel" data-step="1">
			<div class="mw-res__cal-head">
				<button type="button" class="mw-res__nav" id="mw-res-prev" aria-label="Mes anterior">‹</button>
				<strong id="mw-res-month"></strong>
				<button type="button" class="mw-res__nav" id="mw-res-next" aria-label="Mes siguiente">›</button>
			</div>
			<div class="mw-res__week"><span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sá</span><span>Do</span></div>
			<div class="mw-res__days" id="mw-res-days" role="grid"></div>
			<p class="mw-res__label">Hora</p>
			<div class="mw-res__chips" id="mw-res-hours"></div>
			<p class="mw-res__hint" id="mw-res-hours-hint" hidden>No quedan horas disponibles este día. Elige otra fecha.</p>
			<button type="button" class="mw-res__btn" id="mw-res-to2" disabled>Continuar</button>
		</section>

		<section class="mw-res__panel" data-step="2" hidden>
			<p class="mw-res__label">¿Cuántas personas?</p>
			<div class="mw-res__chips" id="mw-res-people"></div>
			<form id="mw-res-form" novalidate>
				<label class="mw-res__field"><span>Nombre completo *</span><input name="nombre" autocomplete="name" required maxlength="80"></label>
				<label class="mw-res__field"><span>Teléfono / WhatsApp *</span><input name="telefono" type="tel" autocomplete="tel" inputmode="tel" required placeholder="600 000 000"></label>
				<label class="mw-res__field"><span>Email (opcional)</span><input name="email" type="email" autocomplete="email"></label>
				<label class="mw-res__field"><span>Comentarios (opcional)</span><textarea name="notas" rows="2" maxlength="500" placeholder="Alergias, trona, celebración..."></textarea></label>
				<label class="mw-res__hp" aria-hidden="true">Empresa <input name="empresa" tabindex="-1" autocomplete="off"></label>
				<label class="mw-res__check"><input type="checkbox" name="acepto" required> <span>Acepto que MR WILL use estos datos solo para gestionar mi reserva.</span></label>
				<p class="mw-res__error" id="mw-res-error2" role="alert"></p>
				<div class="mw-res__row">
					<button type="button" class="mw-res__btn mw-res__btn--ghost" data-mw-back="1">Volver</button>
					<button type="submit" class="mw-res__btn">Revisar reserva</button>
				</div>
			</form>
		</section>

		<section class="mw-res__panel" data-step="3" hidden>
			<dl class="mw-res__summary" id="mw-res-summary"></dl>
			<p class="mw-res__error" id="mw-res-error3" role="alert"></p>
			<div class="mw-res__row">
				<button type="button" class="mw-res__btn mw-res__btn--ghost" data-mw-back="2">Editar</button>
				<button type="button" class="mw-res__btn" id="mw-res-send">Confirmar reserva</button>
			</div>
		</section>

		<section class="mw-res__panel mw-res__done" data-step="4" hidden>
			<div class="mw-res__ok" aria-hidden="true">✓</div>
			<h3>¡Solicitud enviada!</h3>
			<p id="mw-res-done-text">Hemos recibido tu reserva. Te confirmaremos la mesa por teléfono o WhatsApp lo antes posible.</p>
			<a class="mw-res__btn mw-res__btn--wa" id="mw-res-wa" target="_blank" rel="noopener">Enviar también por WhatsApp</a>
			<button type="button" class="mw-res__btn mw-res__btn--ghost" data-mw-close>Cerrar</button>
		</section>
	</div>
</div>
<style id="mw-res-css">
.mw-res{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:1rem;font-family:"Plus Jakarta Sans",Montserrat,system-ui,sans-serif}
.mw-res[hidden]{display:none}
.mw-res button{padding:0;min-width:0;margin:0;line-height:1.2;text-transform:none;letter-spacing:normal;font-family:inherit;border-radius:0;white-space:nowrap}
.mw-res button:focus{outline:none}
.mw-res button:focus-visible,.mw-res a:focus-visible{outline:2px solid #fff;outline-offset:2px}
.mw-res .mw-res__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(6px);animation:mwFade .2s ease}
.mw-res .mw-res__dialog{position:relative;width:100%;max-width:30rem;max-height:calc(100vh - 2rem);overflow:auto;background:#111113;color:#e5e5e5;border:1px solid rgba(255,255,255,.1);border-radius:1.5rem;padding:2rem 1.75rem 1.75rem;box-shadow:0 30px 80px rgba(0,0,0,.6),0 0 0 1px rgba(229,25,32,.08);animation:mwUp .25s ease;outline:none}
@keyframes mwFade{from{opacity:0}}@keyframes mwUp{from{opacity:0;transform:translateY(16px) scale(.98)}}
.mw-res .mw-res__x{position:absolute;top:.9rem;right:.9rem;width:2.25rem;height:2.25rem;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;font-size:1.4rem;line-height:1;cursor:pointer}
.mw-res .mw-res__x:hover{background:#e51920;border-color:#e51920}
.mw-res .mw-res__kicker{margin:0 0 .35rem;font:700 .7rem Montserrat,sans-serif;letter-spacing:.2em;text-transform:uppercase;color:#e51920}
.mw-res .mw-res__title{margin:0 0 1rem;font:900 1.6rem/1.1 Montserrat,sans-serif;color:#fff;text-transform:uppercase;letter-spacing:-.01em}
.mw-res .mw-res__title span{color:#e51920}
.mw-res .mw-res__bar{height:4px;border-radius:9px;background:rgba(255,255,255,.08);margin-bottom:1.5rem;overflow:hidden}
.mw-res .mw-res__bar span{display:block;height:100%;width:33%;background:#e51920;border-radius:9px;transition:width .3s}
.mw-res .mw-res__cal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;color:#fff;font:800 .95rem Montserrat,sans-serif;text-transform:capitalize}
.mw-res .mw-res__nav{width:2.25rem;height:2.25rem;border-radius:.75rem;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;font-size:1.25rem;cursor:pointer}
.mw-res .mw-res__nav:disabled{opacity:.3;cursor:default}
.mw-res .mw-res__week,.mw-res .mw-res__days{display:grid;grid-template-columns:repeat(7,1fr);gap:.3rem;text-align:center}
.mw-res .mw-res__week span{font-size:.7rem;font-weight:700;color:#71717a;text-transform:uppercase;padding:.25rem 0}
.mw-res .mw-res__days button{aspect-ratio:1;border:0;border-radius:.7rem;background:rgba(255,255,255,.04);color:#e5e5e5;font:600 .85rem "Plus Jakarta Sans",sans-serif;cursor:pointer;transition:background .15s}
.mw-res .mw-res__days button:hover:not(:disabled){background:rgba(229,25,32,.25)}
.mw-res .mw-res__days button:disabled{background:transparent;color:#3f3f46;cursor:not-allowed;text-decoration:line-through}
.mw-res .mw-res__days button.is-today{box-shadow:inset 0 0 0 1px rgba(229,25,32,.6)}
.mw-res .mw-res__days button[aria-pressed=true],.mw-res .mw-res__chips button[aria-pressed=true]{background:#e51920;color:#fff;box-shadow:0 6px 18px rgba(229,25,32,.4)}
.mw-res .mw-res__label{margin:1.25rem 0 .6rem;font:700 .75rem Montserrat,sans-serif;letter-spacing:.12em;text-transform:uppercase;color:#a1a1aa}
.mw-res .mw-res__chips{display:flex;flex-wrap:wrap;gap:.5rem}
.mw-res .mw-res__chips button{min-width:3.25rem;padding:.6rem .9rem;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.04);color:#fff;font:700 .85rem Montserrat,sans-serif;cursor:pointer}
.mw-res .mw-res__chips button:hover:not(:disabled){border-color:#e51920}
.mw-res .mw-res__chips button:disabled{opacity:.3;cursor:not-allowed;text-decoration:line-through}
.mw-res .mw-res__hint{margin:.6rem 0 0;font-size:.8rem;color:#fca5a5}
.mw-res .mw-res__field{display:block;margin-top:.9rem}
.mw-res .mw-res__field span{display:block;margin-bottom:.35rem;font-size:.75rem;font-weight:600;color:#a1a1aa}
.mw-res .mw-res__field input,.mw-res .mw-res__field textarea{width:100%;box-sizing:border-box;padding:.8rem 1rem;border-radius:.85rem;border:1px solid rgba(255,255,255,.12);background:#0a0a0a;color:#fff;font:500 .95rem "Plus Jakarta Sans",sans-serif;resize:vertical}
.mw-res .mw-res__field input:focus,.mw-res .mw-res__field textarea:focus{outline:none;border-color:#e51920;box-shadow:0 0 0 3px rgba(229,25,32,.2)}
.mw-res .mw-res__field input[aria-invalid=true]{border-color:#ef4444}
.mw-res .mw-res__hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden}
.mw-res .mw-res__check{display:flex;gap:.6rem;align-items:flex-start;margin-top:1rem;font-size:.8rem;color:#a1a1aa;line-height:1.45;cursor:pointer}
.mw-res .mw-res__check input{margin-top:.15rem;accent-color:#e51920;width:1rem;height:1rem;flex:none}
.mw-res .mw-res__error{min-height:1.2em;margin:.75rem 0 0;font-size:.85rem;color:#fca5a5}
.mw-res .mw-res__error:empty{min-height:0;margin:0}
.mw-res .mw-res__row{display:flex;gap:.75rem;margin-top:1.25rem}
.mw-res .mw-res__row .mw-res__btn{margin-top:0}
.mw-res .mw-res__btn{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;margin-top:1.5rem;padding:1rem 1.25rem;border-radius:999px;border:0;background:#e51920;color:#fff;font:800 .8rem Montserrat,sans-serif;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer;box-shadow:0 10px 25px rgba(229,25,32,.35);transition:background .2s,transform .2s}
.mw-res .mw-res__btn:hover:not(:disabled){background:#b81424;transform:translateY(-1px)}
.mw-res .mw-res__btn:disabled{opacity:.4;cursor:not-allowed;box-shadow:none}
.mw-res .mw-res__btn--ghost{background:transparent;border:1px solid rgba(255,255,255,.2);box-shadow:none}
.mw-res .mw-res__btn--ghost:hover:not(:disabled){background:rgba(255,255,255,.08)}
.mw-res .mw-res__btn--wa{background:#25d366;box-shadow:0 10px 25px rgba(37,211,102,.35)}
.mw-res .mw-res__btn--wa:hover{background:#20bd5a!important}
.mw-res .mw-res__btn.is-loading{opacity:.7;pointer-events:none}
.mw-res .mw-res__summary{margin:0;display:grid;grid-template-columns:auto 1fr;gap:.6rem 1rem;padding:1.25rem;border-radius:1rem;background:#0a0a0a;border:1px solid rgba(255,255,255,.08)}
.mw-res .mw-res__summary dt{font-size:.75rem;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:.08em}
.mw-res .mw-res__summary dd{margin:0;color:#fff;font-weight:600;word-break:break-word}
.mw-res .mw-res__done{text-align:center}
.mw-res .mw-res__done h3{margin:.75rem 0 .5rem;font:900 1.4rem Montserrat,sans-serif;color:#fff;text-transform:uppercase}
.mw-res .mw-res__done p{margin:0;color:#a1a1aa;line-height:1.6}
.mw-res .mw-res__done .mw-res__btn--ghost{margin-top:.75rem}
.mw-res .mw-res__ok{width:4rem;height:4rem;margin:0 auto;border-radius:999px;background:#e51920;color:#fff;font-size:2rem;line-height:4rem;box-shadow:0 0 0 10px rgba(229,25,32,.15)}
.mw-res .mw-res__nav:hover:not(:disabled),.mw-res .mw-res__nav:focus:not(:disabled){background:rgba(229,25,32,.25);border-color:#e51920;color:#fff}
.mw-res .mw-res__x:focus{background:#e51920;border-color:#e51920;color:#fff}
.mw-res .mw-res__days button:focus:not([aria-pressed=true]):not(:hover),.mw-res .mw-res__chips button:focus:not([aria-pressed=true]):not(:hover){background:rgba(255,255,255,.04);color:#fff}
.mw-res .mw-res__days button[aria-pressed=true]:focus,.mw-res .mw-res__chips button[aria-pressed=true]:focus{background:#e51920;color:#fff}
.mw-res .mw-res__btn:focus{background:#e51920;color:#fff}.mw-res .mw-res__btn--ghost:focus{background:transparent}.mw-res .mw-res__btn--wa:focus{background:#25d366}
.mw-res .mw-res__days button{width:100%;min-width:0}
body.mw-res-open{overflow:hidden}
@media (max-width:480px){.mw-res{padding:0;align-items:flex-end}.mw-res .mw-res__dialog{max-width:none;max-height:92vh;border-radius:1.5rem 1.5rem 0 0;padding:1.75rem 1.25rem 1.5rem}.mw-res .mw-res__title{font-size:1.35rem}}
@media (prefers-reduced-motion:reduce){.mw-res .mw-res__backdrop,.mw-res .mw-res__dialog{animation:none}}
</style>
<script id="mw-res-js">
(function () {
	var C = <?php echo wp_json_encode( $config ); ?>;
	var root = document.getElementById('mw-res');
	if (!root || !C.horas.length) return;
	var dialog = root.querySelector('.mw-res__dialog');
	var $ = function (id) { return document.getElementById(id); };
	var MESES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
	var DIAS = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
	var PASOS = {1: 'Fecha y hora', 2: 'Personas y datos', 3: 'Revisa tu reserva', 4: 'Listo'};
	var parse = function (s) { var p = s.split('-'); return new Date(+p[0], p[1] - 1, +p[2]); };
	var iso = function (d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
	var hoy = parse(C.hoy);
	var max = new Date(hoy); max.setDate(max.getDate() + C.antelacion);
	var state = {fecha: null, hora: null, personas: '2', step: 1, opener: null};
	var view = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

	function disponible(d) {
		return d >= hoy && d <= max && C.cerrados.indexOf(d.getDay()) === -1 && horasLibres(d).length > 0;
	}
	function horasLibres(d) {
		var esHoy = iso(d) === C.hoy;
		return C.horas.filter(function (h) { return !esHoy || h > C.ahora; });
	}

	function renderCal() {
		$('mw-res-month').textContent = MESES[view.getMonth()] + ' ' + view.getFullYear();
		$('mw-res-prev').disabled = view <= new Date(hoy.getFullYear(), hoy.getMonth(), 1);
		$('mw-res-next').disabled = new Date(view.getFullYear(), view.getMonth() + 1, 1) > max;
		var grid = $('mw-res-days'); grid.innerHTML = '';
		var offset = (view.getDay() + 6) % 7;
		for (var i = 0; i < offset; i++) grid.appendChild(document.createElement('span'));
		var total = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
		for (var d = 1; d <= total; d++) {
			var date = new Date(view.getFullYear(), view.getMonth(), d);
			var b = document.createElement('button');
			b.type = 'button'; b.textContent = d; b.dataset.date = iso(date);
			b.setAttribute('aria-label', DIAS[date.getDay()] + ' ' + d + ' de ' + MESES[date.getMonth()]);
			b.disabled = !disponible(date);
			if (iso(date) === C.hoy) b.classList.add('is-today');
			b.setAttribute('aria-pressed', state.fecha === iso(date) ? 'true' : 'false');
			grid.appendChild(b);
		}
	}

	function renderHoras() {
		var box = $('mw-res-hours'); box.innerHTML = '';
		var libres = state.fecha ? horasLibres(parse(state.fecha)) : C.horas;
		C.horas.forEach(function (h) {
			var b = document.createElement('button');
			b.type = 'button'; b.textContent = h; b.dataset.hora = h;
			b.disabled = !state.fecha || libres.indexOf(h) === -1;
			b.setAttribute('aria-pressed', state.hora === h ? 'true' : 'false');
			box.appendChild(b);
		});
		$('mw-res-hours-hint').hidden = !state.fecha || libres.length > 0;
		$('mw-res-to2').disabled = !(state.fecha && state.hora);
	}

	function renderPersonas() {
		var box = $('mw-res-people'); box.innerHTML = '';
		['1','2','3','4','5','6','7','8','9','10+'].forEach(function (n) {
			var b = document.createElement('button');
			b.type = 'button'; b.textContent = n; b.dataset.personas = n;
			b.setAttribute('aria-pressed', state.personas === n ? 'true' : 'false');
			b.setAttribute('aria-label', n === '10+' ? '10 personas o más' : n + (n === '1' ? ' persona' : ' personas'));
			box.appendChild(b);
		});
	}

	function go(step) {
		state.step = step;
		root.querySelectorAll('.mw-res__panel').forEach(function (p) { p.hidden = +p.dataset.step !== step; });
		$('mw-res-step').textContent = (step < 4 ? 'Paso ' + step + ' de 3 · ' : '') + PASOS[step];
		$('mw-res-bar').style.width = Math.min(step, 3) / 3 * 100 + '%';
		var first = root.querySelector('.mw-res__panel[data-step="' + step + '"] button:not(:disabled), .mw-res__panel[data-step="' + step + '"] input');
		if (step > 1 && first) first.focus(); else dialog.focus();
	}

	function datos() {
		var f = $('mw-res-form');
		return {
			fecha: state.fecha, hora: state.hora, personas: state.personas,
			nombre: f.nombre.value.trim(), telefono: f.telefono.value.trim(), email: f.email.value.trim(),
			notas: f.notas.value.trim(), empresa: f.empresa.value, acepto: f.acepto.checked
		};
	}

	function validar() {
		var f = $('mw-res-form'), d = datos(), msg = '';
		[f.nombre, f.telefono, f.email].forEach(function (i) { i.removeAttribute('aria-invalid'); });
		if (d.nombre.length < 2) { msg = 'Escribe tu nombre.'; f.nombre.setAttribute('aria-invalid', 'true'); }
		else if (d.telefono.replace(/\D/g, '').length < 9) { msg = 'Escribe un teléfono válido.'; f.telefono.setAttribute('aria-invalid', 'true'); }
		else if (d.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email)) { msg = 'El email no es válido.'; f.email.setAttribute('aria-invalid', 'true'); }
		else if (!d.acepto) { msg = 'Marca la casilla para aceptar el uso de tus datos.'; }
		$('mw-res-error2').textContent = msg;
		if (msg) { var bad = f.querySelector('[aria-invalid=true]'); (bad || f.acepto).focus(); }
		return !msg;
	}

	function resumen() {
		var d = datos(), date = parse(d.fecha);
		var filas = [
			['Fecha', DIAS[date.getDay()] + ' ' + date.getDate() + ' de ' + MESES[date.getMonth()]],
			['Hora', d.hora], ['Personas', d.personas], ['Nombre', d.nombre], ['Teléfono', d.telefono]
		];
		if (d.email) filas.push(['Email', d.email]);
		if (d.notas) filas.push(['Notas', d.notas]);
		var dl = $('mw-res-summary'); dl.innerHTML = '';
		filas.forEach(function (r) {
			var dt = document.createElement('dt'); dt.textContent = r[0];
			var dd = document.createElement('dd'); dd.textContent = r[1];
			dl.appendChild(dt); dl.appendChild(dd);
		});
	}

	function enviar() {
		var btn = $('mw-res-send');
		btn.classList.add('is-loading'); btn.textContent = 'Enviando…';
		$('mw-res-error3').textContent = '';
		fetch(C.endpoint, {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(datos())})
			.then(function (r) { return r.json().then(function (j) { return {ok: r.ok, j: j}; }); })
			.then(function (res) {
				if (!res.ok || !res.j.ok) throw new Error(res.j.message || 'No se pudo enviar la reserva.');
				$('mw-res-wa').href = res.j.whatsapp;
				go(4);
			})
			.catch(function (e) {
				$('mw-res-error3').textContent = (e && e.message && e.message !== 'Failed to fetch') ? e.message : 'No se pudo enviar. Revisa tu conexión e inténtalo de nuevo.';
			})
			.finally(function () { btn.classList.remove('is-loading'); btn.textContent = 'Confirmar reserva'; });
	}

	function abrir(opener) {
		state.opener = opener || document.activeElement;
		if (state.step === 4) { state.fecha = state.hora = null; $('mw-res-form').reset(); state.personas = '2'; }
		if (!state.fecha) {
			var d = new Date(hoy);
			while (d <= max && !disponible(d)) d.setDate(d.getDate() + 1);
			if (d <= max) { state.fecha = iso(d); view = new Date(d.getFullYear(), d.getMonth(), 1); }
		}
		renderCal(); renderHoras(); renderPersonas();
		root.hidden = false; document.body.classList.add('mw-res-open');
		go(1);
	}
	function cerrar() {
		root.hidden = true; document.body.classList.remove('mw-res-open');
		if (state.opener && state.opener.focus) state.opener.focus();
	}

	$('mw-res-days').addEventListener('click', function (e) {
		var b = e.target.closest('button'); if (!b || b.disabled) return;
		state.fecha = b.dataset.date;
		if (state.hora && horasLibres(parse(state.fecha)).indexOf(state.hora) === -1) state.hora = null;
		renderCal(); renderHoras();
		$('mw-res-days').querySelector('[data-date="' + state.fecha + '"]').focus();
	});
	$('mw-res-hours').addEventListener('click', function (e) {
		var b = e.target.closest('button'); if (!b || b.disabled) return;
		state.hora = b.dataset.hora; renderHoras();
		$('mw-res-hours').querySelector('[data-hora="' + state.hora + '"]').focus();
	});
	$('mw-res-people').addEventListener('click', function (e) {
		var b = e.target.closest('button'); if (!b) return;
		state.personas = b.dataset.personas; renderPersonas();
		$('mw-res-people').querySelector('[data-personas="' + state.personas + '"]').focus();
	});
	$('mw-res-prev').addEventListener('click', function () { view.setMonth(view.getMonth() - 1); renderCal(); });
	$('mw-res-next').addEventListener('click', function () { view.setMonth(view.getMonth() + 1); renderCal(); });
	$('mw-res-to2').addEventListener('click', function () { go(2); });
	$('mw-res-form').addEventListener('submit', function (e) { e.preventDefault(); if (validar()) { resumen(); go(3); } });
	$('mw-res-send').addEventListener('click', enviar);
	root.addEventListener('click', function (e) {
		if (e.target.closest('[data-mw-close]')) cerrar();
		var back = e.target.closest('[data-mw-back]'); if (back) go(+back.dataset.mwBack);
	});
	document.addEventListener('keydown', function (e) {
		if (root.hidden) return;
		if (e.key === 'Escape') { e.preventDefault(); cerrar(); return; }
		if (e.key !== 'Tab') return;
		var f = Array.prototype.filter.call(dialog.querySelectorAll('button, a[href], input, textarea, [tabindex]:not([tabindex="-1"])'), function (el) {
			return !el.disabled && el.offsetParent !== null && !el.closest('.mw-res__hp');
		});
		if (!f.length) return;
		if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
		else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
	});

	// Abre el modal desde cualquier botón de reserva: enlaces a #reserva o a WhatsApp con «reserva» en el mensaje.
	document.addEventListener('click', function (e) {
		var a = e.target.closest('a[href]'); if (!a || root.contains(a)) return;
		var href = a.getAttribute('href') || '';
		var esReserva = /#reserva(r)?$/.test(href) || (/wa\.me\//.test(href) && /reserva/i.test(decodeURIComponent(href)));
		if (!esReserva) return;
		e.preventDefault(); abrir(a);
	});
	if (/^#reserva(r)?$/.test(location.hash)) abrir();
	window.mrwillAbrirReserva = abrir;
})();
</script>
	<?php
}, 50 );
