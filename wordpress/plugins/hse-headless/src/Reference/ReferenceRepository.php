<?php
/**
 * Public Reference collection query.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Reference;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Builds portable public Reference values. */
final class ReferenceRepository {
	/** Return all ordered published References. */
	public static function get_published_references( $locale = ContentLocale::DEFAULT_LOCALE ) {
		$locale = ContentLocale::sanitize( $locale );
		if ( '' === $locale ) {
			return new \WP_Error( 'hse_invalid_content_locale', __( 'The content language must be en or sr.', 'hse-headless' ), array( 'status' => 400 ) );
		}

		$posts = get_posts(
			array(
				'post_type'      => ReferencePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
				'meta_query'     => array( ContentLocale::query_clause( $locale ) ),
				'no_found_rows'  => true,
			)
		);
		if ( ! $posts ) {
			return new \WP_Error( 'hse_references_not_configured', __( 'At least one published Reference is required.', 'hse-headless' ), array( 'status' => 503 ) );
		}

		$references = array();
		foreach ( $posts as $post ) {
			$fields = ReferenceMeta::get_public_fields( $post->ID );
			if ( '' === trim( get_the_title( $post ) ) || '' === $fields[ ReferenceMeta::REFERENCE_KEY ] || '' === $fields[ ReferenceMeta::QUOTE ] || '' === $fields[ ReferenceMeta::ROLE ] ) {
				return new \WP_Error( 'hse_reference_incomplete', __( 'A published Reference is incomplete.', 'hse-headless' ), array( 'status' => 503 ) );
			}
			$references[] = array(
				'reference_key'         => $fields[ ReferenceMeta::REFERENCE_KEY ],
				'quote'                 => $fields[ ReferenceMeta::QUOTE ],
				'author_name'           => trim( get_the_title( $post ) ),
				'role'                  => $fields[ ReferenceMeta::ROLE ],
				'featured_on_homepage'  => $fields[ ReferenceMeta::FEATURED_ON_HOMEPAGE ],
				'accent_on_homepage'    => $fields[ ReferenceMeta::ACCENT_ON_HOMEPAGE ],
			);
		}

		return $references;
	}
}
