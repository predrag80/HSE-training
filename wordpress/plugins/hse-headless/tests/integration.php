<?php
/**
 * Minimal Course integration checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Course\CourseMeta;
use HSETraining\Headless\Course\CoursePostType;

if ( ! class_exists( CoursePostType::class ) || ! class_exists( CourseMeta::class ) ) {
	WP_CLI::error( 'Course implementation is not loaded.' );
}

$failures    = array();
$created_ids = array();

/**
 * Record a failed assertion without stopping cleanup.
 *
 * @param bool   $condition Whether the assertion passed.
 * @param string $message   Failure message.
 */
function hse_course_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

try {
	hse_course_test_assert( post_type_exists( CoursePostType::POST_TYPE ), 'Course CPT is registered.' );
	hse_course_test_assert( post_type_supports( CoursePostType::POST_TYPE, 'title' ), 'Course supports titles.' );
	hse_course_test_assert( post_type_supports( CoursePostType::POST_TYPE, 'editor' ), 'Course supports content.' );
	hse_course_test_assert( post_type_supports( CoursePostType::POST_TYPE, 'thumbnail' ), 'Course supports featured images.' );
	hse_course_test_assert( post_type_supports( CoursePostType::POST_TYPE, 'revisions' ), 'Course supports revisions.' );

	$normalization_cases = array(
		'NEBOSH IGC'  => 'nebosh-igc',
		' Nebosh IGC ' => 'nebosh-igc',
		'nebosh/igc'  => 'nebosh-igc',
		'NEBOSH@IGC'  => 'nebosh-igc',
	);

	foreach ( $normalization_cases as $input => $expected ) {
		hse_course_test_assert(
			$expected === CourseMeta::sanitize_course_key( $input ),
			sprintf( 'Course key "%s" normalizes to "%s".', $input, $expected )
		);
	}

	$registered_meta = get_registered_meta_keys( 'post', CoursePostType::POST_TYPE );
	foreach ( array( CourseMeta::COURSE_KEY, CourseMeta::SHORT_DESCRIPTION, CourseMeta::VISIBLE_PRICE ) as $meta_key ) {
		$meta_args = isset( $registered_meta[ $meta_key ] ) ? $registered_meta[ $meta_key ] : array();
		hse_course_test_assert( 'string' === ( $meta_args['type'] ?? null ), sprintf( '%s is registered as a string.', $meta_key ) );
		hse_course_test_assert( true === ( $meta_args['single'] ?? null ), sprintf( '%s is registered as a scalar value.', $meta_key ) );
		hse_course_test_assert( false !== ( $meta_args['show_in_rest'] ?? false ), sprintf( '%s is exposed through core REST meta.', $meta_key ) );
	}
	hse_course_test_assert( false === CourseMeta::can_edit_meta( false, CourseMeta::COURSE_KEY, 0 ), 'Anonymous users cannot edit Course metadata.' );

	$keyless_id = wp_insert_post(
		array(
			'post_type'   => CoursePostType::POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => 'Keyless Course integration test',
		),
		true
	);
	if ( is_wp_error( $keyless_id ) ) {
		WP_CLI::error( 'Could not create the temporary keyless Course.' );
	}
	$created_ids[] = $keyless_id;
	hse_course_test_assert( 'draft' === get_post_status( $keyless_id ), 'A keyless Course cannot be published.' );

	$test_key = 'hse-course-test-' . strtolower( wp_generate_password( 8, false, false ) );
	$first_id = wp_insert_post(
		array(
			'post_type'   => CoursePostType::POST_TYPE,
			'post_status' => 'draft',
			'post_title'  => 'Course integration test A',
		),
		true
	);
	$second_id = wp_insert_post(
		array(
			'post_type'   => CoursePostType::POST_TYPE,
			'post_status' => 'draft',
			'post_title'  => 'Course integration test B',
		),
		true
	);

	if ( is_wp_error( $first_id ) || is_wp_error( $second_id ) ) {
		if ( ! is_wp_error( $first_id ) ) {
			wp_delete_post( $first_id, true );
		}
		if ( ! is_wp_error( $second_id ) ) {
			wp_delete_post( $second_id, true );
		}
		WP_CLI::error( 'Could not create temporary Course records.' );
	}

	$created_ids[] = $first_id;
	$created_ids[] = $second_id;

	hse_course_test_assert( false !== update_post_meta( $first_id, CourseMeta::COURSE_KEY, strtoupper( str_replace( '-', ' ', $test_key ) ) ), 'A unique course key can be stored.' );
	hse_course_test_assert( $test_key === get_post_meta( $first_id, CourseMeta::COURSE_KEY, true ), 'The stored course key is canonical.' );

	$duplicate_result = update_post_meta( $second_id, CourseMeta::COURSE_KEY, $test_key );
	hse_course_test_assert( false === $duplicate_result, 'A duplicate course key is rejected.' );
	hse_course_test_assert( '' === get_post_meta( $second_id, CourseMeta::COURSE_KEY, true ), 'A rejected duplicate is not stored.' );

	update_post_meta( $first_id, CourseMeta::COURSE_KEY, $test_key );
	hse_course_test_assert( $test_key === get_post_meta( $first_id, CourseMeta::COURSE_KEY, true ), 'A Course can retain its own key.' );

	wp_update_post(
		array(
			'ID'          => $first_id,
			'post_status' => 'publish',
		)
	);
	$immutable_result = update_post_meta( $first_id, CourseMeta::COURSE_KEY, $test_key . '-changed' );
	hse_course_test_assert( false === $immutable_result, 'A published Course key cannot be changed.' );
	hse_course_test_assert( $test_key === get_post_meta( $first_id, CourseMeta::COURSE_KEY, true ), 'The published Course retains its locked key.' );
	wp_update_post(
		array(
			'ID'          => $first_id,
			'post_status' => 'draft',
		)
	);
	hse_course_test_assert( false === update_post_meta( $first_id, CourseMeta::COURSE_KEY, $test_key . '-draft-change' ), 'The key stays locked after a published Course returns to draft.' );
	wp_update_post(
		array(
			'ID'          => $first_id,
			'post_status' => 'publish',
		)
	);

	$rest_request = new WP_REST_Request( 'GET', '/wp/v2/' . CoursePostType::REST_BASE );
	$rest_request->set_param( 'course_key', $test_key );
	$rest_response = rest_do_request( $rest_request );
	$rest_data     = $rest_response->get_data();
	hse_course_test_assert( 200 === $rest_response->get_status(), 'The public Course collection endpoint responds with HTTP 200.' );
	hse_course_test_assert( 1 === count( $rest_data ), 'The Course collection supports exact course_key lookup.' );
	hse_course_test_assert( $test_key === ( $rest_data[0]['meta'][ CourseMeta::COURSE_KEY ] ?? null ), 'Published REST data includes scalar course_key metadata.' );

	$draft_request  = new WP_REST_Request( 'GET', '/wp/v2/' . CoursePostType::REST_BASE . '/' . $second_id );
	$draft_response = rest_do_request( $draft_request );
	hse_course_test_assert( in_array( $draft_response->get_status(), array( 401, 403 ), true ), 'Unauthenticated REST requests cannot read draft Courses.' );
} finally {
	foreach ( $created_ids as $created_id ) {
		wp_delete_post( $created_id, true );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Course integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Course integration checks passed.' );
