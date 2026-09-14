<?php
/**
 * Editorial settings for public legal pages.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Legal;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Owns locale-specific legal copy while Astro owns presentation. */
final class LegalPageSettings {
	public const OPTION_PREFIX = 'hse_legal_page';
	public const PAGE_KEYS     = array( 'privacy', 'terms', 'copyright' );
	public const MAX_SECTIONS  = 7;

	private const PAGE_SLUG      = 'hse-legal-pages';
	private const SETTINGS_GROUP = 'hse_legal_page_settings';
	private const MAX_TITLE      = 180;
	private const MAX_TEXT       = 1500;
	private const MAX_BODY       = 30000;

	/** @var string */
	private static $active_option_name = '';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( self::class, 'register_admin_page' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
	}

	/** Add the legal editor to the WordPress administration. */
	public static function register_admin_page(): void {
		add_menu_page(
			__( 'Legal Pages', 'hse-headless' ),
			__( 'Legal Pages', 'hse-headless' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'render_admin_page' ),
			'dashicons-privacy',
			26
		);
	}

	/** Register isolated settings for every legal page and locale. */
	public static function register_settings(): void {
		foreach ( self::PAGE_KEYS as $page_key ) {
			foreach ( ContentLocale::supported() as $locale ) {
				register_setting(
					self::settings_group( $page_key, $locale ),
					self::option_name( $page_key, $locale ),
					array(
						'type'              => 'object',
						'description'       => __( 'Structured content for one public legal page.', 'hse-headless' ),
						'sanitize_callback' => array( self::class, 'sanitize_settings' ),
						'default'           => self::defaults(),
						'show_in_rest'      => false,
					)
				);
			}
		}
	}

	/** Return the locale-specific option name. */
	public static function option_name( $page_key, $locale ): string {
		$page_key = self::sanitize_page_key( $page_key );
		$locale   = ContentLocale::sanitize( $locale );

		return self::OPTION_PREFIX . '_' . $page_key . '_' . ( $locale ?: ContentLocale::DEFAULT_LOCALE );
	}

	/** Return a stable, sanitized settings document. */
	public static function get_settings( $page_key, $locale = ContentLocale::DEFAULT_LOCALE ): array {
		$page_key = self::sanitize_page_key( $page_key );
		$locale   = ContentLocale::sanitize( $locale );
		if ( '' === $page_key || '' === $locale ) {
			return array();
		}

		$stored = get_option( self::option_name( $page_key, $locale ), array() );

		return self::sanitize_settings( is_array( $stored ) ? $stored : array() );
	}

	/** Sanitize allowlisted fields and discard unknown input. */
	public static function sanitize_settings( $value ): array {
		$value  = is_array( $value ) ? $value : array();
		$result = array();

		foreach ( self::field_definitions() as $key => $field ) {
			$raw = $value[ $key ] ?? '';
			$raw = is_scalar( $raw ) ? (string) $raw : '';

			if ( 'html' === $field['type'] ) {
				$sanitized = wp_kses_post( $raw );
			} elseif ( 'textarea' === $field['type'] ) {
				$sanitized = sanitize_textarea_field( $raw );
			} else {
				$sanitized = sanitize_text_field( $raw );
			}

			$result[ $key ] = self::bound( $sanitized, (int) $field['max'] );
		}

		return $result;
	}

