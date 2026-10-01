<?php
/**
 * Privacy-bounded monitoring for exceptional checkout failures.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

use HSETraining\Headless\Infrastructure\SentryReporter;

defined( 'ABSPATH' ) || exit;

/** Reports exceptional order-creation failures without customer or payment data. */
final class CommerceCheckoutMonitoring {
	/** Register only failure-path hooks; normal checkout outcomes are not telemetry events. */
	public static function register_hooks(): void {
		add_action( 'woocommerce_checkout_order_exception', array( self::class, 'report_order_creation_failure' ), 100, 1 );
	}

	/** Report an order object discarded by WooCommerce after an exception. */
	public static function report_order_creation_failure( $order ): void {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return;
		}

		$order_id = is_object( $order ) && method_exists( $order, 'get_id' ) ? absint( $order->get_id() ) : 0;
		$gateway  = is_object( $order ) && method_exists( $order, 'get_payment_method' )
			? sanitize_key( (string) $order->get_payment_method() )
			: 'unknown';

		SentryReporter::capture(
			'checkout_order_creation_failed',
			'error',
			'WooCommerce discarded an order after an exceptional checkout failure.',
			array(
				'component'       => 'woocommerce_checkout',
				'fingerprint_key' => 'order_creation',
				'order_id'        => $order_id,
				'payment_gateway' => $gateway,
				'checkout_source' => CommerceCheckoutSource::for_order( $order ),
			),
			'checkout-order-creation:' . $order_id
		);
	}
}
