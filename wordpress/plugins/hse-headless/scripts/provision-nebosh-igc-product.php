<?php
/**
 * Create or update the staging NEBOSH IGC WooCommerce product.
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
$product_id = wc_get_product_id_by_sku( $course_key );
$product    = $product_id ? wc_get_product( $product_id ) : new WC_Product_Simple();

if ( ! $product instanceof WC_Product_Simple ) {
	WP_CLI::error( 'The existing nebosh-igc SKU is not a simple WooCommerce product.' );
}

$product->set_name( 'NEBOSH International General Certificate in Occupational Health and Safety' );
$product->set_slug( 'nebosh-international-general-certificate' );
$product->set_sku( $course_key );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'hidden' );
$product->set_virtual( true );
$product->set_sold_individually( true );
$product->set_manage_stock( false );
$product->set_tax_status( 'none' );
$product->set_regular_price( wc_format_decimal( $price ) );
$product->set_price( wc_format_decimal( $price ) );
$product->set_short_description( 'Sandbox checkout product for the NEBOSH IGC course.' );
$saved_id = $product->save();
update_post_meta( $saved_id, '_hse_course_key', $course_key );

WP_CLI::success(
	sprintf(
		'Provisioned %s at %s %s (product ID retained inside WordPress).',
		$course_key,
		wc_format_decimal( $price ),
		get_woocommerce_currency()
	)
);
