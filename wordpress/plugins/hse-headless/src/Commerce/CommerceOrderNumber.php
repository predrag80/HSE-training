<?php
/**
 * Public order-number sequence for customer-facing WooCommerce references.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Assigns a stable HSE-prefixed number without changing WooCommerce order IDs. */
final class CommerceOrderNumber {
	public const ORDER_META     = '_hse_public_order_number';
	public const SEQUENCE_META  = '_hse_public_order_sequence';
	public const COUNTER_OPTION = 'hse_public_order_number_counter';

	/** Register assignment and display hooks. */
	public static function register_hooks(): void {
		add_action( 'woocommerce_new_order', array( self::class, 'assign' ), 20, 2 );
		add_filter( 'woocommerce_order_number', array( self::class, 'filter' ), 20, 2 );
	}

	/** Assign the next public number once, when WooCommerce creates an order. */
	public static function assign( $order_id, $order = null ): void {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return;
		}

		if ( ! is_object( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! self::is_order( $order ) || '' !== (string) $order->get_meta( self::ORDER_META, true ) ) {
			return;
		}

		$sequence = self::next_sequence();
		if ( $sequence < 1 ) {
			return;
		}

		$order->update_meta_data( self::SEQUENCE_META, $sequence );
		$order->update_meta_data( self::ORDER_META, self::format( $sequence ) );
		$order->save_meta_data();
	}

	/** Return the immutable customer-facing number when one was assigned. */
	public static function filter( $order_number, $order ) {
		if ( ! self::is_order( $order ) ) {
			return $order_number;
		}

		$public_number = (string) $order->get_meta( self::ORDER_META, true );
		return self::is_valid( $public_number ) ? $public_number : $order_number;
	}

	/** Format one positive sequence value for checkout, email, and administration. */
	public static function format( int $sequence ): string {
		return sprintf( 'HSE-%06d', max( 1, $sequence ) );
	}

	/** Validate a stored public number before displaying it. */
	private static function is_valid( string $number ): bool {
		return 1 === preg_match( '/^HSE-[0-9]{6,}$/', $number );
	}

	/** Check only for the small WC order interface used by this integration. */
	private static function is_order( $order ): bool {
		return is_object( $order )
			&& method_exists( $order, 'get_meta' )
			&& method_exists( $order, 'update_meta_data' )
			&& method_exists( $order, 'save_meta_data' );
	}

	/**
	 * Atomically allocate the next number across simultaneous checkouts.
	 *
	 * The first order creates the counter at 1. Later orders use MySQL's
	 * connection-local LAST_INSERT_ID expression so concurrent requests cannot
	 * receive the same sequence value.
	 */
	private static function next_sequence(): int {
		if ( add_option( self::COUNTER_OPTION, 1, '', 'no' ) ) {
			return 1;
		}

		global $wpdb;
		if ( ! isset( $wpdb->options ) ) {
			return 0;
		}

		$query = $wpdb->prepare(
			"UPDATE {$wpdb->options}
			SET option_value = LAST_INSERT_ID(GREATEST(CAST(option_value AS UNSIGNED), 0) + 1)
			WHERE option_name = %s",
			self::COUNTER_OPTION
		);
		if ( 1 !== (int) $wpdb->query( $query ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return 0;
		}

		wp_cache_delete( self::COUNTER_OPTION, 'options' );
		return max( 0, (int) $wpdb->get_var( 'SELECT LAST_INSERT_ID()' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
