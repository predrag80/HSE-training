<?php
/** Course page settings and REST checks for execution with `wp eval-file`. */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Course\CoursePageRestController;
use HSETraining\Headless\Course\CoursePageSettings;

$failures = array();
$original = array();

function hse_course_pages_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

foreach ( CoursePageSettings::PAGE_KEYS as $page_key ) {
	foreach ( array( 'en', 'sr' ) as $locale ) {
		$option_name              = CoursePageSettings::option_name( $page_key, $locale );
		$original[ $option_name ] = array( false !== get_option( $option_name, false ), get_option( $option_name, array() ) );
	}
}

try {
	$sanitized = CoursePageSettings::sanitize_settings( 'courses', array( 'hero_title' => '<b>Courses</b>', 'unknown' => 'discard' ) );
	hse_course_pages_test_assert( 'Courses' === $sanitized['hero_title'], 'Course page text is sanitized.' );
	hse_course_pages_test_assert( ! isset( $sanitized['unknown'] ), 'Unknown Course page fields are discarded.' );

	update_option( CoursePageSettings::option_name( 'courses', 'en' ), array() );
	$unconfigured = CoursePageSettings::get_public_document( 'courses', 'en' );
	hse_course_pages_test_assert( is_wp_error( $unconfigured ), 'An incomplete Course page is not published.' );
	hse_course_pages_test_assert( 503 === ( $unconfigured->get_error_data()['status'] ?? 0 ), 'An incomplete Course page returns HTTP 503.' );

	foreach ( array( 'en' => 'Courses', 'sr' => 'Kursevi' ) as $locale => $title ) {
		$settings = CoursePageSettings::sanitize_settings( 'courses', array(
			'meta_title' => $title, 'meta_description' => 'Description', 'hero_eyebrow' => 'Training',
			'hero_title' => $title, 'hero_intro' => 'Introduction', 'empty_title' => 'Empty', 'empty_text' => 'No courses',
		) );
		update_option( CoursePageSettings::option_name( 'courses', $locale ), $settings );
	}

	$request = new WP_REST_Request( 'GET', '/hse/v1/course-pages/courses' );
	$request->set_param( 'page_key', 'courses' );
	$request->set_param( 'lang', 'sr' );
	$response = CoursePageRestController::get_course_page( $request );
	$data     = is_wp_error( $response ) ? array() : $response->get_data();
	hse_course_pages_test_assert( 'sr' === ( $data['locale'] ?? null ), 'Course page REST response preserves locale.' );
	hse_course_pages_test_assert( 'Kursevi' === ( $data['content']['hero']['title'] ?? null ), 'Serbian Course page content is isolated.' );
	hse_course_pages_test_assert( ! isset( $data['content']['id'] ), 'WordPress IDs do not cross the Course page API boundary.' );
} finally {
	foreach ( $original as $option_name => $state ) {
		if ( $state[0] ) {
			update_option( $option_name, $state[1] );
		} else {
			delete_option( $option_name );
		}
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Course page integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Course page integration checks passed.' );
