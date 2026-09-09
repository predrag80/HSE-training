<?php
/**
 * Public References REST representation.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Reference;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Exposes the ordered Reference collection to Astro. */
final class ReferenceRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/references';

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
				'callback'            => array( self::class, 'get_references' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'lang' => ContentLocale::rest_argument() ),
			)
		);
	}

	/** Return the complete published Reference collection. */
	public static function get_references( $request = null ) {
		$locale = ContentLocale::from_rest_request( $request );
		if ( is_wp_error( $locale ) ) {
			return $locale;
		}

		$references = ReferenceRepository::get_published_references( $locale );
		if ( is_wp_error( $references ) ) {
			return $references;
		}

		return rest_ensure_response(
			array(
				'schema_version' => 1,
				'collection_key' => 'references',
				'locale'         => $locale,
				'references'     => $references,
			)
		);
	}
}
