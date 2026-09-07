<?php
/**
 * HSE client Reference custom post type.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Reference;

defined( 'ABSPATH' ) || exit;

/** Registers the ordered editorial Reference collection. */
final class ReferencePostType {
	public const POST_TYPE = 'hse_reference';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_filter( 'manage_hse_reference_posts_columns', array( self::class, 'add_admin_columns' ) );
		add_action( 'manage_hse_reference_posts_custom_column', array( self::class, 'render_admin_column' ), 10, 2 );
	}

	/** Register the private, admin-managed Reference model. */
	public static function register(): void {
		$labels = array(
			'name'               => _x( 'References', 'Post type general name', 'hse-headless' ),
			'singular_name'      => _x( 'Reference', 'Post type singular name', 'hse-headless' ),
			'menu_name'          => _x( 'References', 'Admin menu text', 'hse-headless' ),
			'add_new'            => __( 'Add New', 'hse-headless' ),
			'add_new_item'       => __( 'Add New Reference', 'hse-headless' ),
			'new_item'           => __( 'New Reference', 'hse-headless' ),
			'edit_item'          => __( 'Edit Reference', 'hse-headless' ),
			'all_items'          => __( 'All References', 'hse-headless' ),
			'search_items'       => __( 'Search References', 'hse-headless' ),
			'not_found'          => __( 'No References found.', 'hse-headless' ),
			'not_found_in_trash' => __( 'No References found in Trash.', 'hse-headless' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => __( 'Client and participant feedback shown by the public application.', 'hse-headless' ),
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
				'menu_icon'           => 'dashicons-format-quote',
				'supports'            => array( 'title', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);
	}

	/** Add stable key, Homepage state, and order columns. */
	public static function add_admin_columns( $columns ): array {
		$columns['hse_reference_key']      = __( 'Reference key', 'hse-headless' );
		$columns['hse_reference_homepage'] = __( 'Homepage', 'hse-headless' );
		$columns['hse_reference_order']    = __( 'Order', 'hse-headless' );

		return $columns;
	}

	/** Render custom collection values. */
	public static function render_admin_column( $column, $post_id ): void {
		if ( 'hse_reference_key' === $column ) {
			echo esc_html( get_post_meta( $post_id, ReferenceMeta::REFERENCE_KEY, true ) );
		}
		if ( 'hse_reference_homepage' === $column ) {
			echo get_post_meta( $post_id, ReferenceMeta::FEATURED_ON_HOMEPAGE, true ) ? esc_html__( 'Yes', 'hse-headless' ) : esc_html__( 'No', 'hse-headless' );
		}
		if ( 'hse_reference_order' === $column ) {
			echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
		}
	}
}
