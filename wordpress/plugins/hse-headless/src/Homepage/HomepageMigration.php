<?php
/**
 * Idempotent Homepage content migrations.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Homepage;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates the first single-slide option into the native slide collection.
 */
final class HomepageMigration {
	private const SCHEMA_OPTION = 'hse_homepage_schema_version';
	private const CURRENT_SCHEMA_VERSION = 3;

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
		$schema_version = (int) get_option( self::SCHEMA_OPTION, 1 );
		if ( self::CURRENT_SCHEMA_VERSION <= $schema_version ) {
			return;
		}

		if ( 2 > $schema_version && ! self::migrate_legacy_hero() ) {
			return;
		}

		if ( 3 > $schema_version ) {
			self::update_hero_copy();
		}

		update_option( self::SCHEMA_OPTION, self::CURRENT_SCHEMA_VERSION, false );
	}

	/**
	 * Convert the former single-slide option into a native hero slide.
	 *
	 * @return bool Whether the migration can advance safely.
	 */
	private static function migrate_legacy_hero(): bool {
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
			return true;
		}

		$legacy = HomepageSettings::get_legacy_settings();
		if ( ! HomepageSettings::legacy_is_configured( $legacy ) ) {
			return true;
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
			return false;
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
			return false;
		}

		return true;
	}

	/** Apply the approved 2026 homepage hero copy to the localized slides. */
	private static function update_hero_copy(): void {
		$directory_url = 'https://hsedirectory.com.au';
		$slides        = array(
			'en' => array(
				'primary' => array(
					'post_title'        => 'Your First Step Into HSE.',
					'leading_title'      => 'Your First Step Into',
					'emphasized_title'   => 'HSE.',
					'primary_cta_label'  => 'Get Qualified. Get Ahead.',
					'primary_cta_url'    => '/nebosh/',
					'message_prefix'     => 'Request a free',
					'message_link_label' => 'consultation',
					'message_link_url'   => '/contact/',
				),
				'guidance' => array(
					'post_title'        => 'Where HSE Talent Meets Opportunity.',
					'leading_title'      => 'WHERE HSE TALENT MEETS',
					'emphasized_title'   => 'OPPORTUNITY.',
					'primary_cta_label'  => 'LET\'S WORK TOGETHER',
					'primary_cta_url'    => $directory_url,
					'message_prefix'     => 'Join the',
					'message_link_label' => 'HSE DIRECTORY',
					'message_link_url'   => $directory_url,
				),
				'business-strategies' => array(
					'post_title'        => 'New HSE Coaching Program',
					'leading_title'      => 'NEW HSE Coaching',
					'emphasized_title'   => 'Program!!!',
					'primary_cta_label'  => '1-1 HSE Coaching',
					'primary_cta_url'    => '/contact/',
					'message_prefix'     => 'Register interest in',
					'message_link_label' => 'Program',
					'message_link_url'   => '/contact/',
				),
			),
			'sr' => array(
				'primary' => array(
					'post_title'        => 'Vaš prvi korak u HSE.',
					'leading_title'      => 'Vaš prvi korak u',
					'emphasized_title'   => 'HSE.',
					'primary_cta_label'  => 'Steknite kvalifikaciju. Napredujte.',
					'primary_cta_url'    => '/nebosh/',
					'message_prefix'     => 'Zatražite besplatne',
					'message_link_label' => 'konsultacije',
					'message_link_url'   => '/contact/',
				),
				'guidance' => array(
					'post_title'        => 'Gde se HSE stručnjaci i prilike susreću.',
					'leading_title'      => 'GDE SE HSE STRUČNJACI I PRILIKE',
					'emphasized_title'   => 'SUSREĆU.',
					'primary_cta_label'  => 'HAJDE DA SARAĐUJEMO',
					'primary_cta_url'    => $directory_url,
					'message_prefix'     => 'Pridružite se',
					'message_link_label' => 'HSE DIRECTORY',
					'message_link_url'   => $directory_url,
				),
				'business-strategies' => array(
					'post_title'        => 'Novi program HSE koučinga',
					'leading_title'      => 'NOVI program HSE',
					'emphasized_title'   => 'koučinga!!!',
					'primary_cta_label'  => '1-na-1 HSE koučing',
					'primary_cta_url'    => '/contact/',
					'message_prefix'     => 'Prijavite interesovanje za',
					'message_link_label' => 'program',
					'message_link_url'   => '/contact/',
				),
			),
		);

		$post_ids = get_posts(
			array(
				'post_type'      => HeroSlidePostType::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $post_ids as $post_id ) {
			$locale    = ContentLocale::get_post_locale( $post_id );
			$slide_key = (string) get_post_meta( $post_id, HeroSlideMeta::SLIDE_KEY, true );
			if ( ! isset( $slides[ $locale ][ $slide_key ] ) ) {
				continue;
			}

			$values = $slides[ $locale ][ $slide_key ];
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $values['post_title'],
				)
			);

			unset( $values['post_title'] );
			foreach ( $values as $meta_key => $value ) {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}
}
