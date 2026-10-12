<?php
/**
 * Close REST surfaces that the public Astro site and checkout never use.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Infrastructure;

defined( 'ABSPATH' ) || exit;

/** Keeps anonymous REST access limited to published content and approved commerce routes. */
final class PublicRestSurface {
	/** Register the REST dispatch guard. */
	public static function register_hooks(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'guard_request' ), 10, 3 );
	}

	/**
	 * Refuse closed routes before WordPress or WooCommerce handles them.
	 *
	 * @param mixed            $result  Earlier short-circuit result.
	 * @param \WP_REST_Server  $server  REST server instance.
	 * @param \WP_REST_Request $request Current REST request.
	 * @return mixed
	 */
	public static function guard_request( $result, $server, $request ) {
		unset( $server );

		if ( null !== $result || ! $request instanceof \WP_REST_Request ) {
			return $result;
		}

		$route = (string) $request->get_route();

		if ( self::is_store_api_route( $route ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_no_route',
				'No route was found matching the URL and request method.',
				array( 'status' => 404 )
			);
		}

		if ( self::is_user_route( $route ) && ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				'Sorry, you are not allowed to do that.',
				array( 'status' => 401 )
			);
		}

		return $result;
	}

	/**
	 * WooCommerce Store API cart and checkout routes bypass the classic HSE
	 * checkout rules, so customers may only use the classic CMS checkout.
	 */
	public static function is_store_api_route( string $route ): bool {
		return 1 === preg_match( '#^/wc/store(?:/|$)#', $route );
	}

	/** Core user routes would disclose CMS account names to anonymous visitors. */
	public static function is_user_route( string $route ): bool {
		return 1 === preg_match( '#^/wp/v2/users(?:/|$)#', $route );
	}
}
