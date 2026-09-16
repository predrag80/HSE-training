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

	/** Whether this environment may expose the staging commerce bridge. */
	public static function is_enabled(): bool {
		return defined( self::ENABLE_FLAG ) && true === constant( self::ENABLE_FLAG );
	}
}
