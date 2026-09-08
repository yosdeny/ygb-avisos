<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para manejar los metaboxes del CPT
 *
 * @package YGB_Avisos
 */
class YGB_Avisos_Metaboxes {

	const DEFAULT_COLORS = [
		'fondo'              => '#FF5733',
		'texto'              => '#FFFFFF',
		'enlace'             => '#FFD700',
		'fondo_encabezado'   => '#C70039',
		'texto_encabezado'   => '#FFFFFF',
	];

	const FONT_SIZE_RANGE = [ 'min' => 10, 'max' => 50 ];
	const SPEED_RANGE     = [ 'min' => 10, 'max' => 100 ];
	const MAX_IMAGE_SIZE  = 2097152;

	private static $simbolos = [
		'1'  => '🔸🔸🔸',
		'2'  => '●●●',
		'3'  => '★★★',
		'4'  => '✦✦✦',
		'5'  => '❄️❄️❄️',
		'6'  => '⚡⚡⚡',
		'7'  => '✨✨✨',
		'8'  => '▶▶▶',
		'9'  => '◆◆◆',
		'10' => '▬▬▬',
		'11' => '━┳━',
		'12' => '◈◈◈',
		'13' => '◎◎◎',
		'14' => '☆★☆',
		'15' => '☀️☀️☀️',
	];

	private static $animaciones = [
		'none'   => '⏹️ Sin animación',
		'pulse'  => '💓 Pulsación',
		'spin'   => '🔄 Giro suave',
		'bounce' => '🏀 Rebote',
		'flash'  => '⚡ Destello',
		'shake'  => '📳 Temblor',
	];

	private static $posiciones_imagen = [
		'center' => '🎯 Center',
		'left'   => '⬅️ Left',
		'right'  => '➡️ Right',
		'top'    => '⬆️ Top',
		'bottom' => '⬇️ Bottom',
	];

	private static $tamanos_imagen = [
		'cover'   => '📐 Cover (crop to fit)',
		'contain' => '📏 Contain (show full)',
		'auto'    => '🔍 Auto (original size)',
	];

	private static $overlays = [
		'none'  => '❌ None',
		'dark'  => '🌑 Dark overlay',
		'light' => '☀️ Light overlay',
	];

	private static $alineacion_imagen = [
		'left'   => '⬅️ Left (show important content)',
		'center' => '🎯 Center',
		'right'  => '➡️ Right',
	];

	public static function get_simbolos() {
		return self::$simbolos;
	}

	public static function get_animaciones() {
		return self::$animaciones;
	}

	public static function get_posiciones_imagen() {
		return self::$posiciones_imagen;
	}

	public static function get_tamanos_imagen() {
		return self::$tamanos_imagen;
	}

	public static function get_overlays() {
		return self::$overlays;
	}

