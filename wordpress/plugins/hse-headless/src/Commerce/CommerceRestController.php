<?php
/**
 * Public, read-only staging commerce representation for Astro.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Exposes price and checkout initiation without exposing WooCommerce API keys. */
final class CommerceRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/commerce/products/(?P<course_key>[a-z0-9]+(?:-[a-z0-9]+)*)';
	public const SCHEMA_VERSION = 1;

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register_route' ) );
	}

	/** Register the public read-only route. */
	public static function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_product' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'course_key' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => array( self::class, 'validate_course_key' ),
					),
				),
			)
		);
	}

	/** Validate the stable key accepted at the public boundary. */
	public static function validate_course_key( $value ): bool {
		return is_string( $value )
			&& strlen( $value ) <= 80
			&& 1 === preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value );
	}

	/** Return the published commerce projection for one Course. */
	public static function get_product( \WP_REST_Request $request ) {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return new \WP_Error(
				'hse_commerce_disabled',
				__( 'The requested endpoint was not found.', 'hse-headless' ),
				array( 'status' => 404 )
			);
		}

		$course_key = (string) $request->get_param( 'course_key' );
		$product    = CommerceProductRepository::get_by_course_key( $course_key );
		if ( is_wp_error( $product ) ) {
			return $product;
		}

		$currency = get_woocommerce_currency();
		$decimals = wc_get_price_decimals();
		$price    = wc_get_price_to_display( $product );

		return rest_ensure_response(
			array(
				'schema_version'   => self::SCHEMA_VERSION,
				'course_key'       => $course_key,
				'name'             => $product->get_name(),
				'price_minor'      => (int) round( (float) $price * ( 10 ** $decimals ) ),
				'currency'         => $currency,
				'currency_decimals' => $decimals,
				'purchasable'      => CommerceProductSync::is_online_sales_course( $course_key )
					&& $product->is_purchasable()
					&& $product->is_in_stock(),
				'checkout_url'     => esc_url_raw(
					add_query_arg( CommerceCheckout::QUERY_VAR, $course_key, home_url( '/' ) )
				),
			)
		);
	}
}
