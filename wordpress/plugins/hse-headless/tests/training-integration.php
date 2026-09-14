<?php
/** Training CPT and REST checks for execution with `wp eval-file`. */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Content\ContentLocale;
use HSETraining\Headless\Course\CourseMeta;
use HSETraining\Headless\Course\CoursePostType;
use HSETraining\Headless\Training\TrainingPostType;

$failures    = array();
$created_ids = array();

function hse_training_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

try {
	hse_training_test_assert( post_type_exists( TrainingPostType::POST_TYPE ), 'Training CPT is registered.' );
	hse_training_test_assert( post_type_supports( TrainingPostType::POST_TYPE, 'editor' ), 'Training supports rich content.' );
	hse_training_test_assert( post_type_supports( TrainingPostType::POST_TYPE, 'thumbnail' ), 'Training supports featured images.' );
	hse_training_test_assert( 1 === (int) get_option( 'hse_training_cpt_migration_version', 0 ), 'Training CPT migration completed.' );

	$legacy_training_ids = get_posts(
		array(
			'post_type'      => CoursePostType::POST_TYPE,
			'post_status'    => array_values( get_post_stati() ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => CourseMeta::COURSE_KEY,
					'value'   => array( 'custom-training-design', 'banksman-slinger', 'train-the-trainer' ),
					'compare' => 'IN',
				),
			),
			'no_found_rows'  => true,
		)
	);
	hse_training_test_assert( array() === $legacy_training_ids, 'No professional Training records remain in the Course CPT.' );

	$registered_meta = get_registered_meta_keys( 'post', TrainingPostType::POST_TYPE );
	foreach ( array( ContentLocale::META_KEY, CourseMeta::COURSE_KEY, CourseMeta::SHORT_DESCRIPTION, CourseMeta::VISIBLE_PRICE, CourseMeta::PAGE_EYEBROW ) as $meta_key ) {
		hse_training_test_assert( isset( $registered_meta[ $meta_key ] ), sprintf( '%s is registered for Training.', $meta_key ) );
	}

	$key         = 'hse-training-test-' . strtolower( wp_generate_password( 8, false, false ) );
	$training_id = wp_insert_post(
		array(
			'post_type'    => TrainingPostType::POST_TYPE,
			'post_status'  => 'draft',
			'post_title'   => 'Training integration test',
			'post_content' => '<p>Training description.</p>',
		),
		true
	);
	if ( is_wp_error( $training_id ) ) {
		WP_CLI::error( 'Could not create the temporary Training record.' );
	}
	$created_ids[] = $training_id;
	update_post_meta( $training_id, ContentLocale::META_KEY, 'en' );
	hse_training_test_assert( false !== update_post_meta( $training_id, CourseMeta::COURSE_KEY, $key ), 'Training accepts a stable course key.' );
	wp_update_post( array( 'ID' => $training_id, 'post_status' => 'publish' ) );
	hse_training_test_assert( 'publish' === get_post_status( $training_id ), 'A keyed Training record can publish.' );

	$course_id = wp_insert_post(
		array(
			'post_type'   => CoursePostType::POST_TYPE,
			'post_status' => 'draft',
			'post_title'  => 'Cross-CPT key test',
		),
		true
	);
	if ( is_wp_error( $course_id ) ) {
		WP_CLI::error( 'Could not create the temporary Course record.' );
	}
	$created_ids[] = $course_id;
	update_post_meta( $course_id, ContentLocale::META_KEY, 'en' );
	hse_training_test_assert( false === update_post_meta( $course_id, CourseMeta::COURSE_KEY, $key ), 'Course keys remain unique across Course and Training CPTs.' );

	$request = new WP_REST_Request( 'GET', '/wp/v2/trainings' );
	$request->set_param( 'course_key', $key );
	$request->set_param( 'lang', 'en' );
	$response = rest_do_request( $request );
	$data     = $response->get_data();
	hse_training_test_assert( 200 === $response->get_status(), 'Training REST collection is public.' );
	hse_training_test_assert( 1 === count( $data ), 'Training REST course_key lookup is unique.' );
	hse_training_test_assert( $key === ( $data[0]['meta'][ CourseMeta::COURSE_KEY ] ?? null ), 'Training REST response exposes the stable key.' );
} finally {
	foreach ( array_reverse( $created_ids ) as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Training integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Training integration checks passed.' );
