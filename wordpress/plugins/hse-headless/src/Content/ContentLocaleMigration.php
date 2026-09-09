<?php
/**
 * Idempotent migration of existing content into the bilingual model.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Content;

use HSETraining\Headless\Company\CompanyPageSettings;
use HSETraining\Headless\Course\CoursePostType;
use HSETraining\Headless\Homepage\HeroSlidePostType;
use HSETraining\Headless\Reference\ReferencePostType;
use HSETraining\Headless\Service\ServicePostType;

defined( 'ABSPATH' ) || exit;

/** Assigns English to existing records without deleting rollback data. */
final class ContentLocaleMigration {
	private const SCHEMA_OPTION         = 'hse_content_locale_schema_version';
	private const CURRENT_SCHEMA_VERSION = 2;

	/** Register the migration after post types and metadata are available. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'maybe_migrate' ), 40 );
	}

	/** Perform the migration once; incomplete runs remain retryable. */
	public static function maybe_migrate(): void {
		if ( self::CURRENT_SCHEMA_VERSION <= (int) get_option( self::SCHEMA_OPTION, 0 ) ) {
			return;
		}

		foreach ( array( CoursePostType::POST_TYPE, HeroSlidePostType::POST_TYPE, ServicePostType::POST_TYPE, ReferencePostType::POST_TYPE ) as $post_type ) {
			$post_ids = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);

			foreach ( $post_ids as $post_id ) {
				if ( ! metadata_exists( 'post', $post_id, ContentLocale::META_KEY )
					&& false === update_post_meta( $post_id, ContentLocale::META_KEY, ContentLocale::DEFAULT_LOCALE ) ) {
					return;
				}
			}
		}

		$legacy_company = get_option( CompanyPageSettings::OPTION_NAME, false );
		$english_option = CompanyPageSettings::option_name( ContentLocale::DEFAULT_LOCALE );
		if ( false !== $legacy_company && false === get_option( $english_option, false ) ) {
			if ( false === add_option( $english_option, $legacy_company, '', false ) ) {
				return;
			}
		}

		update_option( self::SCHEMA_OPTION, self::CURRENT_SCHEMA_VERSION, false );
	}
}
