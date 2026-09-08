<?php
/**
 * Plugin Name: YGB Avisos
 * Plugin URI: https://github.com/yosdeny
 * Description: Plugin de cinta informativa con control total - texto, enlaces, colores, productos y descuentos. Compatible con WooCommerce 8.0+ y HPOS.
 * Version: 2.1.0
 * Author: YGB
 * Author URI: https://github.com/yosdeny
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ygb-avisos
 * Domain Path: /languages
 * Requires at least: 7.0
 * Tested up to: 7.1
 * Requires PHP: 8.0
 * Tested PHP: 8.2
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 *
 * @package YGB_Avisos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validación temprana de versión de PHP.
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action( 'admin_notices', 'ygb_avisos_php_version_notice' );
	return;
}

// Validación de versión de WordPress.
global $wp_version;
if ( version_compare( $wp_version, '7.0', '<' ) ) {
	add_action( 'admin_notices', 'ygb_avisos_wp_version_notice' );
	return;
}

define( 'YGB_AVISOS_VERSION', '2.0.1' );
define( 'YGB_AVISOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'YGB_AVISOS_URL', plugin_dir_url( __FILE__ ) );
define( 'YGB_AVISOS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Aviso de versión de PHP insuficiente.
 */
function ygb_avisos_php_version_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'YGB Avisos requiere PHP 8.1 o superior. El plugin ha sido desactivado.', 'ygb-avisos' ); ?></p>
	</div>
	<?php
}

/**
 * Aviso de versión de WordPress insuficiente.
 */
function ygb_avisos_wp_version_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'YGB Avisos requiere WordPress 6.3 o superior. El plugin ha sido desactivado.', 'ygb-avisos' ); ?></p>
	</div>
	<?php
}

/**
 * Aviso de versión de WooCommerce insuficiente.
 */
function ygb_avisos_wc_version_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p><?php esc_html_e( 'YGB Avisos: Se requiere WooCommerce 4.0 o superior para la integración de productos. Las funciones de WooCommerce no estarán disponibles.', 'ygb-avisos' ); ?></p>
	</div>
	<?php
}

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

class YGB_Avisos {

	private static $instance = null;
	private $options = array();

