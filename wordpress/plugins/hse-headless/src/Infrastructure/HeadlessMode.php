<?php
/**
 * Disable WordPress theme rendering while preserving CMS operations.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Infrastructure;

use HSETraining\Headless\Commerce\CommerceConfiguration;

defined( 'ABSPATH' ) || exit;

final class HeadlessMode {
	/** Register WordPress hooks. */
	public static function register_hooks() {
		add_filter( 'allowed_redirect_hosts', array( self::class, 'allow_public_site_redirect_host' ) );
		add_filter( 'logout_redirect', array( self::class, 'redirect_logout_to_login' ), 10, 3 );
		add_filter( 'xmlrpc_enabled', array( self::class, 'disable_xmlrpc' ), PHP_INT_MAX );
		add_filter( 'xmlrpc_methods', array( self::class, 'disable_xmlrpc_methods' ), PHP_INT_MAX );
		add_filter( 'wp_headers', array( self::class, 'remove_pingback_header' ), PHP_INT_MAX );
		add_action( 'template_redirect', array( self::class, 'redirect_unused_commerce_routes' ), -30 );
		add_action( 'template_redirect', array( self::class, 'mark_frontend_closed' ), 0 );
		add_filter( 'template_include', array( self::class, 'use_closed_template' ), PHP_INT_MAX );
	}

	/**
	 * Keep CMS users on the administration entry point after signing out.
	 *
	 * wp_login_url() is intentionally used instead of a hard-coded path so the
	 * maintained login-hiding plugin can return the configured login route.
	 *
	 * @param string   $redirect_to           Default WordPress redirect.
	 * @param string   $requested_redirect_to Requested redirect, when supplied.
	 * @param \WP_User $user                  User who signed out.
	 * @return string
	 */
	public static function redirect_logout_to_login( $redirect_to, $requested_redirect_to, $user ): string {
		unset( $redirect_to, $requested_redirect_to, $user );

		return add_query_arg( 'loggedout', 'true', wp_login_url() );
	}

	/** Disable authenticated XML-RPC operations when no integration requires them. */
	public static function disable_xmlrpc(): bool {
		return false;
	}

	/** Remove every XML-RPC method, including unauthenticated pingback methods. */
	public static function disable_xmlrpc_methods( array $methods ): array {
		unset( $methods );
		return array();
	}

	/** Do not advertise an endpoint that is intentionally unavailable. */
	public static function remove_pingback_header( array $headers ): array {
		foreach ( array_keys( $headers ) as $name ) {
			if ( 'x-pingback' === strtolower( (string) $name ) ) {
				unset( $headers[ $name ] );
			}
		}

		return $headers;
	}

	/** Permit safe redirects only to the configured public Astro hostname. */
	public static function allow_public_site_redirect_host( array $hosts ): array {
		foreach ( CommerceConfiguration::public_site_urls() as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( is_string( $host ) && '' !== $host && ! in_array( $host, $hosts, true ) ) {
				$hosts[] = $host;
			}
		}

		return $hosts;
	}

	/** Redirect unused WooCommerce storefront surfaces to the public homepage. */
	public static function redirect_unused_commerce_routes(): void {
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_unused_commerce_route() ) {
			return;
		}

		$target = trailingslashit( CommerceConfiguration::public_site_url() );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
		wp_safe_redirect( $target, 302, 'HSE Training' );
		exit;
	}

	/** Determine whether the request is an unused WooCommerce public route. */
	public static function is_unused_commerce_route(): bool {
		return self::matches_unused_commerce_route(
			array(
				'checkout'         => function_exists( 'is_checkout' ) && is_checkout(),
				'cart'             => function_exists( 'is_cart' ) && is_cart(),
				'shop'             => function_exists( 'is_shop' ) && is_shop(),
				'product'          => function_exists( 'is_product' ) && is_product(),
				'product_taxonomy' => function_exists( 'is_product_taxonomy' ) && is_product_taxonomy(),
				'account'          => function_exists( 'is_account_page' ) && is_account_page(),
				'order_tracking'   => function_exists( 'is_order_tracking_page' ) && is_order_tracking_page(),
			)
		);
	}

	/** Pure route decision used by the request handler and integration checks. */
	public static function matches_unused_commerce_route( array $route ): bool {
		if ( ! empty( $route['checkout'] ) ) {
			return false;
		}

		foreach ( array( 'cart', 'shop', 'product', 'product_taxonomy', 'account', 'order_tracking' ) as $key ) {
			if ( ! empty( $route[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether the current request would render a public theme template.
	 *
	 * @return bool
	 */
	public static function should_close_frontend() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( CommerceConfiguration::is_enabled() ) {
			if ( defined( 'WC_API_REQUEST' ) && WC_API_REQUEST ) {
				return false;
			}

			if ( function_exists( 'is_checkout' ) && is_checkout() ) {
				return false;
			}
		}

		$pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
		if ( in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			return false;
		}

		return true;
	}

	/** Serve public frontend requests as non-cacheable 404 responses. */
	public static function mark_frontend_closed() {
		if ( ! self::should_close_frontend() ) {
			return;
		}

		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}

		status_header( 404 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );

		// End the request before WooCommerce can render its Coming Soon template.
		require dirname( __DIR__, 2 ) . '/templates/frontend-closed.php';
		exit;
	}

	/**
	 * Replace all public theme templates with the plugin-owned closed response.
	 *
	 * @param string $template Resolved WordPress template path.
	 * @return string
	 */
	public static function use_closed_template( $template ) {
		if ( ! self::should_close_frontend() ) {
			return $template;
		}

		return dirname( __DIR__, 2 ) . '/templates/frontend-closed.php';
	}
}
