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

		if ( ! is_object( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( absint( $order_id ) );
		}
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_payment_method' ) || ! method_exists( $order, 'get_items' ) ) {
			return $status;
		}

		if ( self::CARD_GATEWAY !== sanitize_key( (string) $order->get_payment_method() ) ) {
			return $status;
		}

		$items = $order->get_items( 'line_item' );
		if ( ! is_iterable( $items ) ) {
			return $status;
		}

		$has_course = false;
		foreach ( $items as $item ) {
			$product = is_object( $item ) && method_exists( $item, 'get_product' ) ? $item->get_product() : null;
			if ( ! is_object( $product )
				|| ! method_exists( $product, 'get_id' )
				|| ! method_exists( $product, 'is_virtual' )
				|| ! $product->is_virtual()
				|| '1' !== (string) get_post_meta( (int) $product->get_id(), CommerceProductSync::SYNCED_META, true ) ) {
				return $status;
			}
			$has_course = true;
		}

		return $has_course ? 'completed' : $status;
	}
}
