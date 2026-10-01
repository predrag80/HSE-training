<?php
/**
 * BokaPOS operational-monitoring checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Commerce\CommerceBokaPosMonitoring;
use HSETraining\Headless\Commerce\CommerceCheckoutMonitoring;
use HSETraining\Headless\Infrastructure\SentryReporter;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$failures = array();

/** Record a failed assertion while allowing all monitoring checks to run. */
function hse_bokapos_monitoring_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

hse_bokapos_monitoring_test_assert( class_exists( CommerceBokaPosMonitoring::class ), 'The BokaPOS monitoring class is loaded.' );
hse_bokapos_monitoring_test_assert( class_exists( CommerceCheckoutMonitoring::class ), 'The checkout monitoring class is loaded.' );
hse_bokapos_monitoring_test_assert( class_exists( SentryReporter::class ), 'The privacy-bounded Sentry reporter is loaded.' );
hse_bokapos_monitoring_test_assert(
	false !== has_action( CommerceBokaPosMonitoring::WATCHDOG_HOOK, array( CommerceBokaPosMonitoring::class, 'run' ) ),
	'The watchdog action is registered.'
);
hse_bokapos_monitoring_test_assert(
	false !== has_action( 'woocommerce_checkout_order_exception', array( CommerceCheckoutMonitoring::class, 'report_order_creation_failure' ) ),
	'Exceptional WooCommerce order-creation failures are registered for reporting.'
);

$now      = time();
$old_time = gmdate( 'Y-m-d H:i:s', $now - 1900 );

$refund_failure = CommerceBokaPosMonitoring::classify_operation(
	array(
		'id'            => 41,
		'order_id'      => 101,
		'refund_id'     => 102,
		'kind'          => 'refund',
		'environment'   => 'sandbox',
		'status'        => 'failed',
		'failure_code'  => 'NO_REFUND_LINES',
		'attention'     => 1,
		'attempts'      => 1,
		'updated_at'    => $old_time,
	),
	$now
);
hse_bokapos_monitoring_test_assert(
	'refund_fiscalization_failed' === ( $refund_failure['incident'] ?? '' )
		&& 'error' === ( $refund_failure['level'] ?? '' ),
	'A failed fiscal refund is classified as an immediate error.'
);
hse_bokapos_monitoring_test_assert(
	101 === ( $refund_failure['context']['order_id'] ?? 0 )
		&& 102 === ( $refund_failure['context']['refund_id'] ?? 0 )
		&& ! isset( $refund_failure['context']['customer_email'] ),
	'Fiscal alerts contain operational identifiers and no customer e-mail.'
);

$delayed_sale = CommerceBokaPosMonitoring::classify_operation(
	array(
		'id'          => 42,
		'order_id'    => 103,
		'kind'        => 'sale',
		'environment' => 'sandbox',
		'status'      => 'retry_scheduled',
		'attention'   => 0,
		'attempts'    => 3,
		'updated_at'  => gmdate( 'Y-m-d H:i:s', $now - 700 ),
	),
	$now
);
hse_bokapos_monitoring_test_assert(
	'fiscalization_delayed' === ( $delayed_sale['incident'] ?? '' )
		&& 'warning' === ( $delayed_sale['level'] ?? '' ),
	'A sale still retrying after ten minutes is classified as delayed.'
);

$fresh_sale = CommerceBokaPosMonitoring::classify_operation(
	array(
		'id'          => 43,
		'order_id'    => 104,
		'kind'        => 'sale',
		'environment' => 'sandbox',
		'status'      => 'retry_scheduled',
		'attention'   => 0,
		'attempts'    => 1,
		'updated_at'  => gmdate( 'Y-m-d H:i:s', $now - 120 ),
	),
	$now
);
hse_bokapos_monitoring_test_assert( null === $fresh_sale, 'A normal short retry window does not create a false alarm.' );

$delayed_delivery = CommerceBokaPosMonitoring::classify_operation(
	array(
		'id'          => 44,
		'order_id'    => 105,
		'kind'        => 'delivery',
		'environment' => 'sandbox',
		'status'      => 'queued',
		'attention'   => 0,
		'attempts'    => 1,
		'updated_at'  => $old_time,
	),
	$now
);
hse_bokapos_monitoring_test_assert(
	'receipt_delivery_delayed' === ( $delayed_delivery['incident'] ?? '' ),
	'A receipt e-mail queued for more than thirty minutes is classified as delayed.'
);

$fiscalized = CommerceBokaPosMonitoring::classify_operation(
	array(
		'id'          => 45,
		'order_id'    => 106,
		'kind'        => 'sale',
		'environment' => 'sandbox',
		'status'      => 'fiscalized',
		'attention'   => 0,
		'updated_at'  => $old_time,
	),
	$now
);
hse_bokapos_monitoring_test_assert( null === $fiscalized, 'A successful fiscal operation does not trigger an alarm.' );

$dsn = 'https://public-key@example.ingest.sentry.io/4512174219264080';
$endpoints = SentryReporter::endpoints_for_dsn( $dsn, 'HSE BokaPOS Watchdog' );
hse_bokapos_monitoring_test_assert(
	'https://example.ingest.sentry.io/api/4512174219264080/envelope/' === ( $endpoints['envelope_url'] ?? '' ),
	'The event envelope endpoint is derived from the public DSN.'
);
hse_bokapos_monitoring_test_assert(
	'https://example.ingest.sentry.io/api/4512174219264080/crons/hse-bokapos-watchdog/public-key/' === ( $endpoints['cron_url'] ?? '' ),
	'The heartbeat endpoint is derived from the public DSN and sanitized monitor slug.'
);
hse_bokapos_monitoring_test_assert(
	null === SentryReporter::endpoints_for_dsn( 'http://public-key@example.test/123' ),
	'An insecure monitoring DSN is rejected.'
);

$schedules = CommerceBokaPosMonitoring::cron_schedules( array() );
hse_bokapos_monitoring_test_assert(
	300 === ( $schedules['hse_every_five_minutes']['interval'] ?? 0 ),
	'The fallback watchdog interval is five minutes.'
);

if ( $failures ) {
	WP_CLI::error( sprintf( '%d BokaPOS monitoring check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All BokaPOS monitoring checks passed.' );
