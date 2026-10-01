<?php
/**
 * Update-safe monitoring for BokaPOS fiscalization and receipt delivery.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

use HSETraining\Headless\Infrastructure\SentryReporter;

defined( 'ABSPATH' ) || exit;

/** Observes provider state without issuing, retrying or mutating fiscal documents. */
final class CommerceBokaPosMonitoring {
	public const WATCHDOG_HOOK = 'hse/monitor_bokapos_operations';
	public const WATCHDOG_GROUP = 'hse-monitoring';
	public const CRON_MONITOR_SLUG = 'hse-bokapos-watchdog';

	private const INTERVAL = 300;
	private const FISCAL_DELAY = 600;
	private const DELIVERY_DELAY = 1800;
	private const LOCK_TTL = 600;
	private const STATE_OPTION = 'hse_bokapos_monitoring_state';
	private const STARTED_OPTION = 'hse_bokapos_monitoring_started_at';
	private const LAST_RUN_OPTION = 'hse_bokapos_monitoring_last_run';
	private const LOCK_OPTION = 'hse_bokapos_monitoring_lock';
	private const HEARTBEAT_WARNING_OPTION = 'hse_bokapos_monitoring_heartbeat_warning';
	private const BOKAPOS_TABLE = 'bokapos_operations';
	private const FISCAL_STATUS_META = '_bokapos_status';
	private const DOCUMENT_ID_META = '_bokapos_document_id';

	/** Register scheduler, watchdog and admin visibility hooks. */
	public static function register_hooks(): void {
		add_filter( 'cron_schedules', array( self::class, 'cron_schedules' ) );
		add_action( 'init', array( self::class, 'ensure_scheduled' ), 30 );
		add_action( self::WATCHDOG_HOOK, array( self::class, 'run' ) );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/** Add a fallback five-minute schedule when Action Scheduler is unavailable. */
	public static function cron_schedules( array $schedules ): array {
		$schedules['hse_every_five_minutes'] = array(
			'interval' => self::INTERVAL,
			'display'  => __( 'Every five minutes (HSE monitoring)', 'hse-headless' ),
		);
		return $schedules;
	}

	/** Ensure exactly one recurring watchdog exists. */
	public static function ensure_scheduled(): void {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return;
		}

		if ( false === get_option( self::STARTED_OPTION, false ) ) {
			add_option( self::STARTED_OPTION, time(), '', false );
		}

		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( ! as_has_scheduled_action( self::WATCHDOG_HOOK, array(), self::WATCHDOG_GROUP ) ) {
				as_schedule_recurring_action(
					time() + 60,
					self::INTERVAL,
					self::WATCHDOG_HOOK,
					array(),
					self::WATCHDOG_GROUP,
					true
				);
			}
			return;
		}

