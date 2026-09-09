<?php
/**
 * Public Service collection queries and representations.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Service;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Builds the stable public Service values shared by REST documents. */
final class ServiceRepository {
	/**
	 * Return ordered published Services.
	 *
	 * @param bool   $featured_only Limit the collection to Homepage-featured Services.
	 * @param string $locale        Allowlisted content locale.
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	public static function get_published_services( $featured_only = false, $locale = ContentLocale::DEFAULT_LOCALE ) {
		$locale = ContentLocale::sanitize( $locale );
		if ( '' === $locale ) {
			return new \WP_Error( 'hse_invalid_content_locale', __( 'Content language is invalid.', 'hse-headless' ), array( 'status' => 400 ) );
		}

		$query = array(
			'post_type'      => ServicePostType::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
			),
			'meta_query'     => array( ContentLocale::query_clause( $locale ) ),
			'no_found_rows'  => true,
		);

		if ( $featured_only ) {
			$query['posts_per_page'] = ServiceMeta::MAX_HOMEPAGE_SERVICES + 1;
			$query['meta_query']     = array(
				'relation' => 'AND',
				array(
					'key'   => ServiceMeta::FEATURED_ON_HOMEPAGE,
					'value' => '1',
				),
				ContentLocale::query_clause( $locale ),
			);
		}

		$posts = get_posts( $query );
		if ( ! $posts ) {
			return new \WP_Error(
				$featured_only ? 'hse_homepage_services_not_configured' : 'hse_services_not_configured',
				$featured_only
					? __( 'Homepage requires at least one featured published Service.', 'hse-headless' )
					: __( 'At least one published Service is required.', 'hse-headless' ),
				array( 'status' => 503 )
			);
		}

		if ( $featured_only && ServiceMeta::MAX_HOMEPAGE_SERVICES < count( $posts ) ) {
			return new \WP_Error(
				'hse_homepage_services_limit',
				__( 'Homepage has too many featured published Services.', 'hse-headless' ),
				array( 'status' => 503 )
			);
		}

		$services = array();
		foreach ( $posts as $post ) {
			$service = self::prepare_service( $post );
			if ( is_wp_error( $service ) ) {
				return $service;
			}

			$services[] = $service;
		}

		return $services;
	}

	/**
	 * Prepare one Service and reject incomplete stored content.
	 *
	 * @param \WP_Post $post Service post.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function prepare_service( $post ) {
		$title = trim( get_the_title( $post ) );
		if ( '' === $title ) {
			return new \WP_Error(
				'hse_service_incomplete',
				__( 'A published Service is incomplete.', 'hse-headless' ),
				array( 'status' => 503 )
			);
		}

		$fields = ServiceMeta::get_public_fields( $post->ID );
		foreach ( $fields as $field => $value ) {
			if ( ServiceMeta::FEATURED_ON_HOMEPAGE !== $field && '' === $value ) {
				return new \WP_Error(
					'hse_service_incomplete',
					__( 'A published Service is incomplete.', 'hse-headless' ),
					array( 'status' => 503 )
				);
			}
		}

		$image = self::prepare_image( (int) get_post_thumbnail_id( $post->ID ) );
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		return array(
			'service_key'          => $fields[ ServiceMeta::SERVICE_KEY ],
			'title'                => $title,
			'card_label'           => $fields[ ServiceMeta::CARD_LABEL ],
			'short_description'    => $fields[ ServiceMeta::SHORT_DESCRIPTION ],
			'detailed_description' => $fields[ ServiceMeta::DETAILED_DESCRIPTION ],
			'cta'                  => array(
				'label' => $fields[ ServiceMeta::CTA_LABEL ],
				'url'   => $fields[ ServiceMeta::CTA_URL ],
			),
			'image'                => $image,
			'featured_on_homepage' => $fields[ ServiceMeta::FEATURED_ON_HOMEPAGE ],
		);
	}

	/** Resolve an attachment into a portable public image value. */
	private static function prepare_image( $attachment_id ) {
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( ! $image ) {
			return new \WP_Error(
				'hse_service_image_unavailable',
				__( 'A published Service image is unavailable.', 'hse-headless' ),
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
