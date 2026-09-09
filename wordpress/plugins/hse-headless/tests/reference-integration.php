<?php
/** Reference collection checks for execution with `wp eval-file`. */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Reference\ReferenceMeta;
use HSETraining\Headless\Reference\ReferencePostType;
use HSETraining\Headless\Reference\ReferenceRestController;
use HSETraining\Headless\Content\ContentLocale;

if ( ! class_exists( ReferenceRestController::class ) ) {
	WP_CLI::error( 'Reference implementation is not loaded.' );
}

$failures         = array();
$created_ids      = array();
$published_before = get_posts( array( 'post_type' => ReferencePostType::POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ) );

/** Record a failed assertion without stopping cleanup. */
function hse_reference_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

/** Create a complete temporary Reference. */
function hse_reference_test_create( $key, $order, $featured = true, $locale = 'en' ) {
	$post_id = wp_insert_post( array( 'post_type' => ReferencePostType::POST_TYPE, 'post_status' => 'draft', 'post_title' => 'Reference author ' . $order, 'menu_order' => $order ), true );
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Could not create a temporary Reference.' );
	}
	update_post_meta( $post_id, ContentLocale::META_KEY, $locale );
	update_post_meta( $post_id, ReferenceMeta::REFERENCE_KEY, $key );
	update_post_meta( $post_id, ReferenceMeta::QUOTE, 'Clear and practical training reference.' );
	update_post_meta( $post_id, ReferenceMeta::ROLE, 'HSE professional' );
	update_post_meta( $post_id, ReferenceMeta::FEATURED_ON_HOMEPAGE, $featured );
	update_post_meta( $post_id, ReferenceMeta::ACCENT_ON_HOMEPAGE, false );

	return $post_id;
}

try {
	hse_reference_test_assert( post_type_exists( ReferencePostType::POST_TYPE ), 'Reference CPT is registered.' );
	hse_reference_test_assert( post_type_supports( ReferencePostType::POST_TYPE, 'page-attributes' ), 'References support explicit ordering.' );
	hse_reference_test_assert( 'saule-kuza' === ReferenceMeta::sanitize_reference_key( 'Saule Kuza' ), 'Reference keys normalize to canonical form.' );
	$registered = get_registered_meta_keys( 'post', ReferencePostType::POST_TYPE );
	hse_reference_test_assert( isset( $registered[ ContentLocale::META_KEY ] ), 'Reference content language is registered.' );

	foreach ( $published_before as $published_id ) {
		wp_update_post( array( 'ID' => $published_id, 'post_status' => 'draft' ) );
	}
	$unconfigured = ReferenceRestController::get_references();
	hse_reference_test_assert( is_wp_error( $unconfigured ), 'An empty Reference collection returns an error.' );
	hse_reference_test_assert( 503 === ( $unconfigured->get_error_data()['status'] ?? 0 ), 'An empty Reference collection returns HTTP 503.' );

	$key_prefix = 'hse-reference-test-' . strtolower( wp_generate_password( 8, false, false ) );
	$orders     = array( 20, 10, 30, 40 );
	foreach ( $orders as $index => $order ) {
		$post_id       = hse_reference_test_create( $key_prefix . '-' . ( $index + 1 ), $order );
		$created_ids[] = $post_id;
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
	}

	$first_id = $created_ids[0];
	hse_reference_test_assert( false === update_post_meta( $first_id, ReferenceMeta::REFERENCE_KEY, $key_prefix . '-changed' ), 'A published Reference key cannot change.' );
	$duplicate_id  = hse_reference_test_create( $key_prefix . '-duplicate', 50, false );
	$created_ids[] = $duplicate_id;
	hse_reference_test_assert( false === update_post_meta( $duplicate_id, ReferenceMeta::REFERENCE_KEY, $key_prefix . '-1' ), 'Duplicate Reference keys are rejected.' );

	$overflow_id   = hse_reference_test_create( $key_prefix . '-overflow', 60 );
	$created_ids[] = $overflow_id;
	wp_update_post( array( 'ID' => $overflow_id, 'post_status' => 'publish' ) );
	hse_reference_test_assert( 'draft' === get_post_status( $overflow_id ), 'A fifth Homepage Reference cannot be published.' );

	$serbian_id    = hse_reference_test_create( $key_prefix . '-1', 5, true, 'sr' );
	$created_ids[] = $serbian_id;
	wp_update_post( array( 'ID' => $serbian_id, 'post_status' => 'publish' ) );
	hse_reference_test_assert( 'publish' === get_post_status( $serbian_id ), 'The same stable Reference key can be published for the Serbian translation.' );
	hse_reference_test_assert( false === update_post_meta( $serbian_id, ContentLocale::META_KEY, 'en' ), 'A published Reference language cannot change.' );

	$response = ReferenceRestController::get_references();
	$data     = is_wp_error( $response ) ? array() : $response->get_data();
	hse_reference_test_assert( ! is_wp_error( $response ), 'Anonymous visitors can read published References.' );
	hse_reference_test_assert( 4 === count( $data['references'] ?? array() ), 'References REST returns all four published records.' );
	hse_reference_test_assert( 'en' === ( $data['locale'] ?? null ), 'References REST defaults to English.' );
	hse_reference_test_assert( $key_prefix . '-2' === ( $data['references'][0]['reference_key'] ?? null ), 'References REST uses WordPress Order.' );
	hse_reference_test_assert( ! isset( $data['references'][0]['id'] ), 'WordPress post IDs do not cross the References API boundary.' );

	$serbian_request = new WP_REST_Request( 'GET', '/hse/v1/references' );
	$serbian_request->set_param( 'lang', 'sr' );
	$serbian_response = ReferenceRestController::get_references( $serbian_request );
	$serbian_data     = is_wp_error( $serbian_response ) ? array() : $serbian_response->get_data();
	hse_reference_test_assert( ! is_wp_error( $serbian_response ), 'Anonymous visitors can read Serbian References.' );
	hse_reference_test_assert( 'sr' === ( $serbian_data['locale'] ?? null ), 'The Serbian References response carries its locale.' );
	hse_reference_test_assert( 1 === count( $serbian_data['references'] ?? array() ), 'The Serbian References query does not leak English records.' );
} finally {
	foreach ( $created_ids as $created_id ) {
		wp_delete_post( $created_id, true );
	}
	remove_filter( 'wp_insert_post_data', array( ReferenceMeta::class, 'validate_publish' ), 10 );
	foreach ( $published_before as $published_id ) {
		wp_update_post( array( 'ID' => $published_id, 'post_status' => 'publish' ) );
	}
	add_filter( 'wp_insert_post_data', array( ReferenceMeta::class, 'validate_publish' ), 10, 4 );
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Reference integration check(s) failed.', count( $failures ) ) );
}
WP_CLI::success( 'All Reference integration checks passed.' );
