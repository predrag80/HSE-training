<?php
/**
 * Public REST surface checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Infrastructure\PublicRestSurface;

if ( ! class_exists( PublicRestSurface::class ) ) {
	WP_CLI::error( 'Public REST surface implementation is not loaded.' );
}

$failures = array();

/** Record a failed assertion without stopping remaining checks. */
function hse_public_rest_surface_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

/** Dispatch a GET request as the current user and return its HTTP status. */
function hse_public_rest_surface_test_status( string $route ): int {
	return rest_do_request( new WP_REST_Request( 'GET', $route ) )->get_status();
}

$original_user_id = get_current_user_id();

hse_public_rest_surface_test_assert(
	10 === has_filter( 'rest_pre_dispatch', array( PublicRestSurface::class, 'guard_request' ) ),
	'The REST guard runs before route callbacks are dispatched.'
);
hse_public_rest_surface_test_assert(
	PublicRestSurface::is_store_api_route( '/wc/store/v1/checkout' )
		&& PublicRestSurface::is_store_api_route( '/wc/store/v1/cart/add-item' )
		&& PublicRestSurface::is_store_api_route( '/wc/store' )
		&& ! PublicRestSurface::is_store_api_route( '/wc/v3/products' )
		&& ! PublicRestSurface::is_store_api_route( '/wc/storefront' ),
	'Only WooCommerce Store API routes are classified as Store API routes.'
);
hse_public_rest_surface_test_assert(
	PublicRestSurface::is_user_route( '/wp/v2/users' )
		&& PublicRestSurface::is_user_route( '/wp/v2/users/1' )
		&& ! PublicRestSurface::is_user_route( '/wp/v2/users-extra' )
		&& ! PublicRestSurface::is_user_route( '/hse/v1/company' ),
	'Only core user routes are classified as user routes.'
);

$earlier_result = new WP_Error( 'earlier', 'Earlier result.' );
hse_public_rest_surface_test_assert(
	$earlier_result === PublicRestSurface::guard_request( $earlier_result, null, new WP_REST_Request( 'GET', '/wc/store/v1/cart' ) ),
	'An earlier short-circuit result is preserved.'
);

wp_set_current_user( 0 );

foreach ( array( '/hse/v1/homepage', '/hse/v1/commerce/products/nebosh-igc', '/hse/v1/contact', '/wp/v2/courses', '/wp/v2/trainings' ) as $route ) {
	hse_public_rest_surface_test_assert(
		null === PublicRestSurface::guard_request( null, null, new WP_REST_Request( 'GET', $route ) ),
		sprintf( 'Anonymous access to %s remains available to the public site.', $route )
	);
}

hse_public_rest_surface_test_assert(
	404 === hse_public_rest_surface_test_status( '/wc/store/v1/products' ),
	'Anonymous visitors cannot list products through the Store API.'
);
hse_public_rest_surface_test_assert(
	404 === rest_do_request( new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) )->get_status(),
	'Anonymous visitors cannot create orders through the Store API checkout.'
);
hse_public_rest_surface_test_assert(
	401 === hse_public_rest_surface_test_status( '/wp/v2/users' ),
	'Anonymous visitors cannot list CMS users.'
);

$administrators = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);

if ( $administrators ) {
	wp_set_current_user( (int) $administrators[0] );

	hse_public_rest_surface_test_assert(
		404 !== hse_public_rest_surface_test_status( '/wc/store/v1/products' ),
		'Store managers keep Store API access for WooCommerce administration.'
	);
	hse_public_rest_surface_test_assert(
		200 === hse_public_rest_surface_test_status( '/wp/v2/users' ),
		'Signed-in CMS users keep core user lookups for the editor.'
	);
} else {
	hse_public_rest_surface_test_assert( false, 'An administrator account is required for the signed-in checks.' );
}

wp_set_current_user( $original_user_id );

if ( $failures ) {
	WP_CLI::error( sprintf( '%d public REST surface check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All public REST surface checks passed.' );
