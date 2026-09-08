<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YGB_Avisos_CPT {

	public static function register() {
		$labels = array(
			'name'               => __( 'Notices', 'ygb-avisos' ),
			'singular_name'      => __( 'Notice', 'ygb-avisos' ),
			'menu_name'          => __( 'Notices', 'ygb-avisos' ),
			'add_new'            => __( 'Add New', 'ygb-avisos' ),
			'add_new_item'       => __( 'Add New Notice', 'ygb-avisos' ),
			'edit_item'          => __( 'Edit Notice', 'ygb-avisos' ),
			'new_item'           => __( 'New Notice', 'ygb-avisos' ),
			'view_item'          => __( 'View Notice', 'ygb-avisos' ),
			'search_items'       => __( 'Search Notices', 'ygb-avisos' ),
			'not_found'          => __( 'No notices found', 'ygb-avisos' ),
			'not_found_in_trash' => __( 'No notices found in trash', 'ygb-avisos' ),
			'all_items'          => __( 'All Notices', 'ygb-avisos' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-megaphone',
			'supports'            => array( 'title', 'editor' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		);

		register_post_type( 'ygb_aviso', $args );

		add_filter( 'manage_ygb_aviso_posts_columns', array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_ygb_aviso_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-ygb_aviso_sortable_columns', array( __CLASS__, 'add_sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_sort' ) );
	}

	public static function add_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			if ( 'title' === $key ) {
				$new_columns[ $key ] = $value;
				$new_columns['shortcode'] = __( 'Shortcode', 'ygb-avisos' );
				$new_columns['position']  = __( 'Position', 'ygb-avisos' );
				$new_columns['type']      = __( 'Type', 'ygb-avisos' );
			} elseif ( 'date' === $key ) {
				$new_columns['modified'] = __( 'Modified', 'ygb-avisos' );
				$new_columns[ $key ] = $value;
			} else {
				$new_columns[ $key ] = $value;
			}
		}

		return $new_columns;
	}

	public static function add_sortable_columns( $columns ) {
		$columns['modified'] = 'modified';
		return $columns;
	}

	public static function handle_sort( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'edit-ygb_aviso' !== $screen->id ) {
			return;
		}

		if ( 'modified' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'modified' );

			if ( ! $query->get( 'order' ) ) {
				$query->set( 'order', 'DESC' );
			}
		}
	}

	public static function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'shortcode':
				echo '<code style="font-size: 11px;">[ygb-aviso id="' . intval( $post_id ) . '"]</code>';
				echo '<br>';
				echo '<span style="font-size: 10px; color: #666;">' . esc_html__( 'Copy to use', 'ygb-avisos' ) . '</span>';
				break;

			case 'position':
				$position = get_post_meta( $post_id, '_ygb_anclaje', true );

				$positions = array(
					'arriba' => '🔝 ' . __( 'Top', 'ygb-avisos' ),
					'abajo'  => '⬇️ ' . __( 'Bottom', 'ygb-avisos' ),
					'ambos'  => '↕️ ' . __( 'Both', 'ygb-avisos' ),
				);

				echo isset( $positions[ $position ] ) ? esc_html( $positions[ $position ] ) : '—';
				break;

			case 'type':
				$tipo = get_post_meta( $post_id, '_ygb_tipo_contenido', true );

				$tipos = array(
					'texto'      => '📝 ' . __( 'Text', 'ygb-avisos' ),
					'productos'  => '🔢 ' . __( 'Products', 'ygb-avisos' ),
					'descuentos' => '🏷️ ' . __( 'On Sale', 'ygb-avisos' ),
				);

				echo isset( $tipos[ $tipo ] ) ? esc_html( $tipos[ $tipo ] ) : '📝 ' . __( 'Text', 'ygb-avisos' );
				break;

			case 'modified':
				$modified      = (int) get_post_modified_time( 'U', false, $post_id );
				$modified_time = $modified ? human_time_diff( $modified, current_time( 'timestamp' ) ) : '';
				$modified_date = (string) get_post_modified_time( get_option( 'date_format' ), false, $post_id );

				echo '<abbr title="' . esc_attr( $modified_date ) . '">';
				echo esc_html( sprintf( __( '%s ago', 'ygb-avisos' ), $modified_time ) );
				echo '</abbr>';
				break;
		}
	}

	public static function get_notice_status( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || 'ygb_aviso' !== $post->post_type ) {
			return 'invalid';
		}

		return $post->post_status;
	}

	public static function is_notice_published( $post_id ) {
		return 'publish' === self::get_notice_status( $post_id );
	}
}