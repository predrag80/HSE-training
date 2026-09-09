<?php
/**
 * Public Homepage REST representation.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Homepage;

use HSETraining\Headless\Company\CompanyPageSettings;
use HSETraining\Headless\Content\ContentLocale;
use HSETraining\Headless\Service\ServiceRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Composes editor settings into a stable, read-only Astro contract.
 */
final class HomepageRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/homepage';
	public const SCHEMA_VERSION = 1;

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register_route' ) );
	}

	/**
	 * Register the public read-only route.
	 */
	public static function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_homepage' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'lang' => ContentLocale::rest_argument() ),
			)
		);
	}

	/**
	 * Return ordered published Hero Slides without exposing WordPress IDs.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_homepage( $request ) {
		$locale = ContentLocale::from_rest_request( $request );
		if ( is_wp_error( $locale ) ) {
			return $locale;
		}

		$posts = get_posts(
			array(
				'post_type'      => HeroSlidePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => HeroSlideMeta::MAX_PUBLISHED + 1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'date'       => 'ASC',
				),
				'meta_query'     => array( ContentLocale::query_clause( $locale ) ),
				'no_found_rows'  => true,
			)
		);

		if ( ! $posts ) {
			return new \WP_Error(
				'hse_homepage_not_configured',
				__( 'Homepage requires at least one published Hero Slide.', 'hse-headless' ),
				array( 'status' => 503 )
			);
		}

		if ( HeroSlideMeta::MAX_PUBLISHED < count( $posts ) ) {
			return new \WP_Error(
				'hse_homepage_slide_limit',
				__( 'Homepage has too many published Hero Slides.', 'hse-headless' ),
				array( 'status' => 503 )
			);
		}

		$slides = array();
		foreach ( $posts as $post ) {
			$slide = self::prepare_slide( $post );
			if ( is_wp_error( $slide ) ) {
				return $slide;
			}

			$slides[] = $slide;
		}

		$heading = trim( $slides[0]['leading_title'] . ' ' . $slides[0]['emphasized_title'] );
		$about   = CompanyPageSettings::get_public_profile( $locale );
		if ( is_wp_error( $about ) ) {
			return $about;
		}
		$featured_services = ServiceRepository::get_published_services( true, $locale );
		if ( is_wp_error( $featured_services ) ) {
			return $featured_services;
		}

		return rest_ensure_response(
			array(
				'schema_version' => self::SCHEMA_VERSION,
				'page_key'       => HomepageSettings::PAGE_KEY,
				'locale'         => $locale,
				'hero'           => array(
					'aria_label' => $heading,
					'heading'    => $heading,
					'slides'     => $slides,
				),
				'about'          => $about,
				'featured_services' => $featured_services,
			)
		);
	}

	/**
	 * Prepare one published slide and reject incomplete stored content.
	 *
	 * @param \WP_Post $post Hero Slide post.
	 * @return array<string, array<string, int|string>|string>|\WP_Error
	 */
	private static function prepare_slide( $post ) {
		$fields = HeroSlideMeta::get_public_fields( $post->ID );
		foreach ( $fields as $value ) {
			if ( '' === $value ) {
				return new \WP_Error(
					'hse_homepage_slide_incomplete',
					__( 'A published Hero Slide is incomplete.', 'hse-headless' ),
					array( 'status' => 503 )
				);
			}
		}

		$image = self::prepare_image( (int) get_post_thumbnail_id( $post->ID ) );
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		return array(
			'slide_key'          => $fields[ HeroSlideMeta::SLIDE_KEY ],
			'image'              => $image,
			'leading_title'      => $fields[ HeroSlideMeta::LEADING_TITLE ],
			'emphasized_title'   => $fields[ HeroSlideMeta::EMPHASIZED_TITLE ],
			'primary_cta_label'  => $fields[ HeroSlideMeta::PRIMARY_CTA_LABEL ],
			'primary_cta_url'    => $fields[ HeroSlideMeta::PRIMARY_CTA_URL ],
			'message_prefix'     => $fields[ HeroSlideMeta::MESSAGE_PREFIX ],
			'message_link_label' => $fields[ HeroSlideMeta::MESSAGE_LINK_LABEL ],
			'message_link_url'   => $fields[ HeroSlideMeta::MESSAGE_LINK_URL ],
		);
	}

	/**
	 * Resolve internal attachment state into a portable public image value.
	 *
	 * @param int $attachment_id WordPress attachment ID.
	 * @return array<string, int|string>|\WP_Error
	 */
	private static function prepare_image( $attachment_id ) {
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( ! $image ) {
			return new \WP_Error(
				'hse_homepage_image_unavailable',
				__( 'Homepage hero image is unavailable.', 'hse-headless' ),
				array( 'status' => 503 )
			);
		}

		return array(
			'url'    => $image[0],
			'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'width'  => (int) $image[1],
			'height' => (int) $image[2],
		);
	}
}
