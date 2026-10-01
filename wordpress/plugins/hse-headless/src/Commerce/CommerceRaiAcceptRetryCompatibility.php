<?php
/**
 * Update-safe RaiAccept retry-payment compatibility.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Ensures a failed order starts a fresh RaiAccept order and payment session. */
final class CommerceRaiAcceptRetryCompatibility {
	public const ACTIVE_REFERENCE_META   = '_hse_raiaccept_active_retry_reference';
	public const PREVIOUS_ORDER_IDS_META = '_hse_raiaccept_previous_order_ids';
	public const RETRY_REFERENCES_META   = '_hse_raiaccept_retry_references';
	public const RETRY_COUNT_META        = '_hse_raiaccept_retry_count';
	public const LAST_RETRY_META         = '_hse_raiaccept_last_retry_at';

	private const GATEWAY_ID        = 'raiaccept';
	private const RETURN_URL_META   = 'raiaccept_return_url';
	private const IFRAME_VISIT_META = 'raiaccept_iframe_visited';

	/** Register the gateway replacement and retry reset. */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_payment_gateways', array( self::class, 'replace_gateway_class' ), 100 );
		add_action( 'woocommerce_before_pay_action', array( self::class, 'prepare_fresh_payment_attempt' ), 5 );
	}

	/**
	 * Replace only the checkout gateway implementation with an update-safe child.
	 *
	 * The official gateway class remains untouched. Its inherited payment flow is
	 * used in full; the child changes only the merchant reference for a retry.
	 */
	public static function replace_gateway_class( array $gateways ): array {
		if ( ! CommerceConfiguration::is_enabled() || ! class_exists( '\RaiAccept_Gateway' ) ) {
			return $gateways;
		}

		$gateway_file = __DIR__ . '/CommerceRaiAcceptRetryGateway.php';
		if ( ! class_exists( CommerceRaiAcceptRetryGateway::class, false ) && is_readable( $gateway_file ) ) {
			require_once $gateway_file;
		}

		if ( ! class_exists( CommerceRaiAcceptRetryGateway::class, false ) ) {
			return $gateways;
		}

		foreach ( $gateways as $index => $gateway ) {
			if ( 'RaiAccept_Gateway' === $gateway || '\RaiAccept_Gateway' === $gateway ) {
				$gateways[ $index ] = CommerceRaiAcceptRetryGateway::class;
			}
		}

		return $gateways;
	}

	/** Prepare a failed RaiAccept order immediately before WooCommerce retries it. */
	public static function prepare_fresh_payment_attempt( $order ): void {
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_retryable_order( $order, self::posted_payment_method() ) ) {
			return;
		}

		self::record_retry_reference(
			$order,
			trim( (string) $order->get_transaction_id() ),
			self::build_retry_reference( absint( $order->get_id() ) )
		);
	}

	/** Build a provider-safe unique merchant reference for every retry request. */
	public static function build_retry_reference( int $order_id ): string {
		$nonce = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( '', true );
		$nonce = substr( preg_replace( '/[^a-zA-Z0-9]/', '', (string) $nonce ), 0, 10 );
		return $order_id . '-retry-' . gmdate( 'YmdHis' ) . '-' . $nonce;
	}

	/**
	 * Store one retry reference and clear only the inactive provider linkage.
	 *
	 * The official gateway will then create both the provider order and payment
	 * session with the same reference through its normal process_payment flow.
	 */
	public static function record_retry_reference( $order, string $previous_order_id, string $reference ): void {
		$order_ids  = self::history( $order->get_meta( self::PREVIOUS_ORDER_IDS_META, true ) );
		$references = self::history( $order->get_meta( self::RETRY_REFERENCES_META, true ) );
		if ( '' !== $previous_order_id && ! in_array( $previous_order_id, $order_ids, true ) ) {
			$order_ids[] = $previous_order_id;
		}
		if ( ! in_array( $reference, $references, true ) ) {
			$references[] = $reference;
		}

		$order->update_meta_data( self::ACTIVE_REFERENCE_META, $reference );
		$order->update_meta_data( self::PREVIOUS_ORDER_IDS_META, $order_ids );
		$order->update_meta_data( self::RETRY_REFERENCES_META, $references );
		$order->update_meta_data( self::RETRY_COUNT_META, absint( $order->get_meta( self::RETRY_COUNT_META, true ) ) + 1 );
		$order->update_meta_data( self::LAST_RETRY_META, gmdate( 'c' ) );
		$order->delete_meta_data( self::RETURN_URL_META );
		$order->delete_meta_data( self::IFRAME_VISIT_META );
		$order->set_transaction_id( '' );
		$order->add_order_note( 'HSE retry: prepared a fresh RaiAccept attempt with a unique merchant reference.' );
		$order->save();
	}

	/** Normalize an order-level audit-history value. */
	private static function history( $value ): array {
		return is_array( $value ) ? array_values( array_filter( array_map( 'strval', $value ) ) ) : array();
	}

	/** Return whether this submitted order-pay request needs a fresh provider order. */
	public static function is_retryable_order( $order, string $payment_method ): bool {
		return self::GATEWAY_ID === $payment_method
			&& is_object( $order )
			&& method_exists( $order, 'get_id' )
			&& method_exists( $order, 'get_status' )
			&& method_exists( $order, 'get_transaction_id' )
			&& method_exists( $order, 'get_meta' )
			&& method_exists( $order, 'update_meta_data' )
			&& method_exists( $order, 'delete_meta_data' )
			&& method_exists( $order, 'set_transaction_id' )
			&& method_exists( $order, 'add_order_note' )
			&& method_exists( $order, 'save' )
			&& 'failed' === $order->get_status();
	}

	/** Read only WooCommerce's nonce-protected payment-method field. */
	private static function posted_payment_method(): string {
		return isset( $_POST['payment_method'] )
			? sanitize_key( wp_unslash( $_POST['payment_method'] ) )
			: '';
	}
}