	public static function get_alineacion_imagen() {
		return self::$alineacion_imagen;
	}

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metaboxes' ) );
		add_action( 'save_post_ygb_aviso', array( __CLASS__, 'save_metaboxes' ) );
		add_action( 'admin_notices', array( __CLASS__, 'display_admin_notices' ) );
		add_action( 'admin_notices', array( __CLASS__, 'display_image_size_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_media_uploader' ) );
	}

	public static function enqueue_media_uploader( $hook ) {
		global $post;

		$is_cpt_page = (
			in_array( $hook, array( 'post.php', 'post-new.php' ), true )
			&& $post
			&& 'ygb_aviso' === $post->post_type
		);

		if ( $is_cpt_page ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery' );
		}
	}

	public static function add_metaboxes() {
		add_meta_box(
			'ygb_aviso_estilos',
			__( '🎨 Style Configuration', 'ygb-avisos' ),
			array( __CLASS__, 'render_estilos' ),
			'ygb_aviso',
			'normal',
			'high'
		);

		add_meta_box(
			'ygb_aviso_posicion',
			__( '⚙️ Position & Behavior', 'ygb-avisos' ),
			array( __CLASS__, 'render_posicion' ),
			'ygb_aviso',
			'side',
			'default'
		);

		if ( class_exists( 'WooCommerce' ) ) {
			add_meta_box(
				'ygb_aviso_productos',
				__( '🛒 Product Configuration', 'ygb-avisos' ),
				array( __CLASS__, 'render_productos' ),
				'ygb_aviso',
				'normal',
				'high'
			);
		}

		add_meta_box(
			'ygb_aviso_shortcode',
			__( '📋 Shortcode', 'ygb-avisos' ),
			array( __CLASS__, 'render_shortcode' ),
			'ygb_aviso',
			'side',
			'low'
		);
	}

	public static function display_admin_notices() {
		global $post;

		if ( ! $post || 'ygb_aviso' !== $post->post_type ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		$color_fields = array(
			'_ygb_color_fondo',
			'_ygb_color_texto',
			'_ygb_color_enlace',
			'_ygb_fondo_encabezado',
			'_ygb_color_encabezado',
		);

		foreach ( $color_fields as $field ) {
			$color = get_post_meta( $post->ID, $field, true );

			if ( ! empty( $color ) && ! self::validate_hex_color( $color ) ) {
				echo '<div class="notice notice-warning is-dismissible">';
				echo '<p>' . esc_html__( 'YGB Avisos: One of the configured colors is not valid. The default color will be used.', 'ygb-avisos' ) . '</p>';
				echo '</div>';
				break;
			}
		}
	}

	public static function display_image_size_notice() {
		global $post;

		if ( ! $post || 'ygb_aviso' !== $post->post_type ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		$imagen_id = intval( get_post_meta( $post->ID, '_ygb_imagen_fondo', true ) );

		if ( ! $imagen_id ) {
			return;
		}

		$file_size = self::get_attached_file_size( $imagen_id );
		$max_size  = apply_filters( 'ygb_avisos_max_image_size', self::MAX_IMAGE_SIZE );

		if ( $file_size > $max_size ) {
			$size_mb = round( $file_size / 1024 / 1024, 2 );
			$max_mb  = round( $max_size / 1024 / 1024, 2 );

			echo '<div class="notice notice-warning is-dismissible">';
			echo '<p>' . sprintf(
				esc_html__( 'YGB Avisos: The background image is large (%s MB). Recommended maximum size is %s MB for better performance.', 'ygb-avisos' ),
				esc_html( $size_mb ),
				esc_html( $max_mb )
			) . '</p>';
			echo '</div>';
		}
	}

	private static function validate_hex_color( $color ) {
		if ( empty( $color ) ) {
			return false;
		}

		$color = trim( strtolower( $color ) );

		if ( preg_match( '/^#?[a-f0-9]{6}$/i', $color ) ) {
			return true;
		}

		if ( preg_match( '/^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/i', $color ) ) {
			return true;
		}

		return false;
	}

	private static function sanitize_hex_color( $color ) {
		if ( empty( $color ) ) {
			return '';
		}

		$color = trim( strtolower( $color ) );

		if ( preg_match( '/^#?[a-f0-9]{6}$/i', $color ) ) {
			if ( '#' !== $color[0] ) {
				$color = '#' . $color;
			}
			return $color;
		}

		if ( preg_match( '/^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/i', $color, $matches ) ) {
			$r = min( 255, max( 0, intval( $matches[1] ) ) );
			$g = min( 255, max( 0, intval( $matches[2] ) ) );
			$b = min( 255, max( 0, intval( $matches[3] ) ) );
			return sprintf( '#%02x%02x%02x', $r, $g, $b );
		}

		return '';
	}

	private static function validate_range( $value, $min, $max, $default ) {
		$value = intval( $value );

		if ( $value < $min ) {
			return $min;
		}

		if ( $value > $max ) {
			return $max;
		}

		if ( 0 === $value ) {
			return $default;
		}

		return $value;
	}

	private static function get_attached_file_size( $attachment_id ) {
		$file = get_attached_file( intval( $attachment_id ) );

		if ( ! $file ) {
			return 0;
		}

		$real_path = realpath( $file );

		if ( ! $real_path ) {
			return 0;
		}

		$upload_dir = wp_upload_dir();
		$base_dir   = realpath( $upload_dir['basedir'] );

		if ( $base_dir && strpos( $real_path, $base_dir ) !== 0 ) {
			return 0;
		}

		if ( function_exists( 'WP_Filesystem' ) && WP_Filesystem() ) {
			global $wp_filesystem;

			if ( is_object( $wp_filesystem ) && method_exists( $wp_filesystem, 'size' ) ) {
				return (int) $wp_filesystem->size( $real_path );
			}
		}

		return (int) @filesize( $real_path );
	}

	private static function validate_background_image( $imagen_id ) {
		$imagen_id = intval( $imagen_id );

		if ( $imagen_id <= 0 ) {
			return false;
		}

		$attachment = get_post( $imagen_id );

		if ( ! $attachment ) {
			return false;
		}

		$mime_type     = get_post_mime_type( $imagen_id );
		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );

		if ( apply_filters( 'ygb_avisos_allow_svg_background', false ) ) {
			$allowed_mimes[] = 'image/svg+xml';
		}

		if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
			return false;
		}

		$file_size = self::get_attached_file_size( $imagen_id );
		$max_size  = apply_filters( 'ygb_avisos_max_image_size', self::MAX_IMAGE_SIZE );

		if ( $file_size > $max_size ) {
			return false;
		}

		return true;
	}

	/**
	 * Obtiene los valores por defecto y las listas blancas de los metadatos.
	 *
	 * @return array
	 */
	private static function get_meta_defaults() {
		return array(
			'color_fondo'              => self::DEFAULT_COLORS['fondo'],
			'color_texto'              => self::DEFAULT_COLORS['texto'],
			'color_enlace'             => self::DEFAULT_COLORS['enlace'],
			'fondo_encabezado'         => self::DEFAULT_COLORS['fondo_encabezado'],
			'color_encabezado'         => self::DEFAULT_COLORS['texto_encabezado'],
			'encabezado'               => '',
			'tamano_fuente'            => 16,
			'velocidad'                => 40,
			'tipo_contenido'           => 'texto',
			'formato_producto'         => '{product_name} - {product_sale_price} ({product_discount} DCTO)',
			'cerrable'                 => 'no',
			'pausa_hover'              => 'si',
			'anclaje'                  => 'arriba',
			'separador'                => '🔸🔸🔸',
			'separador_animacion'      => 'pulse',
			'separador_tipo'           => 'simbolo',
			'separador_simbolo'        => '1',
			'separador_texto'          => 'NOVEDAD',
			'separador_personalizado'  => '🔸🔸🔸',
			'imagen_fondo'             => 0,
			'imagen_parallax'          => 'no',
			'imagen_alineacion_movil'  => 'left',
			'imagen_overlay'           => 'none',
			'imagen_opacidad'          => 50,
			'productos_ids'            => '',
			'whitelists'               => array(
				'anclaje'                => array( 'arriba', 'abajo', 'ambos' ),
				'tipo_contenido'         => array( 'texto', 'productos', 'descuentos' ),
				'separador_tipo'         => array( 'simbolo', 'texto', 'linea', 'flecha', 'emoji' ),
				'separador_animacion'    => array_keys( self::$animaciones ),
				'imagen_alineacion_movil'=> array_keys( self::$alineacion_imagen ),
				'imagen_overlay'         => array_keys( self::$overlays ),
			),
		);
	}

	/**
	 * Obtiene y valida los metadatos completos de un aviso.
	 *
	 * @param int $post_id
	 * @return array
	 */
	private static function get_validated_meta( $post_id ) {
		$defaults = self::get_meta_defaults();
		$meta = array();

		// Colores.
		foreach ( array( 'color_fondo', 'color_texto', 'color_enlace', 'fondo_encabezado', 'color_encabezado' ) as $key ) {
			$meta_key = '_ygb_' . $key;
			$value = self::sanitize_hex_color( get_post_meta( $post_id, $meta_key, true ) );
			$meta[ $key ] = $value ?: $defaults[ $key ];
		}

		// Texto simple.
		$meta['encabezado'] = sanitize_text_field( get_post_meta( $post_id, '_ygb_encabezado', true ) ) ?: $defaults['encabezado'];
		$meta['separador_texto'] = sanitize_text_field( get_post_meta( $post_id, '_ygb_separador_texto', true ) ) ?: $defaults['separador_texto'];
		$meta['separador_personalizado'] = sanitize_text_field( get_post_meta( $post_id, '_ygb_separador_personalizado', true ) ) ?: $defaults['separador_personalizado'];
		$meta['separador'] = sanitize_text_field( get_post_meta( $post_id, '_ygb_separador', true ) ) ?: $defaults['separador'];
		$meta['formato_producto'] = sanitize_text_field( get_post_meta( $post_id, '_ygb_formato_producto', true ) ) ?: $defaults['formato_producto'];

		// Números con rango.
		$meta['tamano_fuente'] = self::validate_range( get_post_meta( $post_id, '_ygb_tamano_fuente', true ), self::FONT_SIZE_RANGE['min'], self::FONT_SIZE_RANGE['max'], $defaults['tamano_fuente'] );
		$meta['velocidad'] = self::validate_range( get_post_meta( $post_id, '_ygb_velocidad', true ), self::SPEED_RANGE['min'], self::SPEED_RANGE['max'], $defaults['velocidad'] );
		$meta['imagen_opacidad'] = max( 10, min( 80, intval( get_post_meta( $post_id, '_ygb_imagen_opacidad', true ) ) ?: $defaults['imagen_opacidad'] ) );

		// Checkboxes.
		$meta['cerrable'] = sanitize_text_field( get_post_meta( $post_id, '_ygb_cerrable', true ) ) ?: $defaults['cerrable'];
		$meta['pausa_hover'] = sanitize_text_field( get_post_meta( $post_id, '_ygb_pausa_hover', true ) ) ?: $defaults['pausa_hover'];
		$meta['imagen_parallax'] = 'si' === get_post_meta( $post_id, '_ygb_imagen_parallax', true ) ? 'si' : $defaults['imagen_parallax'];

		// Selectores con whitelist.
		$whitelists = $defaults['whitelists'];
		foreach ( $whitelists as $key => $allowed ) {
			$meta_key = '_ygb_' . $key;
			$value = sanitize_text_field( get_post_meta( $post_id, $meta_key, true ) );
			if ( ! in_array( $value, $allowed, true ) ) {
				$value = $allowed[0];
			}
			$meta[ $key ] = $value;
		}

		// Manejo especial para separador_simbolo (key).
		$simbolo_key = sanitize_text_field( get_post_meta( $post_id, '_ygb_separador_simbolo', true ) );
		if ( ! array_key_exists( $simbolo_key, self::$simbolos ) ) {
			$simbolo_key = '1';
		}
		$meta['separador_simbolo'] = $simbolo_key;

		// Imagen de fondo.
		$imagen_id = intval( get_post_meta( $post_id, '_ygb_imagen_fondo', true ) );
		if ( $imagen_id > 0 && ! self::validate_background_image( $imagen_id ) ) {
			$imagen_id = 0;
		}
		$meta['imagen_fondo'] = $imagen_id;

		// Productos IDs.
		$ids = get_post_meta( $post_id, '_ygb_productos_ids', true );
		if ( is_array( $ids ) ) {
			$ids = implode( ',', array_map( 'intval', $ids ) );
		}
		$meta['productos_ids'] = is_string( $ids ) ? $ids : '';

		return $meta;
	}

	public static function render_estilos( $post ) {
		wp_nonce_field( 'ygb_aviso_metaboxes', 'ygb_aviso_nonce' );
		$meta = self::get_validated_meta( $post->ID );

		$imagen_url = '';
		if ( $meta['imagen_fondo'] ) {
			$imagen_url = wp_get_attachment_image_url( $meta['imagen_fondo'], 'medium' );
		}
		?>
		<table class="form-table">
			<tr>
				<th><label for="ygb_encabezado"><?php esc_html_e( '📌 Header Text', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_encabezado" name="ygb_encabezado"
						   value="<?php echo esc_attr( $meta['encabezado'] ); ?>" class="regular-text"
						   placeholder="<?php esc_attr_e( 'Example: 📢 NEWS', 'ygb-avisos' ); ?>">
				</td>
			</tr>

			<tr>
				<th colspan="2"><h3 style="margin: 15px 0 0 0;">🎨 Colors</h3></th>
			</tr>

			<tr>
				<th><label for="ygb_color_fondo"><?php esc_html_e( 'Background Color', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_color_fondo" name="ygb_color_fondo"
						   value="<?php echo esc_attr( $meta['color_fondo'] ); ?>" class="ygb-color-field"
						   data-default-color="<?php echo esc_attr( self::DEFAULT_COLORS['fondo'] ); ?>">
					<p class="description"><?php esc_html_e( 'Format: #RRGGBB or rgb(r,g,b)', 'ygb-avisos' ); ?></p>
				</td>
			</tr>

			<tr>
				<th colspan="2"><h3 style="margin: 15px 0 0 0;">🖼️ Background Image (Optional)</h3></th>
			</tr>

			<tr>
				<th><label><?php esc_html_e( 'Image', 'ygb-avisos' ); ?></label></th>
				<td>
					<div class="ygb-image-uploader">
						<input type="hidden" id="ygb_imagen_fondo" name="ygb_imagen_fondo" value="<?php echo esc_attr( $meta['imagen_fondo'] ); ?>">

						<div class="ygb-image-preview" style="margin-bottom: 10px;">
							<?php if ( $imagen_url ) : ?>
								<img src="<?php echo esc_url( $imagen_url ); ?>" style="max-width: 300px; max-height: 100px; border-radius: 5px;">
							<?php else : ?>
								<div class="ygb-image-placeholder" style="width: 300px; height: 60px; background: #f0f0f0; border: 1px dashed #ccc; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999;">
									<?php esc_html_e( 'No image selected', 'ygb-avisos' ); ?>
								</div>
							<?php endif; ?>
						</div>

						<button type="button" class="button ygb-upload-image-btn"><?php esc_html_e( 'Select Image', 'ygb-avisos' ); ?></button>
						<button type="button" class="button ygb-remove-image-btn" style="display: <?php echo $meta['imagen_fondo'] ? 'inline-block' : 'none'; ?>;"><?php esc_html_e( 'Remove', 'ygb-avisos' ); ?></button>

						<p class="description">
							<?php esc_html_e( 'Recommended size: 1920x80px (very wide, low height). Supports JPG, PNG, GIF, WEBP.', 'ygb-avisos' ); ?>
							<?php if ( apply_filters( 'ygb_avisos_allow_svg_background', false ) ) : ?>
								<br><?php esc_html_e( 'SVG files are allowed via filter.', 'ygb-avisos' ); ?>
							<?php endif; ?>
						</p>
					</div>
				</td>
			</tr>

			<tr>
				<th><label><?php esc_html_e( 'Image Options', 'ygb-avisos' ); ?></label></th>
				<td>
					<div style="margin-bottom: 10px;">
						<label style="display: block; margin-bottom: 5px;">
							<input type="checkbox" name="ygb_imagen_parallax" value="si" <?php checked( $meta['imagen_parallax'], 'si' ); ?>>
							<?php esc_html_e( 'Enable parallax effect (slow scroll)', 'ygb-avisos' ); ?>
						</label>
					</div>
				</td>
			</tr>

			<tr>
				<th><label for="ygb_imagen_alineacion_movil"><?php esc_html_e( '📱 Mobile alignment', 'ygb-avisos' ); ?></label></th>
				<td>
					<select id="ygb_imagen_alineacion_movil" name="ygb_imagen_alineacion_movil" style="width: 250px;">
						<?php foreach ( self::$alineacion_imagen as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $meta['imagen_alineacion_movil'], $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select "Left" if your image has important content on the left side (logo, text, call to action). On mobile, the right side will be cut off.', 'ygb-avisos' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="ygb_imagen_overlay"><?php esc_html_e( 'Overlay (for text readability)', 'ygb-avisos' ); ?></label></th>
				<td>
					<select id="ygb_imagen_overlay" name="ygb_imagen_overlay" style="width: 200px; margin-bottom: 10px;">
						<?php foreach ( self::$overlays as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $meta['imagen_overlay'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<div>
						<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Overlay opacity:', 'ygb-avisos' ); ?></label>
						<input type="range" name="ygb_imagen_opacidad" min="10" max="80" value="<?php echo esc_attr( $meta['imagen_opacidad'] ); ?>" style="width: 200px;">
						<span class="ygb-opacity-value"><?php echo intval( $meta['imagen_opacidad'] ); ?>%</span>
						<p class="description"><?php esc_html_e( 'Range: 10% (subtle) to 80% (maximum). Minimum 10% ensures overlay is visible.', 'ygb-avisos' ); ?></p>
					</div>
				</td>
			</tr>

			<tr>
				<th colspan="2"><h3 style="margin: 15px 0 0 0;">📝 Text Styles</h3></th>
			</tr>

			<tr>
				<th><label for="ygb_color_texto"><?php esc_html_e( 'Text Color', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_color_texto" name="ygb_color_texto"
						   value="<?php echo esc_attr( $meta['color_texto'] ); ?>" class="ygb-color-field"
						   data-default-color="<?php echo esc_attr( self::DEFAULT_COLORS['texto'] ); ?>">
				</td>
			</tr>

			<tr>
				<th><label for="ygb_color_enlace"><?php esc_html_e( 'Link Color', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_color_enlace" name="ygb_color_enlace"
						   value="<?php echo esc_attr( $meta['color_enlace'] ); ?>" class="ygb-color-field"
						   data-default-color="<?php echo esc_attr( self::DEFAULT_COLORS['enlace'] ); ?>">
				</td>
			</tr>

			<tr>
				<th colspan="2"><h3 style="margin: 15px 0 0 0;">📌 Header Styles</h3></th>
			</tr>

			<tr>
				<th><label for="ygb_fondo_encabezado"><?php esc_html_e( 'Header Background', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_fondo_encabezado" name="ygb_fondo_encabezado"
						   value="<?php echo esc_attr( $meta['fondo_encabezado'] ); ?>" class="ygb-color-field"
						   data-default-color="<?php echo esc_attr( self::DEFAULT_COLORS['fondo_encabezado'] ); ?>">
				</td>
			</tr>

			<tr>
				<th><label for="ygb_color_encabezado"><?php esc_html_e( 'Header Text Color', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_color_encabezado" name="ygb_color_encabezado"
						   value="<?php echo esc_attr( $meta['color_encabezado'] ); ?>" class="ygb-color-field"
						   data-default-color="<?php echo esc_attr( self::DEFAULT_COLORS['texto_encabezado'] ); ?>">
				</td>
			</tr>

			<tr>
				<th><label for="ygb_tamano_fuente"><?php esc_html_e( 'Font Size (px)', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="number" id="ygb_tamano_fuente" name="ygb_tamano_fuente"
						   value="<?php echo intval( $meta['tamano_fuente'] ); ?>"
						   min="<?php echo intval( self::FONT_SIZE_RANGE['min'] ); ?>"
						   max="<?php echo intval( self::FONT_SIZE_RANGE['max'] ); ?>" step="1">
					<p class="description">
						<?php
						printf(
							esc_html__( 'Recommended: 14-20px (range: %1$d-%2$dpx)', 'ygb-avisos' ),
							intval( self::FONT_SIZE_RANGE['min'] ),
							intval( self::FONT_SIZE_RANGE['max'] )
						);
						?>
					</p>
				</td>
			</tr>

			<tr>
				<th colspan="2"><h3 style="margin: 15px 0 0 0;">🔷 Cycle Separator</h3></th>
			</tr>

			<tr>
				<th><label><?php esc_html_e( 'Separator', 'ygb-avisos' ); ?></label></th>
				<td>
					<div style="margin-bottom: 15px;">
						<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Type:', 'ygb-avisos' ); ?></label>
						<select id="ygb_separador_tipo" name="ygb_separador_tipo" style="width: 300px;">
							<option value="simbolo" <?php selected( $meta['separador_tipo'], 'simbolo' ); ?>><?php esc_html_e( '🔣 Decorative Symbols', 'ygb-avisos' ); ?></option>
							<option value="texto" <?php selected( $meta['separador_tipo'], 'texto' ); ?>><?php esc_html_e( '📝 Custom Text', 'ygb-avisos' ); ?></option>
							<option value="linea" <?php selected( $meta['separador_tipo'], 'linea' ); ?>><?php esc_html_e( '━━━ Lines', 'ygb-avisos' ); ?></option>
							<option value="flecha" <?php selected( $meta['separador_tipo'], 'flecha' ); ?>><?php esc_html_e( '→→→ Arrows', 'ygb-avisos' ); ?></option>
							<option value="emoji" <?php selected( $meta['separador_tipo'], 'emoji' ); ?>><?php esc_html_e( '😊 Emojis', 'ygb-avisos' ); ?></option>
						</select>
					</div>

					<div id="ygb_separador_simbolo_container" style="margin-bottom: 15px; <?php echo 'simbolo' !== $meta['separador_tipo'] ? 'display:none;' : ''; ?>">
						<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Symbol:', 'ygb-avisos' ); ?></label>
						<select id="ygb_separador_simbolo" name="ygb_separador_simbolo" style="width: 300px;">
							<?php foreach ( self::$simbolos as $key => $simbolo ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $meta['separador_simbolo'], $key ); ?>><?php echo esc_html( $simbolo ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div id="ygb_separador_texto_container" style="margin-bottom: 15px; <?php echo 'texto' !== $meta['separador_tipo'] ? 'display:none;' : ''; ?>">
						<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Text:', 'ygb-avisos' ); ?></label>
						<input type="text" id="ygb_separador_texto" name="ygb_separador_texto"
							   value="<?php echo esc_attr( $meta['separador_texto'] ); ?>" class="regular-text"
							   placeholder="<?php esc_attr_e( 'Example: NEWS', 'ygb-avisos' ); ?>">
						<p class="description"><?php esc_html_e( 'The text will be repeated 3 times', 'ygb-avisos' ); ?></p>
					</div>

					<div id="ygb_separador_linea_container" style="margin-bottom: 15px; <?php echo 'linea' !== $meta['separador_tipo'] ? 'display:none;' : ''; ?>">
						<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Line style:', 'ygb-avisos' ); ?></label>
						<select id="ygb_separador_linea" name="ygb_separador_linea" style="width: 300px;">
							<option value="━━━" <?php selected( $meta['separador_personalizado'], '━━━' ); ?>><?php esc_html_e( '━━━ Thick line', 'ygb-avisos' ); ?></option>
							<option value="———" <?php selected( $meta['separador_personalizado'], '———' ); ?>><?php esc_html_e( '——— Medium line', 'ygb-avisos' ); ?></option>
							<option value="⎯⎯⎯" <?php selected( $meta['separador_personalizado'], '⎯⎯⎯' ); ?>><?php esc_html_e( '⎯⎯⎯ Thin line', 'ygb-avisos' ); ?></option>
							<option value="▬▬▬" <?php selected( $meta['separador_personalizado'], '▬▬▬' ); ?>><?php esc_html_e( '▬▬▬ Black line', 'ygb-avisos' ); ?></option>
							<option value="━━┳━━" <?php selected( $meta['separador_personalizado'], '━━┳━━' ); ?>><?php esc_html_e( '━━┳━━ With node', 'ygb-avisos' ); ?></option>
							<option value="╍╍╍" <?php selected( $meta['separador_personalizado'], '╍╍╍' ); ?>><?php esc_html_e( '╍╍╍ Dotted line', 'ygb-avisos' ); ?></option>
						</select>
					</div>

					<div id="ygb_separador_flecha_container" style="margin-bottom: 15px; <?php echo 'flecha' !== $meta['separador_tipo'] ? 'display:none;' : ''; ?>">
						<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Arrow type:', 'ygb-avisos' ); ?></label>
						<select id="ygb_separador_flecha" name="ygb_separador_flecha" style="width: 300px;">
							<option value="→→→" <?php selected( $meta['separador_personalizado'], '→→→' ); ?>><?php esc_html_e( '→→→ Simple arrows', 'ygb-avisos' ); ?></option>
							<option value="⇒⇒⇒" <?php selected( $meta['separador_personalizado'], '⇒⇒⇒' ); ?>><?php esc_html_e( '⇒⇒⇒ Double arrows', 'ygb-avisos' ); ?></option>
							<option value="⇢⇢⇢" <?php selected( $meta['separador_personalizado'], '⇢⇢⇢' ); ?>><?php esc_html_e( '⇢⇢⇢ Dotted arrows', 'ygb-avisos' ); ?></option>
							<option value="➤➤➤" <?php selected( $meta['separador_personalizado'], '➤➤➤' ); ?>><?php esc_html_e( '➤➤➤ Black arrows', 'ygb-avisos' ); ?></option>
							<option value="↠↠↠" <?php selected( $meta['separador_personalizado'], '↠↠↠' ); ?>><?php esc_html_e( '↠↠↠ Arrows with tail', 'ygb-avisos' ); ?></option>
						</select>
					</div>

					<div id="ygb_separador_emoji_container" style="margin-bottom: 15px; <?php echo 'emoji' !== $meta['separador_tipo'] ? 'display:none;' : ''; ?>">
						<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Emoji:', 'ygb-avisos' ); ?></label>
						<select id="ygb_separador_emoji" name="ygb_separador_emoji" style="width: 300px;">
							<option value="⭐⭐⭐" <?php selected( $meta['separador_personalizado'], '⭐⭐⭐' ); ?>><?php esc_html_e( '⭐⭐⭐ Stars', 'ygb-avisos' ); ?></option>
							<option value="💫💫💫" <?php selected( $meta['separador_personalizado'], '💫💫💫' ); ?>><?php esc_html_e( '💫💫💫 Sparkles', 'ygb-avisos' ); ?></option>
							<option value="🔥🔥🔥" <?php selected( $meta['separador_personalizado'], '🔥🔥🔥' ); ?>><?php esc_html_e( '🔥🔥🔥 Fire', 'ygb-avisos' ); ?></option>
							<option value="💥💥💥" <?php selected( $meta['separador_personalizado'], '💥💥💥' ); ?>><?php esc_html_e( '💥💥💥 Explosion', 'ygb-avisos' ); ?></option>
							<option value="✨✨✨" <?php selected( $meta['separador_personalizado'], '✨✨✨' ); ?>><?php esc_html_e( '✨✨✨ Glitter', 'ygb-avisos' ); ?></option>
							<option value="🎯🎯🎯" <?php selected( $meta['separador_personalizado'], '🎯🎯🎯' ); ?>><?php esc_html_e( '🎯🎯🎯 Target', 'ygb-avisos' ); ?></option>
							<option value="🎨🎨🎨" <?php selected( $meta['separador_personalizado'], '🎨🎨🎨' ); ?>><?php esc_html_e( '🎨🎨🎨 Art', 'ygb-avisos' ); ?></option>
							<option value="🎵🎵🎵" <?php selected( $meta['separador_personalizado'], '🎵🎵🎵' ); ?>><?php esc_html_e( '🎵🎵🎵 Musical notes', 'ygb-avisos' ); ?></option>
						</select>
					</div>

					<input type="hidden" id="ygb_separador_valor_final" name="ygb_separador"
						   value="<?php echo esc_attr( $meta['separador'] ); ?>">

					<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
						<label style="display: block; margin-bottom: 10px; font-weight: 600;"><?php esc_html_e( '✨ Separator Animation:', 'ygb-avisos' ); ?></label>
						<select id="ygb_separador_animacion" name="ygb_separador_animacion" style="width: 300px;">
							<?php foreach ( self::$animaciones as $key => $animacion ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $meta['separador_animacion'], $key ); ?>><?php echo esc_html( $animacion ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div style="margin-top: 20px; background: #f8f8f8; padding: 15px; border-radius: 5px;">
						<label style="display: block; margin-bottom: 10px; font-weight: 600;"><?php esc_html_e( '👁️ Separator Preview:', 'ygb-avisos' ); ?></label>
						<div id="ygb_separador_preview" style="font-size: 24px; text-align: center; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
							<span class="separator-preview"><?php echo esc_html( $meta['separador'] ); ?></span>
						</div>
						<p class="description" style="margin-top: 10px; text-align: center;">
							<?php esc_html_e( 'This is how it will look between each cycle of the ticker', 'ygb-avisos' ); ?>
						</p>
					</div>
				</td>
			</tr>
		</table>

		<style>
			.separator-preview {
				display: inline-block;
				font-size: 28px;
			}

			.anim-pulse {
				animation: preview-pulse 2s ease-in-out infinite;
			}

			.anim-spin {
				animation: preview-spin 3s linear infinite;
				display: inline-block;
			}

			.anim-bounce {
				animation: preview-bounce 2s ease infinite;
				display: inline-block;
			}

			.anim-flash {
				animation: preview-flash 2s ease infinite;
			}

			.anim-shake {
				animation: preview-shake 0.5s ease infinite;
				display: inline-block;
			}

			@keyframes preview-pulse {
				0% { opacity: 0.7; transform: scale(1); }
				50% { opacity: 1; transform: scale(1.2); }
				100% { opacity: 0.7; transform: scale(1); }
			}

			@keyframes preview-spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}

			@keyframes preview-bounce {
				0%, 100% { transform: translateY(0); }
				50% { transform: translateY(-10px); }
			}

			@keyframes preview-flash {
				0%, 100% { opacity: 1; }
				50% { opacity: 0.3; }
			}

			@keyframes preview-shake {
				0%, 100% { transform: translateX(0); }
				25% { transform: translateX(-5px); }
				75% { transform: translateX(5px); }
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var mediaFrame;

			$('.ygb-upload-image-btn').on('click', function(e) {
				e.preventDefault();

				var $container = $(this).closest('.ygb-image-uploader');
				var $input = $container.find('#ygb_imagen_fondo');
				var $preview = $container.find('.ygb-image-preview');
				var $removeBtn = $container.find('.ygb-remove-image-btn');

				if (mediaFrame) {
					mediaFrame.open();
					return;
				}

				mediaFrame = wp.media({
					title: '<?php echo esc_js( __( 'Select Background Image', 'ygb-avisos' ) ); ?>',
					button: { text: '<?php echo esc_js( __( 'Use this image', 'ygb-avisos' ) ); ?>' },
					multiple: false,
					library: { type: 'image' }
				});

				mediaFrame.on('select', function() {
					var attachment = mediaFrame.state().get('selection').first().toJSON();

					$input.val(attachment.id);

					var $img = $('<img>', {
						style: 'max-width: 300px; max-height: 100px; border-radius: 5px;'
					});

					$img.attr('src', attachment.url);
					$preview.empty().append($img);
					$removeBtn.show();
				});

				mediaFrame.open();
			});

			$('.ygb-remove-image-btn').on('click', function(e) {
				e.preventDefault();

				var $container = $(this).closest('.ygb-image-uploader');
				var $input = $container.find('#ygb_imagen_fondo');
				var $preview = $container.find('.ygb-image-preview');

				$input.val('');

				var $placeholder = $('<div>', {
					'class': 'ygb-image-placeholder',
					style: 'width: 300px; height: 60px; background: #f0f0f0; border: 1px dashed #ccc; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999;'
				});

				$placeholder.text('<?php echo esc_js( __( 'No image selected', 'ygb-avisos' ) ); ?>');
				$preview.empty().append($placeholder);
				$(this).hide();
			});

			$('input[name="ygb_imagen_opacidad"]').on('input', function() {
				$(this).siblings('.ygb-opacity-value').text($(this).val() + '%');
			});

			// Actualizar el campo oculto del separador cuando cambia el símbolo
			function actualizarSeparadorDesdeSimbolo() {
				var simboloKey = $('#ygb_separador_simbolo').val();
				var simboloTexto = '';
				if (window.ygbAvisosAdmin && window.ygbAvisosAdmin.simbolos && window.ygbAvisosAdmin.simbolos[simboloKey]) {
					simboloTexto = window.ygbAvisosAdmin.simbolos[simboloKey];
				} else {
					simboloTexto = $('#ygb_separador_simbolo option:selected').text();
				}
				$('#ygb_separador_valor_final').val(simboloTexto);
				$('.separator-preview').text(simboloTexto);
			}

			$('#ygb_separador_simbolo').on('change', actualizarSeparadorDesdeSimbolo);
			actualizarSeparadorDesdeSimbolo();
		});
		</script>
		<?php
	}

	public static function render_posicion( $post ) {
		$meta = self::get_validated_meta( $post->ID );
		?>
		<p>
			<label for="ygb_anclaje"><strong><?php esc_html_e( '📌 Anchor:', 'ygb-avisos' ); ?></strong></label><br>
			<select id="ygb_anclaje" name="ygb_anclaje" style="width: 100%;">
				<option value="arriba" <?php selected( $meta['anclaje'], 'arriba' ); ?>><?php esc_html_e( '🔝 Fixed top', 'ygb-avisos' ); ?></option>
				<option value="abajo" <?php selected( $meta['anclaje'], 'abajo' ); ?>><?php esc_html_e( '⬇️ Fixed bottom', 'ygb-avisos' ); ?></option>
				<option value="ambos" <?php selected( $meta['anclaje'], 'ambos' ); ?>><?php esc_html_e( '↕️ Both fixed', 'ygb-avisos' ); ?></option>
			</select>
			<small style="color:#666; display:block; margin-top:5px;">
				<?php esc_html_e( 'The notice will be fixed in the selected position', 'ygb-avisos' ); ?>
			</small>
		</p>

		<p>
			<label for="ygb_velocidad"><strong><?php esc_html_e( '⚡ Speed:', 'ygb-avisos' ); ?></strong></label><br>
			<input type="number" id="ygb_velocidad" name="ygb_velocidad"
				   value="<?php echo intval( $meta['velocidad'] ); ?>"
				   min="<?php echo intval( self::SPEED_RANGE['min'] ); ?>"
				   max="<?php echo intval( self::SPEED_RANGE['max'] ); ?>" step="5" style="width: 100%;">
			<small style="color:#666; display:block; margin-top:5px;">
				<?php
				printf(
					esc_html__( 'Higher number = faster (%1$d-%2$d)', 'ygb-avisos' ),
					intval( self::SPEED_RANGE['min'] ),
					intval( self::SPEED_RANGE['max'] )
				);
				?>
			</small>
		</p>

		<p>
			<label>
				<input type="checkbox" name="ygb_cerrable" value="si" <?php checked( $meta['cerrable'], 'si' ); ?>>
				<strong><?php esc_html_e( '❌ Show close button', 'ygb-avisos' ); ?></strong>
			</label>
		</p>

		<p>
			<label>
				<input type="checkbox" name="ygb_pausa_hover" value="si" <?php checked( $meta['pausa_hover'], 'si' ); ?>>
				<strong><?php esc_html_e( '⏸️ Pause on hover', 'ygb-avisos' ); ?></strong>
			</label>
		</p>
		<?php
	}

	public static function render_productos( $post ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$meta = self::get_validated_meta( $post->ID );
		?>
		<table class="form-table">
			<tr>
				<th><label for="ygb_tipo_contenido"><?php esc_html_e( '📦 Content Type', 'ygb-avisos' ); ?></label></th>
				<td>
					<select id="ygb_tipo_contenido" name="ygb_tipo_contenido" style="width: 300px;">
						<option value="texto" <?php selected( $meta['tipo_contenido'], 'texto' ); ?>><?php esc_html_e( '📝 Manual Text', 'ygb-avisos' ); ?></option>
						<option value="productos" <?php selected( $meta['tipo_contenido'], 'productos' ); ?>><?php esc_html_e( '🔢 Specific Products', 'ygb-avisos' ); ?></option>
						<option value="descuentos" <?php selected( $meta['tipo_contenido'], 'descuentos' ); ?>><?php esc_html_e( '🏷️ Products on Sale', 'ygb-avisos' ); ?></option>
					</select>
				</td>
			</tr>

			<tr id="ygb_productos_row" style="<?php echo 'productos' === $meta['tipo_contenido'] ? '' : 'display:none;'; ?>">
				<th><label for="ygb_productos_ids"><?php esc_html_e( '🔢 Product IDs', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_productos_ids" name="ygb_productos_ids"
						   value="<?php echo esc_attr( $meta['productos_ids'] ); ?>" class="large-text"
						   placeholder="<?php esc_attr_e( 'Example: 123, 456, 789', 'ygb-avisos' ); ?>">
					<p class="description"><?php esc_html_e( 'IDs separated by commas (numbers only)', 'ygb-avisos' ); ?></p>
				</td>
			</tr>

			<tr>
				<th><label for="ygb_formato_producto"><?php esc_html_e( '📝 Product Format', 'ygb-avisos' ); ?></label></th>
				<td>
					<input type="text" id="ygb_formato_producto" name="ygb_formato_producto"
						   value="<?php echo esc_attr( $meta['formato_producto'] ); ?>" class="large-text">
					<p class="description">
						<strong><?php esc_html_e( 'Variables:', 'ygb-avisos' ); ?></strong> {product_name}, {product_price}, {product_sale_price}, {product_discount}<br>
						<strong><?php esc_html_e( 'Example:', 'ygb-avisos' ); ?></strong> {product_name} - {product_sale_price} ({product_discount} DCTO)
					</p>
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			$('#ygb_tipo_contenido').on('change', function() {
				if ($(this).val() === 'productos') {
					$('#ygb_productos_row').show();
				} else {
					$('#ygb_productos_row').hide();
				}
			});
		});
		</script>
		<?php
	}

	public static function render_shortcode( $post ) {
		?>
		<div style="text-align: center; padding: 10px;">
			<p style="margin: 0 0 10px;"><?php esc_html_e( 'Use this shortcode:', 'ygb-avisos' ); ?></p>
			<code style="font-size: 16px; background: #f1f1f1; padding: 8px; display: block;">
			   [ygb-aviso id="<?php echo intval( $post->ID ); ?>"]
			</code>
			<p class="description" style="margin-top: 10px;">
				<?php esc_html_e( 'Also available:', 'ygb-avisos' ); ?> <code>[ygb-universal]</code> <?php esc_html_e( 'for the global notice', 'ygb-avisos' ); ?>
			</p>
		</div>
		<?php
	}

	public static function save_metaboxes( $post_id ) {
		if ( ! isset( $_POST['ygb_aviso_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['ygb_aviso_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'ygb_aviso_metaboxes' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$defaults = self::get_meta_defaults();

		// Colores.
		foreach ( array( 'color_fondo', 'color_texto', 'color_enlace', 'fondo_encabezado', 'color_encabezado' ) as $field ) {
			if ( isset( $_POST['ygb_' . $field] ) ) {
				$color = self::sanitize_hex_color( sanitize_text_field( wp_unslash( $_POST['ygb_' . $field] ) ) );
				if ( ! empty( $color ) ) {
					update_post_meta( $post_id, '_ygb_' . $field, $color );
				}
			}
		}

		// Imagen de fondo.
		if ( isset( $_POST['ygb_imagen_fondo'] ) ) {
			$imagen_id = intval( wp_unslash( $_POST['ygb_imagen_fondo'] ) );
			if ( $imagen_id > 0 && ! self::validate_background_image( $imagen_id ) ) {
				$imagen_id = 0;
			}
			update_post_meta( $post_id, '_ygb_imagen_fondo', $imagen_id );
		}

		update_post_meta( $post_id, '_ygb_imagen_parallax', isset( $_POST['ygb_imagen_parallax'] ) ? 'si' : 'no' );

		// Selectores con whitelist.
		$whitelists = $defaults['whitelists'];
		foreach ( $whitelists as $field => $allowed ) {
			if ( isset( $_POST['ygb_' . $field] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST['ygb_' . $field] ) );
				if ( in_array( $value, $allowed, true ) ) {
					update_post_meta( $post_id, '_ygb_' . $field, $value );
				} else {
					update_post_meta( $post_id, '_ygb_' . $field, $allowed[0] );
				}
			}
		}

		// Guardar separador_simbolo específicamente como key.
		if ( isset( $_POST['ygb_separador_simbolo'] ) ) {
			$simbolo_key = sanitize_text_field( wp_unslash( $_POST['ygb_separador_simbolo'] ) );
			if ( ! array_key_exists( $simbolo_key, self::$simbolos ) ) {
				$simbolo_key = '1';
			}
			update_post_meta( $post_id, '_ygb_separador_simbolo', $simbolo_key );
		}

		// Overlay opacidad.
		if ( isset( $_POST['ygb_imagen_opacidad'] ) ) {
			$opacidad = max( 10, min( 80, intval( wp_unslash( $_POST['ygb_imagen_opacidad'] ) ) ) );
			update_post_meta( $post_id, '_ygb_imagen_opacidad', $opacidad );
		}

		// Campos de texto simples.
		$campos_texto = array(
			'ygb_encabezado',
			'ygb_formato_producto',
			'ygb_separador_texto',
			'ygb_separador',
		);

		foreach ( $campos_texto as $campo ) {
			if ( isset( $_POST[ $campo ] ) ) {
				update_post_meta( $post_id, '_' . $campo, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
			}
		}

		// Rangos numéricos.
		if ( isset( $_POST['ygb_tamano_fuente'] ) ) {
			$tamano = self::validate_range(
				wp_unslash( $_POST['ygb_tamano_fuente'] ),
				self::FONT_SIZE_RANGE['min'],
				self::FONT_SIZE_RANGE['max'],
				16
			);
			update_post_meta( $post_id, '_ygb_tamano_fuente', $tamano );
		} else {
			update_post_meta( $post_id, '_ygb_tamano_fuente', 16 );
		}

		if ( isset( $_POST['ygb_velocidad'] ) ) {
			$velocidad = self::validate_range(
				wp_unslash( $_POST['ygb_velocidad'] ),
				self::SPEED_RANGE['min'],
				self::SPEED_RANGE['max'],
				40
			);
			update_post_meta( $post_id, '_ygb_velocidad', $velocidad );
		} else {
			update_post_meta( $post_id, '_ygb_velocidad', 40 );
		}

		update_post_meta( $post_id, '_ygb_cerrable', isset( $_POST['ygb_cerrable'] ) ? 'si' : 'no' );
		update_post_meta( $post_id, '_ygb_pausa_hover', isset( $_POST['ygb_pausa_hover'] ) ? 'si' : 'no' );

		// Separador personalizado.
		$tipo_separador = isset( $_POST['ygb_separador_tipo'] ) ? sanitize_text_field( wp_unslash( $_POST['ygb_separador_tipo'] ) ) : '';
		$personalizado  = '';

		if ( 'linea' === $tipo_separador && isset( $_POST['ygb_separador_linea'] ) ) {
			$personalizado = sanitize_text_field( wp_unslash( $_POST['ygb_separador_linea'] ) );
		} elseif ( 'flecha' === $tipo_separador && isset( $_POST['ygb_separador_flecha'] ) ) {
			$personalizado = sanitize_text_field( wp_unslash( $_POST['ygb_separador_flecha'] ) );
		} elseif ( 'emoji' === $tipo_separador && isset( $_POST['ygb_separador_emoji'] ) ) {
			$personalizado = sanitize_text_field( wp_unslash( $_POST['ygb_separador_emoji'] ) );
		} elseif ( isset( $_POST['ygb_separador_personalizado'] ) ) {
			$personalizado = sanitize_text_field( wp_unslash( $_POST['ygb_separador_personalizado'] ) );
		}

		if ( '' !== $personalizado ) {
			update_post_meta( $post_id, '_ygb_separador_personalizado', $personalizado );
		}

		if ( isset( $_POST['ygb_productos_ids'] ) ) {
			$raw_ids = sanitize_text_field( wp_unslash( $_POST['ygb_productos_ids'] ) );
			$ids     = array_filter( array_map( 'absint', explode( ',', str_replace( ' ', '', $raw_ids ) ) ) );
			update_post_meta( $post_id, '_ygb_productos_ids', implode( ',', $ids ) );
		}
	}
}