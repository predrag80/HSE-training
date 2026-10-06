<?php
/**
 * Headless frontend boundary checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Infrastructure\HeadlessMode;

if ( ! class_exists( HeadlessMode::class ) ) {
	WP_CLI::error( 'Headless mode implementation is not loaded.' );
}

$failures = array();

/** Record a failed assertion without stopping remaining checks. */
function hse_headless_mode_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

$template = HeadlessMode::use_closed_template( '/tmp/theme-index.php' );

hse_headless_mode_test_assert(
	-30 === has_action( 'template_redirect', array( HeadlessMode::class, 'redirect_unused_commerce_routes' ) ),
	'Unused WooCommerce storefront routes redirect before checkout and headless template handling.'
);
hse_headless_mode_test_assert(
	0 === has_action( 'template_redirect', array( HeadlessMode::class, 'mark_frontend_closed' ) ),
	'Headless mode marks frontend responses before theme redirects run.'
);
hse_headless_mode_test_assert(
	false !== has_filter( 'allowed_redirect_hosts', array( HeadlessMode::class, 'allow_public_site_redirect_host' ) ),
	'The configured Astro hostname is allowlisted for safe storefront redirects.'
);
hse_headless_mode_test_assert(
	10 === has_filter( 'logout_redirect', array( HeadlessMode::class, 'redirect_logout_to_login' ) ),
	'CMS logout returns users to the maintained login entry point instead of the disabled frontend.'
);
hse_headless_mode_test_assert(
	'loggedout=true' === wp_parse_url(
		HeadlessMode::redirect_logout_to_login( home_url( '/' ), '', wp_get_current_user() ),
		PHP_URL_QUERY
	),
	'The logout destination includes the standard logged-out confirmation state.'
);
hse_headless_mode_test_assert(
	PHP_INT_MAX === has_filter( 'xmlrpc_enabled', array( HeadlessMode::class, 'disable_xmlrpc' ) )
		&& PHP_INT_MAX === has_filter( 'xmlrpc_methods', array( HeadlessMode::class, 'disable_xmlrpc_methods' ) )
		&& PHP_INT_MAX === has_filter( 'wp_headers', array( HeadlessMode::class, 'remove_pingback_header' ) ),
	'XML-RPC authentication and all XML-RPC methods are disabled.'
);
hse_headless_mode_test_assert(
	false === HeadlessMode::disable_xmlrpc()
		&& array() === HeadlessMode::disable_xmlrpc_methods( array( 'demo.sayHello' => 'handler' ) )
		&& array( 'Content-Type' => 'text/html' ) === HeadlessMode::remove_pingback_header(
			array(
				'Content-Type' => 'text/html',
				'X-Pingback'   => 'https://cms.hsetraining.rs/xmlrpc.php',
			)
		),
	'XML-RPC methods and the public pingback advertisement are removed.'
);
hse_headless_mode_test_assert(
	PHP_INT_MAX === has_filter( 'template_include', array( HeadlessMode::class, 'use_closed_template' ) ),
	'Headless mode owns the final public template selection.'
);
hse_headless_mode_test_assert( HeadlessMode::should_close_frontend(), 'Normal public requests are closed.' );
hse_headless_mode_test_assert( file_exists( $template ), 'The closed frontend template exists.' );
hse_headless_mode_test_assert(
	'frontend-closed.php' === basename( $template ),
	'Public requests use the plugin-owned closed template.'
);
hse_headless_mode_test_assert(
	HeadlessMode::matches_unused_commerce_route( array( 'cart' => true ) )
		&& HeadlessMode::matches_unused_commerce_route( array( 'shop' => true ) )
		&& HeadlessMode::matches_unused_commerce_route( array( 'product' => true ) )
		&& HeadlessMode::matches_unused_commerce_route( array( 'product_taxonomy' => true ) )
		&& HeadlessMode::matches_unused_commerce_route( array( 'account' => true ) )
		&& HeadlessMode::matches_unused_commerce_route( array( 'order_tracking' => true ) ),
	'Cart, shop, catalogue, account and order-tracking surfaces are treated as unused storefront routes.'
);
hse_headless_mode_test_assert(
	! HeadlessMode::matches_unused_commerce_route( array( 'checkout' => true, 'cart' => true ) )
		&& ! HeadlessMode::matches_unused_commerce_route( array() ),
	'Checkout and unrelated public requests are never redirected by the storefront-route rule.'
);

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Headless mode integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Headless mode integration checks passed.' );
