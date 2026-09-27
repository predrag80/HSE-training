<?php
/**
 * Free Resource custom post type.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Resource;

defined( 'ABSPATH' ) || exit;

/** Registers the ordered Free Resources collection. */
final class ResourcePostType {
	public const POST_TYPE = 'hse_resource';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_filter( 'manage_hse_resource_posts_columns', array( self::class, 'add_admin_columns' ) );
		add_action( 'manage_hse_resource_posts_custom_column', array( self::class, 'render_admin_column' ), 10, 2 );
	}

	/** Register the private, admin-managed Resource model. */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => _x( 'Free Resources', 'Post type general name', 'hse-headless' ),
					'singular_name'      => _x( 'Free Resource', 'Post type singular name', 'hse-headless' ),
					'menu_name'          => _x( 'Free Resources', 'Admin menu text', 'hse-headless' ),
					'add_new_item'       => __( 'Add New Free Resource', 'hse-headless' ),
					'edit_item'          => __( 'Edit Free Resource', 'hse-headless' ),
					'all_items'          => __( 'All Free Resources', 'hse-headless' ),
					'search_items'       => __( 'Search Free Resources', 'hse-headless' ),
					'not_found'          => __( 'No Free Resources found.', 'hse-headless' ),
					'not_found_in_trash' => __( 'No Free Resources found in Trash.', 'hse-headless' ),
				),
				'description'         => __( 'Free links, videos and downloadable HSE materials.', 'hse-headless' ),
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
				'menu_icon'           => 'dashicons-download',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);
	}

	/** Add useful overview columns. */
	public static function add_admin_columns( $columns ): array {
		$columns['hse_resource_type']  = __( 'Type', 'hse-headless' );
		$columns['hse_resource_topic'] = __( 'Topic', 'hse-headless' );
		$columns['hse_resource_order'] = __( 'Order', 'hse-headless' );

		return $columns;
	}

	/** Render collection values. */
	public static function render_admin_column( $column, $post_id ): void {
		if ( 'hse_resource_type' === $column ) {
			echo esc_html( ResourceMeta::type_label( get_post_meta( $post_id, ResourceMeta::TYPE, true ) ) );
		}
		if ( 'hse_resource_topic' === $column ) {
			echo esc_html( get_post_meta( $post_id, ResourceMeta::TOPIC, true ) );
		}
		if ( 'hse_resource_order' === $column ) {
			echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
		}
	}
}
