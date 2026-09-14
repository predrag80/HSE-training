<?php
/** Public Legal Page REST representations. */

namespace HSETraining\Headless\Legal;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Exposes only validated, published legal content. */
final class LegalPageRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/legal-pages/(?P<page_key>privacy|terms|copyright)';

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
				'callback'            => array( self::class, 'get_legal_page' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'lang' => ContentLocale::rest_argument() ),
			)
		);
	}

	/** Return one localized legal page. */
	public static function get_legal_page( $request ) {
		$locale = ContentLocale::from_rest_request( $request );
		if ( is_wp_error( $locale ) ) {
			return $locale;
		}

		$page_key = is_object( $request ) && method_exists( $request, 'get_param' ) ? $request->get_param( 'page_key' ) : '';
		$document = LegalPageSettings::get_public_document( $page_key, $locale );

		return is_wp_error( $document ) ? $document : rest_ensure_response( $document );
	}
}