	/** Compose the public legal page document. */
	public static function get_public_document( $page_key, $locale = ContentLocale::DEFAULT_LOCALE ) {
		$page_key = self::sanitize_page_key( $page_key );
		$locale   = ContentLocale::sanitize( $locale );
		if ( '' === $page_key || '' === $locale ) {
			return new \WP_Error( 'hse_legal_page_invalid', __( 'Legal page or language is invalid.', 'hse-headless' ), array( 'status' => 400 ) );
		}

		$settings = self::get_settings( $page_key, $locale );
		foreach ( array( 'meta_title', 'meta_description', 'eyebrow', 'title', 'intro', 'last_updated' ) as $required_key ) {
			if ( '' === ( $settings[ $required_key ] ?? '' ) ) {
				return new \WP_Error( 'hse_legal_page_not_configured', __( 'Legal page content is not configured.', 'hse-headless' ), array( 'status' => 503 ) );
			}
		}

		$sections = array();
		for ( $index = 1; $index <= self::MAX_SECTIONS; $index++ ) {
			$title = $settings[ 'section_' . $index . '_title' ] ?? '';
			$body  = $settings[ 'section_' . $index . '_body' ] ?? '';
			if ( '' === $title && '' === $body ) {
				continue;
			}
			if ( '' === $title || '' === $body ) {
				return new \WP_Error( 'hse_legal_page_incomplete_section', __( 'Every legal section requires both a title and body.', 'hse-headless' ), array( 'status' => 503 ) );
			}
			$sections[] = array(
				'title'     => $title,
				'body_html' => wp_kses_post( wpautop( $body ) ),
			);
		}

		if ( array() === $sections ) {
			return new \WP_Error( 'hse_legal_page_not_configured', __( 'Legal page content is not configured.', 'hse-headless' ), array( 'status' => 503 ) );
		}

		return array(
			'schema_version' => 1,
			'page_key'       => $page_key,
			'locale'         => $locale,
			'content'        => array(
				'meta'         => array(
					'title'       => $settings['meta_title'],
					'description' => $settings['meta_description'],
				),
				'eyebrow'      => $settings['eyebrow'],
				'title'        => $settings['title'],
				'intro'        => $settings['intro'],
				'last_updated' => $settings['last_updated'],
				'sections'     => $sections,
			),
		);
	}

