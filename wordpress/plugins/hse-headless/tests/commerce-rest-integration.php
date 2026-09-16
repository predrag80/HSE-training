<?php
/**
 * Staging commerce REST checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Commerce\CommerceConfiguration;
use HSETraining\Headless\Commerce\CommerceRestController;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! CommerceConfiguration::is_enabled() ) {
	WP_CLI::error( 'Define HSE_WOOCOMMERCE_STAGING_BRIDGE as true before running the commerce check.' );
}

$failures  = array();
$test_key  = 'hse-commerce-test-' . strtolower( wp_generate_password( 8, false, false ) );
$product   = new WC_Product_Simple();
$product_id = 0;

/** Record a failed assertion while preserving cleanup. */
function hse_commerce_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

try {
	$product->set_name( 'Temporary HSE commerce test' );
	$product->set_sku( $test_key );
	$product->set_status( 'publish' );
	$product->set_virtual( true );
	$product->set_regular_price( '1000.00' );
	$product_id = $product->save();

	$request  = new WP_REST_Request( 'GET', '/hse/v1/commerce/products/' . $test_key );
	$response = rest_do_request( $request );
	$data     = $response->get_data();

	hse_commerce_test_assert( 200 === $response->get_status(), 'Commerce product endpoint responds with HTTP 200.' );
	hse_commerce_test_assert( 1 === ( $data['schema_version'] ?? null ), 'Commerce schema is explicitly versioned.' );
	hse_commerce_test_assert( $test_key === ( $data['course_key'] ?? null ), 'Commerce lookup preserves course_key.' );
	$expected_minor = (int) round( 1000 * ( 10 ** wc_get_price_decimals() ) );
	hse_commerce_test_assert( $expected_minor === ( $data['price_minor'] ?? null ), 'Commerce price is returned in integer minor units.' );
	hse_commerce_test_assert( false === ( $data['purchasable'] ?? null ), 'A Woo product without an explicitly enabled Course is not exposed as purchasable.' );
	hse_commerce_test_assert( ! array_key_exists( 'product_id', $data ), 'WordPress product IDs do not cross the public boundary.' );
	hse_commerce_test_assert(
		false !== strpos( (string) ( $data['checkout_url'] ?? '' ), rawurlencode( $test_key ) ),
		'Checkout URL carries the stable course_key.'
	);
} finally {
	if ( $product_id ) {
		wp_delete_post( $product_id, true );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Commerce integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Commerce REST integration checks passed.' );
