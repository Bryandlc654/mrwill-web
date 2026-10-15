# Contexto del proyecto — MR WILL web

> Resumen para retomar el trabajo sin el historial. Última actualización: 2026-10-15 (incluye WordPress, sección 12).

## 1. Objetivo
Landing de una sola página para **MR WILL – Pollos & Parrillas**, un restaurante de pollo a la brasa y parrillas en Madrid.
Objetivos: mostrar la carta, generar pedidos y reservas por WhatsApp y mostrar la ubicación del local.

## 2. Tecnologías y estructura
- Un solo `index.html` (1.868 líneas) con HTML, CSS propio en `<style>` y JS inline. **No hay build ni `package.json`.**
- Tailwind por **Play CDN** (`cdn.tailwindcss.com`) con la configuración inline en `<head>`. Colores de marca: `brandRed #e51920`, `brand.brightRed #E50914`, fondo `#0A0A0A`.
- Font Awesome 6.5.1 y Google Fonts (Plus Jakarta Sans, Montserrat, Poppins, Caveat; Cormorant Garamond y Playfair se cargan pero no se usan).
- Iframe de Google Maps y fotos de Unsplash en los testimonios.

```
index.html            página completa
imagenes/             19 MB de imágenes (PNG/JPG)
brush-underline.svg   no se usa
.claude/launch.json   servidor de vista previa: python -m http.server 5501
.vscode/settings.json Live Server en el puerto 5501
.gitignore            /chrome_hist.db
README.md             vacío (solo el título)
contexto.md           este archivo
```

## 3. Secciones de index.html (con líneas aproximadas)
| Sección | id | Líneas |
|---|---|---|
| Header fijo + menú lateral móvil | `main-header`, `mobile-sidebar` | 511–609 |
| Hero ("EL SABOR / QUE NOS UNE") | `inicio` (es el `<main>`) | 611–672 |
| Menú en acordeón (4 filas `<a>`) | `menu` | 676–843 |
| Grid de 4 tarjetas "¿Por qué elegirnos?" | `nosotros` | 847–911 |
| Banner de especialidades | `especialidades` | 915–957 |
| Pedido: celular sticky con parallax y texto en marquee | `pedido` | 961–1028 |
| Testimonios (3 tarjetas) | `testimonios` | 1032–1207 |
| Local (fachada) + mapa + footer | `locales`, `contacto` | 1211–1323 |
| JS: header, menú lateral, reveal, parallax, acordeón, barra flotante | — | 1327–1509 |
| Barra CTA flotante + botón flotante de WhatsApp | `floating-cta-bar` | 1511–1552 |
| Modal de reserva en 2 pasos + su JS | `reservation-modal` | 1555–1865 |

## 4. Estado real de las funcionalidades
Lo marcado como revisado solo se comprobó **leyendo el código**. **No se probó en el navegador.**
- Header que se compacta al hacer scroll, menú lateral móvil (incluye cierre con Escape) y animaciones de entrada: implementados; no se han verificado visualmente.
- Parallax del celular en `#pedido`: implementado; no se ha verificado.
- Reserva: calendario, horas, personas, nombre y teléfono, y al confirmar abre `wa.me` con el mensaje. Implementada, pero tiene los fallos descritos en el punto 7.
- Barra CTA flotante: **no funciona** (ver fallo 1).
- Acordeón del menú en móvil: **no funciona** el estado activo (ver fallo 2).

## 5. Cambios de esta sesión
- Se clonó `https://github.com/Bryandlc654/mrwill-web` en `D:\Escritorio\mrwill`, en la rama `main`, último commit `52535a3 "Proyecto"`. El árbol de trabajo estaba limpio.
- Se analizó todo el código. **No se modificó ningún archivo del proyecto**; solo se creó este `contexto.md`.

## 6. Decisiones técnicas
- Todavía no se ha tomado ninguna. Se preguntó al usuario si quiere corregir solo los fallos funcionales o también el rendimiento, el contenido y el SEO. **No ha respondido aún.**

