<?php
/**
 * Update-safe compatibility for BokaPOS fiscal-receipt e-mail delivery.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Keeps the official BokaPOS plugin reliable in background execution contexts. */
final class CommerceBokaPosEmailCompatibility {
	public const WATCHDOG_HOOK = 'hse/bokapos_receipt_email_watchdog';

	private const WATCHDOG_GROUP = 'hse-commerce';
	private const WATCHDOG_DELAY = 120;
	private const FISCAL_STATUS_META = '_bokapos_status';
	private const DOCUMENT_ID_META = '_bokapos_document_id';
	private const DELIVERY_META = '_hse_customer_email_bokapos_receipt_sent_at';
	private const RETRY_META = '_hse_bokapos_receipt_email_retry_attempted_at';

	/** Register compatibility hooks without modifying the provider plugin. */
	public static function register_hooks(): void {
		/* Run before WooCommerce maps the provider event to its e-mail notification. */
		add_action( 'bokapos_order_fiscalized', array( self::class, 'prepare_receipt_email' ), 1, 3 );
		add_action( self::WATCHDOG_HOOK, array( self::class, 'retry_missing_receipt_email' ), 10, 1 );
	}

	/** Load the WordPress file helper required by the provider's PDF attachment code. */
	public static function ensure_wordpress_file_helpers(): bool {
		if ( function_exists( 'wp_tempnam' ) ) {
			return true;
		}

		if ( ! defined( 'ABSPATH' ) ) {
			return false;
		}

		$file_api = ABSPATH . 'wp-admin/includes/file.php';
		if ( is_readable( $file_api ) ) {
			require_once $file_api;
		}

		return function_exists( 'wp_tempnam' );
	}

	/** Prepare the synchronous provider e-mail and schedule one delivery check. */
	public static function prepare_receipt_email( $order, $operation = array(), $document = array() ): void {
		unset( $operation, $document );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return;
		}

		self::ensure_wordpress_file_helpers();

		$order_id = absint( $order->get_id() );
		if ( $order_id < 1 ) {
			return;
		}

		$args = array( $order_id );
		if ( function_exists( 'as_schedule_single_action' ) ) {
			$scheduled = function_exists( 'as_has_scheduled_action' )
				? as_has_scheduled_action( self::WATCHDOG_HOOK, $args, self::WATCHDOG_GROUP )
				: false;
			if ( false === $scheduled ) {
				as_schedule_single_action(
					time() + self::WATCHDOG_DELAY,
					self::WATCHDOG_HOOK,
					$args,
					self::WATCHDOG_GROUP,
					true
				);
			}
			return;
		}

		if ( ! wp_next_scheduled( self::WATCHDOG_HOOK, $args ) ) {
			wp_schedule_single_event( time() + self::WATCHDOG_DELAY, self::WATCHDOG_HOOK, $args );
		}
	}

	/** Retry a missing WooCommerce BokaPOS receipt notification exactly once. */
	public static function retry_missing_receipt_email( $order_id ): void {
		if ( ! CommerceConfiguration::is_enabled() || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( absint( $order_id ) );
		if ( ! self::is_order( $order )
			|| 'fiscalized' !== sanitize_key( (string) $order->get_meta( self::FISCAL_STATUS_META, true ) )
			|| '' === trim( (string) $order->get_meta( self::DOCUMENT_ID_META, true ) )
			|| '' !== trim( (string) $order->get_meta( self::DELIVERY_META, true ) )
			|| '' !== trim( (string) $order->get_meta( self::RETRY_META, true ) ) ) {
			return;
		}

		self::ensure_wordpress_file_helpers();

		if ( ! function_exists( 'WC' ) || ! is_object( WC() ) || ! method_exists( WC(), 'mailer' ) ) {
			self::log_warning( 'BokaPOS receipt e-mail watchdog could not initialize the WooCommerce mailer.', $order->get_id() );
			return;
		}

		$mailer = WC()->mailer();
		$emails = is_object( $mailer ) && method_exists( $mailer, 'get_emails' ) ? $mailer->get_emails() : array();
		$email  = is_array( $emails ) && isset( $emails['WC_Email_BokaPOS_Receipt'] ) ? $emails['WC_Email_BokaPOS_Receipt'] : null;
		if ( ! is_object( $email ) || ! method_exists( $email, 'trigger' ) ) {
			self::log_warning( 'BokaPOS receipt e-mail watchdog could not find the provider e-mail class.', $order->get_id() );
			return;
		}

		$order->update_meta_data( self::RETRY_META, gmdate( 'c' ) );
		$order->save();

		try {
			$sent = (bool) $email->trigger( $order, array(), array(), false );
		} catch ( \Throwable $error ) {
			self::log_warning( 'BokaPOS receipt e-mail safety retry failed: ' . $error->getMessage(), $order->get_id() );
			return;
		}

		if ( ! $sent ) {
			self::log_warning( 'BokaPOS receipt e-mail safety retry did not report a successful delivery.', $order->get_id() );
		}
	}

	/** Write bounded operational context without customer or receipt data. */
	private static function log_warning( string $message, int $order_id ): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->warning(
				$message,
				array(
					'source'   => 'hse-bokapos-email',
					'order_id' => $order_id,
				)
			);
		}
	}

	/** Test the minimum order interface needed by this compatibility layer. */
	private static function is_order( $order ): bool {
		return is_object( $order )
			&& method_exists( $order, 'get_id' )
			&& method_exists( $order, 'get_meta' )
			&& method_exists( $order, 'update_meta_data' )
			&& method_exists( $order, 'save' );
	}
}
