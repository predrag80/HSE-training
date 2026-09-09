<?php
/**
 * Hero Slide metadata, validation, and editor fields.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Homepage;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the fields and publication rules for Homepage Hero Slides.
 */
final class HeroSlideMeta {
	public const SLIDE_KEY          = 'slide_key';
	public const LEADING_TITLE      = 'leading_title';
	public const EMPHASIZED_TITLE   = 'emphasized_title';
	public const PRIMARY_CTA_LABEL  = 'primary_cta_label';
	public const PRIMARY_CTA_URL    = 'primary_cta_url';
	public const MESSAGE_PREFIX     = 'message_prefix';
	public const MESSAGE_LINK_LABEL = 'message_link_label';
	public const MESSAGE_LINK_URL   = 'message_link_url';
	public const MAX_PUBLISHED      = 5;

	private const LOCKED_SLIDE_KEY = '_hse_locked_slide_key';
	private const NONCE_ACTION     = 'hse_save_hero_slide_details';
	private const NONCE_NAME       = 'hse_hero_slide_details_nonce';
	private const MAX_KEY_LENGTH   = 80;
	private const MAX_TITLE_LENGTH = 120;
	private const MAX_LABEL_LENGTH = 160;
	private const MAX_URL_LENGTH   = 2048;

	/** @var string */
	private static $admin_error_code = '';

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'add_meta_boxes_hero_slide', array( self::class, 'register_meta_box' ) );
		add_action( 'save_post_hero_slide', array( self::class, 'save_meta_box' ), 10, 2 );
		add_action( 'transition_post_status', array( self::class, 'lock_key_on_publish' ), 10, 3 );
		add_action( 'added_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );
		add_action( 'updated_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );

		add_filter( 'wp_insert_post_data', array( self::class, 'validate_publish' ), 10, 4 );
		add_filter( 'add_post_metadata', array( self::class, 'guard_slide_key_add' ), 10, 5 );
		add_filter( 'update_post_metadata', array( self::class, 'guard_slide_key_update' ), 10, 5 );
		add_filter( 'redirect_post_location', array( self::class, 'add_admin_error_to_redirect' ), 10, 2 );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/**
	 * Register private metadata with sanitization and revision support.
	 */
	public static function register_meta(): void {
		ContentLocale::register_post_meta( HeroSlidePostType::POST_TYPE );

		$fields = array(
			self::SLIDE_KEY          => array( self::class, 'sanitize_slide_key' ),
			self::LEADING_TITLE      => array( self::class, 'sanitize_title_text' ),
			self::EMPHASIZED_TITLE   => array( self::class, 'sanitize_title_text' ),
			self::PRIMARY_CTA_LABEL  => array( self::class, 'sanitize_label_text' ),
			self::PRIMARY_CTA_URL    => array( self::class, 'sanitize_link_url' ),
			self::MESSAGE_PREFIX     => array( self::class, 'sanitize_label_text' ),
			self::MESSAGE_LINK_LABEL => array( self::class, 'sanitize_label_text' ),
			self::MESSAGE_LINK_URL   => array( self::class, 'sanitize_link_url' ),
		);

		foreach ( $fields as $meta_key => $sanitize_callback ) {
			register_post_meta(
				HeroSlidePostType::POST_TYPE,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => array( self::class, 'can_edit_meta' ),
					'revisions_enabled' => true,
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * Normalize a stable slide key.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_slide_key( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = strtolower( remove_accents( trim( (string) $value ) ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
		$value = trim( (string) $value, '-' );
		$value = substr( $value, 0, self::MAX_KEY_LENGTH );

		return rtrim( $value, '-' );
	}

	/**
	 * Sanitize a title fragment.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_title_text( $value ): string {
		return self::sanitize_bounded_text( $value, self::MAX_TITLE_LENGTH );
	}

	/**
	 * Sanitize a CTA or message label.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_label_text( $value ): string {
		return self::sanitize_bounded_text( $value, self::MAX_LABEL_LENGTH );
	}

	/**
	 * Accept a same-site path, page anchor, or absolute HTTP(S) URL.
	 *
	 * @param mixed $value Raw URL.
	 */
	public static function sanitize_link_url( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, self::MAX_URL_LENGTH ) : substr( $value, 0, self::MAX_URL_LENGTH );

		if ( preg_match( '/^#[A-Za-z][A-Za-z0-9_-]*$/', $value ) ) {
			return $value;
		}

		if ( 0 === strpos( $value, '/' ) && 0 !== strpos( $value, '//' ) ) {
			return esc_url_raw( $value, array( 'http', 'https' ) );
		}

		return wp_http_validate_url( $value ) ? esc_url_raw( $value, array( 'http', 'https' ) ) : '';
	}

	/**
	 * Authorize metadata changes.
	 *
	 * @param bool   $allowed   Existing authorization result.
	 * @param string $meta_key  Metadata key.
	 * @param int    $object_id Hero Slide post ID.
	 */
	public static function can_edit_meta( $allowed, $meta_key, $object_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $object_id );
	}

	/**
	 * Register the native Hero Slide details box.
	 */
	public static function register_meta_box(): void {
		remove_meta_box( 'postcustom', HeroSlidePostType::POST_TYPE, 'normal' );
		add_meta_box(
			'hse-hero-slide-details',
			__( 'Hero slide details', 'hse-headless' ),
			array( self::class, 'render_meta_box' ),
			HeroSlidePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render editor fields.
	 *
	 * @param \WP_Post $post Hero Slide post.
	 */
	public static function render_meta_box( $post ): void {
		$is_locked = '' !== get_post_meta( $post->ID, self::LOCKED_SLIDE_KEY, true );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		ContentLocale::render_editor_field( $post->ID );
		self::render_text_field( self::SLIDE_KEY, __( 'Slide key', 'hse-headless' ), $post->ID, self::MAX_KEY_LENGTH, $is_locked, __( 'Stable identifier using lowercase letters, numbers, and hyphens. It locks after first publication.', 'hse-headless' ) );
		self::render_text_field( self::LEADING_TITLE, __( 'Leading title', 'hse-headless' ), $post->ID, self::MAX_TITLE_LENGTH );
		self::render_text_field( self::EMPHASIZED_TITLE, __( 'Emphasized title', 'hse-headless' ), $post->ID, self::MAX_TITLE_LENGTH );
		self::render_text_field( self::PRIMARY_CTA_LABEL, __( 'Primary CTA label', 'hse-headless' ), $post->ID, self::MAX_LABEL_LENGTH );
		self::render_text_field( self::PRIMARY_CTA_URL, __( 'Primary CTA URL', 'hse-headless' ), $post->ID, self::MAX_URL_LENGTH, false, __( 'Internal path, page anchor, or full HTTP(S) URL.', 'hse-headless' ) );
		self::render_text_field( self::MESSAGE_PREFIX, __( 'Supporting message', 'hse-headless' ), $post->ID, self::MAX_LABEL_LENGTH );
		self::render_text_field( self::MESSAGE_LINK_LABEL, __( 'Supporting link label', 'hse-headless' ), $post->ID, self::MAX_LABEL_LENGTH );
		self::render_text_field( self::MESSAGE_LINK_URL, __( 'Supporting link URL', 'hse-headless' ), $post->ID, self::MAX_URL_LENGTH, false, __( 'Internal path, page anchor, or full HTTP(S) URL.', 'hse-headless' ) );
		?>
		<p class="description">
			<?php esc_html_e( 'Set a Hero image in the Featured image panel. Use the Order field in Page Attributes to control slider sequence. At most five slides can be published.', 'hse-headless' ); ?>
		</p>
		<?php
	}

	/**
	 * Render one required text input.
	 *
	 * @param string $meta_key   Field/meta key.
	 * @param string $label      Field label.
	 * @param int    $post_id    Hero Slide post ID.
	 * @param int    $max_length Maximum input length.
	 * @param bool   $readonly   Whether the key is locked.
	 * @param string $description Optional help text.
	 */
	private static function render_text_field( $meta_key, $label, $post_id, $max_length, $readonly = false, $description = '' ): void {
		$field_id = 'hse-hero-' . str_replace( '_', '-', $meta_key );
		$value    = get_post_meta( $post_id, $meta_key, true );
		?>
		<p>
			<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
			<input class="widefat" id="<?php echo esc_attr( $field_id ); ?>" maxlength="<?php echo esc_attr( $max_length ); ?>" name="hse_<?php echo esc_attr( $meta_key ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>" <?php wp_readonly( $readonly ); ?> required>
			<?php if ( $description ) : ?>
				<span class="description"><?php echo esc_html( $description ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Save editor fields.
	 *
	 * @param int      $post_id Hero Slide post ID.
	 * @param \WP_Post $post    Hero Slide post.
	 */
	public static function save_meta_box( $post_id, $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_string( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION )
			|| ! current_user_can( 'edit_post', $post_id )
			|| wp_is_post_autosave( $post_id )
			|| wp_is_post_revision( $post_id ) ) {
			return;
		}

		$locale = ContentLocale::save_post_locale( $post_id );
		if ( is_wp_error( $locale ) ) {
			self::$admin_error_code = $locale->get_error_code();

			return;
		}

		foreach ( self::field_sanitizers() as $meta_key => $sanitize_callback ) {
			$field_name = 'hse_' . $meta_key;
			$raw_value  = isset( $_POST[ $field_name ] ) ? wp_unslash( $_POST[ $field_name ] ) : '';
			$value      = call_user_func( $sanitize_callback, $raw_value );

			if ( self::SLIDE_KEY === $meta_key ) {
				$validation = self::validate_slide_key( $value, $post_id );
				if ( is_wp_error( $validation ) ) {
					self::$admin_error_code = $validation->get_error_code();
					continue;
				}
			}

			update_post_meta( $post_id, $meta_key, $value );
		}

		if ( 'publish' === $post->post_status && ! self::is_complete( $post_id ) ) {
			self::$admin_error_code = 'hse_hero_slide_incomplete';
			self::move_to_draft( $post_id );
		}
	}

	/**
	 * Prevent incomplete, duplicate, or excessive slide publication.
	 *
	 * @param array $data                Sanitized post data.
	 * @param array $postarr             Post data.
	 * @param array $unsanitized_postarr Unsanitized post data.
	 * @param bool  $update              Whether this is an update.
	 * @return array
	 */
	public static function validate_publish( $data, $postarr, $unsanitized_postarr, $update ) {
		unset( $unsanitized_postarr, $update );

		if ( HeroSlidePostType::POST_TYPE !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}

		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$locale  = ContentLocale::get_posted_or_stored_locale( $post_id );
		$locale_validation = ContentLocale::validate_for_post( $locale, $post_id );
		if ( is_wp_error( $locale_validation ) ) {
			$data['post_status']     = 'draft';
			self::$admin_error_code = $locale_validation->get_error_code();

			return $data;
		}

		$key     = self::posted_or_stored_value( self::SLIDE_KEY, $post_id );
		$key_validation = self::validate_slide_key( $key, $post_id );

		if ( is_wp_error( $key_validation ) ) {
			$data['post_status']     = 'draft';
			self::$admin_error_code = $key_validation->get_error_code();

			return $data;
		}

		foreach ( array_keys( self::field_sanitizers() ) as $meta_key ) {
			if ( '' === self::posted_or_stored_value( $meta_key, $post_id ) ) {
				$data['post_status']     = 'draft';
				self::$admin_error_code = 'hse_hero_slide_incomplete';

				return $data;
			}
		}

		$thumbnail_id = isset( $_POST['_thumbnail_id'] ) ? absint( $_POST['_thumbnail_id'] ) : (int) get_post_thumbnail_id( $post_id );
		if ( ! $thumbnail_id || ! wp_attachment_is_image( $thumbnail_id ) ) {
			$data['post_status']     = 'draft';
			self::$admin_error_code = 'hse_hero_slide_incomplete';

			return $data;
		}

		$current_status = $post_id ? get_post_status( $post_id ) : false;
		if ( 'publish' !== $current_status && self::MAX_PUBLISHED <= self::published_count( $locale ) ) {
			$data['post_status']     = 'draft';
			self::$admin_error_code = 'hse_hero_slide_limit';
		}

		return $data;
	}

	/**
	 * Validate stable key presence, immutability, and uniqueness.
	 *
	 * @param mixed $value   Candidate key.
	 * @param int   $post_id Current Hero Slide post ID.
	 * @return true|\WP_Error
	 */
	public static function validate_slide_key( $value, $post_id = 0 ) {
		$slide_key = self::sanitize_slide_key( $value );
		if ( '' === $slide_key ) {
			return new \WP_Error( 'hse_slide_key_required', __( 'Slide key is required.', 'hse-headless' ) );
		}

		$locked_key = $post_id ? get_post_meta( $post_id, self::LOCKED_SLIDE_KEY, true ) : '';
		if ( '' !== $locked_key && $locked_key !== $slide_key ) {
			return new \WP_Error( 'hse_slide_key_immutable', __( 'Slide key cannot change after publication.', 'hse-headless' ) );
		}

		$locale = ContentLocale::get_posted_or_stored_locale( $post_id );
		if ( self::slide_key_exists( $slide_key, $post_id, $locale ) ) {
			return new \WP_Error( 'hse_slide_key_duplicate', __( 'Another Hero Slide already uses this slide key.', 'hse-headless' ) );
		}

		return true;
	}

	/**
	 * Enforce key rules for add_post_meta().
	 */
	public static function guard_slide_key_add( $check, $object_id, $meta_key, $meta_value, $unique ) {
		unset( $unique );

		return self::guard_slide_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Enforce key rules for update_post_meta().
	 */
	public static function guard_slide_key_update( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		unset( $prev_value );

		return self::guard_slide_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Lock a stable key when a Hero Slide is first published.
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Previous post status.
	 * @param \WP_Post $post       Updated post.
	 */
	public static function lock_key_on_publish( $new_status, $old_status, $post ): void {
		unset( $old_status );

		if ( 'publish' !== $new_status || HeroSlidePostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		$key = get_post_meta( $post->ID, self::SLIDE_KEY, true );
		if ( $key && '' === get_post_meta( $post->ID, self::LOCKED_SLIDE_KEY, true ) ) {
			add_post_meta( $post->ID, self::LOCKED_SLIDE_KEY, $key, true );
		}
	}

	/**
	 * Lock a key written to an already-published slide.
	 */
	public static function lock_key_after_meta_write( $meta_id, $object_id, $meta_key, $meta_value ): void {
		unset( $meta_id );

		if ( self::SLIDE_KEY === $meta_key
			&& 'publish' === get_post_status( $object_id )
			&& '' === get_post_meta( $object_id, self::LOCKED_SLIDE_KEY, true ) ) {
			add_post_meta( $object_id, self::LOCKED_SLIDE_KEY, self::sanitize_slide_key( $meta_value ), true );
		}
	}

	/**
	 * Add a stable error code to the editor redirect.
	 */
	public static function add_admin_error_to_redirect( $location, $post_id ) {
		if ( self::$admin_error_code && HeroSlidePostType::POST_TYPE === get_post_type( $post_id ) ) {
			$location = add_query_arg( 'hse_hero_error', self::$admin_error_code, $location );
			self::$admin_error_code = '';
		}

		return $location;
	}

	/**
	 * Display publication validation feedback.
	 */
	public static function render_admin_notice(): void {
		if ( ! isset( $_GET['hse_hero_error'] ) ) {
			return;
		}

		$error_code = sanitize_key( wp_unslash( $_GET['hse_hero_error'] ) );
		$messages   = array(
			'hse_slide_key_required'    => __( 'Hero Slide was saved as a draft because a slide key is required.', 'hse-headless' ),
			'hse_slide_key_immutable'   => __( 'The published Hero Slide key is locked and was not changed.', 'hse-headless' ),
			'hse_slide_key_duplicate'   => __( 'Hero Slide was saved as a draft because its slide key must be unique.', 'hse-headless' ),
			'hse_content_locale_invalid' => __( 'Hero Slide was saved as a draft because its content language is invalid.', 'hse-headless' ),
			'hse_content_locale_immutable' => __( 'Hero Slide language cannot change after publication.', 'hse-headless' ),
			'hse_content_locale_not_saved' => __( 'Hero Slide was saved as a draft because its content language could not be saved.', 'hse-headless' ),
			'hse_hero_slide_incomplete' => __( 'Hero Slide was saved as a draft. Complete every field and select a featured image before publishing.', 'hse-headless' ),
			'hse_hero_slide_limit'      => sprintf( __( 'Hero Slide was saved as a draft because no more than %d slides may be published.', 'hse-headless' ), self::MAX_PUBLISHED ),
		);

		if ( isset( $messages[ $error_code ] ) ) {
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $messages[ $error_code ] ) );
		}
	}

	/**
	 * Return all public field values for one slide.
	 *
	 * @param int $post_id Hero Slide post ID.
	 * @return array<string, string>
	 */
	public static function get_public_fields( $post_id ): array {
		$fields = array();
		foreach ( self::field_sanitizers() as $meta_key => $sanitize_callback ) {
			$fields[ $meta_key ] = (string) call_user_func( $sanitize_callback, get_post_meta( $post_id, $meta_key, true ) );
		}

		return $fields;
	}

	/**
	 * Return registered field sanitizers.
	 *
	 * @return array<string, callable>
	 */
	private static function field_sanitizers(): array {
		return array(
			self::SLIDE_KEY          => array( self::class, 'sanitize_slide_key' ),
			self::LEADING_TITLE      => array( self::class, 'sanitize_title_text' ),
			self::EMPHASIZED_TITLE   => array( self::class, 'sanitize_title_text' ),
			self::PRIMARY_CTA_LABEL  => array( self::class, 'sanitize_label_text' ),
			self::PRIMARY_CTA_URL    => array( self::class, 'sanitize_link_url' ),
			self::MESSAGE_PREFIX     => array( self::class, 'sanitize_label_text' ),
			self::MESSAGE_LINK_LABEL => array( self::class, 'sanitize_label_text' ),
			self::MESSAGE_LINK_URL   => array( self::class, 'sanitize_link_url' ),
		);
	}

	/**
	 * Fetch a posted editor value or the current stored value.
	 */
	private static function posted_or_stored_value( $meta_key, $post_id ): string {
		$field_name = 'hse_' . $meta_key;
		$value      = isset( $_POST[ $field_name ] ) ? wp_unslash( $_POST[ $field_name ] ) : get_post_meta( $post_id, $meta_key, true );
		$sanitizers = self::field_sanitizers();

		return (string) call_user_func( $sanitizers[ $meta_key ], $value );
	}

	/**
	 * Return whether all public fields and the featured image are present.
	 */
	private static function is_complete( $post_id ): bool {
		foreach ( self::get_public_fields( $post_id ) as $value ) {
			if ( '' === $value ) {
				return false;
			}
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		return $thumbnail_id && wp_attachment_is_image( $thumbnail_id );
	}

	/**
	 * Reject invalid stable key writes at the metadata boundary.
	 */
	private static function guard_slide_key_write( $check, $object_id, $meta_key, $meta_value ) {
		if ( null !== $check
			|| self::SLIDE_KEY !== $meta_key
			|| HeroSlidePostType::POST_TYPE !== get_post_type( $object_id ) ) {
			return $check;
		}

		return is_wp_error( self::validate_slide_key( $meta_value, $object_id ) ) ? false : null;
	}

	/**
	 * Determine whether another Hero Slide uses the key.
	 */
	private static function slide_key_exists( $slide_key, $post_id, $locale ): bool {
		$matches = get_posts(
			array(
				'post_type'      => HeroSlidePostType::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post__not_in'   => $post_id ? array( $post_id ) : array(),
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => self::SLIDE_KEY,
						'value' => $slide_key,
					),
					ContentLocale::query_clause( $locale ),
				),
				'no_found_rows'  => true,
			)
		);

		return ! empty( $matches );
	}

	/**
	 * Count currently published Hero Slides.
	 */
	private static function published_count( $locale ): int {
		$posts = get_posts(
			array(
				'post_type'      => HeroSlidePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( ContentLocale::query_clause( $locale ) ),
				'no_found_rows'  => true,
			)
		);

		return count( $posts );
	}

	/**
	 * Safely move an invalid published slide back to draft.
	 */
	private static function move_to_draft( $post_id ): void {
		remove_action( 'save_post_hero_slide', array( self::class, 'save_meta_box' ), 10 );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		add_action( 'save_post_hero_slide', array( self::class, 'save_meta_box' ), 10, 2 );
	}

	/**
	 * Sanitize plain text and enforce its maximum length.
	 */
	private static function sanitize_bounded_text( $value, $max_length ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( (string) $value );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_length ) : substr( $value, 0, $max_length );
	}
}
