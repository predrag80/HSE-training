<?php
/**
 * Idempotent migration of professional Training records out of Courses.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Training;

use HSETraining\Headless\Course\CourseMeta;
use HSETraining\Headless\Course\CoursePostType;

defined( 'ABSPATH' ) || exit;

/** Moves only the allowlisted Training business records to the Training CPT. */
final class TrainingMigration {
	private const OPTION_NAME = 'hse_training_cpt_migration_version';
	private const VERSION     = 1;
	private const KEYS        = array( 'custom-training-design', 'banksman-slinger', 'train-the-trainer' );

	/** Register the migration after both post types exist. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'migrate' ), 30 );
	}

	/** Preserve post IDs and metadata while changing only the owning post type. */
	public static function migrate(): void {
		if ( self::VERSION <= (int) get_option( self::OPTION_NAME, 0 ) ) {
			return;
		}

		$post_ids = get_posts(
			array(
				'post_type'      => CoursePostType::POST_TYPE,
				'post_status'    => array_values( get_post_stati() ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => CourseMeta::COURSE_KEY,
						'value'   => self::KEYS,
						'compare' => 'IN',
					),
				),
				'no_found_rows'  => true,
			)
		);

		foreach ( $post_ids as $post_id ) {
			$result = wp_update_post(
				array(
					'ID'        => (int) $post_id,
					'post_type' => TrainingPostType::POST_TYPE,
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				return;
			}
		}

		update_option( self::OPTION_NAME, self::VERSION, false );
	}
}