## 7. Problemas encontrados (ninguno corregido)
**Fallos funcionales**
1. `initFloatingCTAScroll()` se llama en la línea ~1508, **antes** de que exista `<aside id="floating-cta-bar">` (línea ~1511), así que la barra nunca aparece. Solución: llamarla en `DOMContentLoaded` o mover el script.
2. Las filas del acordeón son `<a>`, y `e.target.closest('a')` (línea ~1474) siempre devuelve la fila, así que el toggle de `is-active` nunca se ejecuta.
3. `selectGuests('10+')` usa `btn.textContent.includes('10+')`, pero el botón dice "+10 pers", así que nunca se marca (línea ~1834). Mejor usar un `data-guests`.
4. La hora por defecto, 13:00, no se ve marcada al abrir el modal, y se pueden elegir horas ya pasadas del día de hoy.
5. Accesibilidad: el modal y el menú lateral ocultos siguen recibiendo el foco con Tab (solo se ocultan con opacidad o translate). El modal no se cierra con Escape, no atrapa el foco y no bloquea el scroll.

**Rendimiento**
- Las imágenes que se usan pesan unos 13 MB (PNG de 1,5–2,8 MB) y casi ninguna tiene `loading="lazy"`. La del hero pesa 1,8 MB (malo para el LCP).
- Imágenes que no se usan: `bento_fresh_raw_meat.jpg`, `dark_black_moody…png`, `menu_card_steak_sliced.png`, `menu_steak_knife_feature.png`, `modern_sleek…on_a.png` (el no transparente), `smiling_charismatic…png`, `steak_knife_isolated.png` y `brush-underline.svg`.
- Tailwind Play CDN no está pensado para producción. Se cargan 2 familias de fuentes que no se usan.

**Contenido de relleno o incoherente**
- Hay dos números de WhatsApp de prueba distintos: `51999999999` (menú móvil, línea ~603) y `34600000000` (líneas ~1017, ~1540 y ~1859).
- Las redes sociales enlazan a instagram.com, facebook.com y tiktok.com genéricos.
- Los testimonios son inventados, con fotos de Unsplash que no corresponden a los nombres.
- El footer dice © 2025 y el README está vacío.

**SEO y estructura**
- Faltan `meta description`, Open Graph, favicon y JSON-LD `Restaurant`.
- `<main>` solo envuelve el hero.
- Hay nombres de archivo con espacios (`logo MR WILL -  horizontal.png`, `Fachada de restaurante Mr Will Parrillas.png`).

## 8. Dónde nos quedamos
Terminó el análisis. Está pendiente la respuesta del usuario: **¿corregir solo los fallos funcionales (1–5) o también el rendimiento, el contenido y el SEO?**

## 9. Próximos pasos (por prioridad)
1. Corregir los fallos funcionales 1–5 en `index.html`.
2. Probar en el navegador, en escritorio y en móvil (375 px): barra flotante, acordeón, modal de reserva (incluido +10) y menú lateral.
3. Pedir al usuario los datos reales: número de WhatsApp, redes sociales, dirección y reseñas reales. Unificar el número de WhatsApp.
4. Optimizar las imágenes (WebP/AVIF, redimensionarlas, `loading="lazy"` y precarga del hero), quitar las que no se usan (**con confirmación**) y renombrar los archivos con espacios.
5. Reemplazar Tailwind CDN por un CSS compilado (Tailwind CLI) y quitar las fuentes que no se usan.
6. SEO: meta description, Open Graph, favicon, JSON-LD y `<main>` alrededor de todo el contenido. Actualizar el año y el README.

## 10. Comandos
```bash
python -m http.server 5501
```
Luego abrir http://localhost:5501. También existe la configuración `mrwill` en `.claude/launch.json`, que sirve para la vista previa.
- No hay tests, linter ni build.
- **El despliegue no está definido** en el repo (no hay configuración de hosting). Hay que preguntar al usuario dónde se publica.

## 11. Restricciones
- Responder al usuario en **español**.
- No hacer commit ni push sin que el usuario lo pida. El repo remoto es del usuario (`Bryandlc654/mrwill-web`).
- No borrar imágenes ni archivos sin confirmación.
- No inventar datos de contacto ni reseñas; los reales los debe dar el usuario.

