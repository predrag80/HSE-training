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
	0 === has_action( 'template_redirect', array( HeadlessMode::class, 'mark_frontend_closed' ) ),
	'Headless mode marks frontend responses before theme redirects run.'
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

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Headless mode integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Headless mode integration checks passed.' );
