<?php
/**
 * Homepage Hero Slide custom post type.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Homepage;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ordered editorial Hero Slide collection.
 */
final class HeroSlidePostType {
	public const POST_TYPE = 'hero_slide';

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_filter( 'manage_hero_slide_posts_columns', array( self::class, 'add_admin_columns' ) );
		add_action( 'manage_hero_slide_posts_custom_column', array( self::class, 'render_admin_column' ), 10, 2 );
	}

	/**
	 * Register the private, admin-managed post type.
	 */
	public static function register(): void {
		$labels = array(
			'name'               => _x( 'Hero Slides', 'Post type general name', 'hse-headless' ),
			'singular_name'      => _x( 'Hero Slide', 'Post type singular name', 'hse-headless' ),
			'menu_name'          => _x( 'Hero Slides', 'Admin menu text', 'hse-headless' ),
			'add_new'            => __( 'Add New', 'hse-headless' ),
			'add_new_item'       => __( 'Add New Hero Slide', 'hse-headless' ),
			'new_item'           => __( 'New Hero Slide', 'hse-headless' ),
			'edit_item'          => __( 'Edit Hero Slide', 'hse-headless' ),
			'all_items'          => __( 'Hero Slides', 'hse-headless' ),
			'search_items'       => __( 'Search Hero Slides', 'hse-headless' ),
			'not_found'          => __( 'No Hero Slides found.', 'hse-headless' ),
			'not_found_in_trash' => __( 'No Hero Slides found in Trash.', 'hse-headless' ),
			'featured_image'     => __( 'Hero image', 'hse-headless' ),
			'set_featured_image' => __( 'Set hero image', 'hse-headless' ),
			'remove_featured_image' => __( 'Remove hero image', 'hse-headless' ),
			'use_featured_image' => __( 'Use as hero image', 'hse-headless' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => __( 'Ordered Homepage hero slides consumed by the HSE Training public application.', 'hse-headless' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => HomepageSettings::PAGE_SLUG,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);
	}

	/**
	 * Add stable key and order to the collection overview.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function add_admin_columns( $columns ): array {
		$columns['hse_slide_key']   = __( 'Slide key', 'hse-headless' );
		$columns['hse_slide_order'] = __( 'Order', 'hse-headless' );

		return $columns;
	}

	/**
	 * Render custom collection values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Hero Slide post ID.
	 */
	public static function render_admin_column( $column, $post_id ): void {
		if ( 'hse_slide_key' === $column ) {
			echo esc_html( get_post_meta( $post_id, HeroSlideMeta::SLIDE_KEY, true ) );
		}

		if ( 'hse_slide_order' === $column ) {
			echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
		}
	}
}
