<?php
/**
 * Production-only Sentry scope checks for execution with `wp eval-file`.
 *
 * No Sentry event or e-mail is sent.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Contact\ContactRestController;
use HSETraining\Headless\Infrastructure\LegacySentryMonitoringCleanup;
use HSETraining\Headless\Infrastructure\SentryReporter;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$failures = array();

/** Record a failed assertion while allowing all scope checks to run. */
function hse_sentry_scope_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

$is_production = 'production' === SentryReporter::environment_name();
$dsn           = 'https://public-key@example.ingest.sentry.io/4512174219264080';
$endpoints     = SentryReporter::endpoints_for_dsn( $dsn );

hse_sentry_scope_test_assert(
	'https://example.ingest.sentry.io/api/4512174219264080/envelope/' === ( $endpoints['envelope_url'] ?? '' ),
	'The event envelope endpoint is derived from the public DSN.'
);
hse_sentry_scope_test_assert(
	null === SentryReporter::endpoints_for_dsn( 'http://public-key@example.test/123' ),
	'An insecure monitoring DSN is rejected.'
);
hse_sentry_scope_test_assert(
	$is_production === SentryReporter::should_report_context( array( 'checkout_source' => 'production' ) ),
	'Production checkout events are enabled only in the production CMS environment.'
);
hse_sentry_scope_test_assert(
	$is_production === SentryReporter::should_report_context( array( 'frontend_environment' => 'production' ) ),
	'Production contact events are enabled only in the production CMS environment.'
);
hse_sentry_scope_test_assert(
	! SentryReporter::should_report_context( array( 'checkout_source' => 'staging' ) )
		&& ! SentryReporter::should_report_context( array( 'frontend_environment' => 'dev' ) )
		&& ! SentryReporter::should_report_context( array() ),
	'Non-production and unclassified events are not reported.'
);
hse_sentry_scope_test_assert(
	'production' === ContactRestController::monitoring_environment_for_origin( 'https://hsetraining.rs' )
		&& 'production' === ContactRestController::monitoring_environment_for_origin( 'https://www.hsetraining.rs' )
		&& 'non-production' === ContactRestController::monitoring_environment_for_origin( 'https://staging.hsetraining.rs' ),
	'Only the production contact origin is classified for Sentry.'
);
hse_sentry_scope_test_assert(
	false !== has_action( 'init', array( LegacySentryMonitoringCleanup::class, 'run' ) ),
	'The retired BokaPOS monitor has an idempotent cleanup migration.'
);
hse_sentry_scope_test_assert(
	false === has_action( 'hse/monitor_bokapos_operations' ),
	'The retired BokaPOS Sentry watchdog is not registered.'
);

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Sentry scope check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All production-only Sentry scope checks passed. No event or e-mail was sent.' );
