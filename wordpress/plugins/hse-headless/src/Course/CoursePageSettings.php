<?php
/**
 * Editorial settings for the Courses landing and NEBOSH overview pages.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Course;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Owns bounded, locale-specific content while Astro owns presentation. */
final class CoursePageSettings {
	public const OPTION_PREFIX = 'hse_course_page';
	public const PAGE_KEYS     = array( 'courses', 'nebosh' );

	private const PAGE_SLUG      = 'hse-course-pages';
	private const SETTINGS_GROUP = 'hse_course_page_settings';
	private const MAX_TITLE      = 180;
	private const MAX_LABEL      = 180;
	private const MAX_TEXT       = 1000;

	/** @var string */
	private static $active_option_name = '';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( self::class, 'register_admin_page' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
	}

	/** Add one editor below the Courses content type. */
	public static function register_admin_page(): void {
		add_submenu_page(
			'edit.php?post_type=course',
			__( 'Course pages', 'hse-headless' ),
			__( 'Course pages', 'hse-headless' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'render_admin_page' )
		);
	}

	/** Register isolated settings for every page and locale. */
	public static function register_settings(): void {
		foreach ( self::PAGE_KEYS as $page_key ) {
			foreach ( ContentLocale::supported() as $locale ) {
				register_setting(
					self::settings_group( $page_key, $locale ),
					self::option_name( $page_key, $locale ),
					array(
						'type'              => 'object',
						'description'       => __( 'Structured copy for one public Course page.', 'hse-headless' ),
						'sanitize_callback' => static function ( $value ) use ( $page_key ) {
							return self::sanitize_settings( $page_key, $value );
						},
						'default'           => self::defaults( $page_key ),
						'show_in_rest'      => false,
					)
				);
			}
		}
	}

	/** Return a stable option name for one page and locale. */
	public static function option_name( $page_key, $locale ): string {
		$page_key = self::sanitize_page_key( $page_key );
		$locale   = ContentLocale::sanitize( $locale );

		return self::OPTION_PREFIX . '_' . $page_key . '_' . ( $locale ?: ContentLocale::DEFAULT_LOCALE );
	}

	/** Return sanitized settings with a stable shape. */
	public static function get_settings( $page_key, $locale = ContentLocale::DEFAULT_LOCALE ): array {
		$page_key = self::sanitize_page_key( $page_key );
		$locale   = ContentLocale::sanitize( $locale );
		if ( '' === $page_key || '' === $locale ) {
			return array();
		}

		$stored = get_option( self::option_name( $page_key, $locale ), array() );

		return self::sanitize_settings( $page_key, is_array( $stored ) ? $stored : array() );
	}

	/** Sanitize all allowlisted fields and discard everything else. */
	public static function sanitize_settings( $page_key, $value ): array {
		$page_key = self::sanitize_page_key( $page_key );
		$value    = is_array( $value ) ? $value : array();
		$result   = array();

		foreach ( self::field_definitions( $page_key ) as $key => $field ) {
			$raw            = $value[ $key ] ?? '';
			$sanitized      = 'textarea' === $field['type']
				? sanitize_textarea_field( is_scalar( $raw ) ? (string) $raw : '' )
				: sanitize_text_field( is_scalar( $raw ) ? (string) $raw : '' );
			$result[ $key ] = self::bound( $sanitized, (int) $field['max'] );
		}

		return $result;
	}

	/** Compose the public REST content document. */
	public static function get_public_document( $page_key, $locale = ContentLocale::DEFAULT_LOCALE ) {
		$page_key = self::sanitize_page_key( $page_key );
		$locale   = ContentLocale::sanitize( $locale );
		if ( '' === $page_key || '' === $locale ) {
			return new \WP_Error( 'hse_course_page_invalid', __( 'Course page or language is invalid.', 'hse-headless' ), array( 'status' => 400 ) );
		}

		$settings = self::get_settings( $page_key, $locale );
		foreach ( self::field_definitions( $page_key ) as $key => $unused ) {
			if ( '' === ( $settings[ $key ] ?? '' ) ) {
				return new \WP_Error( 'hse_course_page_not_configured', __( 'Course page content is not configured.', 'hse-headless' ), array( 'status' => 503 ) );
			}
		}

		$content = 'courses' === $page_key ? self::courses_document( $settings ) : self::nebosh_document( $settings );

		return array(
			'schema_version' => 1,
			'page_key'       => $page_key,
			'locale'         => $locale,
			'content'        => $content,
		);
	}