## 12. Sitio WordPress + Elementor (en paralelo al index.html)
- URL: https://linen-donkey-240753.hostingersite.com (tema Hello Elementor, Elementor 4 con widgets atómicos V4 y Pro Elements).
- MCP: servidor `linen-donkey-240753-hostingersite-com-elementor` en `~/.claude.json` (copia de seguridad en `~/.claude.json.bak`). Si sus herramientas no están cargadas, se puede llamar por HTTP JSON-RPC a `/wp-json/elementor/mcp/` con autenticación Basic.
- Variables globales: `brand-red #e51920`, `brand-dark #0A0A0A`, `font-display Montserrat`, `font-outline Poppins`. La clase `alko-header-sticky` aplica el fondo con desenfoque al hacer scroll (con JS y CSS personalizados del sitio).
- Plantillas del Theme Builder, activas en todo el sitio: **Header Global (152)** y **Footer Global (125)**.
- Páginas: **Inicio (28)**, **Contacto (139)** en `/contacto/` y **Nuestro Local (156)** en `/nuestro-local/` **Nuestro Menú (167)** en `/nuestro-menu/` y **Sobre Nosotros (174)** en `/sobre-nosotros/`. Ninguna tiene header propio.
- Menú: enlaces a `/`, `/nuestro-menu/`, `/nuestro-local/`, `/sobre-nosotros/` y `/contacto/`. El enlace activo se marca con reglas `body.home` / `body.page-id-X` en el estilo de `header_nav` y `mobile_menu_links` de la plantilla 152. **Al crear una página nueva hay que añadir su regla.** El conversor de Elementor borra el prefijo de la primera regla que va después de `}`, por eso se antepone `& x-none,`.
- Nuestro Local (con un estilo distinto al de Contacto): hero a pantalla completa con la fachada y el texto alineado abajo a la izquierda ("LA CASA DEL POLLO A LA BRASA" con letra de contorno roja); botones y una tarjeta de cristal con enlace a Google Maps; galería bento clara "Lo que te espera" con 4 fotos; franja roja con 01/02/03 (Carbón de verdad, Salón acogedor, Para compartir); y un bloque final oscuro con "MADRID" gigante en contorno y "¿Nos vemos hoy?". No se inventaron la dirección ni el horario.
- Nuestro Menú (con otro estilo): hero oscuro partido en dos, con "NUESTRO MENÚ" (MENÚ en cursiva roja) a la izquierda y una foto rectangular vertical a la derecha: en escritorio va en position absolute, ocupa el 50% del ancho y el 100vh de alto, pegada al borde derecho, con un degradado a la izquierda y la pegatina "AL CARBÓN" (para que no se recoloque, el grid lleva position static); en tableta y móvil la foto pasa debajo del texto a ancho completo (65vh / 55vh); chips con enlaces a anclas (#pollos, #parrillas, #guarniciones, #combos); 4 categorías en zigzag con foto inclinada y sombra sólida, número en contorno, etiquetas y enlace subrayado a WhatsApp; y una llamada final roja con rayas. Las categorías son las mismas que en Inicio. **No hay platos concretos ni precios**: faltan los de la carta real. Ojo: en un flex en columna (tableta), `flex: 1 1 0px` colapsa la imagen; en tableta hay que usar `flex: 0 0 auto`.
- Sobre Nosotros (con otro estilo): hero solo tipográfico, sin foto ("SOMOS BRASA, CARBÓN Y MESA" con contorno rojo, más la frase en Caveat "Buen pollo, mejores momentos." subrayada); "Quiénes somos" en fondo crema con un collage (fachada, foto pequeña con borde y la etiqueta "Madrid"); "Lo que nos define": lista editorial numerada sobre negro con 4 valores (los dos primeros salen de las tarjetas de index.html); y al final una banda roja inclinada "POLLO ✦ BRASA ✦ PARRILLA ✦ CARBÓN" y la llamada "Ven a conocernos". No se inventaron historia, fechas ni nombres.
- Para ver el escritorio en el panel del navegador (1024 px es el punto de corte de tableta), hay que emular 1280×800 con resize_window.
- Problema detectado: si se reconstruye con `build-composition` (`replace_children`) una página ya publicada, el cambio se queda en el autoguardado y `publish` no lo pasa a la versión pública. Solución: `update-page-settings {post_status: draft}`, reconstruir, publicar y después hacer un `manage-elements` mínimo más un nuevo `publish` para que se regenere `local-<id>-frontend-*.css`.
- **Panel «MR WILL» en wp-admin**: lo crea el plugin Code Snippets (instalado con permiso del usuario) con el fragmento PHP ID 5. Hay copia en `wordpress/mrwill-panel.php`; para cambiarlo, editar la copia, comprobarla con `php -l` y subirla con PUT a `/wp-json/code-snippets/v1/snippets/5`.
  - **Ajustes**: opción `mrwill_whatsapp` (solo dígitos, también editable vía REST en `/wp/v2/settings`). Si tiene valor, un ob_start en `template_redirect` sustituye cualquier `wa.me/<dígitos>` de la web por ese número. Vacía = se dejan los enlaces como están (`34600000000`). También existe el shortcode `[mrwill_whatsapp_url mensaje="..."]`.
  - **Reseñas**: tipo de contenido `mrwill_resena` (título = nombre, contenido = texto, imagen destacada = foto, meta `mrwill_rol` y `mrwill_estrellas`, «Orden» = menu_order). Se cargaron las 4 reseñas originales (IDs 178–181).
  - En Inicio, el carrusel (`carousel_viewport` 71d6b484) es un `e-collection-loop` con query_id `mrwill_resenas` (el hook `elementor/query/mrwill_resenas` cambia el post_type). Usa las etiquetas dinámicas post-title, post-featured-image y el shortcode `[mrwill_resena campo="texto|rol|estrellas"]`. Distribución: flex-wrap, 3/2/1 por fila con la última fila centrada; el loop-item no lleva la clase global `review-carousel-slide` porque esta fuerza min-width.
- **Reservas** (fragmento de Code Snippets ID 6, copia en `wordpress/mrwill-reservas.php`, que depende del fragmento 5):
  - El modal `#mw-res` se inyecta en `wp_footer`. Se abre con cualquier enlace a `#reserva`/`/#reserva`, con un `wa.me` cuyo texto incluya «reserva», al cargar una URL con `#reserva` o con `window.mrwillAbrirReserva()`.
  - 3 pasos: (1) calendario que empieza en lunes con los días pasados, cerrados o fuera de la antelación desactivados, y horas ya pasadas de hoy desactivadas; (2) personas 1–9 y «10+», nombre, teléfono, email y comentarios opcionales, casilla de consentimiento y honeypot `empresa`; (3) resumen y confirmación. Después muestra «¡Solicitud enviada!» con un botón para enviar también por WhatsApp.
  - Tiene Escape, foco atrapado, bloqueo de scroll, devuelve el foco al botón que lo abrió y en móvil se muestra como hoja inferior. Corrige los fallos 3, 4 y 5 del modal original de index.html.
  - POST a `/wp-json/mrwill/v1/reservas` (público): valida en el servidor fecha, hora, día cerrado, antelación, personas, teléfono, email y consentimiento, con un límite de 5 por hora por IP. Guarda el tipo `mrwill_reserva` (MR WILL → Reservas, con columnas y estado Pendiente/Confirmada/Cancelada) y manda un `wp_mail` al email de avisos.
  - MR WILL → «Reservas · Ajustes»: horas (por defecto 13:00, 14:30, 20:00, 21:30, las del index original), días cerrados, antelación (60) y email de avisos (por defecto admin_email).
  - El CSS del modal lleva el prefijo `.mw-res .mw-res__*` para ganar a los estilos de botón del tema Hello (`[type=button]` con padding y color #c36).
  - Queda una reserva de prueba «PRUEBA – borrar» (17/10, 14:30, 10+) que el usuario debe borrar; seguramente también llegó un email de aviso.
- Pendiente: poner el WhatsApp real en MR WILL → Ajustes (ahora está vacío, así que se ve `34600000000`), la dirección y el horario reales, y cambiar la contraseña de aplicación de WordPress (se compartió en el chat).