		if ( ! wp_next_scheduled( self::WATCHDOG_HOOK ) ) {
			wp_schedule_event( time() + 60, 'hse_every_five_minutes', self::WATCHDOG_HOOK );
		}
	}

	/** Remove only this plugin's monitor jobs when the plugin is deactivated. */
	public static function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::WATCHDOG_HOOK, array(), self::WATCHDOG_GROUP );
		}
		wp_clear_scheduled_hook( self::WATCHDOG_HOOK );
		delete_option( self::LOCK_OPTION );
	}

	/** Execute one read-only reconciliation pass. */
	public static function run(): void {
		if ( ! CommerceConfiguration::is_enabled() || ! self::acquire_lock() ) {
			return;
		}

		$started       = microtime( true );
		$check_in_id   = SentryReporter::start_check_in( self::CRON_MONITOR_SLUG );
		$check_in_state = 'ok';
		$heartbeat_ok  = '' !== $check_in_id;

		try {
			if ( $heartbeat_ok ) {
				delete_option( self::HEARTBEAT_WARNING_OPTION );
			} elseif ( ! get_option( self::HEARTBEAT_WARNING_OPTION, false ) ) {
				$reported = SentryReporter::capture(
					'sentry_cron_checkin_unavailable',
					'warning',
					'Sentry did not accept the fiscal watchdog heartbeat. Incident monitoring remains active.',
					array(
						'component'    => 'sentry_crons',
						'monitor_slug' => self::CRON_MONITOR_SLUG,
					),
					'system:sentry-cron-checkin-unavailable:' . SentryReporter::environment_name()
				);
				if ( $reported ) {
					update_option( self::HEARTBEAT_WARNING_OPTION, time(), false );
				}
			}

			$monitoring_started = (int) get_option( self::STARTED_OPTION, time() );
			$state              = get_option( self::STATE_OPTION, array() );
			$state              = is_array( $state ) ? $state : array();
			$observations       = self::collect_observations( $monitoring_started );
			$incident_count     = 0;

			foreach ( $observations as $key => $observation ) {
				$incident = (string) ( $observation['incident'] ?? '' );
				if ( '' === $incident ) {
					if ( isset( $state[ $key ] ) ) {
						$previous = is_array( $state[ $key ] ) ? $state[ $key ] : array();
						$context  = is_array( $observation['context'] ?? null ) ? $observation['context'] : array();
						$context['monitor_key'] = (string) $key;
						$context['recovered_incident'] = sanitize_key( (string) ( $previous['incident'] ?? 'unknown' ) );
						if ( SentryReporter::capture(
							'bokapos_operation_recovered',
							'info',
							'The monitored BokaPOS operation recovered.',
							$context,
							'recovery:' . $key . ':' . (string) ( $previous['signature'] ?? '' )
						) ) {
							unset( $state[ $key ] );
							update_option( self::STATE_OPTION, $state, false );
						}
					}
					continue;
				}

				++$incident_count;
				$signature = hash( 'sha256', wp_json_encode( array( $incident, $observation['signature'] ?? '' ) ) );
				$same_signature = isset( $state[ $key ]['signature'] )
					&& hash_equals( (string) $state[ $key ]['signature'], $signature );
				$delivery_state = (string) ( $state[ $key ]['delivery_state'] ?? '' );
				$claimed_at     = (int) ( $state[ $key ]['claimed_at'] ?? 0 );
				if ( $same_signature
					&& ( 'delivered' === $delivery_state || ( 'sending' === $delivery_state && $claimed_at >= time() - self::LOCK_TTL ) ) ) {
					continue;
				}

				$context = is_array( $observation['context'] ?? null ) ? $observation['context'] : array();
				$context['monitor_key'] = (string) $key;
				$state[ $key ] = array(
					'incident'       => $incident,
					'signature'      => $signature,
					'claimed_at'     => time(),
					'alerted_at'     => (int) ( $state[ $key ]['alerted_at'] ?? 0 ),
					'delivery_state' => 'sending',
				);
				update_option( self::STATE_OPTION, $state, false );

				$sent = SentryReporter::capture(
					$incident,
					(string) ( $observation['level'] ?? 'error' ),
					(string) ( $observation['message'] ?? 'BokaPOS operation needs attention.' ),
					$context,
					'incident:' . $key . ':' . $signature
				);
				if ( $sent ) {
					$state[ $key ]['alerted_at']     = time();
					$state[ $key ]['delivery_state'] = 'delivered';
				} else {
					unset( $state[ $key ] );
				}
				update_option( self::STATE_OPTION, $state, false );
			}

			$state = self::prune_state( $state );
			update_option( self::STATE_OPTION, $state, false );
			update_option(
				self::LAST_RUN_OPTION,
				array(
					'checked_at'     => time(),
					'incident_count' => $incident_count,
					'ok'             => true,
					'sentry'         => SentryReporter::is_configured(),
					'heartbeat'      => $heartbeat_ok,
				),
				false
			);
		} catch ( \Throwable $error ) {
			$check_in_state = 'error';
			update_option(
				self::LAST_RUN_OPTION,
				array(
					'checked_at'     => time(),
					'incident_count' => 1,
					'ok'             => false,
					'sentry'         => SentryReporter::is_configured(),
					'heartbeat'      => $heartbeat_ok,
				),
				false
			);
			SentryReporter::capture(
				'bokapos_monitoring_failed',
				'error',
				'The BokaPOS monitoring pass could not be completed.',
				array( 'failure_type' => get_class( $error ) )
			);
		} finally {
			SentryReporter::finish_check_in(
				self::CRON_MONITOR_SLUG,
				$check_in_id,
				$check_in_state,
				(int) ceil( microtime( true ) - $started )
			);
			self::release_lock();
		}
	}

	/** Classify one provider journal row without mutating it. */
	public static function classify_operation( array $row, int $now ): ?array {
		$status = sanitize_key( (string) ( $row['status'] ?? '' ) );
		$kind   = sanitize_key( (string) ( $row['kind'] ?? '' ) );
		$age    = self::row_age( $row, $now );
		$failed = in_array( $status, array( 'failed', 'rejected', 'needs_attention' ), true ) || ! empty( $row['attention'] );

		if ( $failed ) {
			if ( 'refund' === $kind ) {
				$incident = 'refund_fiscalization_failed';
				$message  = 'A BokaPOS fiscal refund needs attention.';
			} elseif ( 'delivery' === $kind ) {
				$incident = 'receipt_delivery_failed';
				$message  = 'BokaPOS could not deliver a fiscal document e-mail.';
			} elseif ( 'sale' === $kind ) {
				$incident = 'fiscalization_failed';
				$message  = 'A BokaPOS sale fiscalization needs attention.';
			} else {
				$incident = 'fiscal_operation_failed';
				$message  = 'A BokaPOS fiscal operation needs attention.';
			}

			return self::observation( $row, $incident, 'error', $message, $status . ':' . (string) ( $row['failure_code'] ?? '' ) );
		}

		$active = in_array( $status, array( 'pending', 'submitting', 'retry_scheduled', 'outcome_unknown' ), true );
		if ( in_array( $kind, array( 'sale', 'refund' ), true ) && $active && $age >= self::FISCAL_DELAY ) {
			$incident = 'refund' === $kind ? 'refund_fiscalization_delayed' : 'fiscalization_delayed';
			$message  = 'refund' === $kind
				? 'A BokaPOS fiscal refund has not completed within ten minutes.'
				: 'A BokaPOS sale fiscalization has not completed within ten minutes.';
			return self::observation( $row, $incident, 'warning', $message, $status . ':delayed' );
		}

		if ( 'delivery' === $kind && in_array( $status, array( 'queued', 'pending', 'submitting', 'retry_scheduled' ), true ) && $age >= self::DELIVERY_DELAY ) {
			return self::observation(
				$row,
				'receipt_delivery_delayed',
				'warning',
				'A BokaPOS fiscal document e-mail has remained unfinished for more than thirty minutes.',
				$status . ':delayed'
			);
		}

		return null;
	}

	/** Build all current observations from the provider journal and WooCommerce gaps. */
	private static function collect_observations( int $monitoring_started ): array {
		$rows = self::load_operations( $monitoring_started );
		if ( is_wp_error( $rows ) ) {
			return array(
				'system:bokapos-journal' => array(
					'incident'  => 'bokapos_journal_unavailable',
					'level'     => 'error',
					'message'   => 'The BokaPOS local fiscal journal is unavailable.',
					'signature' => 'missing-table',
					'context'   => array( 'component' => 'bokapos_operations' ),
				),
			);
		}

		$observations = array();
		$now          = time();
		foreach ( $rows as $row ) {
			$key = 'operation:' . absint( $row['id'] ?? 0 );
			$observations[ $key ] = self::classify_operation( $row, $now ) ?? self::recovered_observation( $row );
		}

		self::append_missing_sale_observations( $observations, $rows, $monitoring_started, $now );
		self::append_missing_delivery_observations( $observations, $rows, $monitoring_started, $now );
		return $observations;
	}

	/** Read bounded, non-sensitive columns from the BokaPOS local journal. */
	private static function load_operations( int $monitoring_started ) {
		global $wpdb;
		$table = $wpdb->prefix . self::BOKAPOS_TABLE;
		if ( (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return new \WP_Error( 'hse_bokapos_table_missing' );
		}

		$since = gmdate( 'Y-m-d H:i:s', max( 0, $monitoring_started - self::FISCAL_DELAY ) );
		$sql   = "SELECT id, order_id, refund_id, kind, environment, idempotency_key, status, remote_status, document_id, failure_code, attempts, attention, created_at, updated_at FROM {$table} WHERE updated_at >= %s ORDER BY id DESC LIMIT 1000";
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $since ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return is_array( $rows ) ? $rows : array();
	}

	/** Detect completed orders and expected refunds for which no fiscal operation exists. */
	private static function append_missing_sale_observations( array &$observations, array $rows, int $monitoring_started, int $now ): void {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$by_order  = array();
		$by_refund = array();
		foreach ( $rows as $row ) {
			$by_order[ absint( $row['order_id'] ?? 0 ) ][] = $row;
			$refund_id = absint( $row['refund_id'] ?? 0 );
			if ( $refund_id > 0 ) {
				$by_refund[ $refund_id ][] = $row;
			}
		}

		$orders = wc_get_orders(
			array(
				'status'        => array( 'completed', 'refunded' ),
				'limit'         => 200,
				'orderby'       => 'date',
				'order'         => 'DESC',
				'date_modified' => '>' . max( 0, $monitoring_started - 1 ),
			)
		);

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
				continue;
			}
			$order_id       = absint( $order->get_id() );
			$date_completed = method_exists( $order, 'get_date_completed' ) ? $order->get_date_completed() : null;
			$completed_at   = is_object( $date_completed ) && method_exists( $date_completed, 'getTimestamp' ) ? (int) $date_completed->getTimestamp() : 0;
			if ( $completed_at >= $monitoring_started && $now - $completed_at >= self::FISCAL_DELAY ) {
				$sale_ok = 'fiscalized' === sanitize_key( (string) $order->get_meta( self::FISCAL_STATUS_META, true ) )
					&& '' !== trim( (string) $order->get_meta( self::DOCUMENT_ID_META, true ) );
				if ( ! $sale_ok ) {
					$sale_ok = self::has_successful_operation( $by_order[ $order_id ] ?? array(), 'sale' );
				}

				$key = 'order:' . $order_id . ':sale';
				$observations[ $key ] = $sale_ok
					? self::order_recovered_observation( $order_id )
					: array(
						'incident'  => 'completed_order_not_fiscalized',
						'level'     => 'error',
						'message'   => 'A completed WooCommerce order has no confirmed BokaPOS sale receipt after ten minutes.',
						'signature' => 'missing-sale',
						'context'   => self::order_context( $order_id ),
					);
			}

			if ( ! method_exists( $order, 'get_refunds' ) ) {
				continue;
			}
			foreach ( $order->get_refunds() as $refund ) {
				if ( ! is_object( $refund ) || ! method_exists( $refund, 'get_id' ) ) {
					continue;
				}
				$refund_id = absint( $refund->get_id() );
				$status    = sanitize_key( (string) $refund->get_meta( self::FISCAL_STATUS_META, true ) );
				$created   = method_exists( $refund, 'get_date_created' ) ? $refund->get_date_created() : null;
				$created_at = is_object( $created ) && method_exists( $created, 'getTimestamp' ) ? (int) $created->getTimestamp() : 0;
				if ( '' === $status || $created_at < $monitoring_started || $now - $created_at < self::FISCAL_DELAY ) {
					continue;
				}

				$refund_rows = $by_refund[ $refund_id ] ?? array();
				if ( $refund_rows ) {
					continue;
				}
				$key = 'refund:' . $refund_id . ':fiscal';
				$observations[ $key ] = in_array( $status, array( 'fiscalized', 'completed' ), true )
					? self::order_recovered_observation( $order_id, $refund_id )
					: array(
						'incident'  => 'refund_fiscalization_missing',
						'level'     => 'error',
						'message'   => 'A WooCommerce refund marked for BokaPOS has no fiscal operation after ten minutes.',
						'signature' => $status . ':missing-refund-operation',
						'context'   => self::order_context( $order_id, $refund_id ),
					);
			}
		}
	}

	/** Detect a fiscalized document for which the configured BokaPOS e-mail job never appeared. */
	private static function append_missing_delivery_observations( array &$observations, array $rows, int $monitoring_started, int $now ): void {
		$settings = get_option( 'bokapos_settings', array() );
		$delivery = is_array( $settings ) && is_array( $settings['delivery'] ?? null ) ? $settings['delivery'] : array();
		if ( empty( $delivery['bokapos_email'] ) ) {
			return;
		}

		$deliveries = array();
		foreach ( $rows as $row ) {
			if ( 'delivery' === sanitize_key( (string) ( $row['kind'] ?? '' ) ) ) {
				$deliveries[] = $row;
			}
		}

		foreach ( $rows as $row ) {
			$kind = sanitize_key( (string) ( $row['kind'] ?? '' ) );
			if ( ! in_array( $kind, array( 'sale', 'refund' ), true )
				|| ! in_array( sanitize_key( (string) ( $row['status'] ?? '' ) ), array( 'fiscalized', 'completed' ), true )
				|| ( 'refund' === $kind && empty( $delivery['bokapos_email_refunds'] ) ) ) {
				continue;
			}

			$created_at = self::row_timestamp( (string) ( $row['created_at'] ?? '' ) );
			$document_id = trim( (string) ( $row['document_id'] ?? '' ) );
			if ( '' === $document_id || $created_at < $monitoring_started || $now - $created_at < self::FISCAL_DELAY ) {
				continue;
			}

			$discriminator = '-d' . substr( preg_replace( '/[^a-f0-9]/i', '', $document_id ) ?? '', 0, 8 );
			$has_delivery   = false;
			foreach ( $deliveries as $delivery_row ) {
				if ( absint( $delivery_row['order_id'] ?? 0 ) === absint( $row['order_id'] ?? 0 )
					&& false !== strpos( (string) ( $delivery_row['idempotency_key'] ?? '' ), $discriminator ) ) {
					$has_delivery = true;
					break;
				}
			}

			$key = 'document:' . absint( $row['id'] ?? 0 ) . ':delivery';
			$observations[ $key ] = $has_delivery
				? self::recovered_observation( $row )
				: array(
					'incident'  => 'receipt_delivery_missing',
					'level'     => 'warning',
					'message'   => 'A fiscal document exists, but no BokaPOS e-mail delivery operation was created within ten minutes.',
					'signature' => $kind . ':missing-delivery',
					'context'   => self::operation_context( $row ),
				);
		}
	}

	/** Return whether a set of journal rows contains a successful operation of a kind. */
	private static function has_successful_operation( array $rows, string $kind ): bool {
		foreach ( $rows as $row ) {
			if ( $kind === sanitize_key( (string) ( $row['kind'] ?? '' ) )
				&& in_array( sanitize_key( (string) ( $row['status'] ?? '' ) ), array( 'fiscalized', 'completed', 'delivered' ), true ) ) {
				return true;
			}
		}
		return false;
	}

	/** Standard journal observation with privacy-safe operational context. */
	private static function observation( array $row, string $incident, string $level, string $message, string $signature ): array {
		return array(
			'incident'  => $incident,
			'level'     => $level,
			'message'   => $message,
			'signature' => $signature,
			'context'   => self::operation_context( $row ),
		);
	}

	/** A healthy observation retains identifiers so a previous incident can recover. */
	private static function recovered_observation( array $row ): array {
		return array(
			'incident'  => '',
			'level'     => 'info',
			'message'   => '',
			'signature' => '',
			'context'   => self::operation_context( $row ),
		);
	}

	/** A healthy order/refund observation used for recovery. */
	private static function order_recovered_observation( int $order_id, int $refund_id = 0 ): array {
		return array(
			'incident'  => '',
			'level'     => 'info',
			'message'   => '',
			'signature' => '',
			'context'   => self::order_context( $order_id, $refund_id ),
		);
	}

	/** Whitelisted provider journal fields for alerts. */
	private static function operation_context( array $row ): array {
		$order_id = absint( $row['order_id'] ?? 0 );
		return array(
			'order_id'           => $order_id,
			'refund_id'          => absint( $row['refund_id'] ?? 0 ),
			'operation_id'       => absint( $row['id'] ?? 0 ),
			'operation_kind'     => sanitize_key( (string) ( $row['kind'] ?? '' ) ),
			'status'             => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'remote_status'      => sanitize_key( (string) ( $row['remote_status'] ?? '' ) ),
			'failure_code'       => sanitize_key( (string) ( $row['failure_code'] ?? '' ) ),
			'attempts'           => absint( $row['attempts'] ?? 0 ),
			'fiscal_environment' => sanitize_key( (string) ( $row['environment'] ?? '' ) ),
			'admin_url'          => self::order_admin_url( $order_id ),
		);
	}

	/** Whitelisted WooCommerce identifiers for a missing-operation alert. */
	private static function order_context( int $order_id, int $refund_id = 0 ): array {
		return array(
			'order_id'  => $order_id,
			'refund_id' => $refund_id,
			'admin_url' => self::order_admin_url( $order_id ),
		);
	}

	/** HPOS order editor URL without customer data. */
	private static function order_admin_url( int $order_id ): string {
		return admin_url( 'admin.php?page=wc-orders&action=edit&id=' . absint( $order_id ) );
	}

	/** Age in seconds using the UTC journal timestamp. */
	private static function row_age( array $row, int $now ): int {
		$timestamp = self::row_timestamp( (string) ( $row['updated_at'] ?? $row['created_at'] ?? '' ) );
		return $timestamp > 0 ? max( 0, $now - $timestamp ) : PHP_INT_MAX;
	}

	/** Convert the BokaPOS journal's UTC MySQL timestamp to Unix time. */
	private static function row_timestamp( string $value ): int {
		if ( '' === trim( $value ) ) {
			return 0;
		}
		$timestamp = strtotime( $value . ' UTC' );
		return false === $timestamp ? 0 : $timestamp;
	}

	/** Bound durable idempotency markers without retaining business/customer payloads. */
	private static function prune_state( array $state ): array {
		$cutoff = time() - 90 * DAY_IN_SECONDS;
		foreach ( $state as $key => $entry ) {
			if ( ! is_array( $entry ) || (int) ( $entry['alerted_at'] ?? 0 ) < $cutoff ) {
				unset( $state[ $key ] );
			}
		}
		if ( count( $state ) > 1000 ) {
			uasort(
				$state,
				static fn( $left, $right ) => (int) ( $right['alerted_at'] ?? 0 ) <=> (int) ( $left['alerted_at'] ?? 0 )
			);
			$state = array_slice( $state, 0, 1000, true );
		}
		return $state;
	}

	/** Atomic run lock with stale-lock recovery. */
	private static function acquire_lock(): bool {
		$now = time();
		if ( add_option( self::LOCK_OPTION, $now, '', false ) ) {
			return true;
		}
		$locked_at = (int) get_option( self::LOCK_OPTION, 0 );
		if ( $locked_at > 0 && $locked_at >= $now - self::LOCK_TTL ) {
			return false;
		}
		delete_option( self::LOCK_OPTION );
		return add_option( self::LOCK_OPTION, $now, '', false );
	}

	/** Release the current watchdog run lock. */
	private static function release_lock(): void {
		delete_option( self::LOCK_OPTION );
	}

	/** Show administrators when incidents exist or the watchdog stopped running. */
	public static function render_admin_notice(): void {
		if ( ! CommerceConfiguration::is_enabled() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$last = get_option( self::LAST_RUN_OPTION, array() );
		if ( ! is_array( $last ) || empty( $last['checked_at'] ) ) {
			return;
		}

		$age       = time() - (int) $last['checked_at'];
		$incidents = absint( $last['incident_count'] ?? 0 );
		$heartbeat_ok = ! array_key_exists( 'heartbeat', $last ) || ! empty( $last['heartbeat'] );
		if ( ! empty( $last['ok'] ) && $heartbeat_ok && $age < 15 * MINUTE_IN_SECONDS && 0 === $incidents ) {
			return;
		}

		$class = empty( $last['ok'] ) || $age >= 15 * MINUTE_IN_SECONDS ? 'notice notice-error' : 'notice notice-warning';
		if ( $age >= 15 * MINUTE_IN_SECONDS ) {
			$message = __( 'HSE fiscal monitoring has not completed in the last 15 minutes. Check the server cron and WooCommerce Scheduled Actions.', 'hse-headless' );
		} elseif ( ! $heartbeat_ok ) {
			$message = __( 'Fiscal incident monitoring is active, but Sentry Cron Monitoring has not accepted its heartbeat. Enable Crons for the configured Sentry project.', 'hse-headless' );
		} else {
			$message = sprintf( /* translators: %d: number of current incidents */ __( 'HSE fiscal monitoring found %d operation(s) that need attention.', 'hse-headless' ), $incidents );
		}
		$link = admin_url( 'admin.php?page=bokapos-journal&attention=1' );
		printf(
			'<div class="%1$s"><p><strong>%2$s</strong> <a href="%3$s">%4$s</a></p></div>',
			esc_attr( $class ),
			esc_html( $message ),
			esc_url( $link ),
			esc_html__( 'Open the BokaPOS fiscal journal.', 'hse-headless' )
		);
	}
}
