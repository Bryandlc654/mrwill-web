<?php
// Copia del fragmento «MR WILL – Panel de ajustes y reseñas» (Code Snippets, ID 5).
// En Code Snippets se pega SIN la línea <?php inicial.

/**
 * MR WILL – Panel de ajustes (WhatsApp) y Reseñas.
 *
 * - Menú «MR WILL» en el escritorio con «Ajustes» y «Reseñas».
 * - Todos los enlaces wa.me/<número> de la web usan el número guardado en Ajustes.
 * - Reseñas: tipo de contenido propio que el carrusel de Inicio muestra con un
 *   bucle de Elementor (ID de consulta: mrwill_resenas).
 * - Shortcodes: [mrwill_resena campo="texto|rol|estrellas"] y [mrwill_whatsapp_url mensaje="..."].
 */

function mrwill_wa_number() {
	return preg_replace( '/\D+/', '', (string) get_option( 'mrwill_whatsapp', '' ) );
}

/* ---------- Menú y página de ajustes ---------- */

add_action( 'admin_menu', function () {
	add_menu_page( 'MR WILL', 'MR WILL', 'manage_options', 'mrwill', 'mrwill_settings_page', 'dashicons-store', 3 );
	add_submenu_page( 'mrwill', 'Ajustes MR WILL', 'Ajustes', 'manage_options', 'mrwill', 'mrwill_settings_page' );
} );

add_action( 'init', function () {
	register_setting( 'mrwill', 'mrwill_whatsapp', array(
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => function ( $value ) {
			return preg_replace( '/\D+/', '', (string) $value );
		},
	) );
} );

function mrwill_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$number = mrwill_wa_number();
	?>
	<div class="wrap">
		<h1>Ajustes MR WILL</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mrwill' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mrwill_whatsapp">Número de WhatsApp</label></th>
					<td>
						<input name="mrwill_whatsapp" id="mrwill_whatsapp" type="text" class="regular-text" value="<?php echo esc_attr( $number ); ?>" placeholder="34600000000" inputmode="numeric">
						<p class="description">Con prefijo de país y sin espacios ni «+» (ejemplo: 34612345678). Se aplica a todos los botones y enlaces de WhatsApp de la web.</p>
						<?php if ( $number ) : ?>
							<p><a href="<?php echo esc_url( 'https://wa.me/' . $number ); ?>" target="_blank" rel="noopener">Probar enlace: wa.me/<?php echo esc_html( $number ); ?></a></p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Guardar cambios' ); ?>
		</form>
		<h2>Reseñas</h2>
		<p>Las reseñas del carrusel de Inicio se gestionan en <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mrwill_resena' ) ); ?>">MR WILL → Reseñas</a>. Se muestran según el campo «Orden» (de menor a mayor).</p>
	</div>
	<?php
}

/* ---------- WhatsApp: reemplazo global en la web ---------- */

add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || is_feed() || mrwill_wa_number() === '' ) {
		return;
	}
	ob_start( function ( $html ) {
		return preg_replace( '#wa\.me/\d+#', 'wa.me/' . mrwill_wa_number(), $html );
	} );
} );

add_shortcode( 'mrwill_whatsapp_url', function ( $atts ) {
	$atts = shortcode_atts( array( 'mensaje' => '' ), $atts );
	$url  = 'https://wa.me/' . mrwill_wa_number();
	if ( $atts['mensaje'] !== '' ) {
		$url .= '?text=' . rawurlencode( $atts['mensaje'] );
	}
	return esc_url( $url );
} );

/* ---------- Reseñas ---------- */

add_action( 'init', function () {
	register_post_type( 'mrwill_resena', array(
		'labels'       => array(
			'name'               => 'Reseñas',
			'singular_name'      => 'Reseña',
			'add_new'            => 'Añadir reseña',
			'add_new_item'       => 'Añadir reseña',
			'edit_item'          => 'Editar reseña',
			'new_item'           => 'Nueva reseña',
			'view_item'          => 'Ver reseña',
			'search_items'       => 'Buscar reseñas',
			'not_found'          => 'No hay reseñas',
			'not_found_in_trash' => 'No hay reseñas en la papelera',
			'all_items'          => 'Reseñas',
			'featured_image'     => 'Foto del cliente',
			'set_featured_image' => 'Elegir foto del cliente',
			'remove_featured_image' => 'Quitar foto',
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => 'mrwill',
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
	) );

	$auth = function () {
		return current_user_can( 'edit_posts' );
	};
	register_post_meta( 'mrwill_resena', 'mrwill_rol', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => $auth,
	) );
	register_post_meta( 'mrwill_resena', 'mrwill_estrellas', array(
		'type'              => 'integer',
		'single'            => true,
		'default'           => 5,
		'show_in_rest'      => true,
		'sanitize_callback' => function ( $value ) {
			return max( 1, min( 5, (int) $value ) );
		},
		'auth_callback'     => $auth,
	) );
} );

