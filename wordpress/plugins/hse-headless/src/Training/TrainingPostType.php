<?php
/**
 * Training custom post type.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Training;

defined( 'ABSPATH' ) || exit;

/** Registers professional Training programmes separately from NEBOSH Courses. */
final class TrainingPostType {
	public const POST_TYPE = 'training';
	public const REST_BASE = 'trainings';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
	}

	/** Register the headless Training post type. */
	public static function register(): void {
		$labels = array(
			'name'                  => _x( 'Trainings', 'Post type general name', 'hse-headless' ),
			'singular_name'         => _x( 'Training', 'Post type singular name', 'hse-headless' ),
			'menu_name'             => _x( 'Trainings', 'Admin menu text', 'hse-headless' ),
			'name_admin_bar'        => _x( 'Training', 'Add New in toolbar', 'hse-headless' ),
			'add_new'               => __( 'Add New', 'hse-headless' ),
			'add_new_item'          => __( 'Add New Training', 'hse-headless' ),
			'new_item'              => __( 'New Training', 'hse-headless' ),
			'edit_item'             => __( 'Edit Training', 'hse-headless' ),
			'view_item'             => __( 'View Training API record', 'hse-headless' ),
			'all_items'             => __( 'All Trainings', 'hse-headless' ),
			'search_items'          => __( 'Search Trainings', 'hse-headless' ),
			'not_found'             => __( 'No Trainings found.', 'hse-headless' ),
			'not_found_in_trash'    => __( 'No Trainings found in Trash.', 'hse-headless' ),
			'featured_image'        => __( 'Training featured image', 'hse-headless' ),
			'set_featured_image'    => __( 'Set training featured image', 'hse-headless' ),
			'remove_featured_image' => __( 'Remove training featured image', 'hse-headless' ),
			'use_featured_image'    => __( 'Use as training featured image', 'hse-headless' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => __( 'Professional training marketing content consumed by the public application.', 'hse-headless' ),
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
