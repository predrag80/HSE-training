<?php
/**
 * Public Company Page REST representation.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Company;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes page-specific data and the shared Company profile.
 */
final class CompanyPageRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/company';

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
				'callback'            => array( self::class, 'get_company_page' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/** Return the Company Page document. */
	public static function get_company_page() {
		$profile = CompanyPageSettings::get_public_profile();
		if ( is_wp_error( $profile ) ) {
			return $profile;
		}

		$fields = CompanyPageSettings::get_public_page_fields();
		if ( '' === $fields['hero']['eyebrow'] || '' === $fields['hero']['title'] || '' === $fields['intro_cta']['label'] || '' === $fields['intro_cta']['url'] ) {
			return new \WP_Error( 'hse_company_page_not_configured', __( 'Company Page content is not configured.', 'hse-headless' ), array( 'status' => 503 ) );
		}

		return rest_ensure_response(
			array(
				'schema_version' => 1,
				'page_key'       => CompanyPageSettings::PAGE_KEY,
				'hero'           => $fields['hero'],
				'profile'        => $profile,
				'intro_cta'      => $fields['intro_cta'],
			)
		);
	}
}
