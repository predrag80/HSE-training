<?php
/**
 * Idempotent Homepage content migrations.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Homepage;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates the first single-slide option into the native slide collection.
 */
final class HomepageMigration {
	private const SCHEMA_OPTION = 'hse_homepage_schema_version';
	private const CURRENT_SCHEMA_VERSION = 2;

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'maybe_migrate' ), 30 );
	}

	/**
	 * Run the migration once after the Hero Slide post type and metadata exist.
	 */
	public static function maybe_migrate(): void {
		if ( self::CURRENT_SCHEMA_VERSION <= (int) get_option( self::SCHEMA_OPTION, 1 ) ) {
			return;
		}

		$existing = get_posts(
			array(
				'post_type'      => HeroSlidePostType::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( $existing ) {
			update_option( self::SCHEMA_OPTION, self::CURRENT_SCHEMA_VERSION, false );

			return;
		}

		$legacy = HomepageSettings::get_legacy_settings();
		if ( ! HomepageSettings::legacy_is_configured( $legacy ) ) {
			update_option( self::SCHEMA_OPTION, self::CURRENT_SCHEMA_VERSION, false );

			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => HeroSlidePostType::POST_TYPE,
				'post_status' => 'draft',
				'post_title'  => trim( $legacy['hero_leading_title'] . ' ' . $legacy['hero_emphasized_title'] ),
				'menu_order'  => 0,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return;
		}

		$field_map = array(
			HeroSlideMeta::SLIDE_KEY          => 'primary',
			HeroSlideMeta::LEADING_TITLE      => $legacy['hero_leading_title'],
			HeroSlideMeta::EMPHASIZED_TITLE   => $legacy['hero_emphasized_title'],
			HeroSlideMeta::PRIMARY_CTA_LABEL  => $legacy['hero_primary_cta_label'],
			HeroSlideMeta::PRIMARY_CTA_URL    => $legacy['hero_primary_cta_url'],
			HeroSlideMeta::MESSAGE_PREFIX     => $legacy['hero_message_prefix'],
			HeroSlideMeta::MESSAGE_LINK_LABEL => $legacy['hero_message_link_label'],
			HeroSlideMeta::MESSAGE_LINK_URL   => $legacy['hero_message_link_url'],
		);

		foreach ( $field_map as $meta_key => $value ) {
			update_post_meta( $post_id, $meta_key, $value );
		}

		set_post_thumbnail( $post_id, (int) $legacy['hero_image_id'] );
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $result ) || 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		// Retain the original option as rollback data until a later reviewed cleanup.
		update_option( self::SCHEMA_OPTION, self::CURRENT_SCHEMA_VERSION, false );
	}
}
