<?php
/**
 * Payment-driven WooCommerce order lifecycle rules.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Keeps paid card orders and offline bank-transfer orders on distinct paths. */
final class CommerceOrderLifecycle {
	public const CARD_GATEWAY = 'raiaccept';

	/** Register the paid-order status rule. */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_payment_complete_order_status', array( self::class, 'payment_complete_status' ), 20, 3 );
		add_action( 'woocommerce_order_status_processing', array( self::class, 'complete_processing_card_order' ), 999, 2 );
	}

	/**
	 * Complete a verified RaiAccept payment immediately when it contains only
	 * virtual Course products synchronized by this plugin.
	 *
	 * Direct bank transfer never reaches this branch: WooCommerce keeps it on
	 * hold until the merchant has independently confirmed that funds arrived.
	 */
	public static function payment_complete_status( $status, $order_id, $order = null ): string {
		$status = is_scalar( $status ) ? sanitize_key( (string) $status ) : '';
		if ( ! CommerceConfiguration::is_enabled() ) {
			return $status;
		}

		$order = self::resolve_order( $order_id, $order );
		return self::is_course_card_order( $order ) ? 'completed' : $status;
	}

	/**
	 * Some RaiAccept versions persist `processing` directly instead of asking
	 * WooCommerce for its payment-complete status. Finish that verified paid
	 * transition after other processing-status listeners (including fiscal
	 * integrations) have received the event.
	 */
	public static function complete_processing_card_order( $order_id, $order = null ): void {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return;
		}

		$order = self::resolve_order( $order_id, $order );
		if ( ! self::is_course_card_order( $order )
			|| ! method_exists( $order, 'get_status' )
			|| 'processing' !== sanitize_key( (string) $order->get_status() )
			|| ! method_exists( $order, 'is_paid' )
			|| ! $order->is_paid()
			|| ! method_exists( $order, 'update_status' ) ) {
			return;
		}

		$order->update_status(
			'completed',
			__( 'Verified RaiAccept card payment automatically completed for immediate digital course delivery.', 'hse-headless' ),
			true
		);
	}

	/** Resolve a callback order without trusting the supplied object. */
	private static function resolve_order( $order_id, $order = null ) {
		if ( ! is_object( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( absint( $order_id ) );
		}

		return is_object( $order ) ? $order : null;
	}

	/** Return whether an order contains only plugin-synchronized virtual Courses paid by card. */
	private static function is_course_card_order( $order ): bool {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_payment_method' ) || ! method_exists( $order, 'get_items' ) ) {
			return false;
		}

		if ( self::CARD_GATEWAY !== sanitize_key( (string) $order->get_payment_method() ) ) {
			return false;
		}

		$items = $order->get_items( 'line_item' );
		if ( ! is_iterable( $items ) ) {
			return false;
		}

		$has_course = false;
		foreach ( $items as $item ) {
			$product = is_object( $item ) && method_exists( $item, 'get_product' ) ? $item->get_product() : null;
			if ( ! is_object( $product )
				|| ! method_exists( $product, 'get_id' )
				|| ! method_exists( $product, 'is_virtual' )
				|| ! $product->is_virtual()
				|| '1' !== (string) get_post_meta( (int) $product->get_id(), CommerceProductSync::SYNCED_META, true ) ) {
				return false;
			}
			$has_course = true;
		}

		return $has_course;
	}
}
