<?php
/**
 * Shared WooCommerce bridge configuration.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Keeps the temporary WooCommerce evaluation disabled unless explicitly enabled. */
final class CommerceConfiguration {
	public const ENABLE_FLAG = 'HSE_WOOCOMMERCE_STAGING_BRIDGE';
	public const PUBLIC_SITE_URL = 'HSE_PUBLIC_SITE_URL';
	public const DEV_PUBLIC_SITE_URL = 'HSE_PUBLIC_SITE_DEV_URL';
	public const STAGING_PUBLIC_SITE_URL = 'HSE_PUBLIC_SITE_STAGING_URL';

	/** Whether this CMS may expose the approved commerce bridge. */
	public static function is_enabled(): bool {
		return defined( self::ENABLE_FLAG ) && true === constant( self::ENABLE_FLAG );
	}

	/** Return the Astro origin owned by one allowlisted checkout source. */
	public static function public_site_url( ?string $source = null ): string {
		$source        = null === $source ? 'production' : CommerceCheckoutSource::sanitize( $source );
		$constant_name = self::PUBLIC_SITE_URL;
		$fallback      = 'https://hsetraining.rs';

		if ( 'dev' === $source ) {
			$constant_name = self::DEV_PUBLIC_SITE_URL;
			$fallback      = 'https://dev.hsetraining.rs';
		} elseif ( 'staging' === $source ) {
			$constant_name = self::STAGING_PUBLIC_SITE_URL;
			$fallback      = 'https://staging.hsetraining.rs';
		}

		if ( defined( $constant_name ) ) {
			$configured = esc_url_raw( (string) constant( $constant_name ) );
			if ( '' !== $configured ) {
				return untrailingslashit( $configured );
			}
		}

		if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
			return 'http://localhost:4321';
		}

		return $fallback;
	}

	/** Return every public Astro origin that is safe for CMS redirects. */
	public static function public_site_urls(): array {
		return array_values(
			array_unique(
				array(
					self::public_site_url( 'dev' ),
					self::public_site_url( 'staging' ),
					self::public_site_url( 'production' ),
				)
			)
		);
	}
}
