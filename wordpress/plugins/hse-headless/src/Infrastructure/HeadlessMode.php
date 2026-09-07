<?php
/**
 * Disable WordPress theme rendering while preserving CMS operations.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Infrastructure;

defined( 'ABSPATH' ) || exit;

final class HeadlessMode {
	/** Register WordPress hooks. */
	public static function register_hooks() {
		add_action( 'template_redirect', array( self::class, 'mark_frontend_closed' ), 0 );
		add_filter( 'template_include', array( self::class, 'use_closed_template' ), PHP_INT_MAX );
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

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}

		$pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
		if ( in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			return false;
		}

		return true;
	}

	/** Mark public frontend requests as non-cacheable 404 responses. */
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
