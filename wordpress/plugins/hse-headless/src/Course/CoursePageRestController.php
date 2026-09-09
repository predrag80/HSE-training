<?php
/** Public Course page REST representations. */

namespace HSETraining\Headless\Course;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

final class CoursePageRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/course-pages/(?P<page_key>courses|nebosh)';

	public static function register_hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register_route' ) );
	}

	public static function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_course_page' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'lang' => ContentLocale::rest_argument() ),
			)
		);
	}

	public static function get_course_page( $request ) {
		$locale = ContentLocale::from_rest_request( $request );
		if ( is_wp_error( $locale ) ) {
			return $locale;
		}

		$page_key = is_object( $request ) && method_exists( $request, 'get_param' ) ? $request->get_param( 'page_key' ) : '';
		$document = CoursePageSettings::get_public_document( $page_key, $locale );

		return is_wp_error( $document ) ? $document : rest_ensure_response( $document );
	}
}
