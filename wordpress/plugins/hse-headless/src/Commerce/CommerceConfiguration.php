<?php
/**
 * Staging-only WooCommerce bridge configuration.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Keeps the temporary WooCommerce evaluation disabled unless explicitly enabled. */
final class CommerceConfiguration {
	public const ENABLE_FLAG = 'HSE_WOOCOMMERCE_STAGING_BRIDGE';
	public const PUBLIC_SITE_URL = 'HSE_PUBLIC_SITE_URL';

	/** Whether this environment may expose the staging commerce bridge. */
	public static function is_enabled(): bool {
		return defined( self::ENABLE_FLAG ) && true === constant( self::ENABLE_FLAG );
	}

	/** Return the environment-owned Astro origin used by checkout navigation. */
	public static function public_site_url(): string {
		if ( defined( self::PUBLIC_SITE_URL ) ) {
			$configured = esc_url_raw( (string) constant( self::PUBLIC_SITE_URL ) );
			if ( '' !== $configured ) {
				return untrailingslashit( $configured );
			}
		}

		if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
			return 'http://localhost:4321';
		}

		return 'https://hsetraining.rs';
	}
}