	/** Render page and language tabs plus the bounded form. */
	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page_key = isset( $_GET['content_page'] ) ? self::sanitize_page_key( wp_unslash( $_GET['content_page'] ) ) : 'courses';
		$page_key = $page_key ?: 'courses';
		$locale   = isset( $_GET['lang'] ) ? ContentLocale::sanitize( wp_unslash( $_GET['lang'] ) ) : ContentLocale::DEFAULT_LOCALE;
		$locale   = $locale ?: ContentLocale::DEFAULT_LOCALE;
		$settings = self::get_settings( $page_key, $locale );
		self::$active_option_name = self::option_name( $page_key, $locale );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Course pages', 'hse-headless' ); ?></h1>
			<p><?php esc_html_e( 'Edit page copy here. Layout, imagery, routes, and animation remain controlled by Astro.', 'hse-headless' ); ?></p>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Course page', 'hse-headless' ); ?>">
				<?php foreach ( self::PAGE_KEYS as $supported_page ) : ?>
					<a class="nav-tab <?php echo $page_key === $supported_page ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::admin_url( $supported_page, $locale ) ); ?>"><?php echo esc_html( self::page_label( $supported_page ) ); ?></a>
				<?php endforeach; ?>
			</nav>
			<h2 class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Content language', 'hse-headless' ); ?>">
				<?php foreach ( ContentLocale::supported() as $supported_locale ) : ?>
					<a class="nav-tab <?php echo $locale === $supported_locale ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::admin_url( $page_key, $supported_locale ) ); ?>"><?php echo esc_html( ContentLocale::label( $supported_locale ) ); ?></a>
				<?php endforeach; ?>
			</h2>
			<p><strong><?php echo esc_html( self::page_label( $page_key ) ); ?>:</strong> <?php echo esc_html( ContentLocale::label( $locale ) ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( self::settings_group( $page_key, $locale ) ); ?>
				<table class="form-table" role="presentation"><tbody>
					<?php foreach ( self::field_definitions( $page_key ) as $key => $field ) : ?>
						<tr>
							<th scope="row"><label for="hse-course-page-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $field['type'] ) : ?>
									<textarea class="large-text" id="hse-course-page-<?php echo esc_attr( $key ); ?>" maxlength="<?php echo esc_attr( $field['max'] ); ?>" name="<?php echo esc_attr( self::$active_option_name . '[' . $key . ']' ); ?>" rows="4" required><?php echo esc_textarea( $settings[ $key ] ); ?></textarea>
								<?php else : ?>
									<input class="regular-text" id="hse-course-page-<?php echo esc_attr( $key ); ?>" maxlength="<?php echo esc_attr( $field['max'] ); ?>" name="<?php echo esc_attr( self::$active_option_name . '[' . $key . ']' ); ?>" type="text" value="<?php echo esc_attr( $settings[ $key ] ); ?>" required>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody></table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** @return array<string, array{label:string,type:string,max:int}> */
	private static function field_definitions( $page_key ): array {
		$common = array(
			'meta_title'       => array( 'label' => __( 'SEO title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'meta_description' => array( 'label' => __( 'SEO description', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
		);

		if ( 'courses' === $page_key ) {
			return $common + array(
				'hero_eyebrow' => array( 'label' => __( 'Eyebrow', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
				'hero_title'    => array( 'label' => __( 'Page title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
				'hero_intro'    => array( 'label' => __( 'Intro', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
				'empty_title'   => array( 'label' => __( 'Empty state title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
				'empty_text'    => array( 'label' => __( 'Empty state text', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			);
		}

		if ( 'nebosh' !== $page_key ) {
			return array();
		}

		return $common + array(
			'hero_eyebrow' => array( 'label' => __( 'Hero eyebrow', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'hero_title' => array( 'label' => __( 'Hero title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'strength_1_title' => array( 'label' => __( 'Benefit 1 title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'strength_1_text' => array( 'label' => __( 'Benefit 1 text', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'strength_2_title' => array( 'label' => __( 'Benefit 2 title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'strength_2_text' => array( 'label' => __( 'Benefit 2 text', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'strength_3_title' => array( 'label' => __( 'Benefit 3 title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'strength_3_text' => array( 'label' => __( 'Benefit 3 text', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'strength_4_title' => array( 'label' => __( 'Benefit 4 title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'strength_4_text' => array( 'label' => __( 'Benefit 4 text', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'intro_kicker' => array( 'label' => __( 'Intro eyebrow', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'intro_title' => array( 'label' => __( 'Intro title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'intro_paragraph_1' => array( 'label' => __( 'Intro paragraph 1', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'intro_paragraph_2' => array( 'label' => __( 'Intro paragraph 2', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'intro_cta_label' => array( 'label' => __( 'Intro CTA label', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'feature_image_alt' => array( 'label' => __( 'Feature image alternative text', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'visual_title' => array( 'label' => __( 'Visual callout title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'visual_text' => array( 'label' => __( 'Visual callout text', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'courses_kicker' => array( 'label' => __( 'Course selection eyebrow', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'courses_title' => array( 'label' => __( 'Course selection title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE ),
			'current_label' => array( 'label' => __( 'Current qualification label', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'legacy_label' => array( 'label' => __( 'Legacy qualification label', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'igc_fallback' => array( 'label' => __( 'IGC fallback description', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'iogc_fallback' => array( 'label' => __( 'IOGC fallback description', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'igc_cta_label' => array( 'label' => __( 'IGC CTA label', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'iogc_cta_label' => array( 'label' => __( 'IOGC CTA label', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'testimonial_quote' => array( 'label' => __( 'Testimonial quote', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT ),
			'testimonial_author' => array( 'label' => __( 'Testimonial author', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
			'testimonial_role' => array( 'label' => __( 'Testimonial role', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_LABEL ),
		);
	}

	/** @return array<string, string> */
	private static function defaults( $page_key ): array {
		return array_fill_keys( array_keys( self::field_definitions( $page_key ) ), '' );
	}

	/** Map flat storage to the Courses page contract. */
	private static function courses_document( $settings ): array {
		return array(
			'meta' => array( 'title' => $settings['meta_title'], 'description' => $settings['meta_description'] ),
			'hero' => array( 'eyebrow' => $settings['hero_eyebrow'], 'title' => $settings['hero_title'], 'intro' => $settings['hero_intro'] ),
			'empty_state' => array( 'title' => $settings['empty_title'], 'text' => $settings['empty_text'] ),
		);
	}

	/** Map flat storage to the NEBOSH overview contract. */
	private static function nebosh_document( $settings ): array {
		$strengths = array();
		for ( $index = 1; $index <= 4; $index++ ) {
			$strengths[] = array( 'title' => $settings[ 'strength_' . $index . '_title' ], 'text' => $settings[ 'strength_' . $index . '_text' ] );
		}

		return array(
			'meta' => array( 'title' => $settings['meta_title'], 'description' => $settings['meta_description'] ),
			'hero' => array( 'eyebrow' => $settings['hero_eyebrow'], 'title' => $settings['hero_title'] ),
			'strengths' => $strengths,
			'intro' => array(
				'kicker' => $settings['intro_kicker'], 'title' => $settings['intro_title'],
				'paragraphs' => array( $settings['intro_paragraph_1'], $settings['intro_paragraph_2'] ),
				'cta_label' => $settings['intro_cta_label'], 'image_alt' => $settings['feature_image_alt'],
				'visual_title' => $settings['visual_title'], 'visual_text' => $settings['visual_text'],
			),
			'course_selection' => array(
				'kicker' => $settings['courses_kicker'], 'title' => $settings['courses_title'],
				'current_label' => $settings['current_label'], 'legacy_label' => $settings['legacy_label'],
				'igc_fallback' => $settings['igc_fallback'], 'iogc_fallback' => $settings['iogc_fallback'],
				'igc_cta_label' => $settings['igc_cta_label'], 'iogc_cta_label' => $settings['iogc_cta_label'],
			),
			'testimonial' => array( 'quote' => $settings['testimonial_quote'], 'author' => $settings['testimonial_author'], 'role' => $settings['testimonial_role'] ),
		);
	}

	/** Return an isolated settings group. */
	private static function settings_group( $page_key, $locale ): string {
		return self::SETTINGS_GROUP . '_' . self::sanitize_page_key( $page_key ) . '_' . ContentLocale::sanitize( $locale );
	}

	/** Allow only known stable page keys. */
	private static function sanitize_page_key( $page_key ): string {
		$page_key = is_scalar( $page_key ) ? sanitize_key( (string) $page_key ) : '';

		return in_array( $page_key, self::PAGE_KEYS, true ) ? $page_key : '';
	}

	/** Build a safe editor URL. */
	private static function admin_url( $page_key, $locale ): string {
		return add_query_arg(
			array( 'post_type' => 'course', 'page' => self::PAGE_SLUG, 'content_page' => $page_key, 'lang' => $locale ),
			admin_url( 'edit.php' )
		);
	}

	/** Return a human-readable page label. */
	private static function page_label( $page_key ): string {
		return 'nebosh' === $page_key ? __( 'NEBOSH overview', 'hse-headless' ) : __( 'Courses landing', 'hse-headless' );
	}

	/** Bound one UTF-8 string. */
	private static function bound( $value, $max_length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_length ) : substr( $value, 0, $max_length );
	}
}
