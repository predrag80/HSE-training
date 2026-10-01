<?php
/**
 * RaiAccept retry gateway override.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

use HSETraining\Headless\Infrastructure\SentryReporter;

defined( 'ABSPATH' ) || exit;

/** Uses a fresh merchant reference while retaining the official gateway flow. */
final class CommerceRaiAcceptRetryGateway extends \RaiAccept_Gateway {
	/** Report exceptional provider/API failures while preserving the official gateway outcome. */
	public function process_payment( $order_id ) {
		try {
			return parent::process_payment( $order_id );
		} catch ( \Throwable $error ) {
			$order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( $order_id ) ) : null;
			$reference = is_object( $order ) && method_exists( $order, 'get_meta' )
				? trim( (string) $order->get_meta( CommerceRaiAcceptRetryCompatibility::ACTIVE_REFERENCE_META, true ) )
				: '';

			SentryReporter::capture(
				'raiaccept_payment_request_failed',
				'error',
				'RaiAccept could not create or continue a hosted card-payment request.',
				array(
					'component'       => 'raiaccept_gateway',
					'fingerprint_key' => 'payment_request',
					'order_id'        => absint( $order_id ),
					'checkout_source' => CommerceCheckoutSource::for_order( $order ),
					'failure_type'    => get_class( $error ),
				),
				'raiaccept-payment-request:' . absint( $order_id ) . ':' . sanitize_text_field( $reference )
			);

			throw $error;
		}
	}

	/** Map the same retry reference into provider order and session payloads. */
	public function map_merchant_order_reference( $order ) {
		$reference = is_object( $order ) && method_exists( $order, 'get_meta' )
			? trim( (string) $order->get_meta( CommerceRaiAcceptRetryCompatibility::ACTIVE_REFERENCE_META, true ) )
			: '';

		return '' !== $reference ? $reference : parent::map_merchant_order_reference( $order );
	}
}
