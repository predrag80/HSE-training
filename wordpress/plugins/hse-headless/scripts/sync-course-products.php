<?php
/**
 * Synchronize published Course records into derived WooCommerce products.
 *
 * Run with:
 * wp eval-file wp-content/plugins/hse-headless/scripts/sync-course-products.php
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Commerce\CommerceConfiguration;
use HSETraining\Headless\Commerce\CommerceProductSync;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! CommerceConfiguration::is_enabled() ) {
	WP_CLI::error( 'Define HSE_WOOCOMMERCE_STAGING_BRIDGE as true before synchronizing Course products.' );
}

$woocommerce_version = defined( 'WC_VERSION' ) ? (string) WC_VERSION : '';
if ( 0 !== strpos( $woocommerce_version, '10.' ) ) {
	WP_CLI::error( sprintf( 'Expected WooCommerce 10.x, found %s.', $woocommerce_version ?: 'unknown' ) );
}

$result = CommerceProductSync::sync_all();
WP_CLI::success(
	sprintf(
		'Synchronized %d Course product(s) and retired %d stale product(s).',
		count( $result['synced'] ),
		count( $result['retired'] )
	)
);
