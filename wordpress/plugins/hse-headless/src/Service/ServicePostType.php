<?php
/**
 * HSE Service custom post type.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Service;

defined( 'ABSPATH' ) || exit;

/** Registers the ordered editorial Service collection. */
final class ServicePostType {
	public const POST_TYPE = 'hse_service';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_filter( 'manage_hse_service_posts_columns', array( self::class, 'add_admin_columns' ) );
		add_action( 'manage_hse_service_posts_custom_column', array( self::class, 'render_admin_column' ), 10, 2 );
	}

	/** Register the private, admin-managed Service model. */
	public static function register(): void {
		$labels = array(
			'name'                  => _x( 'Services', 'Post type general name', 'hse-headless' ),
			'singular_name'         => _x( 'Service', 'Post type singular name', 'hse-headless' ),
			'menu_name'             => _x( 'Services', 'Admin menu text', 'hse-headless' ),
			'add_new'               => __( 'Add New', 'hse-headless' ),
			'add_new_item'          => __( 'Add New Service', 'hse-headless' ),
			'new_item'              => __( 'New Service', 'hse-headless' ),
			'edit_item'             => __( 'Edit Service', 'hse-headless' ),
			'all_items'             => __( 'All Services', 'hse-headless' ),
			'search_items'          => __( 'Search Services', 'hse-headless' ),
			'not_found'             => __( 'No Services found.', 'hse-headless' ),
			'not_found_in_trash'    => __( 'No Services found in Trash.', 'hse-headless' ),
			'featured_image'        => __( 'Service image', 'hse-headless' ),
			'set_featured_image'    => __( 'Set service image', 'hse-headless' ),
			'remove_featured_image' => __( 'Remove service image', 'hse-headless' ),
			'use_featured_image'    => __( 'Use as service image', 'hse-headless' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => __( 'HSE consulting services shared by the Homepage and Consulting Page.', 'hse-headless' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_icon'           => 'dashicons-clipboard',
				'supports'            => array( 'title', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);
	}

	/** Add stable key, homepage state, and order columns. */
	public static function add_admin_columns( $columns ): array {
		$columns['hse_service_key']      = __( 'Service key', 'hse-headless' );
		$columns['hse_service_homepage'] = __( 'Homepage', 'hse-headless' );
		$columns['hse_service_order']    = __( 'Order', 'hse-headless' );

		return $columns;
	}

	/** Render custom collection values. */
	public static function render_admin_column( $column, $post_id ): void {
		if ( 'hse_service_key' === $column ) {
			echo esc_html( get_post_meta( $post_id, ServiceMeta::SERVICE_KEY, true ) );
		}
		if ( 'hse_service_homepage' === $column ) {
			echo get_post_meta( $post_id, ServiceMeta::FEATURED_ON_HOMEPAGE, true ) ? esc_html__( 'Yes', 'hse-headless' ) : esc_html__( 'No', 'hse-headless' );
		}
		if ( 'hse_service_order' === $column ) {
			echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
		}
	}
}
