<?php
/**
 * Service metadata, validation, and WordPress editor fields.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Service;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Owns the public Service fields and publication rules. */
final class ServiceMeta {
	public const SERVICE_KEY          = 'service_key';
	public const CARD_LABEL           = 'card_label';
	public const SHORT_DESCRIPTION    = 'short_description';
	public const DETAILED_DESCRIPTION = 'detailed_description';
	public const CTA_LABEL            = 'cta_label';
	public const CTA_URL              = 'cta_url';
	public const FEATURED_ON_HOMEPAGE = 'featured_on_homepage';
	public const MAX_HOMEPAGE_SERVICES = 4;

	private const LOCKED_SERVICE_KEY = '_hse_locked_service_key';
	private const NONCE_ACTION       = 'hse_save_service_details';
	private const NONCE_NAME         = 'hse_service_details_nonce';
	private const MAX_KEY_LENGTH     = 80;
	private const MAX_LABEL_LENGTH   = 120;
	private const MAX_SHORT_LENGTH   = 240;
	private const MAX_DETAIL_LENGTH  = 1000;
	private const MAX_URL_LENGTH     = 2048;

	/** @var string */
	private static $admin_error_code = '';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'add_meta_boxes_hse_service', array( self::class, 'register_meta_box' ) );
		add_action( 'save_post_hse_service', array( self::class, 'save_meta_box' ), 10, 2 );
		add_action( 'transition_post_status', array( self::class, 'lock_key_on_publish' ), 10, 3 );
		add_action( 'added_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );
		add_action( 'updated_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );
		add_filter( 'wp_insert_post_data', array( self::class, 'validate_publish' ), 10, 4 );
		add_filter( 'add_post_metadata', array( self::class, 'guard_service_key_add' ), 10, 5 );
		add_filter( 'update_post_metadata', array( self::class, 'guard_service_key_update' ), 10, 5 );
		add_filter( 'redirect_post_location', array( self::class, 'add_admin_error_to_redirect' ), 10, 2 );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/** Register private, sanitized Service metadata. */
	public static function register_meta(): void {
		ContentLocale::register_post_meta( ServicePostType::POST_TYPE );

		foreach ( self::text_sanitizers() as $meta_key => $sanitize_callback ) {
			register_post_meta(
				ServicePostType::POST_TYPE,
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

		register_post_meta(
			ServicePostType::POST_TYPE,
			self::FEATURED_ON_HOMEPAGE,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'sanitize_callback' => array( self::class, 'sanitize_boolean' ),
				'auth_callback'     => array( self::class, 'can_edit_meta' ),
				'revisions_enabled' => true,
				'show_in_rest'      => false,
			)
		);
	}

	/** Normalize a stable Service key. */
	public static function sanitize_service_key( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = strtolower( remove_accents( trim( (string) $value ) ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
		$value = trim( (string) $value, '-' );
		$value = substr( $value, 0, self::MAX_KEY_LENGTH );

		return rtrim( $value, '-' );
	}

	/** Sanitize a short card label or CTA label. */
	public static function sanitize_label( $value ): string {
		return self::sanitize_text( $value, self::MAX_LABEL_LENGTH );
	}

	/** Sanitize a short listing description. */
	public static function sanitize_short_description( $value ): string {
		return self::sanitize_text( $value, self::MAX_SHORT_LENGTH );
	}

	/** Sanitize a longer plain-text Consulting description. */
	public static function sanitize_detailed_description( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = sanitize_textarea_field( (string) $value );

		return self::bound( $value, self::MAX_DETAIL_LENGTH );
	}

	/** Sanitize an internal path, anchor, or absolute HTTP(S) URL. */
	public static function sanitize_link_url( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = self::bound( trim( (string) $value ), self::MAX_URL_LENGTH );
		if ( preg_match( '/^#[A-Za-z][A-Za-z0-9_-]*$/', $value ) ) {
			return $value;
		}
		if ( 0 === strpos( $value, '/' ) && 0 !== strpos( $value, '//' ) ) {
			return esc_url_raw( $value, array( 'http', 'https' ) );
		}

		return wp_http_validate_url( $value ) ? esc_url_raw( $value, array( 'http', 'https' ) ) : '';
	}

	/** Normalize WordPress checkbox input. */
	public static function sanitize_boolean( $value ): bool {
		return rest_sanitize_boolean( $value );
	}

	/** Authorize metadata changes. */
	public static function can_edit_meta( $allowed, $meta_key, $object_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $object_id );
	}

	/** Register the Service details editor. */
	public static function register_meta_box(): void {
		remove_meta_box( 'postcustom', ServicePostType::POST_TYPE, 'normal' );
		add_meta_box(
			'hse-service-details',
			__( 'Service details', 'hse-headless' ),
			array( self::class, 'render_meta_box' ),
			ServicePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/** Render all Service fields. */
	public static function render_meta_box( $post ): void {
		$is_locked = '' !== get_post_meta( $post->ID, self::LOCKED_SERVICE_KEY, true );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		ContentLocale::render_editor_field( $post->ID );
		self::render_input( self::SERVICE_KEY, __( 'Service key', 'hse-headless' ), $post->ID, self::MAX_KEY_LENGTH, $is_locked, __( 'Stable identifier; locks after first publication.', 'hse-headless' ) );
		self::render_input( self::CARD_LABEL, __( 'Homepage card label', 'hse-headless' ), $post->ID, self::MAX_LABEL_LENGTH );
		self::render_input( self::SHORT_DESCRIPTION, __( 'Short description', 'hse-headless' ), $post->ID, self::MAX_SHORT_LENGTH );
		self::render_textarea( self::DETAILED_DESCRIPTION, __( 'Detailed description', 'hse-headless' ), $post->ID );
		self::render_input( self::CTA_LABEL, __( 'CTA label', 'hse-headless' ), $post->ID, self::MAX_LABEL_LENGTH );
		self::render_input( self::CTA_URL, __( 'CTA URL', 'hse-headless' ), $post->ID, self::MAX_URL_LENGTH, false, __( 'Internal path, anchor, or full HTTP(S) URL.', 'hse-headless' ) );
		$featured = (bool) get_post_meta( $post->ID, self::FEATURED_ON_HOMEPAGE, true );
		?>
		<p>
			<label><input name="hse_<?php echo esc_attr( self::FEATURED_ON_HOMEPAGE ); ?>" type="checkbox" value="1" <?php checked( $featured ); ?>> <?php esc_html_e( 'Show this service on the Homepage', 'hse-headless' ); ?></label>
		</p>
		<p class="description"><?php esc_html_e( 'Select a Featured image and use Page Attributes → Order for display sequence. At most four published services may be featured on the Homepage.', 'hse-headless' ); ?></p>
		<?php
	}

	/** Render one required input. */
	private static function render_input( $meta_key, $label, $post_id, $max_length, $readonly = false, $description = '' ): void {
		$id = 'hse-service-' . str_replace( '_', '-', $meta_key );
		?>
		<p>
			<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
			<input class="widefat" id="<?php echo esc_attr( $id ); ?>" maxlength="<?php echo esc_attr( $max_length ); ?>" name="hse_<?php echo esc_attr( $meta_key ); ?>" type="text" value="<?php echo esc_attr( get_post_meta( $post_id, $meta_key, true ) ); ?>" <?php wp_readonly( $readonly ); ?> required>
			<?php if ( $description ) : ?><span class="description"><?php echo esc_html( $description ); ?></span><?php endif; ?>
		</p>
		<?php
	}

	/** Render the detailed description textarea. */
	private static function render_textarea( $meta_key, $label, $post_id ): void {
		$id = 'hse-service-' . str_replace( '_', '-', $meta_key );
		?>
		<p>
			<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
			<textarea class="widefat" id="<?php echo esc_attr( $id ); ?>" maxlength="<?php echo esc_attr( self::MAX_DETAIL_LENGTH ); ?>" name="hse_<?php echo esc_attr( $meta_key ); ?>" rows="5" required><?php echo esc_textarea( get_post_meta( $post_id, $meta_key, true ) ); ?></textarea>
		</p>
		<?php
	}

	/** Save fields from the native editor. */
	public static function save_meta_box( $post_id, $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_string( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$locale = ContentLocale::save_post_locale( $post_id );
		if ( is_wp_error( $locale ) ) {
			self::$admin_error_code = $locale->get_error_code();

			return;
		}

		foreach ( self::text_sanitizers() as $meta_key => $sanitize_callback ) {
			$field_name = 'hse_' . $meta_key;
			$raw        = isset( $_POST[ $field_name ] ) ? wp_unslash( $_POST[ $field_name ] ) : '';
			$value      = call_user_func( $sanitize_callback, $raw );

			if ( self::SERVICE_KEY === $meta_key ) {
				$validation = self::validate_service_key( $value, $post_id );
				if ( is_wp_error( $validation ) ) {
					self::$admin_error_code = $validation->get_error_code();
					continue;
				}
			}

			update_post_meta( $post_id, $meta_key, $value );
		}

		$featured = isset( $_POST[ 'hse_' . self::FEATURED_ON_HOMEPAGE ] );
		update_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, $featured );

		if ( 'publish' === $post->post_status && ! self::is_complete( $post_id ) ) {
			self::$admin_error_code = 'hse_service_incomplete';
			self::move_to_draft( $post_id );
		}
	}

	/** Prevent invalid Service publication. */
	public static function validate_publish( $data, $postarr, $unsanitized_postarr, $update ) {
		unset( $unsanitized_postarr, $update );

		if ( ServicePostType::POST_TYPE !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}

		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$locale  = ContentLocale::get_posted_or_stored_locale( $post_id );
		$locale_validation = ContentLocale::validate_for_post( $locale, $post_id );
		if ( is_wp_error( $locale_validation ) ) {
			return self::reject_publish( $data, $locale_validation->get_error_code() );
		}

		$key     = self::posted_or_stored( self::SERVICE_KEY, $post_id );
		$key_validation = self::validate_service_key( $key, $post_id );
		if ( is_wp_error( $key_validation ) ) {
			return self::reject_publish( $data, $key_validation->get_error_code() );
		}

		if ( '' === trim( (string) ( $data['post_title'] ?? '' ) ) ) {
			return self::reject_publish( $data, 'hse_service_incomplete' );
		}

		foreach ( array_keys( self::text_sanitizers() ) as $meta_key ) {
			if ( '' === self::posted_or_stored( $meta_key, $post_id ) ) {
				return self::reject_publish( $data, 'hse_service_incomplete' );
			}
		}

		$thumbnail_id = isset( $_POST['_thumbnail_id'] ) ? (int) wp_unslash( $_POST['_thumbnail_id'] ) : (int) get_post_thumbnail_id( $post_id );
		if ( 0 >= $thumbnail_id || ! wp_attachment_is_image( $thumbnail_id ) ) {
			return self::reject_publish( $data, 'hse_service_incomplete' );
		}

		$featured = isset( $_POST[ 'hse_' . self::FEATURED_ON_HOMEPAGE ] ) ? true : (bool) get_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, true );
		if ( $featured && self::MAX_HOMEPAGE_SERVICES <= self::featured_count( $post_id, $locale ) ) {
			return self::reject_publish( $data, 'hse_service_featured_limit' );
		}

		return $data;
	}

	/** Validate stable key presence, immutability, and uniqueness. */
	public static function validate_service_key( $value, $post_id = 0 ) {
		$key = self::sanitize_service_key( $value );
		if ( '' === $key ) {
			return new \WP_Error( 'hse_service_key_required', __( 'Service key is required.', 'hse-headless' ) );
		}
		$locked = $post_id ? get_post_meta( $post_id, self::LOCKED_SERVICE_KEY, true ) : '';
		if ( '' !== $locked && $locked !== $key ) {
			return new \WP_Error( 'hse_service_key_immutable', __( 'Service key cannot change after publication.', 'hse-headless' ) );
		}
		$locale = ContentLocale::get_posted_or_stored_locale( $post_id );
		if ( self::service_key_exists( $key, $post_id, $locale ) ) {
			return new \WP_Error( 'hse_service_key_duplicate', __( 'Another Service already uses this key.', 'hse-headless' ) );
		}

		return true;
	}

	/** Guard add_post_meta key writes. */
	public static function guard_service_key_add( $check, $object_id, $meta_key, $meta_value, $unique ) {
		unset( $unique );

		return self::guard_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/** Guard update_post_meta key writes. */
	public static function guard_service_key_update( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		unset( $prev_value );

		return self::guard_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/** Lock a stable key on first publication. */
	public static function lock_key_on_publish( $new_status, $old_status, $post ): void {
		unset( $old_status );
		if ( 'publish' !== $new_status || ServicePostType::POST_TYPE !== $post->post_type ) {
			return;
		}
		$key = get_post_meta( $post->ID, self::SERVICE_KEY, true );
		if ( $key && '' === get_post_meta( $post->ID, self::LOCKED_SERVICE_KEY, true ) ) {
			add_post_meta( $post->ID, self::LOCKED_SERVICE_KEY, $key, true );
		}
	}

	/** Lock a key written after a Service is already published. */
	public static function lock_key_after_meta_write( $meta_id, $object_id, $meta_key, $meta_value ): void {
		unset( $meta_id );
		if ( self::SERVICE_KEY === $meta_key && 'publish' === get_post_status( $object_id ) && '' === get_post_meta( $object_id, self::LOCKED_SERVICE_KEY, true ) ) {
			add_post_meta( $object_id, self::LOCKED_SERVICE_KEY, self::sanitize_service_key( $meta_value ), true );
		}
	}

	/** Add publication errors to the editor redirect. */
	public static function add_admin_error_to_redirect( $location, $post_id ) {
		if ( self::$admin_error_code && ServicePostType::POST_TYPE === get_post_type( $post_id ) ) {
			$location = add_query_arg( 'hse_service_error', self::$admin_error_code, $location );
			self::$admin_error_code = '';
		}

		return $location;
	}

	/** Render Service validation notices. */
	public static function render_admin_notice(): void {
		if ( ! isset( $_GET['hse_service_error'] ) ) {
			return;
		}
		$code = sanitize_key( wp_unslash( $_GET['hse_service_error'] ) );
		$messages = array(
			'hse_service_key_required'    => __( 'Service was saved as a draft because a service key is required.', 'hse-headless' ),
			'hse_service_key_immutable'   => __( 'The published Service key is locked and was not changed.', 'hse-headless' ),
			'hse_service_key_duplicate'   => __( 'Service was saved as a draft because its key must be unique.', 'hse-headless' ),
			'hse_content_locale_invalid'   => __( 'Service was saved as a draft because its content language is invalid.', 'hse-headless' ),
			'hse_content_locale_immutable' => __( 'Service language cannot change after publication.', 'hse-headless' ),
			'hse_content_locale_not_saved' => __( 'Service was saved as a draft because its content language could not be saved.', 'hse-headless' ),
			'hse_service_incomplete'      => __( 'Service was saved as a draft. Complete every field and select a Featured image.', 'hse-headless' ),
			'hse_service_featured_limit' => sprintf( __( 'Service was saved as a draft because no more than %d services may be featured.', 'hse-headless' ), self::MAX_HOMEPAGE_SERVICES ),
		);
		if ( isset( $messages[ $code ] ) ) {
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $messages[ $code ] ) );
		}
	}

	/** Return sanitized public fields for one Service. */
	public static function get_public_fields( $post_id ): array {
		$fields = array();
		foreach ( self::text_sanitizers() as $meta_key => $callback ) {
			$fields[ $meta_key ] = (string) call_user_func( $callback, get_post_meta( $post_id, $meta_key, true ) );
		}
		$fields[ self::FEATURED_ON_HOMEPAGE ] = (bool) get_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, true );

		return $fields;
	}

	/** Return text field sanitizers. */
	private static function text_sanitizers(): array {
		return array(
			self::SERVICE_KEY          => array( self::class, 'sanitize_service_key' ),
			self::CARD_LABEL           => array( self::class, 'sanitize_label' ),
			self::SHORT_DESCRIPTION    => array( self::class, 'sanitize_short_description' ),
			self::DETAILED_DESCRIPTION => array( self::class, 'sanitize_detailed_description' ),
			self::CTA_LABEL            => array( self::class, 'sanitize_label' ),
			self::CTA_URL              => array( self::class, 'sanitize_link_url' ),
		);
	}

	/** Return a posted editor value or the stored value. */
	private static function posted_or_stored( $meta_key, $post_id ): string {
		$name       = 'hse_' . $meta_key;
		$value      = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : get_post_meta( $post_id, $meta_key, true );
		$callbacks  = self::text_sanitizers();

		return (string) call_user_func( $callbacks[ $meta_key ], $value );
	}

	/** Return whether a Service is complete after save. */
	private static function is_complete( $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post || '' === trim( $post->post_title ) ) {
			return false;
		}
		foreach ( self::get_public_fields( $post_id ) as $meta_key => $value ) {
			if ( self::FEATURED_ON_HOMEPAGE !== $meta_key && '' === $value ) {
				return false;
			}
		}
		$thumbnail = get_post_thumbnail_id( $post_id );

		return $thumbnail && wp_attachment_is_image( $thumbnail );
	}

	/** Guard invalid stable key writes at the metadata boundary. */
	private static function guard_key_write( $check, $object_id, $meta_key, $meta_value ) {
		if ( null !== $check || self::SERVICE_KEY !== $meta_key || ServicePostType::POST_TYPE !== get_post_type( $object_id ) ) {
			return $check;
		}

		return is_wp_error( self::validate_service_key( $meta_value, $object_id ) ) ? false : null;
	}

	/** Determine whether another Service uses a stable key. */
	private static function service_key_exists( $key, $post_id, $locale ): bool {
		$matches = get_posts(
			array(
				'post_type' => ServicePostType::POST_TYPE, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 1, 'fields' => 'ids', 'post__not_in' => $post_id ? array( $post_id ) : array(),
				'meta_query' => array(
					'relation' => 'AND',
					array( 'key' => self::SERVICE_KEY, 'value' => $key ),
					ContentLocale::query_clause( $locale ),
				),
				'no_found_rows' => true,
			)
		);

		return ! empty( $matches );
	}

	/** Count other published Homepage-featured Services. */
	private static function featured_count( $exclude_id, $locale ): int {
		$matches = get_posts(
			array(
				'post_type' => ServicePostType::POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => -1,
				'fields' => 'ids', 'post__not_in' => $exclude_id ? array( $exclude_id ) : array(),
				'meta_query' => array(
					'relation' => 'AND',
					array( 'key' => self::FEATURED_ON_HOMEPAGE, 'value' => '1' ),
					ContentLocale::query_clause( $locale ),
				),
				'no_found_rows' => true,
			)
		);

		return count( $matches );
	}

	/** Mark publication as rejected and retain a draft. */
	private static function reject_publish( $data, $error_code ) {
		$data['post_status']     = 'draft';
		self::$admin_error_code = $error_code;

		return $data;
	}

	/** Move an invalid published Service to draft without recursion. */
	private static function move_to_draft( $post_id ): void {
		remove_action( 'save_post_hse_service', array( self::class, 'save_meta_box' ), 10 );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		add_action( 'save_post_hse_service', array( self::class, 'save_meta_box' ), 10, 2 );
	}

	/** Sanitize bounded single-line text. */
	private static function sanitize_text( $value, $max_length ): string {
		return self::bound( is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '', $max_length );
	}

	/** Bound a UTF-8 string. */
	private static function bound( $value, $max_length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_length ) : substr( $value, 0, $max_length );
	}
}
