<?php
/**
 * Company Page editorial settings and shared About profile.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Company;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the canonical company profile shared by Homepage and Company Page.
 */
final class CompanyPageSettings {
	public const OPTION_NAME = 'hse_company_page';
	public const PAGE_KEY    = 'company';

	private const PAGE_SLUG       = 'hse-company-page';
	private const SETTINGS_GROUP  = 'hse_company_page_settings';
	private const MAX_TITLE       = 160;
	private const MAX_LABEL       = 160;
	private const MAX_DESCRIPTION = 800;
	private const MAX_URL         = 2048;

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( self::class, 'register_admin_page' ) );
		add_action( 'admin_init', array( self::class, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the dedicated Company Page editor.
	 */
	public static function register_admin_page(): void {
		add_menu_page(
			__( 'Company Page', 'hse-headless' ),
			__( 'Company Page', 'hse-headless' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'render_admin_page' ),
			'dashicons-building',
			22
		);
	}

	/**
	 * Register the bounded settings document.
	 */
	public static function register_setting(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'object',
				'description'       => __( 'Company Page content shared with the Homepage About section.', 'hse-headless' ),
				'sanitize_callback' => array( self::class, 'sanitize_settings' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Load the shared image picker only on the Company Page screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook_suffix ): void {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		$plugin_file = dirname( __DIR__, 2 ) . '/hse-headless.php';

		wp_enqueue_media();
		wp_enqueue_style( 'hse-company-admin', plugins_url( 'assets/company-admin.css', $plugin_file ), array(), '0.4.0' );
		wp_enqueue_script( 'hse-company-admin', plugins_url( 'assets/company-admin.js', $plugin_file ), array(), '0.4.0', true );
		wp_localize_script(
			'hse-company-admin',
			'hseCompanyAdmin',
			array(
				'dialogTitle' => __( 'Select image', 'hse-headless' ),
				'buttonLabel' => __( 'Use this image', 'hse-headless' ),
			)
		);
	}

	/**
	 * Return sanitized settings with a stable shape.
	 *
	 * @return array<string, int|string>
	 */
	public static function get_settings(): array {
		$stored = get_option( self::OPTION_NAME, array() );

		return self::sanitize_settings( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Sanitize all supported fields and discard unknown input.
	 *
	 * @param mixed $value Raw settings value.
	 * @return array<string, int|string>
	 */
	public static function sanitize_settings( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'hero_eyebrow'             => self::sanitize_text( $value['hero_eyebrow'] ?? '', self::MAX_LABEL ),
			'hero_title'               => self::sanitize_text( $value['hero_title'] ?? '', self::MAX_TITLE ),
			'about_eyebrow'            => self::sanitize_text( $value['about_eyebrow'] ?? '', self::MAX_LABEL ),
			'about_headline'           => self::sanitize_text( $value['about_headline'] ?? '', self::MAX_TITLE ),
			'about_description'        => self::sanitize_textarea( $value['about_description'] ?? '', self::MAX_DESCRIPTION ),
			'about_primary_cta_label'  => self::sanitize_text( $value['about_primary_cta_label'] ?? '', self::MAX_LABEL ),
			'about_primary_cta_url'    => self::sanitize_link_url( $value['about_primary_cta_url'] ?? '' ),
			'about_secondary_cta_label'=> self::sanitize_text( $value['about_secondary_cta_label'] ?? '', self::MAX_LABEL ),
			'about_secondary_cta_url'  => self::sanitize_link_url( $value['about_secondary_cta_url'] ?? '' ),
			'about_signature_label'    => self::sanitize_text( $value['about_signature_label'] ?? '', self::MAX_LABEL ),
			'about_primary_image_id'   => self::sanitize_image_id( $value['about_primary_image_id'] ?? 0 ),
			'about_secondary_image_id' => self::sanitize_image_id( $value['about_secondary_image_id'] ?? 0 ),
			'value_training_title'     => self::sanitize_text( $value['value_training_title'] ?? '', self::MAX_TITLE ),
			'value_training_description' => self::sanitize_text( $value['value_training_description'] ?? '', self::MAX_DESCRIPTION ),
			'value_management_title'     => self::sanitize_text( $value['value_management_title'] ?? '', self::MAX_TITLE ),
			'value_management_description'=> self::sanitize_text( $value['value_management_description'] ?? '', self::MAX_DESCRIPTION ),
			'value_consultancy_title'     => self::sanitize_text( $value['value_consultancy_title'] ?? '', self::MAX_TITLE ),
			'value_consultancy_description'=> self::sanitize_text( $value['value_consultancy_description'] ?? '', self::MAX_DESCRIPTION ),
			'company_intro_cta_label'     => self::sanitize_text( $value['company_intro_cta_label'] ?? '', self::MAX_LABEL ),
			'company_intro_cta_url'       => self::sanitize_link_url( $value['company_intro_cta_url'] ?? '' ),
		);
	}

	/**
	 * Compose the shared public profile without WordPress attachment IDs.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_public_profile() {
		$settings = self::get_settings();
		$required = array(
			'about_eyebrow', 'about_headline', 'about_description', 'about_primary_cta_label',
			'about_primary_cta_url', 'about_secondary_cta_label', 'about_secondary_cta_url',
			'about_signature_label', 'about_primary_image_id', 'about_secondary_image_id',
			'value_training_title', 'value_training_description', 'value_management_title',
			'value_management_description', 'value_consultancy_title', 'value_consultancy_description',
		);

		foreach ( $required as $field ) {
			if ( '' === $settings[ $field ] || 0 === $settings[ $field ] ) {
				return new \WP_Error(
					'hse_company_page_not_configured',
					__( 'Company Page content is not configured.', 'hse-headless' ),
					array( 'status' => 503 )
				);
			}
		}

		$primary_image   = self::prepare_image( (int) $settings['about_primary_image_id'] );
		$secondary_image = self::prepare_image( (int) $settings['about_secondary_image_id'] );
		if ( is_wp_error( $primary_image ) ) {
			return $primary_image;
		}
		if ( is_wp_error( $secondary_image ) ) {
			return $secondary_image;
		}

		return array(
			'eyebrow'         => $settings['about_eyebrow'],
			'headline'        => $settings['about_headline'],
			'description'     => $settings['about_description'],
			'primary_image'   => $primary_image,
			'secondary_image' => $secondary_image,
			'primary_cta'     => array(
				'label' => $settings['about_primary_cta_label'],
				'url'   => $settings['about_primary_cta_url'],
			),
			'secondary_cta'   => array(
				'label' => $settings['about_secondary_cta_label'],
				'url'   => $settings['about_secondary_cta_url'],
			),
			'signature_label' => $settings['about_signature_label'],
			'values'          => array(
				array(
					'value_key'   => 'professional-training',
					'icon_key'    => 'training',
					'title'       => $settings['value_training_title'],
					'description' => $settings['value_training_description'],
				),
				array(
					'value_key'   => 'hse-management',
					'icon_key'    => 'management',
					'title'       => $settings['value_management_title'],
					'description' => $settings['value_management_description'],
				),
				array(
					'value_key'   => 'on-site-consultancy',
					'icon_key'    => 'consultancy',
					'title'       => $settings['value_consultancy_title'],
					'description' => $settings['value_consultancy_description'],
				),
			),
		);
	}

	/**
	 * Return page-specific hero and intro values.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_public_page_fields(): array {
		$settings = self::get_settings();

		return array(
			'hero'      => array(
				'eyebrow' => $settings['hero_eyebrow'],
				'title'   => $settings['hero_title'],
			),
			'intro_cta' => array(
				'label' => $settings['company_intro_cta_label'],
				'url'   => $settings['company_intro_cta_url'],
			),
		);
	}

	/**
	 * Render the dedicated editor form.
	 */
	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::get_settings();
		?>
		<div class="wrap hse-company-admin">
			<h1><?php esc_html_e( 'Company Page', 'hse-headless' ); ?></h1>
			<p><?php esc_html_e( 'The shared About profile appears on both the Homepage and Company Page. Layout and animation remain in Astro.', 'hse-headless' ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<h2><?php esc_html_e( 'Company page hero', 'hse-headless' ); ?></h2>
				<?php self::render_text_field( 'hero_eyebrow', __( 'Hero eyebrow', 'hse-headless' ), $settings['hero_eyebrow'], self::MAX_LABEL ); ?>
				<?php self::render_text_field( 'hero_title', __( 'Hero title', 'hse-headless' ), $settings['hero_title'], self::MAX_TITLE ); ?>

				<h2><?php esc_html_e( 'Shared About profile', 'hse-headless' ); ?></h2>
				<?php self::render_text_field( 'about_eyebrow', __( 'Eyebrow', 'hse-headless' ), $settings['about_eyebrow'], self::MAX_LABEL ); ?>
				<?php self::render_text_field( 'about_headline', __( 'Headline', 'hse-headless' ), $settings['about_headline'], self::MAX_TITLE ); ?>
				<?php self::render_textarea_field( 'about_description', __( 'Description', 'hse-headless' ), $settings['about_description'] ); ?>
				<?php self::render_text_field( 'about_primary_cta_label', __( 'Primary CTA label', 'hse-headless' ), $settings['about_primary_cta_label'], self::MAX_LABEL ); ?>
				<?php self::render_text_field( 'about_primary_cta_url', __( 'Primary CTA URL', 'hse-headless' ), $settings['about_primary_cta_url'], self::MAX_URL ); ?>
				<?php self::render_text_field( 'about_secondary_cta_label', __( 'Secondary CTA label', 'hse-headless' ), $settings['about_secondary_cta_label'], self::MAX_LABEL ); ?>
				<?php self::render_text_field( 'about_secondary_cta_url', __( 'Secondary CTA URL', 'hse-headless' ), $settings['about_secondary_cta_url'], self::MAX_URL ); ?>
				<?php self::render_text_field( 'about_signature_label', __( 'Signature label', 'hse-headless' ), $settings['about_signature_label'], self::MAX_LABEL ); ?>
				<?php self::render_image_field( 'about_primary_image_id', __( 'Primary image', 'hse-headless' ), (int) $settings['about_primary_image_id'] ); ?>
				<?php self::render_image_field( 'about_secondary_image_id', __( 'Secondary image', 'hse-headless' ), (int) $settings['about_secondary_image_id'] ); ?>

				<h2><?php esc_html_e( 'Homepage value highlights', 'hse-headless' ); ?></h2>
				<?php self::render_text_field( 'value_training_title', __( 'Training title', 'hse-headless' ), $settings['value_training_title'], self::MAX_TITLE ); ?>
				<?php self::render_text_field( 'value_training_description', __( 'Training description', 'hse-headless' ), $settings['value_training_description'], self::MAX_DESCRIPTION ); ?>
				<?php self::render_text_field( 'value_management_title', __( 'Management title', 'hse-headless' ), $settings['value_management_title'], self::MAX_TITLE ); ?>
				<?php self::render_text_field( 'value_management_description', __( 'Management description', 'hse-headless' ), $settings['value_management_description'], self::MAX_DESCRIPTION ); ?>
				<?php self::render_text_field( 'value_consultancy_title', __( 'Consultancy title', 'hse-headless' ), $settings['value_consultancy_title'], self::MAX_TITLE ); ?>
				<?php self::render_text_field( 'value_consultancy_description', __( 'Consultancy description', 'hse-headless' ), $settings['value_consultancy_description'], self::MAX_DESCRIPTION ); ?>

				<h2><?php esc_html_e( 'Company page intro action', 'hse-headless' ); ?></h2>
				<?php self::render_text_field( 'company_intro_cta_label', __( 'CTA label', 'hse-headless' ), $settings['company_intro_cta_label'], self::MAX_LABEL ); ?>
				<?php self::render_text_field( 'company_intro_cta_url', __( 'CTA URL', 'hse-headless' ), $settings['company_intro_cta_url'], self::MAX_URL ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** Render one required text field. */
	private static function render_text_field( $key, $label, $value, $max_length ): void {
		self::render_field_start( $key, $label );
		?>
		<input class="regular-text" id="hse-<?php echo esc_attr( str_replace( '_', '-', $key ) ); ?>" maxlength="<?php echo esc_attr( $max_length ); ?>" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>" required>
		<?php
		self::render_field_end();
	}

	/** Render one required textarea field. */
	private static function render_textarea_field( $key, $label, $value ): void {
		self::render_field_start( $key, $label );
		?>
		<textarea class="large-text" id="hse-<?php echo esc_attr( str_replace( '_', '-', $key ) ); ?>" maxlength="<?php echo esc_attr( self::MAX_DESCRIPTION ); ?>" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" rows="4" required><?php echo esc_textarea( $value ); ?></textarea>
		<?php
		self::render_field_end();
	}

	/** Render one WordPress Media Library field. */
	private static function render_image_field( $key, $label, $image_id ): void {
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		self::render_field_start( $key, $label );
		?>
		<div data-hse-media-field>
			<input data-hse-image-id name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" type="hidden" value="<?php echo esc_attr( $image_id ); ?>">
			<div class="hse-company-admin__image-preview" data-hse-image-preview>
				<?php if ( $image_url ) : ?><img src="<?php echo esc_url( $image_url ); ?>" alt=""><?php endif; ?>
			</div>
			<button class="button" data-hse-select-image type="button"><?php esc_html_e( 'Select image', 'hse-headless' ); ?></button>
			<button class="button-link-delete" data-hse-remove-image type="button"<?php echo $image_url ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove image', 'hse-headless' ); ?></button>
		</div>
		<?php
		self::render_field_end();
	}

	/** Start a standard WordPress settings row. */
	private static function render_field_start( $key, $label ): void {
		?>
		<table class="form-table" role="presentation"><tbody><tr>
			<th scope="row"><label for="hse-<?php echo esc_attr( str_replace( '_', '-', $key ) ); ?>"><?php echo esc_html( $label ); ?></label></th><td>
		<?php
	}

	/** Close a standard WordPress settings row. */
	private static function render_field_end(): void {
		?></td></tr></tbody></table><?php
	}

	/** @return array<string, int|string> */
	private static function defaults(): array {
		return array_fill_keys(
			array(
				'hero_eyebrow', 'hero_title', 'about_eyebrow', 'about_headline', 'about_description',
				'about_primary_cta_label', 'about_primary_cta_url', 'about_secondary_cta_label',
				'about_secondary_cta_url', 'about_signature_label', 'value_training_title',
				'value_training_description', 'value_management_title', 'value_management_description',
				'value_consultancy_title', 'value_consultancy_description', 'company_intro_cta_label',
				'company_intro_cta_url',
			),
			''
		) + array( 'about_primary_image_id' => 0, 'about_secondary_image_id' => 0 );
	}

	/** Sanitize one image attachment ID. */
	private static function sanitize_image_id( $value ): int {
		$image_id = absint( $value );

		return $image_id && wp_attachment_is_image( $image_id ) ? $image_id : 0;
	}

	/** Sanitize and bound one plain text value. */
	private static function sanitize_text( $value, $max_length ): string {
		return self::bound( is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '', $max_length );
	}

	/** Sanitize and bound one multiline plain text value. */
	private static function sanitize_textarea( $value, $max_length ): string {
		return self::bound( is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '', $max_length );
	}

	/** Sanitize an internal path, anchor, or absolute HTTP(S) URL. */
	private static function sanitize_link_url( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = self::bound( trim( (string) $value ), self::MAX_URL );
		if ( preg_match( '/^#[A-Za-z][A-Za-z0-9_-]*$/', $value ) ) {
			return $value;
		}
		if ( 0 === strpos( $value, '/' ) && 0 !== strpos( $value, '//' ) ) {
			return esc_url_raw( $value, array( 'http', 'https' ) );
		}

		return wp_http_validate_url( $value ) ? esc_url_raw( $value, array( 'http', 'https' ) ) : '';
	}

	/** Bound a UTF-8 string. */
	private static function bound( $value, $max_length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_length ) : substr( $value, 0, $max_length );
	}

	/** Resolve an attachment into the public image contract. */
	private static function prepare_image( $attachment_id ) {
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( ! $image ) {
			return new \WP_Error( 'hse_company_image_unavailable', __( 'A Company Page image is unavailable.', 'hse-headless' ), array( 'status' => 503 ) );
		}

		return array(
			'url'    => $image[0],
			'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'width'  => (int) $image[1],
			'height' => (int) $image[2],
		);
	}
}
