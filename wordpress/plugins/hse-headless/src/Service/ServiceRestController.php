<?php
/**
 * Public Services REST representation.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Service;

defined( 'ABSPATH' ) || exit;

/** Exposes the ordered Service collection to Astro. */
final class ServiceRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/services';
	public const SCHEMA_VERSION = 1;

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
				'callback'            => array( self::class, 'get_services' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/** Return the complete published Service collection. */
	public static function get_services() {
		$services = ServiceRepository::get_published_services();
		if ( is_wp_error( $services ) ) {
			return $services;
		}

		return rest_ensure_response(
			array(
				'schema_version' => self::SCHEMA_VERSION,
				'collection_key' => 'services',
				'services'       => $services,
			)
		);
	}
}
