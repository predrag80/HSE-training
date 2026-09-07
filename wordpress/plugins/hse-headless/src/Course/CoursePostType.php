<?php
/**
 * Course custom post type.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Course;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the editorial Course model.
 */
final class CoursePostType {
	public const POST_TYPE = 'course';
	public const REST_BASE = 'courses';

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
	}

	/**
	 * Register the Course post type.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_post_type/
	 */
	public static function register(): void {
		$labels = array(
			'name'                  => _x( 'Courses', 'Post type general name', 'hse-headless' ),
			'singular_name'         => _x( 'Course', 'Post type singular name', 'hse-headless' ),
			'menu_name'             => _x( 'Courses', 'Admin menu text', 'hse-headless' ),
			'name_admin_bar'        => _x( 'Course', 'Add New in toolbar', 'hse-headless' ),
			'add_new'               => __( 'Add New', 'hse-headless' ),
			'add_new_item'          => __( 'Add New Course', 'hse-headless' ),
			'new_item'              => __( 'New Course', 'hse-headless' ),
			'edit_item'             => __( 'Edit Course', 'hse-headless' ),
			'view_item'             => __( 'View Course API record', 'hse-headless' ),
			'all_items'             => __( 'All Courses', 'hse-headless' ),
			'search_items'          => __( 'Search Courses', 'hse-headless' ),
			'not_found'             => __( 'No Courses found.', 'hse-headless' ),
			'not_found_in_trash'    => __( 'No Courses found in Trash.', 'hse-headless' ),
			'featured_image'        => __( 'Course featured image', 'hse-headless' ),
			'set_featured_image'    => __( 'Set course featured image', 'hse-headless' ),
			'remove_featured_image' => __( 'Remove course featured image', 'hse-headless' ),
			'use_featured_image'    => __( 'Use as course featured image', 'hse-headless' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => __( 'Course marketing content consumed by the HSE Training public application.', 'hse-headless' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => true,
				'rest_base'           => self::REST_BASE,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_icon'           => 'dashicons-welcome-learn-more',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);
	}
}
