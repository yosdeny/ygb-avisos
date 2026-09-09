<?php
declare(strict_types=1);

/**
 * Uninstall handler for YGB Avisos.
 *
 * @package YGB_Avisos
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Removes plugin data for the current site.
 *
 * @return void
 */
function ygb_avisos_uninstall_site(): void {
    global $wpdb;

    delete_option('ygb_avisos_settings');
    delete_option('ygb_avisos_cache_version');

    $meta_keys = [
        '_ygb_color_fondo',
        '_ygb_color_texto',
        '_ygb_color_enlace',
        '_ygb_fondo_encabezado',
        '_ygb_color_encabezado',
        '_ygb_encabezado',
        '_ygb_tamano_fuente',
        '_ygb_velocidad',
        '_ygb_tipo_contenido',
        '_ygb_formato_producto',
        '_ygb_cerrable',
        '_ygb_pausa_hover',
        '_ygb_anclaje',
        '_ygb_separador',
        '_ygb_separador_tipo',
        '_ygb_separador_simbolo',
        '_ygb_separador_texto',
        '_ygb_separador_personalizado',
        '_ygb_separador_animacion',
        '_ygb_imagen_fondo',
        '_ygb_imagen_parallax',
        '_ygb_imagen_alineacion_movil',
        '_ygb_imagen_overlay',
        '_ygb_imagen_opacidad',
        '_ygb_productos_ids',
    ];

    foreach ($meta_keys as $meta_key) {
        delete_metadata('post', 0, $meta_key, '', true);
    }

    if ($wpdb instanceof wpdb) {
        $transient_pattern = '_transient%ygb_avisos%';

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $transient_pattern
            )
        );
    }

    flush_rewrite_rules();
}

if (is_multisite()) {
    $site_ids = get_sites([
        'fields' => 'ids',
        'number' => 0,
    ]);

    foreach ($site_ids as $site_id) {
        switch_to_blog((int) $site_id);
        ygb_avisos_uninstall_site();
        restore_current_blog();
    }
} else {
    ygb_avisos_uninstall_site();
}