/* La API REST solo expone los meta si el tipo admite custom-fields; se activa solo ahí para no mostrar esa caja en el editor. */
add_action( 'rest_api_init', function () {
	add_post_type_support( 'mrwill_resena', 'custom-fields' );
} );

add_filter( 'use_block_editor_for_post_type', function ( $use, $post_type ) {
	return $post_type === 'mrwill_resena' ? false : $use;
}, 10, 2 );

add_filter( 'enter_title_here', function ( $title, $post ) {
	return $post->post_type === 'mrwill_resena' ? 'Nombre del cliente' : $title;
}, 10, 2 );

add_action( 'add_meta_boxes_mrwill_resena', function () {
	add_meta_box( 'mrwill_resena_datos', 'Datos de la reseña', function ( $post ) {
		wp_nonce_field( 'mrwill_resena_guardar', 'mrwill_resena_nonce' );
		$rol       = get_post_meta( $post->ID, 'mrwill_rol', true );
		$estrellas = (int) get_post_meta( $post->ID, 'mrwill_estrellas', true );
		$estrellas = $estrellas ? $estrellas : 5;
		?>
		<p><label for="mrwill_rol"><strong>Descripción</strong> (ej.: Cliente frecuente)</label><br>
			<input type="text" id="mrwill_rol" name="mrwill_rol" value="<?php echo esc_attr( $rol ); ?>" style="width:100%"></p>
		<p><label for="mrwill_estrellas"><strong>Estrellas</strong></label><br>
			<select id="mrwill_estrellas" name="mrwill_estrellas">
				<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<option value="<?php echo $i; ?>" <?php selected( $estrellas, $i ); ?>><?php echo str_repeat( '★', $i ); ?></option>
				<?php endfor; ?>
			</select></p>
		<p class="description">El texto de la reseña va en el editor. La foto, en «Foto del cliente». El orden del carrusel, en «Atributos → Orden».</p>
		<?php
	}, 'mrwill_resena', 'side', 'high' );
} );

add_action( 'save_post_mrwill_resena', function ( $post_id ) {
	if ( ! isset( $_POST['mrwill_resena_nonce'] ) || ! wp_verify_nonce( $_POST['mrwill_resena_nonce'], 'mrwill_resena_guardar' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, 'mrwill_rol', sanitize_text_field( wp_unslash( $_POST['mrwill_rol'] ?? '' ) ) );
	update_post_meta( $post_id, 'mrwill_estrellas', max( 1, min( 5, (int) ( $_POST['mrwill_estrellas'] ?? 5 ) ) ) );
} );

add_filter( 'manage_mrwill_resena_posts_columns', function ( $columns ) {
	return array(
		'cb'        => $columns['cb'],
		'title'     => 'Cliente',
		'estrellas' => 'Estrellas',
		'rol'       => 'Descripción',
		'orden'     => 'Orden',
		'date'      => $columns['date'],
	);
} );

add_action( 'manage_mrwill_resena_posts_custom_column', function ( $column, $post_id ) {
	if ( $column === 'estrellas' ) {
		echo esc_html( str_repeat( '★', max( 1, (int) get_post_meta( $post_id, 'mrwill_estrellas', true ) ) ) );
	} elseif ( $column === 'rol' ) {
		echo esc_html( get_post_meta( $post_id, 'mrwill_rol', true ) );
	} elseif ( $column === 'orden' ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	}
}, 10, 2 );

/* Bucle de Elementor con ID de consulta «mrwill_resenas». */
add_action( 'elementor/query/mrwill_resenas', function ( $query ) {
	$query->set( 'post_type', 'mrwill_resena' );
	$query->set( 'post_status', 'publish' );
	$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	$query->set( 'posts_per_page', 12 );
} );

add_shortcode( 'mrwill_resena', function ( $atts ) {
	$atts    = shortcode_atts( array( 'campo' => 'texto' ), $atts );
	$post_id = get_the_ID();
	if ( ! $post_id || get_post_type( $post_id ) !== 'mrwill_resena' ) {
		return '';
	}
	switch ( $atts['campo'] ) {
		case 'estrellas':
			$n = (int) get_post_meta( $post_id, 'mrwill_estrellas', true );
			$n = $n ? max( 1, min( 5, $n ) ) : 5;
			return '<span>' . str_repeat( '<span>★</span>', $n ) . '</span>';
		case 'rol':
			return esc_html( get_post_meta( $post_id, 'mrwill_rol', true ) );
		default:
			$texto = trim( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
			return $texto === '' ? '' : '“' . esc_html( $texto ) . '”';
	}
} );
