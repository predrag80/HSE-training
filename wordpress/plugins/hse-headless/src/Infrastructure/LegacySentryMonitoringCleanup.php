<?php
/**
 * Removes the retired BokaPOS-to-Sentry watchdog schedule.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Infrastructure;

defined( 'ABSPATH' ) || exit;

/** Performs one idempotent cleanup after monitoring is narrowed to checkout/contact. */
final class LegacySentryMonitoringCleanup {
	private const VERSION_OPTION = 'hse_sentry_scope_cleanup_version';
	private const VERSION = 1;
	private const WATCHDOG_HOOK = 'hse/monitor_bokapos_operations';
	private const WATCHDOG_GROUP = 'hse-monitoring';

	/** Register the one-time cleanup after WooCommerce has initialized. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'run' ), 40 );
	}

	/** Remove only the retired custom watchdog and its non-business state. */
	public static function run(): void {
		if ( self::VERSION <= (int) get_option( self::VERSION_OPTION, 0 ) ) {
			return;
		}

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::WATCHDOG_HOOK, array(), self::WATCHDOG_GROUP );
		}
		wp_clear_scheduled_hook( self::WATCHDOG_HOOK );

		foreach (
			array(
				'hse_bokapos_monitoring_state',
				'hse_bokapos_monitoring_started_at',
				'hse_bokapos_monitoring_last_run',
				'hse_bokapos_monitoring_lock',
				'hse_bokapos_monitoring_heartbeat_warning',
			) as $option
		) {
			delete_option( $option );
		}

		update_option( self::VERSION_OPTION, self::VERSION, false );
	}
}
