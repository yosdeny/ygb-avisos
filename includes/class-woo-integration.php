<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YGB_Avisos_Woo {

	const CACHE_DURATION = HOUR_IN_SECONDS;
	const MAX_PRODUCTS   = 10;

	public static function init() {
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_hpos_compatibility' ) );

		add_shortcode( 'ygb-producto-actual', array( __CLASS__, 'producto_actual_shortcode' ) );

		add_action( 'woocommerce_before_single_product', array( __CLASS__, 'display_product_ticker' ), 5 );
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'display_archive_ticker' ), 5 );

		add_action( 'save_post_product', array( __CLASS__, 'clear_product_cache' ), 10, 3 );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'clear_product_cache_on_update' ), 10, 2 );
		add_action( 'woocommerce_bulk_action_finished', array( __CLASS__, 'clear_all_product_cache' ), 10, 2 );

		add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'clear_product_cache_on_stock_change' ) );
		add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'clear_product_cache_on_stock_change' ) );

		add_action( 'before_delete_post', array( __CLASS__, 'clear_product_cache_on_delete' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'clear_product_cache_on_delete' ) );
		add_action( 'woocommerce_product_import_before_import', array( __CLASS__, 'clear_all_product_cache' ) );
	}

	public static function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				YGB_AVISOS_BASENAME,
				true
			);
		}
	}

	public static function display_product_ticker() {
		global $product;

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$product_id = $product->get_id();
		$avisos     = self::get_avisos_by_product_id( $product_id );

		if ( ! empty( $avisos ) ) {
			foreach ( $avisos as $aviso ) {
				if ( get_post_status( $aviso->ID ) === 'publish' ) {
					echo do_shortcode( '[ygb-aviso id="' . intval( $aviso->ID ) . '"]' );
				}
			}
		}
	}

	public static function display_archive_ticker() {
		if ( is_product_category() || is_shop() ) {
			$avisos = self::get_avisos_for_archive();

			if ( ! empty( $avisos ) ) {
				foreach ( $avisos as $aviso ) {
					if ( get_post_status( $aviso->ID ) === 'publish' ) {
						echo do_shortcode( '[ygb-aviso id="' . intval( $aviso->ID ) . '"]' );
					}
				}
			}
		}
	}

	private static function get_avisos_by_product_id( $product_id ) {
		$transient_key = 'ygb_avisos_product_' . $product_id;
		$avisos        = get_transient( $transient_key );

		if ( false === $avisos ) {
			global $wpdb;

			$sql = $wpdb->prepare(
				"SELECT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type = %s
				   AND p.post_status = 'publish'
				   AND pm.meta_key = '_ygb_productos_ids'
				   AND FIND_IN_SET(%d, pm.meta_value)",
				'ygb_aviso',
				$product_id
			);

			$post_ids = $wpdb->get_col( $sql );

			if ( ! empty( $post_ids ) ) {
				$avisos = get_posts( array(
					'post__in'            => $post_ids,
					'post_type'           => 'ygb_aviso',
					'posts_per_page'      => 5,
					'post_status'         => 'publish',
					'orderby'             => 'post__in',
					'suppress_filters'    => false,
					'no_found_rows'       => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				) );
			} else {
				$avisos = array();
			}

			set_transient( $transient_key, $avisos, self::CACHE_DURATION );
		}

		return $avisos;
	}

	private static function get_avisos_for_archive() {
		$transient_key = 'ygb_avisos_archive';
		$avisos        = get_transient( $transient_key );

		if ( false === $avisos ) {
			$avisos = get_posts( array(
				'post_type'           => 'ygb_aviso',
				'posts_per_page'      => 3,
				'post_status'         => 'publish',
				'meta_key'            => '_ygb_tipo_contenido',
				'meta_value'          => 'descuentos',
				'meta_compare'        => '=',
				'suppress_filters'    => false,
				'no_found_rows'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			) );

			set_transient( $transient_key, $avisos, self::CACHE_DURATION );
		}

		return $avisos;
	}

	public static function clear_product_cache( $post_id, $post, $update ) {
		if ( $update ) {
			self::clear_all_product_cache( $post_id );
		}
	}

	public static function clear_product_cache_on_update( $product_id, $product ) {
		self::clear_all_product_cache( $product_id );
	}

	public static function clear_product_cache_on_stock_change( $product ) {
		if ( $product && method_exists( $product, 'get_id' ) ) {
			self::clear_all_product_cache( $product->get_id() );
		}
	}

	public static function clear_all_product_cache( $product_id = null ) {
		if ( $product_id ) {
			delete_transient( 'ygb_avisos_product_' . $product_id );
		}

		delete_transient( 'ygb_avisos_archive' );
		delete_transient( 'ygb_avisos_sale_products' );
	}

	public static function clear_product_cache_on_delete( $post_id ) {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}
		self::clear_all_product_cache( $post_id );
	}

	public static function producto_actual_shortcode( $atts, $content = null ) {
		global $product;

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			if ( function_exists( 'wc_get_product' ) && is_singular( 'product' ) ) {
				$product = wc_get_product( get_the_ID() );
			}
			if ( ! $product ) {
				return '<!-- No product found -->';
			}
		}

		$atts = shortcode_atts( array(
			'formato'     => '{product_name} - {product_price}',
			'show_rating' => 'no',
			'show_stock'  => 'no',
			'link'        => 'yes',
		), $atts );

		$formato     = sanitize_text_field( $atts['formato'] );
		$show_rating = sanitize_text_field( $atts['show_rating'] ) === 'yes';
		$show_stock  = sanitize_text_field( $atts['show_stock'] ) === 'yes';
		$link        = sanitize_text_field( $atts['link'] ) === 'yes';

		$product_id = $product->get_id();
		$nombre     = esc_html( $product->get_name() );

		$precio_regular = $product->get_regular_price();
		$precio_actual  = $product->get_price();
		$precio_html    = wp_strip_all_tags( wc_price( $precio_actual ) );

		$precio_oferta = '';
		if ( $product->is_on_sale() ) {
			$sale_price = $product->get_sale_price();
			if ( $sale_price ) {
				$precio_oferta = wp_strip_all_tags( wc_price( $sale_price ) );
			}
		}

		$descuento = '';
		if ( $product->is_on_sale() && floatval( $precio_regular ) > 0 ) {
			$regular = floatval( $precio_regular );
			$sale    = floatval( $product->get_sale_price() );
			if ( $sale > 0 && $sale < $regular ) {
				$descuento = esc_html( round( 100 - ( $sale / $regular * 100 ) ) . '%' );
			}
		}

		$rating_html = '';
		if ( $show_rating ) {
			$average_rating = $product->get_average_rating();
			if ( $average_rating > 0 ) {
				$rating_html = sprintf(
					'<span class="ygb-rating" style="margin:0 5px;">⭐ %.1f</span>',
					floatval( $average_rating )
				);
			}
		}

		$stock_html = '';
		if ( $show_stock ) {
			$stock       = $product->get_stock_status();
			$stock_text  = ( 'instock' === $stock ) ? __( '✓ In stock', 'ygb-avisos' ) : __( '✗ Out of stock', 'ygb-avisos' );
			$stock_class = ( 'instock' === $stock ) ? 'in-stock' : 'out-of-stock';
			$stock_html  = '<span class="ygb-stock ' . esc_attr( $stock_class ) . '" style="margin:0 5px;">' . esc_html( $stock_text ) . '</span>';
		}

		$sku_html = $product->get_sku() ? '<span class="ygb-sku" style="margin:0 5px;">SKU: ' . esc_html( $product->get_sku() ) . '</span>' : '';

		$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
		$categories_html = ! empty( $categories ) ? '<span class="ygb-categories" style="margin:0 5px;">📁 ' . esc_html( implode( ', ', array_slice( $categories, 0, 3 ) ) ) . '</span>' : '';

		$texto = str_replace(
			array(
				'{product_name}',
				'{product_price}',
				'{product_sale_price}',
				'{product_discount}',
				'{product_sku}',
				'{product_rating}',
				'{product_stock}',
				'{product_categories}',
			),
			array(
				$nombre,
				$precio_html,
				$precio_oferta,
				$descuento,
				$sku_html,
				$rating_html,
				$stock_html,
				$categories_html,
			),
			$formato
		);

		$texto = preg_replace( '/\s+/', ' ', $texto );

		if ( ! empty( $content ) ) {
			$texto .= ' ' . wp_kses_post( $content );
		}

		$texto = wp_kses_post( $texto );

		if ( $link ) {
			$permalink = esc_url( get_permalink( $product_id ) );
			$texto = '<a href="' . $permalink . '" target="_blank" rel="noopener noreferrer nofollow">' . $texto . '</a>';
		}

		return '<span class="ygb-product-shortcode">' . $texto . '</span>';
	}

	public static function get_sale_products_cached( $limit = self::MAX_PRODUCTS ) {
		$transient_key = 'ygb_avisos_sale_products_' . $limit;
		$products      = get_transient( $transient_key );

		if ( false === $products ) {
			$product_ids = wc_get_product_ids_on_sale();

			if ( ! empty( $product_ids ) ) {
				$product_ids = array_slice( $product_ids, 0, $limit );

				$products = wc_get_products( array(
					'include' => $product_ids,
					'limit'   => $limit,
					'status'  => 'publish',
				) );
			} else {
				$products = array();
			}

			set_transient( $transient_key, $products, self::CACHE_DURATION );
		}

		return $products;
	}

	public static function check_woocommerce_compatibility() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		$wc_version = WC()->version;

		if ( version_compare( $wc_version, '4.0', '<' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_version_notice' ) );
			return false;
		}

		return true;
	}

	public static function woocommerce_version_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php echo esc_html__( 'YGB Avisos: WooCommerce version 4.0 or higher is required for full compatibility. Please update WooCommerce.', 'ygb-avisos' ); ?>
			</p>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'YGB_Avisos_Woo', 'check_woocommerce_compatibility' ) );