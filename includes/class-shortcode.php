<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YGB_Avisos_Shortcode {

	const MIN_OVERLAY_OPACITY = 0.1;
	const MAX_OVERLAY_OPACITY = 0.8;

	public static function init() {
		add_shortcode( 'ygb-aviso', array( __CLASS__, 'render_aviso' ) );
		add_shortcode( 'ygb-avisos-lista', array( __CLASS__, 'render_lista' ) );
		add_shortcode( 'ygb-universal', array( __CLASS__, 'render_universal' ) );

		add_action( 'wp_footer', array( __CLASS__, 'render_universal_auto' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ensure_jquery' ) );
	}

	public static function ensure_jquery() {
		wp_enqueue_script( 'jquery' );
	}

	public static function render_aviso( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts );
		$aviso_id = intval( $atts['id'] );

		$post = get_post( $aviso_id );

		if ( ! $post || 'ygb_aviso' !== $post->post_type ) {
			return '<!-- Notice not found -->';
		}

		if ( 'publish' !== get_post_status( $post ) && ! current_user_can( 'edit_post', $post->ID ) ) {
			return '<!-- Notice not accessible -->';
		}

		return self::generate_manual_html( $post );
	}

	public static function render_lista( $atts ) {
		$atts = shortcode_atts( array( 'cantidad' => 5 ), $atts );

		$avisos = get_posts( array(
			'post_type'      => 'ygb_aviso',
			'posts_per_page' => intval( $atts['cantidad'] ),
			'post_status'    => 'publish'
		) );

		if ( empty( $avisos ) ) {
			return '<!-- No notices found -->';
		}

		$html = '';

		foreach ( $avisos as $aviso ) {
			$html .= self::generate_manual_html( $aviso );
		}

		return $html;
	}

	public static function render_universal() {
		$options = get_option( 'ygb_avisos_settings', array() );
		$universal_id = isset( $options['universal_id'] ) ? intval( $options['universal_id'] ) : 0;

		if ( empty( $universal_id ) ) {
			return '';
		}

		$post = get_post( $universal_id );

		if ( ! $post || 'ygb_aviso' !== $post->post_type || 'publish' !== get_post_status( $post ) ) {
			return '';
		}

		return self::generate_universal_html( $post );
	}

	public static function render_universal_auto() {
		$options = get_option( 'ygb_avisos_settings', array() );
		$universal_id = isset( $options['universal_id'] ) ? intval( $options['universal_id'] ) : 0;

		if ( empty( $universal_id ) ) {
			return;
		}

		$post = get_post( $universal_id );

		if ( ! $post || 'ygb_aviso' !== $post->post_type || 'publish' !== get_post_status( $post ) ) {
			return;
		}

		echo self::generate_universal_html( $post );
	}

	/**
	 * Devuelve las propiedades CSS permitidas en estilos inline.
	 *
	 * @return string[] Lista de propiedades.
	 */
	private static function get_allowed_style_properties() {
		return array(
			'color',
			'background-color',
			'font-weight',
			'text-decoration',
			'font-style',
		);
	}

	/**
	 * Filtro para limitar las propiedades CSS permitidas en estilos inline.
	 *
	 * @param array $styles Propiedades permitidas actuales.
	 * @return array Propiedades filtradas.
	 */
	public static function filter_safe_style_css( $styles ) {
		return self::get_allowed_style_properties();
	}

	/**
	 * Sanitiza el contenido del ticker permitiendo solo etiquetas y atributos seguros,
	 * y restringiendo los estilos inline a propiedades CSS seguras.
	 *
	 * @param string $content Contenido crudo del aviso.
	 * @return string Contenido saneado.
	 */
	private static function sanitize_ticker_content( $content ) {
		$allowed_html = array(
			'span' => array(
				'class' => array(),
				'style' => array(),
			),
			'a' => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
				'rel'    => array(),
				'class'  => array(),
				'style'  => array(),
			),
			'strong' => array(
				'style' => array(),
			),
			'em' => array(
				'style' => array(),
			),
			'b' => array(
				'style' => array(),
			),
			'i' => array(
				'style' => array(),
			),
			'br' => array(),
		);

		// Filtro temporal para limitar las propiedades CSS permitidas.
		add_filter( 'safe_style_css', array( __CLASS__, 'filter_safe_style_css' ) );
		$content = wp_kses( $content, $allowed_html );
		remove_filter( 'safe_style_css', array( __CLASS__, 'filter_safe_style_css' ) );

		return $content;
	}

	/**
	 * Obtiene la configuración de imagen de fondo para un aviso.
	 *
	 * @param int $aviso_id ID del aviso.
	 * @return array Configuración.
	 */
	private static function get_background_image_config( $aviso_id ) {
		$imagen_id = intval( get_post_meta( $aviso_id, '_ygb_imagen_fondo', true ) );

		$config = array(
			'has_image'        => false,
			'url'              => '',
			'parallax'         => get_post_meta( $aviso_id, '_ygb_imagen_parallax', true ) === 'si',
			'alineacion_movil' => sanitize_text_field( get_post_meta( $aviso_id, '_ygb_imagen_alineacion_movil', true ) ) ?: 'left',
			'overlay'          => sanitize_text_field( get_post_meta( $aviso_id, '_ygb_imagen_overlay', true ) ) ?: 'none',
			'opacity'          => intval( get_post_meta( $aviso_id, '_ygb_imagen_opacidad', true ) ) ?: 50,
		);

		if ( $imagen_id ) {
			$attachment = get_post( $imagen_id );

			if ( $attachment && strpos( $attachment->post_mime_type, 'image/' ) === 0 ) {
				$url = wp_get_attachment_image_url( $imagen_id, 'full' );

				if ( $url ) {
					$config['has_image'] = true;
					$config['url']       = $url;
				}
			}
		}

		return $config;
	}

	/**
	 * Genera el CSS para la imagen de fondo.
	 *
	 * @param string $ticker_id ID del ticker.
	 * @param array  $bg_config Configuración de imagen.
	 * @return string CSS.
	 */
	private static function get_background_image_css( $ticker_id, $bg_config ) {
		if ( ! $bg_config['has_image'] ) {
			return '';
		}

		$mobile_position = $bg_config['alineacion_movil'];

		$css = '
			#' . esc_attr( $ticker_id ) . ' {
				background-image: url("' . esc_url( $bg_config['url'] ) . '") !important;
				background-repeat: no-repeat !important;
			}

			@media (min-width: 1025px) {
				#' . esc_attr( $ticker_id ) . ' {
					background-size: 100% auto !important;
					background-position: center center !important;
				}
			}

			@media (max-width: 1024px) {
				#' . esc_attr( $ticker_id ) . ' {
					background-size: auto 100% !important;
					background-position: ' . esc_attr( $mobile_position ) . ' center !important;
				}
			}
		';

		if ( $bg_config['parallax'] ) {
			$css .= '
				@media (min-width: 1025px) {
					#' . esc_attr( $ticker_id ) . ' {
						background-attachment: fixed !important;
					}
				}
			';
		}

		return $css;
	}

	/**
	 * Obtiene el rango de opacidad del overlay.
	 *
	 * @return array Rango.
	 */
	private static function get_overlay_opacity_range() {
		$default = array(
			'min' => self::MIN_OVERLAY_OPACITY,
			'max' => self::MAX_OVERLAY_OPACITY,
		);

		return apply_filters( 'ygb_avisos_overlay_opacity_range', $default );
	}

	/**
	 * Genera el HTML del overlay para la imagen de fondo.
	 *
	 * @param array $bg_config Configuración de imagen.
	 * @return string HTML del overlay.
	 */
	private static function get_overlay_html( $bg_config ) {
		if ( ! $bg_config['has_image'] || 'none' === $bg_config['overlay'] ) {
			return '';
		}

		$overlay_color = 'dark' === $bg_config['overlay'] ? '0, 0, 0' : '255, 255, 255';

		$range   = self::get_overlay_opacity_range();
		$opacity = $bg_config['opacity'] / 100;
		$opacity = max( $range['min'], min( $range['max'], $opacity ) );

		return '<div class="ygb-ticker-bg-overlay ' . esc_attr( $bg_config['overlay'] ) . '" style="background-color: rgba(' . esc_attr( $overlay_color ) . ', ' . floatval( $opacity ) . ');"></div>';
	}

	/**
	 * Genera el botón de cierre.
	 *
	 * @param string $ticker_id   ID del ticker.
	 * @param string $color_texto Color del texto.
	 * @return string HTML del botón.
	 */
	private static function get_close_button_html( $ticker_id, $color_texto ) {
		return '<button class="ygb-ticker-close-btn"
						data-ticker-id="' . esc_attr( $ticker_id ) . '"
						style="background: transparent !important;
							   border: none !important;
							   box-shadow: none !important;
							   color: ' . esc_attr( $color_texto ) . ' !important;
							   font-size: 20px !important;
							   line-height: 1 !important;
							   padding: 0 10px !important;
							   margin: 0 !important;
							   cursor: pointer !important;
							   transition: opacity 0.2s ease !important;
							   opacity: 0.7 !important;
							   width: auto !important;
							   min-width: auto !important;
							   border-radius: 0 !important;
							   font-weight: normal !important;
							   text-transform: none !important;">
						✕
					   </button>';
	}

	/**
	 * Genera el CSS principal para un ticker.
	 *
	 * @param array $args Parámetros del ticker.
	 * @return string CSS.
	 */
	private static function get_ticker_styles( $args ) {
		$ticker_id       = $args['ticker_id'];
		$aviso_id        = $args['aviso_id'];
		$color_fondo     = $args['color_fondo'];
		$color_texto     = $args['color_texto'];
		$color_enlace    = $args['color_enlace'];
		$fondo_encabezado = $args['fondo_encabezado'];
		$color_encabezado = $args['color_encabezado'];
		$tamano_fuente   = $args['tamano_fuente'];
		$velocidad       = $args['velocidad'];
		$bg_config       = isset( $args['bg_config'] ) ? $args['bg_config'] : array();
		$has_bg_image    = ! empty( $bg_config['has_image'] );
		$is_fixed        = ! empty( $args['is_fixed'] );
		$anclaje         = isset( $args['anclaje'] ) ? $args['anclaje'] : '';

		$css = '<style>';
		$css .= '#' . esc_attr( $ticker_id ) . ' {';
		$css .= 'background-color: ' . esc_attr( $color_fondo ) . ' !important;';
		$css .= 'color: ' . esc_attr( $color_texto ) . ' !important;';
		if ( $is_fixed ) {
			if ( 'arriba' === $anclaje ) {
				$css .= 'position: fixed; top: 0; left: 0; right: 0; z-index: 999999;';
			} elseif ( 'abajo' === $anclaje ) {
				$css .= 'position: fixed; bottom: 0; left: 0; right: 0; z-index: 999999;';
			}
		}
		$css .= 'width: 100%;';
		$css .= 'overflow: hidden;';
		$css .= 'display: flex;';
		$css .= 'align-items: center;';
		$css .= 'padding: 0.2em 0 !important;';
		$css .= 'box-shadow: 0 2px 5px rgba(0,0,0,0.2);';
		$css .= 'font-family: inherit;';
		$css .= 'box-sizing: border-box;';
		$css .= '}';
		$css .= '#' . esc_attr( $ticker_id ) . ' a {';
		$css .= 'color: ' . esc_attr( $color_enlace ) . ' !important;';
		$css .= 'text-decoration: none;';
		$css .= 'font-weight: 600;';
		$css .= 'margin: 0 10px;';
		$css .= '}';
		$css .= '#' . esc_attr( $ticker_id ) . ' a:hover { text-decoration: underline; }';
		$css .= '#' . esc_attr( $ticker_id ) . ' .ygb-ticker-header {';
		$css .= 'background-color: ' . esc_attr( $fondo_encabezado ) . ' !important;';
		$css .= 'color: ' . esc_attr( $color_encabezado ) . ' !important;';
		$css .= 'padding: 0.2em 15px;';
		$css .= 'margin-right: 15px;';
		$css .= 'border-radius: 4px;';
		$css .= 'font-weight: bold;';
		$css .= 'white-space: nowrap;';
		$css .= 'flex-shrink: 0;';
		$css .= '}';
		$css .= '#' . esc_attr( $ticker_id ) . ' .ygb-ticker-content { flex: 1; overflow: hidden; }';
		$css .= '#' . esc_attr( $ticker_id ) . ' .ygb-ticker-track {';
		$css .= 'display: flex;';
		$css .= 'white-space: nowrap;';
		$css .= 'animation: ygb-scroll-' . esc_attr( (string) $aviso_id ) . ' linear infinite;';
		$css .= 'width: fit-content;';
		$css .= 'align-items: center;';
		$css .= '}';
		$css .= '#' . esc_attr( $ticker_id ) . ' .ygb-ticker-track-content {';
		$css .= 'display: inline-block;';
		$css .= 'white-space: nowrap;';
		$css .= 'font-size: ' . esc_attr( (string) $tamano_fuente ) . 'px !important;';
		$css .= '}';
		$css .= '#' . esc_attr( $ticker_id ) . ' .ygb-ticker-separator-cycle {';
		$css .= 'display: inline-block;';
		$css .= 'margin: 0 30px;';
		$css .= 'font-size: 1.2em;';
		$css .= 'opacity: 0.9;';
		$css .= 'color: inherit;';
		$css .= 'white-space: nowrap;';
		$css .= '}';
		$css .= '@keyframes ygb-scroll-' . esc_attr( (string) $aviso_id ) . ' {';
		$css .= '0% { transform: translateX(0); }';
		$css .= '100% { transform: translateX(-50%); }';
		$css .= '}';
		$css .= '#' . esc_attr( $ticker_id ) . ':hover .ygb-ticker-track { animation-play-state: paused; }';
		$css .= '.ygb-ticker-item { margin: 0 10px; display: inline-block; font-size: inherit; }';
		$css .= '.ygb-ticker-separator { margin: 0 3px; opacity: 0.5; font-size: inherit; }';

		if ( $has_bg_image ) {
			$css .= '#' . esc_attr( $ticker_id ) . '.has-bg-image .ygb-ticker-track-content,';
			$css .= '#' . esc_attr( $ticker_id ) . '.has-bg-image .ygb-ticker-header,';
			$css .= '#' . esc_attr( $ticker_id ) . '.has-bg-image .ygb-ticker-item a {';
			$css .= 'text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);';
			$css .= '}';
			if ( isset( $bg_config['overlay'] ) && 'light' === $bg_config['overlay'] ) {
				$css .= '#' . esc_attr( $ticker_id ) . '.has-bg-image .ygb-ticker-bg-overlay.light ~ .ygb-ticker-track-content,';
				$css .= '#' . esc_attr( $ticker_id ) . '.has-bg-image .ygb-ticker-bg-overlay.light ~ .ygb-ticker-header,';
				$css .= '#' . esc_attr( $ticker_id ) . '.has-bg-image .ygb-ticker-bg-overlay.light ~ .ygb-ticker-item a {';
				$css .= 'text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);';
				$css .= '}';
			}
		}

		$css .= self::get_background_image_css( $ticker_id, $bg_config );
		$css .= '</style>';

		return $css;
	}

	/**
	 * Genera la estructura HTML base del ticker.
	 *
	 * @param array $args Parámetros.
	 * @return string HTML.
	 */
	private static function get_ticker_html( $args ) {
		$ticker_id      = $args['ticker_id'];
		$ticker_classes = $args['ticker_classes'];
		$velocidad      = $args['velocidad'];
		$pausa_hover    = $args['pausa_hover'];
		$encabezado     = $args['encabezado'];
		$contenido      = $args['contenido'];
		$separador      = $args['separador'];
		$clase_animacion = $args['clase_animacion'];
		$cerrable       = $args['cerrable'];
		$color_texto    = $args['color_texto'];
		$bg_config      = isset( $args['bg_config'] ) ? $args['bg_config'] : array();

		$html  = '<div id="' . esc_attr( $ticker_id ) . '" class="' . esc_attr( $ticker_classes ) . '" data-speed="' . esc_attr( (string) $velocidad ) . '" data-pause-hover="' . esc_attr( $pausa_hover ) . '">';
		$html .= self::get_overlay_html( $bg_config );

		if ( ! empty( $encabezado ) ) {
			$html .= '<span class="ygb-ticker-header">' . esc_html( $encabezado ) . '</span>';
		}

		$html .= '<div class="ygb-ticker-content"><div class="ygb-ticker-track">';
		$html .= '<div class="ygb-ticker-track-content">' . $contenido . '</div>';
		$html .= '<span class="ygb-ticker-separator-cycle ' . esc_attr( $clase_animacion ) . '">' . esc_html( $separador ) . '</span>';
		$html .= '<div class="ygb-ticker-track-content">' . $contenido . '</div>';
		$html .= '</div></div>';

		if ( 'si' === $cerrable ) {
			$html .= self::get_close_button_html( $ticker_id, $color_texto );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Genera el HTML para un aviso manual (shortcode [ygb-aviso]).
	 *
	 * @param WP_Post $post Aviso publicado.
	 * @return string HTML del ticker.
	 */
	private static function generate_manual_html( $post ) {
		$aviso_id = $post->ID;

		// Obtener y sanitizar metadatos.
		$color_fondo       = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_fondo', true ) ) ?: '#FF5733';
		$color_texto       = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_texto', true ) ) ?: '#FFFFFF';
		$color_enlace      = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_enlace', true ) ) ?: '#FFD700';
		$fondo_encabezado  = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_fondo_encabezado', true ) ) ?: '#C70039';
		$color_encabezado  = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_encabezado', true ) ) ?: '#FFFFFF';
		$encabezado        = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_encabezado', true ) );
		$tamano_fuente     = max( 10, min( 50, absint( get_post_meta( $aviso_id, '_ygb_tamano_fuente', true ) ) ?: 16 ) );
		$velocidad         = max( 10, min( 100, absint( get_post_meta( $aviso_id, '_ygb_velocidad', true ) ) ?: 40 ) );
		$tipo              = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_tipo_contenido', true ) ) ?: 'texto';
		$formato           = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_formato_producto', true ) ) ?: '{product_name} - {product_sale_price} ({product_discount} DCTO)';
		$cerrable          = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_cerrable', true ) ) ?: 'no';
		$pausa_hover       = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_pausa_hover', true ) ) ?: 'si';
		$separador         = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_separador', true ) ) ?: '🔸🔸🔸';
		$animacion         = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_separador_animacion', true ) ) ?: 'pulse';

		$bg_config = self::get_background_image_config( $aviso_id );

		// Contenido.
		if ( 'texto' === $tipo ) {
			$raw_content = $post->post_content;
			if ( empty( $raw_content ) ) {
				$raw_content = 'Notice #' . $aviso_id;
			}
			$contenido = self::sanitize_ticker_content( $raw_content );
		} else {
			$contenido = self::get_productos_reales( $aviso_id, $tipo, $formato );
		}

		$ticker_id       = 'ygb-manual-' . $aviso_id;
		$clase_animacion = ( 'none' !== $animacion ) ? 'anim-' . $animacion : '';
		$ticker_classes  = 'ygb-ticker';

		if ( $bg_config['has_image'] ) {
			$ticker_classes .= ' has-bg-image';
		}
		if ( $bg_config['parallax'] ) {
			$ticker_classes .= ' has-parallax';
		}

		$args = array(
			'ticker_id'        => $ticker_id,
			'aviso_id'         => $aviso_id,
			'color_fondo'      => $color_fondo,
			'color_texto'      => $color_texto,
			'color_enlace'     => $color_enlace,
			'fondo_encabezado' => $fondo_encabezado,
			'color_encabezado' => $color_encabezado,
			'tamano_fuente'    => $tamano_fuente,
			'velocidad'        => $velocidad,
			'bg_config'        => $bg_config,
			'is_fixed'         => false,
			'ticker_classes'   => $ticker_classes,
			'pausa_hover'      => $pausa_hover,
			'encabezado'       => $encabezado,
			'contenido'        => $contenido,
			'separador'        => $separador,
			'clase_animacion'  => $clase_animacion,
			'cerrable'         => $cerrable,
			'color_texto'      => $color_texto,
			'bg_config'        => $bg_config,
		);

		$css  = self::get_ticker_styles( $args );
		$html = $css . self::get_ticker_html( $args );

		return $html;
	}

	/**
	 * Genera el HTML para el aviso universal (shortcode [ygb-universal]).
	 *
	 * @param WP_Post $post Aviso publicado.
	 * @return string HTML del ticker.
	 */
	private static function generate_universal_html( $post ) {
		$aviso_id = $post->ID;

		$color_fondo       = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_fondo', true ) ) ?: '#FF5733';
		$color_texto       = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_texto', true ) ) ?: '#FFFFFF';
		$color_enlace      = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_enlace', true ) ) ?: '#FFD700';
		$fondo_encabezado  = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_fondo_encabezado', true ) ) ?: '#C70039';
		$color_encabezado  = self::sanitize_hex_color( get_post_meta( $aviso_id, '_ygb_color_encabezado', true ) ) ?: '#FFFFFF';
		$encabezado        = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_encabezado', true ) );
		$tamano_fuente     = max( 10, min( 50, absint( get_post_meta( $aviso_id, '_ygb_tamano_fuente', true ) ) ?: 16 ) );
		$velocidad         = max( 10, min( 100, absint( get_post_meta( $aviso_id, '_ygb_velocidad', true ) ) ?: 40 ) );
		$tipo              = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_tipo_contenido', true ) ) ?: 'texto';
		$formato           = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_formato_producto', true ) ) ?: '{product_name} - {product_sale_price} ({product_discount} DCTO)';
		$cerrable          = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_cerrable', true ) ) ?: 'no';
		$pausa_hover       = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_pausa_hover', true ) ) ?: 'si';
		$anclaje           = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_anclaje', true ) ) ?: 'arriba';
		$separador         = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_separador', true ) ) ?: '🔸🔸🔸';
		$animacion         = sanitize_text_field( get_post_meta( $aviso_id, '_ygb_separador_animacion', true ) ) ?: 'pulse';

		$bg_config = self::get_background_image_config( $aviso_id );

		if ( 'texto' === $tipo ) {
			$raw_content = $post->post_content;
			if ( empty( $raw_content ) ) {
				$raw_content = 'Notice #' . $aviso_id;
			}
			$contenido = self::sanitize_ticker_content( $raw_content );
		} else {
			$contenido = self::get_productos_reales( $aviso_id, $tipo, $formato );
		}

		$clase_animacion = ( 'none' !== $animacion ) ? 'anim-' . $animacion : '';
		$ticker_classes  = 'ygb-ticker';

		if ( $bg_config['has_image'] ) {
			$ticker_classes .= ' has-bg-image';
		}
		if ( $bg_config['parallax'] ) {
			$ticker_classes .= ' has-parallax';
		}

		// Manejo especial para anclaje "ambos" (dos tickers).
		if ( 'ambos' === $anclaje ) {
			return self::generate_both_anchors_html(
				$aviso_id,
				$color_fondo,
				$color_texto,
				$color_enlace,
				$fondo_encabezado,
				$color_encabezado,
				$encabezado,
				$tamano_fuente,
				$velocidad,
				$cerrable,
				$separador,
				$clase_animacion,
				$contenido,
				$bg_config,
				$pausa_hover
			);
		}

		$ticker_id = 'ygb-universal-' . $aviso_id;
		$is_fixed  = ( 'arriba' === $anclaje || 'abajo' === $anclaje );

		$args = array(
			'ticker_id'        => $ticker_id,
			'aviso_id'         => $aviso_id,
			'color_fondo'      => $color_fondo,
			'color_texto'      => $color_texto,
			'color_enlace'     => $color_enlace,
			'fondo_encabezado' => $fondo_encabezado,
			'color_encabezado' => $color_encabezado,
			'tamano_fuente'    => $tamano_fuente,
			'velocidad'        => $velocidad,
			'bg_config'        => $bg_config,
			'is_fixed'         => $is_fixed,
			'anclaje'          => $anclaje,
			'ticker_classes'   => $ticker_classes,
			'pausa_hover'      => $pausa_hover,
			'encabezado'       => $encabezado,
			'contenido'        => $contenido,
			'separador'        => $separador,
			'clase_animacion'  => $clase_animacion,
			'cerrable'         => $cerrable,
			'color_texto'      => $color_texto,
			'bg_config'        => $bg_config,
		);

		$css  = self::get_ticker_styles( $args );
		$html = $css . self::get_ticker_html( $args );

		return $html;
	}

	/**
	 * Genera HTML para anclaje "ambos" (dos tickers fijos arriba y abajo).
	 *
	 * @param int    $aviso_id         ID del aviso.
	 * @param string $color_fondo      Color de fondo.
	 * @param string $color_texto      Color de texto.
	 * @param string $color_enlace     Color de enlace.
	 * @param string $fondo_encabezado Color fondo encabezado.
	 * @param string $color_encabezado Color texto encabezado.
	 * @param string $encabezado       Texto del encabezado.
	 * @param int    $tamano_fuente    Tamaño de fuente.
	 * @param int    $velocidad        Velocidad.
	 * @param string $cerrable         Indica si es cerrable.
	 * @param string $separador        Separador entre ciclos.
	 * @param string $clase_animacion  Clase de animación.
	 * @param string $contenido        Contenido del ticker.
	 * @param array  $bg_config        Configuración de imagen de fondo.
	 * @param string $pausa_hover      Indica pausa en hover.
	 * @return string HTML.
	 */
	private static function generate_both_anchors_html(
		$aviso_id,
		$color_fondo,
		$color_texto,
		$color_enlace,
		$fondo_encabezado,
		$color_encabezado,
		$encabezado,
		$tamano_fuente,
		$velocidad,
		$cerrable,
		$separador,
		$clase_animacion,
		$contenido,
		$bg_config,
		$pausa_hover
	) {
		$ticker_top_id    = 'ygb-universal-' . $aviso_id . '-top';
		$ticker_bottom_id = 'ygb-universal-' . $aviso_id . '-bottom';
		$font_size        = intval( $tamano_fuente );
		$velocidad        = max( 1, intval( $velocidad ) );
		$duration         = 100 / $velocidad;

		$ticker_classes = 'ygb-universal-ticker ygb-ticker';

		if ( ! empty( $bg_config['has_image'] ) ) {
			$ticker_classes .= ' has-bg-image';
		}
		if ( ! empty( $bg_config['parallax'] ) ) {
			$ticker_classes .= ' has-parallax';
		}

		// Estilos compartidos para ambos tickers.
		$css = '<style>';
		$css .= '@keyframes ygb-scroll-' . esc_attr( (string) $aviso_id ) . ' {';
		$css .= '0% { transform: translateX(0); }';
		$css .= '100% { transform: translateX(-50%); }';
		$css .= '}';
		$css .= '.ygb-universal-ticker { transition: opacity 0.3s ease; }';
		$css .= '.has-bg-image .ygb-ticker-track-content, .has-bg-image .ygb-ticker-header, .has-bg-image .ygb-ticker-item a { text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2); }';
		$css .= self::get_background_image_css( $ticker_top_id, $bg_config );
		$css .= self::get_background_image_css( $ticker_bottom_id, $bg_config );
		$css .= '</style>';

		$args_top = array(
			'ticker_id'       => $ticker_top_id,
			'ticker_classes'  => $ticker_classes,
			'velocidad'       => $velocidad,
			'pausa_hover'     => $pausa_hover,
			'encabezado'      => $encabezado,
			'contenido'       => $contenido,
			'separador'       => $separador,
			'clase_animacion' => $clase_animacion,
			'cerrable'        => $cerrable,
			'color_texto'     => $color_texto,
			'bg_config'       => $bg_config,
			'is_fixed'        => true,
			'anclaje'         => 'arriba',
		);
		$args_bottom = array(
			'ticker_id'       => $ticker_bottom_id,
			'ticker_classes'  => $ticker_classes,
			'velocidad'       => $velocidad,
			'pausa_hover'     => $pausa_hover,
			'encabezado'      => $encabezado,
			'contenido'       => $contenido,
			'separador'       => $separador,
			'clase_animacion' => $clase_animacion,
			'cerrable'        => $cerrable,
			'color_texto'     => $color_texto,
			'bg_config'       => $bg_config,
			'is_fixed'        => true,
			'anclaje'         => 'abajo',
		);

		$html  = $css;
		$html .= self::get_ticker_html( $args_top );
		$html .= self::get_ticker_html( $args_bottom );

		return $html;
	}

	/**
	 * Sanitiza un color hexadecimal.
	 *
	 * @param string $color Color.
	 * @return string Color saneado o vacío.
	 */
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

	/**
	 * Obtiene los productos reales para el ticker.
	 *
	 * @param int    $aviso_id ID del aviso.
	 * @param string $tipo     Tipo de contenido.
	 * @param string $formato  Formato con placeholders.
	 * @return string HTML.
	 */
	private static function get_productos_reales( $aviso_id, $tipo, $formato ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return esc_html__( 'WooCommerce not active', 'ygb-avisos' );
		}

		$productos = array();

		if ( 'productos' === $tipo ) {
			$ids_string = get_post_meta( $aviso_id, '_ygb_productos_ids', true );
			if ( ! empty( $ids_string ) ) {
				$ids = array_filter( array_map( 'absint', explode( ',', $ids_string ) ) );
				if ( ! empty( $ids ) ) {
					$productos = wc_get_products( array(
						'include' => $ids,
						'limit'   => -1,
						'status'  => 'publish',
					) );
				}
			}
		} elseif ( 'descuentos' === $tipo ) {
			$product_ids = wc_get_product_ids_on_sale();
			if ( ! empty( $product_ids ) ) {
				$product_ids = array_slice( $product_ids, 0, 10 );
				$productos   = wc_get_products( array(
					'include' => $product_ids,
					'limit'   => 10,
					'status'  => 'publish',
				) );
			}
		}

		if ( empty( $productos ) ) {
			return esc_html__( 'No products available', 'ygb-avisos' );
		}

		$items = array();
		foreach ( $productos as $producto ) {
			$nombre        = esc_html( $producto->get_name() );
			$precio        = wp_strip_all_tags( wc_price( $producto->get_price() ) );
			$precio_oferta = $producto->get_sale_price() ? wp_strip_all_tags( wc_price( $producto->get_sale_price() ) ) : '';

			$descuento = '';
			$regular   = floatval( $producto->get_regular_price() );
			$sale      = floatval( $producto->get_sale_price() );
			if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
				$descuento = esc_html( round( 100 - ( $sale / $regular * 100 ) ) . '%' );
			}

			$texto = str_replace(
				array( '{product_name}', '{product_price}', '{product_sale_price}', '{product_discount}' ),
				array( $nombre, $precio, $precio_oferta, $descuento ),
				$formato
			);

			$permalink = esc_url( get_permalink( $producto->get_id() ) );
			$items[]   = '<span class="ygb-ticker-item"><a href="' . $permalink . '" target="_blank" rel="noopener noreferrer">' . $texto . '</a></span>';
		}

		return implode( ' <span class="ygb-ticker-separator">•</span> ', $items );
	}
}