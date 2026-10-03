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
		add_action( 'template_redirect', array( self::class, 'bootstrap_checkout_source' ), -11 );
		add_action( 'wp_loaded', array( self::class, 'bootstrap_checkout_ajax_source' ), 19 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'persist_order_source' ), 10, 1 );
	}

	/** Accept only the public environments that share this checkout. */
	public static function sanitize( $value ): string {
		$value = strtolower( is_string( $value ) ? trim( $value ) : '' );
		return in_array( $value, array( 'dev', 'staging', 'production' ), true ) ? $value : 'staging';
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

	/** Resolve the source before checkout, retry, and confirmation HTML is rendered. */
	public static function bootstrap_checkout_source(): void {
		if ( ! CommerceConfiguration::is_enabled() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		self::capture( self::resolve_request_source() );
	}

	/** Restore the source before WooCommerce renders AJAX checkout fragments. */
	public static function bootstrap_checkout_ajax_source(): void {
		if ( ! CommerceConfiguration::is_enabled() || ! CommerceLocale::is_checkout_ajax() ) {
			return;
		}

		self::capture( self::resolve_request_source() );
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
			$source = (string) $order->get_meta( self::ORDER_META, true );
			if ( in_array( $source, array( 'dev', 'staging', 'production' ), true ) ) {
				return $source;
			}
		}

		return self::current();
	}

	/** Resolve explicit query, verified order return, then the Woo session. */
	private static function resolve_request_source(): string {
		if ( isset( $_GET[ self::QUERY_VAR ] ) ) {
			$value = sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
			if ( in_array( $value, array( 'dev', 'staging', 'production' ), true ) ) {
				return $value;
			}
		}

		$order_source = self::order_return_source();
		if ( '' !== $order_source ) {
			return $order_source;
		}

		if ( function_exists( 'WC' ) && WC()->session ) {
			$session_source = WC()->session->get( self::SESSION_KEY );
			if ( in_array( $session_source, array( 'dev', 'staging', 'production' ), true ) ) {
				return $session_source;
			}
		}

		return 'staging';
	}

	/** Read source only from a valid order-received URL carrying the matching key. */
	private static function order_return_source(): string {
		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() || ! function_exists( 'wc_get_order' ) ) {
			return '';
		}

		$order_id  = absint( get_query_var( 'order-received' ) );
		$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		$order     = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
			return '';
		}

		$source = (string) $order->get_meta( self::ORDER_META, true );
		return in_array( $source, array( 'dev', 'staging', 'production' ), true ) ? $source : '';
	}
}
