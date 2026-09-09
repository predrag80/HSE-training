<?php
/**
 * Homepage admin entry point and legacy single-slide settings reader.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Homepage;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the Homepage admin parent and preserves the first-slice migration input.
 */
final class HomepageSettings {
	public const OPTION_NAME = 'hse_homepage';
	public const PAGE_KEY    = 'home';
	public const PAGE_SLUG   = 'hse-homepage';

	private const MAX_TITLE_LENGTH = 120;
	private const MAX_LABEL_LENGTH = 160;
	private const MAX_URL_LENGTH   = 2048;

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( self::class, 'register_admin_page' ) );
	}

	/**
	 * Register the Homepage parent screen used by its content collections.
	 */
	public static function register_admin_page(): void {
		add_menu_page(
			__( 'Homepage', 'hse-headless' ),
			__( 'Homepage', 'hse-headless' ),
			'edit_posts',
			self::PAGE_SLUG,
			array( self::class, 'render_admin_page' ),
			'dashicons-admin-home',
			21
		);
	}

	/**
	 * Render the Homepage content overview.
	 */
	public static function render_admin_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$locale   = isset( $_GET['lang'] ) ? ContentLocale::sanitize( wp_unslash( $_GET['lang'] ) ) : ContentLocale::DEFAULT_LOCALE;
		$locale   = $locale ?: ContentLocale::DEFAULT_LOCALE;
		$list_url = add_query_arg( array( 'post_type' => HeroSlidePostType::POST_TYPE, 'lang' => $locale ), admin_url( 'edit.php' ) );
		$new_url  = add_query_arg( array( 'post_type' => HeroSlidePostType::POST_TYPE, 'lang' => $locale ), admin_url( 'post-new.php' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Homepage', 'hse-headless' ); ?></h1>
			<p><?php esc_html_e( 'WordPress owns published Homepage content. Astro continues to own layout, section order, styling, and animation.', 'hse-headless' ); ?></p>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Content language', 'hse-headless' ); ?>">
				<?php foreach ( ContentLocale::supported() as $supported_locale ) : ?>
					<a class="nav-tab <?php echo $locale === $supported_locale ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'lang' => $supported_locale ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( ContentLocale::label( $supported_locale ) ); ?></a>
				<?php endforeach; ?>
			</nav>
			<p><strong><?php esc_html_e( 'Managing language:', 'hse-headless' ); ?></strong> <?php echo esc_html( ContentLocale::label( $locale ) ); ?></p>
			<h2><?php esc_html_e( 'Hero slides', 'hse-headless' ); ?></h2>
			<p><?php esc_html_e( 'Create up to five published slides and use the Order field to control their display sequence.', 'hse-headless' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $new_url ); ?>"><?php esc_html_e( 'Add Hero Slide', 'hse-headless' ); ?></a>
				<a class="button" href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'Manage Hero Slides', 'hse-headless' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Return the retained first-slice settings for the one-time migration.
	 *
	 * @return array<string, int|string>
	 */
	public static function get_legacy_settings(): array {
		$stored = get_option( self::OPTION_NAME, array() );

		return self::sanitize_legacy_settings( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Sanitize retained first-slice fields before migration.
	 *
	 * @param mixed $value Raw settings value.
	 * @return array<string, int|string>
	 */
	public static function sanitize_legacy_settings( $value ): array {
		$value    = is_array( $value ) ? $value : array();
		$image_id = isset( $value['hero_image_id'] ) ? absint( $value['hero_image_id'] ) : 0;

		if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}

		return array(
			'hero_leading_title'      => self::sanitize_bounded_text( $value['hero_leading_title'] ?? '', self::MAX_TITLE_LENGTH ),
			'hero_emphasized_title'   => self::sanitize_bounded_text( $value['hero_emphasized_title'] ?? '', self::MAX_TITLE_LENGTH ),
			'hero_primary_cta_label'  => self::sanitize_bounded_text( $value['hero_primary_cta_label'] ?? '', self::MAX_LABEL_LENGTH ),
			'hero_primary_cta_url'    => HeroSlideMeta::sanitize_link_url( $value['hero_primary_cta_url'] ?? '' ),
			'hero_message_prefix'     => self::sanitize_bounded_text( $value['hero_message_prefix'] ?? '', self::MAX_LABEL_LENGTH ),
			'hero_message_link_label' => self::sanitize_bounded_text( $value['hero_message_link_label'] ?? '', self::MAX_LABEL_LENGTH ),
			'hero_message_link_url'   => HeroSlideMeta::sanitize_link_url( $value['hero_message_link_url'] ?? '' ),
			'hero_image_id'           => $image_id,
		);
	}

	/**
	 * Determine whether retained first-slice data can be migrated.
	 *
	 * @param array<string, int|string> $settings Sanitized legacy settings.
	 */
	public static function legacy_is_configured( $settings ): bool {
		$required_text = array(
			'hero_leading_title',
			'hero_emphasized_title',
			'hero_primary_cta_label',
			'hero_primary_cta_url',
			'hero_message_prefix',
			'hero_message_link_label',
			'hero_message_link_url',
		);

		foreach ( $required_text as $field ) {
			if ( '' === ( $settings[ $field ] ?? '' ) ) {
				return false;
			}
		}

		return 0 < (int) ( $settings['hero_image_id'] ?? 0 );
	}

	/**
	 * Sanitize plain editor text and enforce its maximum length.
	 *
	 * @param mixed $value      Raw value.
	 * @param int   $max_length Maximum character count.
	 */
	private static function sanitize_bounded_text( $value, $max_length ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( (string) $value );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_length ) : substr( $value, 0, $max_length );
	}
}
