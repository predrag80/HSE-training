<?php
/**
 * Service model and REST checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Service\ServiceMeta;
use HSETraining\Headless\Service\ServicePostType;
use HSETraining\Headless\Service\ServiceRestController;

if ( ! class_exists( ServicePostType::class ) || ! class_exists( ServiceMeta::class ) || ! class_exists( ServiceRestController::class ) ) {
	WP_CLI::error( 'Service implementation is not loaded.' );
}

$failures         = array();
$created_ids      = array();
$attachment_id    = 0;
$original_user    = get_current_user_id();
$published_before = get_posts(
	array(
		'post_type'      => ServicePostType::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

/** Record a failed assertion without stopping cleanup. */
function hse_service_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

/** Create a complete temporary Service. */
function hse_service_test_create( $key, $order, $attachment_id, $featured = true ) {
	$post_id = wp_insert_post(
		array(
			'post_type'   => ServicePostType::POST_TYPE,
			'post_status' => 'draft',
			'post_title'  => 'Service ' . $order,
			'menu_order'  => $order,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Could not create a temporary Service.' );
	}

	$fields = array(
		ServiceMeta::SERVICE_KEY          => $key,
		ServiceMeta::CARD_LABEL           => 'Capability',
		ServiceMeta::SHORT_DESCRIPTION    => 'Short service description',
		ServiceMeta::DETAILED_DESCRIPTION => 'Detailed service description for the Consulting Page.',
		ServiceMeta::CTA_LABEL            => 'Enquire',
		ServiceMeta::CTA_URL              => '/contact/',
		ServiceMeta::FEATURED_ON_HOMEPAGE => $featured,
	);

	foreach ( $fields as $meta_key => $value ) {
		update_post_meta( $post_id, $meta_key, $value );
	}
	set_post_thumbnail( $post_id, $attachment_id );

	return $post_id;
}

try {
	hse_service_test_assert( post_type_exists( ServicePostType::POST_TYPE ), 'Service CPT is registered.' );
	hse_service_test_assert( post_type_supports( ServicePostType::POST_TYPE, 'thumbnail' ), 'Services support featured images.' );
	hse_service_test_assert( post_type_supports( ServicePostType::POST_TYPE, 'page-attributes' ), 'Services support explicit ordering.' );
	hse_service_test_assert( 'hse-leadership' === ServiceMeta::sanitize_service_key( 'HSE Leadership' ), 'Service keys normalize to canonical form.' );
	hse_service_test_assert( '' === ServiceMeta::sanitize_link_url( 'javascript:alert(1)' ), 'Unsafe Service links are rejected.' );
	hse_service_test_assert( '/contact/' === ServiceMeta::sanitize_link_url( '/contact/' ), 'Same-site Service links are accepted.' );

	foreach ( $published_before as $published_id ) {
		wp_update_post( array( 'ID' => $published_id, 'post_status' => 'draft' ) );
	}

	$unconfigured = ServiceRestController::get_services();
	hse_service_test_assert( is_wp_error( $unconfigured ), 'An empty Service collection does not return a partial document.' );
	hse_service_test_assert( 503 === ( $unconfigured->get_error_data()['status'] ?? 0 ), 'An empty Service collection returns HTTP 503.' );

	$temp_file = wp_tempnam( 'hse-service-test.png' );
	$png       = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true );
	if ( ! $temp_file || false === $png || false === file_put_contents( $temp_file, $png ) ) {
		throw new RuntimeException( 'Could not create the temporary Service image.' );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_status'    => 'inherit',
			'post_title'     => 'Service integration image',
		),
		$temp_file,
		0,
		true
	);
	if ( is_wp_error( $attachment_id ) ) {
		throw new RuntimeException( 'Could not register the temporary Service image.' );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $temp_file ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Service integration image' );

	$key_prefix = 'hse-service-test-' . strtolower( wp_generate_password( 8, false, false ) );
	$first_id   = hse_service_test_create( $key_prefix . '-later', 20, $attachment_id );
	$second_id  = hse_service_test_create( $key_prefix . '-first', 10, $attachment_id );
	$created_ids[] = $first_id;
	$created_ids[] = $second_id;
	wp_update_post( array( 'ID' => $first_id, 'post_status' => 'publish' ) );
	wp_update_post( array( 'ID' => $second_id, 'post_status' => 'publish' ) );

	hse_service_test_assert( 'publish' === get_post_status( $first_id ), 'A complete Service can be published.' );
	hse_service_test_assert( false === update_post_meta( $first_id, ServiceMeta::SERVICE_KEY, $key_prefix . '-changed' ), 'A published Service key cannot be changed.' );

	$duplicate_id   = hse_service_test_create( $key_prefix . '-duplicate', 30, $attachment_id, false );
	$created_ids[]  = $duplicate_id;
	hse_service_test_assert( false === update_post_meta( $duplicate_id, ServiceMeta::SERVICE_KEY, $key_prefix . '-later' ), 'Duplicate Service keys are rejected.' );

	for ( $index = 3; $index <= ServiceMeta::MAX_HOMEPAGE_SERVICES; $index++ ) {
		$post_id       = hse_service_test_create( $key_prefix . '-' . $index, $index * 10, $attachment_id );
		$created_ids[] = $post_id;
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
	}

	$overflow_id   = hse_service_test_create( $key_prefix . '-overflow', 100, $attachment_id );
	$created_ids[] = $overflow_id;
	wp_update_post( array( 'ID' => $overflow_id, 'post_status' => 'publish' ) );
	hse_service_test_assert( 'draft' === get_post_status( $overflow_id ), 'A fifth Homepage-featured Service cannot be published.' );

	wp_set_current_user( 0 );
	$response = ServiceRestController::get_services();
	hse_service_test_assert( ! is_wp_error( $response ), 'Anonymous visitors can read published Services.' );
	$data = is_wp_error( $response ) ? array() : $response->get_data();
	hse_service_test_assert( 1 === ( $data['schema_version'] ?? null ), 'Services REST contract remains version 1.' );
	hse_service_test_assert( 'services' === ( $data['collection_key'] ?? null ), 'Services REST uses the stable collection key.' );
	hse_service_test_assert( ServiceMeta::MAX_HOMEPAGE_SERVICES === count( $data['services'] ?? array() ), 'Services REST returns all four published Services.' );
	hse_service_test_assert( $key_prefix . '-first' === ( $data['services'][0]['service_key'] ?? null ), 'Services REST orders items by the WordPress Order field.' );
	hse_service_test_assert( 1 === ( $data['services'][0]['image']['width'] ?? null ), 'Services REST resolves image dimensions.' );
	hse_service_test_assert( ! isset( $data['services'][0]['image']['id'] ), 'WordPress attachment IDs do not cross the Services API boundary.' );
} finally {
	wp_set_current_user( $original_user );
	foreach ( $created_ids as $created_id ) {
		wp_delete_post( $created_id, true );
	}
	if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
		wp_delete_attachment( $attachment_id, true );
	}

	remove_filter( 'wp_insert_post_data', array( ServiceMeta::class, 'validate_publish' ), 10 );
	foreach ( $published_before as $published_id ) {
		wp_update_post( array( 'ID' => $published_id, 'post_status' => 'publish' ) );
	}
	add_filter( 'wp_insert_post_data', array( ServiceMeta::class, 'validate_publish' ), 10, 4 );
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Service integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Service integration checks passed.' );
