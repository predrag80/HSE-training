<?php
/**
 * Course-to-product synchronization checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Commerce\CommerceConfiguration;
use HSETraining\Headless\Commerce\CommerceProductSync;
use HSETraining\Headless\Course\CourseMeta;
use HSETraining\Headless\Course\CoursePostType;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! CommerceConfiguration::is_enabled() ) {
	WP_CLI::error( 'Define HSE_WOOCOMMERCE_STAGING_BRIDGE as true before running the product sync check.' );
}

$failures = array();

/** Record a failed assertion while allowing every synchronized Course to run. */
function hse_commerce_product_sync_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

$result = CommerceProductSync::sync_all();
$keys   = array();
$ids    = get_posts(
	array(
		'post_type'      => CoursePostType::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

foreach ( $ids as $post_id ) {
	$key = CourseMeta::sanitize_course_key( get_post_meta( $post_id, CourseMeta::COURSE_KEY, true ) );
	if ( '' !== $key ) {
		$keys[ $key ] = true;
	}
}

hse_commerce_product_sync_test_assert( count( $keys ) === count( $result['synced'] ), 'Every distinct published Course key is synchronized.' );
hse_commerce_product_sync_test_assert( 'RSD' === get_woocommerce_currency(), 'WooCommerce checkout currency is RSD.' );
hse_commerce_product_sync_test_assert( 2 === wc_get_price_decimals(), 'WooCommerce uses two decimal places for RSD.' );

foreach ( array_keys( $keys ) as $course_key ) {
	$product_id = wc_get_product_id_by_sku( $course_key );
	$product    = $product_id ? wc_get_product( $product_id ) : false;
	hse_commerce_product_sync_test_assert( $product instanceof WC_Product_Simple, sprintf( '%s resolves to one simple product.', $course_key ) );
	if ( ! $product ) {
		continue;
	}
	$source_ids = get_posts(
		array(
			'post_type'      => CoursePostType::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => CourseMeta::COURSE_KEY,
			'meta_value'     => $course_key,
			'no_found_rows'  => true,
		)
	);
	$source_id      = $source_ids ? (int) $source_ids[0] : 0;
	$online_price   = CourseMeta::sanitize_online_price( get_post_meta( $source_id, CourseMeta::ONLINE_PRICE, true ) );
	$online_enabled = (bool) get_post_meta( $source_id, CourseMeta::ONLINE_PURCHASE_ENABLED, true ) && '' !== $online_price;

	hse_commerce_product_sync_test_assert( 'publish' === $product->get_status(), sprintf( '%s product is published.', $course_key ) );
	hse_commerce_product_sync_test_assert( 'hidden' === $product->get_catalog_visibility(), sprintf( '%s remains hidden from the Woo catalog.', $course_key ) );
	hse_commerce_product_sync_test_assert(
		'' !== get_post_meta( $product_id, '_hse_course_title_en', true ),
		sprintf( '%s carries English Course content.', $course_key )
	);
	hse_commerce_product_sync_test_assert(
		'' !== get_post_meta( $product_id, '_hse_course_title_sr', true ),
		sprintf( '%s carries Serbian Course content.', $course_key )
	);

	if ( $online_enabled ) {
		hse_commerce_product_sync_test_assert( $online_price === $product->get_price(), sprintf( '%s receives its enabled CMS checkout price.', $course_key ) );
		hse_commerce_product_sync_test_assert( CommerceProductSync::is_online_sales_course( $course_key ), sprintf( '%s is exposed as available for online purchase.', $course_key ) );
	} else {
		hse_commerce_product_sync_test_assert( '' === $product->get_price(), sprintf( '%s has no checkout price.', $course_key ) );
		hse_commerce_product_sync_test_assert( ! $product->is_purchasable(), sprintf( '%s cannot be purchased.', $course_key ) );
	}
}

$test_key        = 'hse-sync-test-' . strtolower( wp_generate_password( 8, false, false ) );
$test_course_id  = 0;
$test_product_id = 0;
try {
	$test_course_id = wp_insert_post(
		array(
			'post_type'    => CoursePostType::POST_TYPE,
			'post_status'  => 'draft',
			'post_title'   => 'Temporary synchronized Course',
			'post_content' => '<p>Temporary Course content.</p>',
		),
		true
	);
	if ( is_wp_error( $test_course_id ) ) {
		throw new RuntimeException( $test_course_id->get_error_message() );
	}

	update_post_meta( $test_course_id, CourseMeta::COURSE_KEY, $test_key );
	update_post_meta( $test_course_id, 'content_locale', 'en' );
	update_post_meta( $test_course_id, CourseMeta::SHORT_DESCRIPTION, 'Temporary Course summary.' );
	wp_update_post( array( 'ID' => $test_course_id, 'post_status' => 'publish' ) );

	$test_product_id = wc_get_product_id_by_sku( $test_key );
	$test_product    = $test_product_id ? wc_get_product( $test_product_id ) : false;
	hse_commerce_product_sync_test_assert( $test_product instanceof WC_Product_Simple, 'Publishing a Course automatically creates its product.' );
	hse_commerce_product_sync_test_assert( $test_product && '' === $test_product->get_price(), 'A newly synchronized Course is not priced until online purchase is enabled.' );

	update_post_meta( $test_course_id, CourseMeta::ONLINE_PURCHASE_ENABLED, '1' );
	update_post_meta( $test_course_id, CourseMeta::ONLINE_PRICE, '49.50' );
	wp_update_post( array( 'ID' => $test_course_id, 'post_title' => 'Purchasable temporary synchronized Course' ) );
	$test_product = $test_product_id ? wc_get_product( $test_product_id ) : false;
	hse_commerce_product_sync_test_assert( $test_product && '49.50' === $test_product->get_price(), 'Enabling online purchase copies the CMS price to WooCommerce.' );
	hse_commerce_product_sync_test_assert( CommerceProductSync::is_online_sales_course( $test_key ), 'An enabled Course becomes available to checkout.' );

	update_post_meta( $test_course_id, CourseMeta::ONLINE_PURCHASE_ENABLED, '0' );
	wp_update_post( array( 'ID' => $test_course_id ) );
	$test_product = $test_product_id ? wc_get_product( $test_product_id ) : false;
	hse_commerce_product_sync_test_assert( $test_product && '' === $test_product->get_price(), 'Disabling online purchase removes the WooCommerce checkout price.' );

	wp_update_post( array( 'ID' => $test_course_id, 'post_title' => 'Updated temporary synchronized Course' ) );
	$test_product = $test_product_id ? wc_get_product( $test_product_id ) : false;
	hse_commerce_product_sync_test_assert(
		$test_product && 'Updated temporary synchronized Course' === $test_product->get_name(),
		'Editing a Course automatically updates its product.'
	);
} catch ( Throwable $error ) {
	hse_commerce_product_sync_test_assert( false, 'Automatic Course product synchronization test failed: ' . $error->getMessage() );
} finally {
	if ( $test_course_id && ! is_wp_error( $test_course_id ) ) {
		wp_delete_post( $test_course_id, true );
	}
	if ( $test_product_id ) {
		wp_delete_post( $test_product_id, true );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Course product synchronization check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Course product synchronization checks passed.' );