	/** Render page and language tabs plus the editorial form. */
	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page_key = isset( $_GET['content_page'] ) ? self::sanitize_page_key( wp_unslash( $_GET['content_page'] ) ) : 'privacy';
		$page_key = $page_key ?: 'privacy';
		$locale   = isset( $_GET['lang'] ) ? ContentLocale::sanitize( wp_unslash( $_GET['lang'] ) ) : ContentLocale::DEFAULT_LOCALE;
		$locale   = $locale ?: ContentLocale::DEFAULT_LOCALE;
		$settings = self::get_settings( $page_key, $locale );
		self::$active_option_name = self::option_name( $page_key, $locale );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Legal Pages', 'hse-headless' ); ?></h1>
			<p><?php esc_html_e( 'Edit public legal copy here. Astro continues to own layout, routes, typography, and numbering.', 'hse-headless' ); ?></p>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Legal page', 'hse-headless' ); ?>">
				<?php foreach ( self::PAGE_KEYS as $supported_page ) : ?>
					<a class="nav-tab <?php echo $page_key === $supported_page ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::admin_url( $supported_page, $locale ) ); ?>"><?php echo esc_html( self::page_label( $supported_page ) ); ?></a>
				<?php endforeach; ?>
			</nav>
			<h2 class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Content language', 'hse-headless' ); ?>">
				<?php foreach ( ContentLocale::supported() as $supported_locale ) : ?>
					<a class="nav-tab <?php echo $locale === $supported_locale ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::admin_url( $page_key, $supported_locale ) ); ?>"><?php echo esc_html( ContentLocale::label( $supported_locale ) ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form action="options.php" method="post">
				<?php settings_fields( self::settings_group( $page_key, $locale ) ); ?>
				<table class="form-table" role="presentation"><tbody>
					<?php foreach ( array_slice( self::field_definitions(), 0, 6, true ) as $key => $field ) : ?>
						<?php self::render_field_row( $key, $field, $settings[ $key ] ); ?>
					<?php endforeach; ?>
				</tbody></table>

				<?php for ( $index = 1; $index <= self::MAX_SECTIONS; $index++ ) : ?>
					<h2><?php echo esc_html( sprintf( __( 'Section %d', 'hse-headless' ), $index ) ); ?></h2>
					<table class="form-table" role="presentation"><tbody>
						<?php self::render_field_row( 'section_' . $index . '_title', self::field_definitions()[ 'section_' . $index . '_title' ], $settings[ 'section_' . $index . '_title' ] ); ?>
						<?php self::render_field_row( 'section_' . $index . '_body', self::field_definitions()[ 'section_' . $index . '_body' ], $settings[ 'section_' . $index . '_body' ] ); ?>
					</tbody></table>
				<?php endfor; ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** @return array<string, array{label:string,type:string,max:int,required:bool}> */
	private static function field_definitions(): array {
		$fields = array(
			'meta_title'       => array( 'label' => __( 'SEO title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE, 'required' => true ),
			'meta_description' => array( 'label' => __( 'SEO description', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT, 'required' => true ),
			'eyebrow'          => array( 'label' => __( 'Eyebrow', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE, 'required' => true ),
			'title'            => array( 'label' => __( 'Page title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE, 'required' => true ),
			'intro'            => array( 'label' => __( 'Introduction', 'hse-headless' ), 'type' => 'textarea', 'max' => self::MAX_TEXT, 'required' => true ),
			'last_updated'     => array( 'label' => __( 'Last updated text', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE, 'required' => true ),
		);

		for ( $index = 1; $index <= self::MAX_SECTIONS; $index++ ) {
			$fields[ 'section_' . $index . '_title' ] = array( 'label' => __( 'Section title', 'hse-headless' ), 'type' => 'text', 'max' => self::MAX_TITLE, 'required' => false );
			$fields[ 'section_' . $index . '_body' ]  = array( 'label' => __( 'Section content', 'hse-headless' ), 'type' => 'html', 'max' => self::MAX_BODY, 'required' => false );
		}

		return $fields;
	}

	/** @return array<string, string> */
	private static function defaults(): array {
		return array_fill_keys( array_keys( self::field_definitions() ), '' );
	}

	/** Render one text, textarea, or rich-text field. */
	private static function render_field_row( $key, $field, $value ): void {
		$field_id   = 'hse-legal-page-' . str_replace( '_', '-', $key );
		$field_name = self::$active_option_name . '[' . $key . ']';
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
			<td>
				<?php if ( 'html' === $field['type'] ) : ?>
					<?php
					wp_editor(
						$value,
						str_replace( '-', '_', $field_id ),
						array(
							'textarea_name' => $field_name,
							'textarea_rows' => 8,
							'media_buttons' => false,
						)
					);
					?>
				<?php elseif ( 'textarea' === $field['type'] ) : ?>
					<textarea class="large-text" id="<?php echo esc_attr( $field_id ); ?>" maxlength="<?php echo esc_attr( $field['max'] ); ?>" name="<?php echo esc_attr( $field_name ); ?>" rows="4" <?php echo $field['required'] ? 'required' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
				<?php else : ?>
					<input class="regular-text" id="<?php echo esc_attr( $field_id ); ?>" maxlength="<?php echo esc_attr( $field['max'] ); ?>" name="<?php echo esc_attr( $field_name ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/** Normalize and allowlist a legal page key. */
	private static function sanitize_page_key( $page_key ): string {
		$page_key = is_scalar( $page_key ) ? sanitize_key( (string) $page_key ) : '';

		return in_array( $page_key, self::PAGE_KEYS, true ) ? $page_key : '';
	}

	/** Bound a string without requiring mbstring. */
	private static function bound( $value, $max ): string {
		$value = (string) $value;

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
	}

	/** Build one admin tab URL. */
	private static function admin_url( $page_key, $locale ): string {
		return add_query_arg(
			array( 'page' => self::PAGE_SLUG, 'content_page' => $page_key, 'lang' => $locale ),
			admin_url( 'admin.php' )
		);
	}

	/** Human-readable page tab label. */
	private static function page_label( $page_key ): string {
		$labels = array(
			'privacy'   => __( 'Privacy Policy', 'hse-headless' ),
			'terms'     => __( 'Terms and Conditions', 'hse-headless' ),
			'copyright' => __( 'Copyright', 'hse-headless' ),
		);

		return $labels[ $page_key ] ?? '';
	}

	/** Return the unique Settings API group. */
	private static function settings_group( $page_key, $locale ): string {
		return self::SETTINGS_GROUP . '_' . $page_key . '_' . $locale;
	}
}
