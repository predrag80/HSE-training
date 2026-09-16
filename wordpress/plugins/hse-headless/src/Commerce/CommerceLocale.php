<?php
/**
 * Locale handoff for the temporary WooCommerce checkout surface.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Keeps the selected public-site language across checkout and gateway returns. */
final class CommerceLocale {
	public const QUERY_VAR   = 'lang';
	public const SESSION_KEY = 'hse_checkout_locale';
	public const COOKIE_NAME = 'hse_checkout_locale';
	public const ORDER_META  = '_hse_checkout_locale';

	private static $current = 'en';

	/** Register checkout lifecycle hooks. */
	public static function register_hooks(): void {
		add_action( 'template_redirect', array( self::class, 'bootstrap_checkout_locale' ), -10 );
		add_action( 'wp_loaded', array( self::class, 'bootstrap_checkout_ajax_locale' ), 20 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'persist_order_locale' ), 10, 1 );
	}

	/** Accept only the two public languages. */
	public static function sanitize( $value ): string {
		return 'sr' === strtolower( is_string( $value ) ? trim( $value ) : '' ) ? 'sr' : 'en';
	}

	/** Return the locale resolved for the current checkout request. */
	public static function current(): string {
		return self::$current;
	}

	/** Save a trusted locale in the Woo session and an HTTP-only browser cookie. */
	public static function capture( $value ): string {
		$locale        = self::sanitize( $value );
		self::$current = $locale;

		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( self::SESSION_KEY, $locale );
		}

		if ( function_exists( 'wc_setcookie' ) && ! headers_sent() ) {
			wc_setcookie( self::COOKIE_NAME, $locale, time() + MONTH_IN_SECONDS, is_ssl(), true );
		}

		return $locale;
	}

	/** Resolve locale before checkout HTML and customer-facing strings are rendered. */
	public static function bootstrap_checkout_locale(): void {
		if ( ! CommerceConfiguration::is_enabled() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		self::capture( self::resolve_request_locale() );
	}

	/** Restore the selected language before Woo renders AJAX checkout fragments. */
	public static function bootstrap_checkout_ajax_locale(): void {
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_checkout_ajax() ) {
			return;
		}

		self::capture( self::resolve_request_locale() );
	}

	/** Report whether Woo is updating or submitting the classic checkout. */
	public static function is_checkout_ajax(): bool {
		if ( ! function_exists( 'wp_doing_ajax' ) || ! wp_doing_ajax() || ! isset( $_GET['wc-ajax'] ) ) {
			return false;
		}

		$action = sanitize_key( wp_unslash( $_GET['wc-ajax'] ) );
		return in_array( $action, array( 'update_order_review', 'checkout' ), true );
	}

	/** Attach the language to the order so the gateway return remains localized. */
	public static function persist_order_locale( $order ): void {
		if ( is_object( $order ) && method_exists( $order, 'update_meta_data' ) ) {
			$order->update_meta_data( self::ORDER_META, self::current() );
		}
	}

	/** Resolve explicit query, verified order return, Woo session, then trusted cookie. */
	private static function resolve_request_locale(): string {
		if ( isset( $_GET[ self::QUERY_VAR ] ) ) {
			$value = sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
			if ( in_array( $value, array( 'en', 'sr' ), true ) ) {
				return $value;
			}
		}

		$order_locale = self::order_return_locale();
		if ( '' !== $order_locale ) {
			return $order_locale;
		}

		if ( function_exists( 'WC' ) && WC()->session ) {
			$session_locale = WC()->session->get( self::SESSION_KEY );
			if ( in_array( $session_locale, array( 'en', 'sr' ), true ) ) {
				return $session_locale;
			}
		}

		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$cookie_locale = sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
			if ( in_array( $cookie_locale, array( 'en', 'sr' ), true ) ) {
				return $cookie_locale;
			}
		}

		return 'en';
	}

	/** Read locale only from a valid order-received URL carrying the matching order key. */
	private static function order_return_locale(): string {
		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() || ! function_exists( 'wc_get_order' ) ) {
			return '';
		}

		$order_id = absint( get_query_var( 'order-received' ) );
		$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		$order     = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
			return '';
		}

		$locale = (string) $order->get_meta( self::ORDER_META, true );
		return in_array( $locale, array( 'en', 'sr' ), true ) ? $locale : '';
	}
}
