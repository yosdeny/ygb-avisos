=== YGB Avisos ===
Contributors: ygb
Tags: ticker, notices, announcements, woocommerce, products
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.0
Tested PHP: 8.2
Stable tag: 2.0.1
Author: YGB
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 4.0
WC tested up to: 8.5

Ticker de avisos para WordPress con soporte para productos y descuentos de WooCommerce.

== Description ==

YGB Avisos muestra cintas informativas (tickers) en frontend con control de colores, velocidad, separadores, imagen de fondo, overlay y contenido dinámico de WooCommerce.

**Características principales:**

* Avisos manuales mediante shortcode `[ygb-aviso id="ID"]`.
* Lista de avisos mediante `[ygb-avisos-lista cantidad="5"]`.
* Aviso global automático mediante `[ygb-universal]` o footer automático.
* Shortcode `[ygb-producto-actual]` para mostrar datos del producto actual.
* Tickers automáticos en páginas de producto y archivos de WooCommerce.
* Contenido dinámico: productos específicos y productos rebajados.
* Imagen de fondo con parallax, overlay y alineación móvil.
* Separadores animados configurables.
* Caché por versión para consultas de productos y avisos (Transients API).
* Compatibilidad con WooCommerce HPOS.
* Editor clásico con soporte de colores en el texto (sanitizado con `safecss_filter_attr`).

== Security ==

La versión 2.0.1 aplica medidas defensivas:

* Sanitización estricta de entradas (`sanitize_text_field`, `absint`, `intval`).
* Escapado contextual de salidas (`esc_html`, `esc_attr`, `esc_url`).
* Whitelist para colores, animaciones, posiciones y tipos de contenido.
* Validación de estado de publicación antes de renderizar avisos.
* Nonce y capability checks en metaboxes.
* Filtrado seguro de estilos inline (`color`, `background-color`, `font-weight`, `text-decoration`, `font-style`).
* Validación temprana de versiones de PHP, WordPress y WooCommerce.
* Caché con Transients API invalidada por eventos (actualización/eliminación de productos).
* Uso de `is_readable()` para inclusión de archivos.
* Rate limiting en shortcode `[ygb-avisos-lista]` (máximo 20 avisos).
* Validación de imágenes de fondo (MIME types, tamaño máximo 2MB filtrable).

== Installation ==

1. Sube la carpeta `ygb-avisos` a `/wp-content/plugins/`.
2. Activa el plugin en el menú Plugins.
3. Ve a `YGB Avisos` > `Add New` para crear avisos.
4. Configura estilos, posición y productos si WooCommerce está activo.
5. Inserta el shortcode mostrado en la pantalla de edición del aviso.

== Frequently Asked Questions ==

= ¿El plugin borra avisos al desinstalar? =

No. El `uninstall.php` elimina opciones, metadatos del plugin y transients prefijados, pero conserva los posts del tipo `ygb_aviso` para evitar pérdida accidental de contenido.

= ¿Requiere WooCommerce? =

No. El modo texto funciona sin WooCommerce. Las funciones de productos y descuentos requieren WooCommerce activo.

= ¿Dónde configuro el aviso global? =

En `YGB Avisos` > `Dashboard`, campo `Global notice ID`. Solo se aceptan avisos publicados.

= ¿Qué PHP necesita? =

PHP 8.1 o superior.

= ¿Puedo usar colores en el texto del ticker? =

Sí, el editor clásico permite aplicar color al texto. Solo se permiten propiedades CSS seguras para evitar inyección.

== Changelog ==

= 2.0.1 =

* Corregido error fatal por namespaces inconsistentes en class-woo-integration.php.
* Restaurado soporte de color en editor clásico mediante `safecss_filter_attr`.
* Añadida validación temprana de versiones de PHP, WordPress y WooCommerce.
* Reemplazado `wp_cache_*` por Transients API para caché persistente.
* Mejoras de seguridad en carga de dependencias con `is_readable()`.
* Corregido test unitario de sanitización usando Reflection para método privado.
* Añadido rate limiting al shortcode `[ygb-avisos-lista]` (máximo 20 avisos).
* Refactorizada constante MAX_IMAGE_SIZE a método filtrable `get_max_image_size()`.
* Documentación de seguridad actualizada con detalles específicos.

= 2.0.0 =

* Refactor de seguridad y arquitectura.
* Corrección de acceso a avisos no publicados.
* `wp_kses` restrictivo sin atributos `style` ni `id`.
* Escapado duro en shortcodes y metaboxes.
* Caché por versión para integraciones con WooCommerce.
* Validación de imagen de fondo en guardado.
* Requiere PHP 8.1 y WordPress 6.3+.

= 1.3.3 =

* Añadida alineación móvil para imágenes.

== Upgrade Notice ==

= 2.0.1 =

Actualización correctiva que restaura colores y corrige errores de carga. Se recomienda actualizar desde 2.0.0.

= 2.0.0 =

Versión refactorizada. Requiere PHP 8.1 y WordPress 6.3. Haz backup antes de actualizar.