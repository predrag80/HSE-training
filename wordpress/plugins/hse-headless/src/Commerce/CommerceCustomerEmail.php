<?php
/**
 * Customer payment notifications and minimal delivery evidence.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Ensures every terminal payment outcome can notify the customer and be audited. */
final class CommerceCustomerEmail {
	private const CUSTOMER_ORDER_EMAILS = array(
		'customer_processing_order',
		'customer_on_hold_order',
		'customer_completed_order',
		'customer_failed_order',
		'customer_cancelled_order',
		'customer_refunded_order',
	);

	/** Register WooCommerce email hooks. */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_email_enabled_customer_cancelled_order', array( self::class, 'enable_cancelled_order_email' ), 20, 3 );
		add_action( 'woocommerce_email_sent', array( self::class, 'record_email_delivery' ), 20, 3 );
	}

	/** The bank requires an electronic confirmation for an unsuccessful outcome too. */
	public static function enable_cancelled_order_email( $enabled, $order = null, $email = null ): bool {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? true : (bool) $enabled;
	}

	/** Store only the notification type and UTC time after the mailer reports success. */
	public static function record_email_delivery( $sent, $email_id, $email ): void {
		$email_id = sanitize_key( is_scalar( $email_id ) ? (string) $email_id : '' );
		if ( true !== $sent || ! in_array( $email_id, self::CUSTOMER_ORDER_EMAILS, true ) || ! is_object( $email ) || ! isset( $email->object ) ) {
			return;
		}

		$order = $email->object;
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) || ! method_exists( $order, 'save' ) ) {
			return;
		}

		$timestamp = gmdate( 'c' );
		$order->update_meta_data( '_hse_customer_email_' . $email_id . '_sent_at', $timestamp );
		if ( method_exists( $order, 'add_order_note' ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: WooCommerce email identifier, 2: UTC timestamp. */
					__( 'Customer notification %1$s sent successfully at %2$s UTC.', 'hse-headless' ),
					$email_id,
					$timestamp
				),
				false
			);
		}
		$order->save();
	}
}
