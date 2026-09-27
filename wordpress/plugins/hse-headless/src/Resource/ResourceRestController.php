<?php
/**
 * Public Free Resources REST representation.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Resource;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Exposes Free Resources to Astro. */
final class ResourceRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/resources';

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
				'callback'            => array( self::class, 'get_resources' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'lang' => ContentLocale::rest_argument() ),
			)
		);
	}

	/** Return the complete localized Resource collection. */
	public static function get_resources( $request = null ) {
		$locale = ContentLocale::from_rest_request( $request );
		if ( is_wp_error( $locale ) ) {
			return $locale;
		}

		return rest_ensure_response(
			array(
				'schema_version' => 1,
				'collection_key' => 'resources',
				'locale'         => $locale,
				'resources'      => ResourceRepository::get_published_resources( $locale ),
			)
		);
	}
}
