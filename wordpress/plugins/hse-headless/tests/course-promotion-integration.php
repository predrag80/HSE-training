<?php
/** Homepage Course promotion checks for execution with `wp eval-file`. */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Course\CourseMeta;
use HSETraining\Headless\Course\CoursePostType;
use HSETraining\Headless\Course\CoursePromotionMeta;

if ( ! class_exists( CoursePromotionMeta::class ) ) {
	WP_CLI::error( 'Course promotion implementation is not loaded.' );
}

$failures         = array();
$created_ids      = array();
$attachment_id    = 0;
$featured_before  = get_posts(
	array(
		'post_type'      => CoursePostType::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => CoursePromotionMeta::FEATURED_ON_HOMEPAGE,
		'meta_value'     => '1',
	)
);

/** Record a failed assertion without stopping cleanup. */
function hse_course_promotion_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

/** Create a complete temporary promoted Course. */
function hse_course_promotion_test_create( $key, $order, $attachment_id ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => CoursePostType::POST_TYPE,
			'post_status'  => 'draft',
			'post_title'   => 'Promoted Course ' . $order,
			'post_content' => '<p>Course content.</p>',
			'menu_order'   => $order,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Could not create a temporary promoted Course.' );
	}
	update_post_meta( $post_id, CourseMeta::COURSE_KEY, $key );
	update_post_meta( $post_id, CourseMeta::SHORT_DESCRIPTION, 'Course promotion summary.' );
	update_post_meta( $post_id, CourseMeta::VISIBLE_PRICE, 'Price on request' );
	update_post_meta( $post_id, CoursePromotionMeta::HOMEPAGE_LABEL, 'Professional course' );
	update_post_meta( $post_id, CoursePromotionMeta::HOMEPAGE_CTA_LABEL, 'View course' );
	update_post_meta( $post_id, CoursePromotionMeta::FEATURED_ON_HOMEPAGE, true );
	set_post_thumbnail( $post_id, $attachment_id );

	return $post_id;
}

try {
	hse_course_promotion_test_assert( post_type_supports( CoursePostType::POST_TYPE, 'page-attributes' ), 'Courses support explicit Homepage order.' );
	$registered = get_registered_meta_keys( 'post', CoursePostType::POST_TYPE );
	foreach ( array( CoursePromotionMeta::HOMEPAGE_LABEL, CoursePromotionMeta::HOMEPAGE_CTA_LABEL, CoursePromotionMeta::FEATURED_ON_HOMEPAGE ) as $meta_key ) {
		hse_course_promotion_test_assert( isset( $registered[ $meta_key ] ), sprintf( '%s is registered.', $meta_key ) );
		hse_course_promotion_test_assert( false !== ( $registered[ $meta_key ]['show_in_rest'] ?? false ), sprintf( '%s is exposed through core REST.', $meta_key ) );
	}

	foreach ( $featured_before as $featured_id ) {
		wp_update_post( array( 'ID' => $featured_id, 'post_status' => 'draft' ) );
	}

	$temp_file = wp_tempnam( 'hse-course-promotion-test.png' );
	$png       = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true );
	if ( ! $temp_file || false === $png || false === file_put_contents( $temp_file, $png ) ) {
		throw new RuntimeException( 'Could not create the temporary Course image.' );
	}
	$attachment_id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_status' => 'inherit', 'post_title' => 'Course promotion test image' ), $temp_file, 0, true );
	if ( is_wp_error( $attachment_id ) ) {
		throw new RuntimeException( 'Could not register the temporary Course image.' );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $temp_file ) );

	$key_prefix = 'hse-promotion-test-' . strtolower( wp_generate_password( 8, false, false ) );
	$orders     = array( 20, 10, 30 );
	foreach ( $orders as $index => $order ) {
		$post_id       = hse_course_promotion_test_create( $key_prefix . '-' . ( $index + 1 ), $order, $attachment_id );
		$created_ids[] = $post_id;
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		hse_course_promotion_test_assert( 'publish' === get_post_status( $post_id ), 'A complete promoted Course can be published.' );
	}

	$overflow_id   = hse_course_promotion_test_create( $key_prefix . '-overflow', 40, $attachment_id );
	$created_ids[] = $overflow_id;
	wp_update_post( array( 'ID' => $overflow_id, 'post_status' => 'publish' ) );
	hse_course_promotion_test_assert( 'draft' === get_post_status( $overflow_id ), 'A fourth promoted Course cannot be published.' );

	$request = new WP_REST_Request( 'GET', '/wp/v2/' . CoursePostType::REST_BASE );
	$request->set_param( CoursePromotionMeta::FEATURED_ON_HOMEPAGE, true );
	$request->set_param( 'orderby', 'menu_order' );
	$request->set_param( 'order', 'asc' );
	$response = rest_do_request( $request );
	$data     = $response->get_data();
	hse_course_promotion_test_assert( 200 === $response->get_status(), 'The promoted Course collection responds with HTTP 200.' );
	hse_course_promotion_test_assert( 3 === count( $data ), 'The promoted Course filter returns exactly the selected Courses.' );
	hse_course_promotion_test_assert( $key_prefix . '-2' === ( $data[0]['meta'][ CourseMeta::COURSE_KEY ] ?? null ), 'Promoted Courses use WordPress Order.' );
	hse_course_promotion_test_assert( true === ( $data[0]['meta'][ CoursePromotionMeta::FEATURED_ON_HOMEPAGE ] ?? null ), 'The public response exposes the Homepage selection.' );
} finally {
	foreach ( $created_ids as $created_id ) {
		wp_delete_post( $created_id, true );
	}
	if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
		wp_delete_attachment( $attachment_id, true );
	}
	remove_filter( 'wp_insert_post_data', array( CoursePromotionMeta::class, 'validate_publish' ), 20 );
	foreach ( $featured_before as $featured_id ) {
		wp_update_post( array( 'ID' => $featured_id, 'post_status' => 'publish' ) );
	}
	add_filter( 'wp_insert_post_data', array( CoursePromotionMeta::class, 'validate_publish' ), 20, 4 );
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Course promotion integration check(s) failed.', count( $failures ) ) );
}
WP_CLI::success( 'All Course promotion integration checks passed.' );
