<?php
/**
 * Staging checkout bridge from an Astro Course CTA to WooCommerce.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Resolves course_key server-side, creates a one-course cart, and opens checkout. */
final class CommerceCheckout {
	public const QUERY_VAR = 'hse_course_checkout';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_filter( 'query_vars', array( self::class, 'register_query_var' ) );
		add_action( 'template_redirect', array( self::class, 'maybe_start_checkout' ), -20 );
	}

	/** Add the stable checkout key to WordPress public query variables. */
	public static function register_query_var( array $query_vars ): array {
		$query_vars[] = self::QUERY_VAR;
		return $query_vars;
	}

	/** Handle a top-level checkout initiation request before headless mode closes templates. */
	public static function maybe_start_checkout(): void {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return;
		}

		$course_key = get_query_var( self::QUERY_VAR );
		if ( ! is_string( $course_key ) || '' === $course_key ) {
			return;
		}

		nocache_headers();
		if ( ! CommerceRestController::validate_course_key( $course_key ) ) {
			self::stop( 400 );
		}

		$product = CommerceProductRepository::get_by_course_key( $course_key );
		if ( is_wp_error( $product )
			|| ! CommerceProductSync::is_online_sales_course( $course_key )
			|| ! $product->is_purchasable()
			|| ! $product->is_in_stock() ) {
			self::stop( is_wp_error( $product ) ? (int) $product->get_error_data()['status'] : 409 );
		}

		if ( ! function_exists( 'wc_load_cart' ) || ! function_exists( 'wc_get_checkout_url' ) ) {
			self::stop( 503 );
		}

		wc_load_cart();
		$locale = CommerceLocale::capture(
			isset( $_GET[ CommerceLocale::QUERY_VAR ] )
				? sanitize_key( wp_unslash( $_GET[ CommerceLocale::QUERY_VAR ] ) )
				: 'en'
		);
		WC()->cart->empty_cart();
		if ( false === WC()->cart->add_to_cart( $product->get_id(), 1 ) ) {
			self::stop( 409 );
		}

		wp_safe_redirect(
			add_query_arg( CommerceLocale::QUERY_VAR, $locale, wc_get_checkout_url() ),
			302,
			'HSE Training'
		);
		exit;
	}

	/** Return a generic non-cacheable response without leaking product internals. */
	private static function stop( int $status ): void {
		wp_die(
			esc_html__( 'Online checkout is temporarily unavailable. Please contact HSE Training.', 'hse-headless' ),
			esc_html__( 'Checkout unavailable', 'hse-headless' ),
			array( 'response' => $status )
		);
	}
}
