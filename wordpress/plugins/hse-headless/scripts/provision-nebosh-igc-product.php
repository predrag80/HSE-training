<?php
/**
 * Enable the staging IGC Course and synchronize its derived Woo product.
 *
 * Run with:
 * HSE_IGC_TEST_PRICE=1000.00 wp eval-file wp-content/plugins/hse-headless/scripts/provision-nebosh-igc-product.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! class_exists( 'WC_Product_Simple' ) ) {
	WP_CLI::error( 'WooCommerce must be active before provisioning the test product.' );
}

$woocommerce_version = defined( 'WC_VERSION' ) ? (string) WC_VERSION : '';
if ( 0 !== strpos( $woocommerce_version, '10.' ) ) {
	WP_CLI::error( sprintf( 'Expected WooCommerce 10.x, found %s.', $woocommerce_version ?: 'unknown' ) );
}

$price = getenv( 'HSE_IGC_TEST_PRICE' );
if ( false === $price || ! is_numeric( $price ) || (float) $price <= 0 ) {
	WP_CLI::error( 'Set HSE_IGC_TEST_PRICE to a positive sandbox amount before running this script.' );
}

$course_key = 'nebosh-igc';
$course_ids = get_posts(
	array(
		'post_type'      => \HSETraining\Headless\Course\CoursePostType::POST_TYPE,
		'post_status'    => array_values( get_post_stati() ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => \HSETraining\Headless\Course\CourseMeta::COURSE_KEY,
		'meta_value'     => $course_key,
		'no_found_rows'  => true,
	)
);
if ( ! $course_ids ) {
	WP_CLI::error( 'No Course record exists for nebosh-igc.' );
}

$formatted_price = \HSETraining\Headless\Course\CourseMeta::sanitize_online_price( $price );
foreach ( $course_ids as $course_id ) {
	update_post_meta( $course_id, \HSETraining\Headless\Course\CourseMeta::ONLINE_PURCHASE_ENABLED, '1' );
	update_post_meta( $course_id, \HSETraining\Headless\Course\CourseMeta::ONLINE_PRICE, $formatted_price );
}

$product = \HSETraining\Headless\Commerce\CommerceProductSync::sync_course_key( $course_key );
if ( is_wp_error( $product ) || ! $product ) {
	WP_CLI::error( is_wp_error( $product ) ? $product->get_error_message() : 'IGC product synchronization failed.' );
}

WP_CLI::success(
	sprintf(
		'Enabled and synchronized %s at %s %s (product ID retained inside WordPress).',
		$course_key,
		$formatted_price,
		get_woocommerce_currency()
	)
);
