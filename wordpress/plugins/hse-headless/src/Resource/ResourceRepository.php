<?php
/**
 * Public Free Resource collection query.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Resource;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Builds portable public Resource values. */
final class ResourceRepository {
	/** Return all ordered published Resources for one language. */
	public static function get_published_resources( $locale = ContentLocale::DEFAULT_LOCALE ) {
		$locale = ContentLocale::sanitize( $locale );
		if ( '' === $locale ) {
			return new \WP_Error( 'hse_invalid_content_locale', __( 'The content language must be en or sr.', 'hse-headless' ), array( 'status' => 400 ) );
		}

		$posts = get_posts(
			array(
				'post_type'      => ResourcePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'meta_query'     => array( ContentLocale::query_clause( $locale ) ),
				'no_found_rows'  => true,
			)
		);

		$resources = array();
		foreach ( $posts as $post ) {
			$key           = sanitize_key( get_post_meta( $post->ID, ResourceMeta::RESOURCE_KEY, true ) );
			$type          = ResourceMeta::sanitize_type( get_post_meta( $post->ID, ResourceMeta::TYPE, true ) );
			$external_url  = esc_url_raw( get_post_meta( $post->ID, ResourceMeta::EXTERNAL_URL, true ) );
			$attachment_id = absint( get_post_meta( $post->ID, ResourceMeta::ATTACHMENT_ID, true ) );
			$attachment    = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
			$action_url    = $attachment ?: $external_url;
			if ( '' === $key || '' === trim( get_the_title( $post ) ) || ! $action_url ) {
				continue;
			}

			$thumbnail_id = get_post_thumbnail_id( $post );
			$image         = null;
			if ( $thumbnail_id ) {
				$image_source = wp_get_attachment_image_src( $thumbnail_id, 'large' );
				if ( $image_source ) {
					$image = array(
						'url'    => esc_url_raw( $image_source[0] ),
						'alt'    => sanitize_text_field( get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) ),
						'width'  => (int) $image_source[1],
						'height' => (int) $image_source[2],
					);
				}
			}

			$attached_file = $attachment_id ? get_attached_file( $attachment_id ) : '';
			$resources[]   = array(
				'resource_key' => $key,
				'title'        => trim( get_the_title( $post ) ),
				'summary'      => sanitize_textarea_field( get_post_meta( $post->ID, ResourceMeta::SUMMARY, true ) ),
				'description'  => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
				'topic'        => sanitize_text_field( get_post_meta( $post->ID, ResourceMeta::TOPIC, true ) ),
				'type'         => $type,
				'featured'     => (bool) get_post_meta( $post->ID, ResourceMeta::FEATURED, true ),
				'action_url'   => esc_url_raw( $action_url ),
				'is_download'  => (bool) $attachment,
				'file_name'    => $attached_file ? sanitize_file_name( basename( $attached_file ) ) : '',
				'file_size'    => $attached_file && file_exists( $attached_file ) ? (int) filesize( $attached_file ) : 0,
				'mime_type'    => $attachment_id ? sanitize_mime_type( get_post_mime_type( $attachment_id ) ) : '',
				'image'        => $image,
			);
		}

		return $resources;
	}
}
