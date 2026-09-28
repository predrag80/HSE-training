<?php
/**
 * Public-site source handoff for the shared WooCommerce checkout.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Keeps the initiating Astro environment attached to the resulting order. */
final class CommerceCheckoutSource {
	public const QUERY_VAR   = 'hse_source';
	public const SESSION_KEY = 'hse_checkout_source';
	public const ORDER_META  = '_hse_checkout_source';

	/** Register order persistence. */
	public static function register_hooks(): void {
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'persist_order_source' ), 10, 1 );
	}

	/** Accept only the public environments that share this checkout. */
	public static function sanitize( $value ): string {
		return 'dev' === strtolower( is_string( $value ) ? trim( $value ) : '' ) ? 'dev' : 'staging';
	}

	/** Save the initiating environment in the WooCommerce session. */
	public static function capture( $value ): string {
		$source = self::sanitize( $value );

		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( self::SESSION_KEY, $source );
		}

		return $source;
	}

	/** Return the checkout source currently stored in the WooCommerce session. */
	public static function current(): string {
		if ( function_exists( 'WC' ) && WC()->session ) {
			return self::sanitize( WC()->session->get( self::SESSION_KEY ) );
		}

		return 'staging';
	}

	/** Attach the source to the order for deterministic email routing. */
	public static function persist_order_source( $order ): void {
		if ( is_object( $order ) && method_exists( $order, 'update_meta_data' ) ) {
			$order->update_meta_data( self::ORDER_META, self::current() );
		}
	}

	/** Read the immutable source stored on an order. */
	public static function for_order( $order ): string {
		if ( is_object( $order ) && method_exists( $order, 'get_meta' ) ) {
			return self::sanitize( $order->get_meta( self::ORDER_META, true ) );
		}

		return 'staging';
	}
}