	private function __construct() {
		$this->load_options();
		$this->load_dependencies();
		$this->init_hooks();
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function load_options() {
		$defaults = array(
			'universal_id' => '',
			'version'      => YGB_AVISOS_VERSION,
		);

		$this->options = get_option( 'ygb_avisos_settings', $defaults );
	}

	private function load_dependencies() {
		$includes_path = YGB_AVISOS_PATH . 'includes/';

		$files = array(
			'class-cpt.php',
			'class-metaboxes.php',
			'class-shortcode.php',
		);

		foreach ( $files as $file ) {
			$file_path = $includes_path . $file;

			if ( is_readable( $file_path ) ) {
				require_once $file_path;
			}
		}

		// Integración con WooCommerce solo si la versión es compatible.
		if ( class_exists( 'WooCommerce' ) ) {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '4.0', '>=' ) ) {
				$woo_file = $includes_path . 'class-woo-integration.php';
				if ( is_readable( $woo_file ) ) {
					require_once $woo_file;
				}
			} else {
				add_action( 'admin_notices', 'ygb_avisos_wc_version_notice' );
			}
		}
	}

	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . YGB_AVISOS_BASENAME, array( $this, 'add_action_links' ) );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	public function init() {
		load_plugin_textdomain( 'ygb-avisos', false, dirname( YGB_AVISOS_BASENAME ) . '/languages' );

		if ( class_exists( 'YGB_Avisos_CPT' ) ) {
			YGB_Avisos_CPT::register();
		}

		if ( class_exists( 'YGB_Avisos_Metaboxes' ) ) {
			YGB_Avisos_Metaboxes::init();
		}

		if ( class_exists( 'YGB_Avisos_Shortcode' ) ) {
			YGB_Avisos_Shortcode::init();
		}

		if ( class_exists( 'WooCommerce' ) && class_exists( 'YGB_Avisos_Woo' ) ) {
			YGB_Avisos_Woo::init();
		}
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'ygb-avisos-styles',
			YGB_AVISOS_URL . 'assets/css/ticker-styles.css',
			array(),
			YGB_AVISOS_VERSION
		);

		wp_enqueue_script(
			'ygb-avisos-scripts',
			YGB_AVISOS_URL . 'assets/js/ticker-scripts.js',
			array( 'jquery' ),
			YGB_AVISOS_VERSION,
			true
		);
	}

	public function enqueue_admin_assets( $hook ) {
		global $post;

		$is_plugin_page = ( 'toplevel_page_ygb-avisos' === $hook );

		$is_cpt_page = (
			in_array( $hook, array( 'post.php', 'post-new.php' ), true )
			&& $post
			&& 'ygb_aviso' === $post->post_type
		);

		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		$is_cpt_list = ( 'edit.php' === $hook && 'ygb_aviso' === $post_type );

		if ( ! $is_plugin_page && ! $is_cpt_page && ! $is_cpt_list ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'dashicons' );

		if ( $is_cpt_page ) {
			$js_file = YGB_AVISOS_URL . 'assets/js/admin-separator.js';
			$js_path = YGB_AVISOS_PATH . 'assets/js/admin-separator.js';

			if ( is_readable( $js_path ) ) {
				wp_enqueue_script(
					'ygb-avisos-admin',
					$js_file,
					array( 'jquery' ),
					YGB_AVISOS_VERSION,
					true
				);

				$simbolos = array();

				if ( class_exists( 'YGB_Avisos_Metaboxes' ) ) {
					$simbolos = YGB_Avisos_Metaboxes::get_simbolos();
				}

				wp_localize_script( 'ygb-avisos-admin', 'ygbAvisosAdmin', array(
					'simbolos'          => $simbolos,
					'nonce'             => wp_create_nonce( 'ygb_avisos_admin_nonce' ),
					'ajax_url'          => admin_url( 'admin-ajax.php' ),
					'default_separator' => '🔸🔸🔸',
				) );
			}
		}

		wp_add_inline_script( 'wp-color-picker', '
			jQuery(document).ready(function($) {
				$(".ygb-color-field").wpColorPicker({
					defaultColor: false,
					change: function(event, ui) {},
					clear: function() {}
				});
			});
		' );
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'YGB Avisos Dashboard', 'ygb-avisos' ),
			__( 'YGB Avisos', 'ygb-avisos' ),
			'manage_options',
			'ygb-avisos',
			array( $this, 'render_dashboard_page' ),
			'dashicons-megaphone',
			25
		);

		add_submenu_page(
			'ygb-avisos',
			__( 'Dashboard', 'ygb-avisos' ),
			__( 'Dashboard', 'ygb-avisos' ),
			'manage_options',
			'ygb-avisos',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'ygb-avisos',
			__( 'All Notices', 'ygb-avisos' ),
			__( 'All Notices', 'ygb-avisos' ),
			'manage_options',
			'edit.php?post_type=ygb_aviso'
		);

		add_submenu_page(
			'ygb-avisos',
			__( 'Add New', 'ygb-avisos' ),
			__( 'Add New', 'ygb-avisos' ),
			'manage_options',
			'post-new.php?post_type=ygb_aviso'
		);
	}

	public function register_settings() {
		register_setting( 'ygb_avisos_settings', 'ygb_avisos_settings', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );
	}

	public function sanitize_settings( $input ) {
		$output = array();

		if ( isset( $input['universal_id'] ) ) {
			$output['universal_id'] = absint( $input['universal_id'] );
		} else {
			$output['universal_id'] = 0;
		}

		$output['version'] = YGB_AVISOS_VERSION;

		return $output;
	}

	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=ygb-avisos' ) ),
			esc_html__( 'Settings', 'ygb-avisos' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'ygb-avisos' ) );
		}

		$counts = wp_count_posts( 'ygb_aviso' );
		$total_avisos = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
		$universal_id = isset( $this->options['universal_id'] ) ? absint( $this->options['universal_id'] ) : 0;
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'YGB Avisos Dashboard', 'ygb-avisos' ); ?></h1>

			<div class="ygb-dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
				<div class="ygb-widget" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea;">
					<div style="font-size: 36px; font-weight: bold; color: #667eea;"><?php echo intval( $total_avisos ); ?></div>
					<div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Published Notices', 'ygb-avisos' ); ?></div>
				</div>

				<div class="ygb-widget" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #38a169;">
					<div style="font-size: 36px; font-weight: bold; color: #38a169;">
						<?php echo ! empty( $universal_id ) ? '✅' : '⚪'; ?>
					</div>
					<div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Global Notice', 'ygb-avisos' ); ?></div>
				</div>

				<div class="ygb-widget" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #ed8936;">
					<div style="font-size: 36px; font-weight: bold; color: #ed8936;">v<?php echo esc_html( YGB_AVISOS_VERSION ); ?></div>
					<div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Version', 'ygb-avisos' ); ?></div>
				</div>
			</div>

			<div class="ygb-settings-panel" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 30px 0; border: 2px solid #FF5733;">
				<h2 style="margin-top: 0; color: #FF5733;">
					<span class="dashicons dashicons-admin-site"></span>
					<?php esc_html_e( 'Universal Shortcode', 'ygb-avisos' ); ?>
				</h2>

				<p style="background: #f8f8f8; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
					<?php esc_html_e( 'The notice you configure here will appear automatically on all pages of your website. Leave it blank to disable.', 'ygb-avisos' ); ?>
				</p>

				<form method="post" action="options.php">
					<?php settings_fields( 'ygb_avisos_settings' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row" style="padding: 15px 0;">
								<label for="universal_id"><?php esc_html_e( 'Global notice ID:', 'ygb-avisos' ); ?></label>
							</th>
							<td style="padding: 15px 0;">
								<input type="number"
									   name="ygb_avisos_settings[universal_id]"
									   id="universal_id"
									   value="<?php echo esc_attr( $universal_id ); ?>"
									   class="regular-text"
									   placeholder="<?php esc_attr_e( 'Example: 2519', 'ygb-avisos' ); ?>"
									   style="padding: 8px; font-size: 16px;">
								<p class="description" style="margin-top: 8px;">
									<?php esc_html_e( 'Find the ID in "All Notices" → Shortcode column', 'ygb-avisos' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row" style="padding: 15px 0;"><?php esc_html_e( 'Current status:', 'ygb-avisos' ); ?></th>
							<td style="padding: 15px 0;">
								<?php if ( ! empty( $universal_id ) ) : ?>
									<?php
									$aviso = get_post( $universal_id );

									if ( $aviso && 'ygb_aviso' === $aviso->post_type && 'publish' === get_post_status( $aviso ) ) :
										?>
										<div style="background: #e8f5e9; padding: 15px; border-radius: 5px; border-left: 4px solid #4CAF50;">
											✅ <?php esc_html_e( 'Active:', 'ygb-avisos' ); ?>
											<strong><?php echo esc_html( $aviso->post_title ); ?></strong>
											(ID: <?php echo intval( $universal_id ); ?>)
										</div>
									<?php else : ?>
										<div style="background: #ffebee; padding: 15px; border-radius: 5px; border-left: 4px solid #f44336;">
											❌ <?php esc_html_e( 'The ID', 'ygb-avisos' ); ?> <?php echo intval( $universal_id ); ?> <?php esc_html_e( 'is not valid', 'ygb-avisos' ); ?>
										</div>
									<?php endif; ?>
								<?php else : ?>
									<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; border-left: 4px solid #9e9e9e;">
										⚪ <?php esc_html_e( 'No global notice configured', 'ygb-avisos' ); ?>
									</div>
								<?php endif; ?>
							</td>
						</tr>
					</table>

					<p style="margin-top: 20px;">
						<button type="submit" class="button button-primary" style="padding: 8px 20px; font-size: 16px;">
							<?php esc_html_e( 'Save Settings', 'ygb-avisos' ); ?>
						</button>
					</p>
				</form>
			</div>

			<div class="ygb-shortcodes-panel" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Available Shortcodes', 'ygb-avisos' ); ?></h2>

				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Description', 'ygb-avisos' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'ygb-avisos' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Specific Notice:', 'ygb-avisos' ); ?></strong></td>
							<td><code>[ygb-aviso id="ID"]</code></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Notice List:', 'ygb-avisos' ); ?></strong></td>
							<td><code>[ygb-avisos-lista cantidad="5"]</code></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Global Notice:', 'ygb-avisos' ); ?></strong></td>
							<td><code>[ygb-universal]</code></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Current Product:', 'ygb-avisos' ); ?></strong></td>
							<td><code>[ygb-producto-actual formato="..."]</code></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public function activate() {
		$this->init();
		flush_rewrite_rules();

		if ( ! get_option( 'ygb_avisos_settings' ) ) {
			add_option( 'ygb_avisos_settings', array(
				'universal_id' => '',
				'version'      => YGB_AVISOS_VERSION,
			) );
		}
	}

	public function deactivate() {
		flush_rewrite_rules();
	}
}

/**
 * Inicializa el plugin una vez que WordPress esté listo.
 */
function ygb_avisos_plugins_loaded() {
	YGB_Avisos::get_instance();
}
add_action( 'plugins_loaded', 'ygb_avisos_plugins_loaded' );