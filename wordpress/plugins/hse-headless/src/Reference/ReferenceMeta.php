<?php
/**
 * Reference metadata, validation, and editor fields.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Reference;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Owns public Reference fields and Homepage selection rules. */
final class ReferenceMeta {
	public const REFERENCE_KEY        = 'reference_key';
	public const QUOTE                = 'quote';
	public const ROLE                 = 'role';
	public const FEATURED_ON_HOMEPAGE = 'featured_on_homepage';
	public const ACCENT_ON_HOMEPAGE   = 'accent_on_homepage';
	public const MAX_FEATURED         = 4;

	private const LOCKED_REFERENCE_KEY = '_hse_locked_reference_key';
	private const NONCE_ACTION         = 'hse_save_reference_details';
	private const NONCE_NAME           = 'hse_reference_details_nonce';
	private const MAX_KEY_LENGTH       = 80;
	private const MAX_QUOTE_LENGTH     = 1000;
	private const MAX_ROLE_LENGTH      = 180;

	/** @var string */
	private static $admin_error_code = '';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'add_meta_boxes_hse_reference', array( self::class, 'register_meta_box' ) );
		add_action( 'save_post_hse_reference', array( self::class, 'save_meta_box' ), 10, 2 );
		add_action( 'transition_post_status', array( self::class, 'lock_key_on_publish' ), 10, 3 );
		add_action( 'added_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );
		add_action( 'updated_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );
		add_filter( 'wp_insert_post_data', array( self::class, 'validate_publish' ), 10, 4 );
		add_filter( 'add_post_metadata', array( self::class, 'guard_reference_key_add' ), 10, 5 );
		add_filter( 'update_post_metadata', array( self::class, 'guard_reference_key_update' ), 10, 5 );
		add_filter( 'redirect_post_location', array( self::class, 'add_admin_error_to_redirect' ), 10, 2 );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/** Register private, sanitized metadata. */
	public static function register_meta(): void {
		ContentLocale::register_post_meta( ReferencePostType::POST_TYPE );

		register_post_meta(
			ReferencePostType::POST_TYPE,
			self::REFERENCE_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => array( self::class, 'sanitize_reference_key' ),
				'auth_callback'     => array( self::class, 'can_edit_meta' ),
				'revisions_enabled' => true,
				'show_in_rest'      => false,
			)
		);
		foreach ( array( self::QUOTE, self::ROLE ) as $meta_key ) {
			register_post_meta(
				ReferencePostType::POST_TYPE,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'sanitize_callback' => self::QUOTE === $meta_key ? array( self::class, 'sanitize_quote' ) : array( self::class, 'sanitize_role' ),
					'auth_callback'     => array( self::class, 'can_edit_meta' ),
					'revisions_enabled' => true,
					'show_in_rest'      => false,
				)
			);
		}
		foreach ( array( self::FEATURED_ON_HOMEPAGE, self::ACCENT_ON_HOMEPAGE ) as $meta_key ) {
			register_post_meta(
				ReferencePostType::POST_TYPE,
				$meta_key,
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
	}

	/** Normalize a stable Reference key. */
	public static function sanitize_reference_key( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = strtolower( remove_accents( trim( (string) $value ) ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
		$value = trim( (string) $value, '-' );
		$value = substr( $value, 0, self::MAX_KEY_LENGTH );

		return rtrim( $value, '-' );
	}

	/** Sanitize a plain-text testimonial. */
	public static function sanitize_quote( $value ): string {
		return self::sanitize_textarea( $value, self::MAX_QUOTE_LENGTH );
	}

	/** Sanitize an author role or organisation. */
	public static function sanitize_role( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = sanitize_text_field( (string) $value );

		return self::bound( $value, self::MAX_ROLE_LENGTH );
	}

	/** Normalize checkbox input. */
	public static function sanitize_boolean( $value ): bool {
		return rest_sanitize_boolean( $value );
	}

	/** Authorize metadata changes. */
	public static function can_edit_meta( $allowed, $meta_key, $object_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $object_id );
	}

	/** Register the Reference details editor. */
	public static function register_meta_box(): void {
		remove_meta_box( 'postcustom', ReferencePostType::POST_TYPE, 'normal' );
		add_meta_box(
			'hse-reference-details',
			__( 'Reference details', 'hse-headless' ),
			array( self::class, 'render_meta_box' ),
			ReferencePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/** Render all Reference fields. */
	public static function render_meta_box( $post ): void {
		$is_locked = '' !== get_post_meta( $post->ID, self::LOCKED_REFERENCE_KEY, true );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		ContentLocale::render_editor_field( $post->ID );
		?>
		<p>
			<label for="hse-reference-key"><strong><?php esc_html_e( 'Reference key', 'hse-headless' ); ?></strong></label><br>
			<input class="widefat" id="hse-reference-key" maxlength="80" name="hse_<?php echo esc_attr( self::REFERENCE_KEY ); ?>" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, self::REFERENCE_KEY, true ) ); ?>" <?php wp_readonly( $is_locked ); ?> required>
			<span class="description"><?php esc_html_e( 'Stable identifier; locks after first publication.', 'hse-headless' ); ?></span>
		</p>
		<p>
			<label for="hse-reference-quote"><strong><?php esc_html_e( 'Quote', 'hse-headless' ); ?></strong></label><br>
			<textarea class="widefat" id="hse-reference-quote" maxlength="1000" name="hse_<?php echo esc_attr( self::QUOTE ); ?>" rows="6" required><?php echo esc_textarea( get_post_meta( $post->ID, self::QUOTE, true ) ); ?></textarea>
		</p>
		<p>
			<label for="hse-reference-role"><strong><?php esc_html_e( 'Role or organisation', 'hse-headless' ); ?></strong></label><br>
			<input class="widefat" id="hse-reference-role" maxlength="180" name="hse_<?php echo esc_attr( self::ROLE ); ?>" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, self::ROLE, true ) ); ?>" required>
		</p>
		<p><label><input name="hse_<?php echo esc_attr( self::FEATURED_ON_HOMEPAGE ); ?>" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, self::FEATURED_ON_HOMEPAGE, true ) ); ?>> <?php esc_html_e( 'Show on the Homepage', 'hse-headless' ); ?></label></p>
		<p><label><input name="hse_<?php echo esc_attr( self::ACCENT_ON_HOMEPAGE ); ?>" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, self::ACCENT_ON_HOMEPAGE, true ) ); ?>> <?php esc_html_e( 'Use the accent card style', 'hse-headless' ); ?></label></p>
		<p class="description"><?php esc_html_e( 'Use Page Attributes → Order for display sequence. At most four published References may appear on the Homepage.', 'hse-headless' ); ?></p>
		<?php
	}

	/** Save native editor fields. */
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

		$fields = array(
			self::REFERENCE_KEY => array( self::class, 'sanitize_reference_key' ),
			self::QUOTE         => array( self::class, 'sanitize_quote' ),
			self::ROLE          => array( self::class, 'sanitize_role' ),
		);
		foreach ( $fields as $meta_key => $callback ) {
			$field = 'hse_' . $meta_key;
			$value = isset( $_POST[ $field ] ) ? call_user_func( $callback, wp_unslash( $_POST[ $field ] ) ) : '';
			if ( self::REFERENCE_KEY === $meta_key && is_wp_error( self::validate_reference_key( $value, $post_id, $locale ) ) ) {
				self::$admin_error_code = 'hse_reference_key_invalid';
				continue;
			}
			update_post_meta( $post_id, $meta_key, $value );
		}
		foreach ( array( self::FEATURED_ON_HOMEPAGE, self::ACCENT_ON_HOMEPAGE ) as $meta_key ) {
			update_post_meta( $post_id, $meta_key, isset( $_POST[ 'hse_' . $meta_key ] ) );
		}

		if ( 'publish' === $post->post_status && ! self::is_complete( $post_id ) ) {
			self::$admin_error_code = 'hse_reference_incomplete';
			self::move_to_draft( $post_id );
		}
	}

	/** Prevent invalid Reference publication. */
	public static function validate_publish( $data, $postarr, $unsanitized_postarr, $update ) {
		unset( $unsanitized_postarr, $update );
		if ( ReferencePostType::POST_TYPE !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}
		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$key     = self::posted_or_stored( self::REFERENCE_KEY, $post_id );
		$locale_validation = ContentLocale::validate_for_post( ContentLocale::get_posted_or_stored_locale( $post_id ), $post_id );
		if ( is_wp_error( $locale_validation ) ) {
			return self::reject_publish( $data, $locale_validation->get_error_code() );
		}
		if ( is_wp_error( self::validate_reference_key( $key, $post_id ) ) || '' === trim( (string) ( $data['post_title'] ?? '' ) ) || '' === self::posted_or_stored( self::QUOTE, $post_id ) || '' === self::posted_or_stored( self::ROLE, $post_id ) ) {
			return self::reject_publish( $data, 'hse_reference_incomplete' );
		}
		$featured = isset( $_POST[ 'hse_' . self::FEATURED_ON_HOMEPAGE ] ) ? true : (bool) get_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, true );
		if ( $featured && self::MAX_FEATURED <= self::featured_count( $post_id ) ) {
			return self::reject_publish( $data, 'hse_reference_featured_limit' );
		}

		return $data;
	}

	/** Validate stable key presence, immutability, and uniqueness. */
	public static function validate_reference_key( $value, $post_id = 0, $locale = null ) {
		$key = self::sanitize_reference_key( $value );
		if ( '' === $key ) {
			return new \WP_Error( 'hse_reference_key_required', __( 'Reference key is required.', 'hse-headless' ) );
		}
		$locked = $post_id ? get_post_meta( $post_id, self::LOCKED_REFERENCE_KEY, true ) : '';
		if ( '' !== $locked && $locked !== $key ) {
			return new \WP_Error( 'hse_reference_key_immutable', __( 'Reference key cannot change after publication.', 'hse-headless' ) );
		}
		$locale = null === $locale ? ContentLocale::get_posted_or_stored_locale( $post_id ) : ContentLocale::sanitize( $locale );
		if ( '' === $locale ) {
			return new \WP_Error( 'hse_content_locale_invalid', __( 'Choose English or Serbian as the content language.', 'hse-headless' ) );
		}
		if ( self::reference_key_exists( $key, $post_id, $locale ) ) {
			return new \WP_Error( 'hse_reference_key_duplicate', __( 'Another Reference already uses this key.', 'hse-headless' ) );
		}

		return true;
	}

	/** Guard add_post_meta key writes. */
	public static function guard_reference_key_add( $check, $object_id, $meta_key, $meta_value, $unique ) {
		unset( $unique );

		return self::guard_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/** Guard update_post_meta key writes. */
	public static function guard_reference_key_update( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		unset( $prev_value );

		return self::guard_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/** Lock a stable key on first publication. */
	public static function lock_key_on_publish( $new_status, $old_status, $post ): void {
		unset( $old_status );
		if ( 'publish' === $new_status && ReferencePostType::POST_TYPE === $post->post_type ) {
			$key = get_post_meta( $post->ID, self::REFERENCE_KEY, true );
			if ( $key && '' === get_post_meta( $post->ID, self::LOCKED_REFERENCE_KEY, true ) ) {
				add_post_meta( $post->ID, self::LOCKED_REFERENCE_KEY, $key, true );
			}
		}
	}

	/** Lock a key written after the Reference is already published. */
	public static function lock_key_after_meta_write( $meta_id, $object_id, $meta_key, $meta_value ): void {
		unset( $meta_id );
		if ( self::REFERENCE_KEY === $meta_key && 'publish' === get_post_status( $object_id ) && '' === get_post_meta( $object_id, self::LOCKED_REFERENCE_KEY, true ) ) {
			add_post_meta( $object_id, self::LOCKED_REFERENCE_KEY, self::sanitize_reference_key( $meta_value ), true );
		}
	}

	/** Add publication errors to the editor redirect. */
	public static function add_admin_error_to_redirect( $location, $post_id ) {
		if ( self::$admin_error_code && ReferencePostType::POST_TYPE === get_post_type( $post_id ) ) {
			$location = add_query_arg( 'hse_reference_error', self::$admin_error_code, $location );
			self::$admin_error_code = '';
		}

		return $location;
	}

	/** Render Reference validation notices. */
	public static function render_admin_notice(): void {
		if ( ! isset( $_GET['hse_reference_error'] ) ) {
			return;
		}
		$code     = sanitize_key( wp_unslash( $_GET['hse_reference_error'] ) );
		$messages = array(
			'hse_reference_incomplete'      => __( 'Reference was saved as a draft. Complete the title, key, quote, and role or organisation.', 'hse-headless' ),
			'hse_reference_key_invalid'     => __( 'Reference key was not changed because it must be unique and immutable after publication.', 'hse-headless' ),
			'hse_reference_featured_limit' => sprintf( __( 'Reference was saved as a draft because no more than %d References may appear on the Homepage.', 'hse-headless' ), self::MAX_FEATURED ),
			'hse_content_locale_invalid'   => __( 'Reference was saved as a draft because its content language is invalid.', 'hse-headless' ),
			'hse_content_locale_immutable' => __( 'Reference language cannot change after first publication.', 'hse-headless' ),
		);
		if ( isset( $messages[ $code ] ) ) {
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $messages[ $code ] ) );
		}
	}

	/** Return sanitized public fields for one Reference. */
	public static function get_public_fields( $post_id ): array {
		return array(
			self::REFERENCE_KEY        => self::sanitize_reference_key( get_post_meta( $post_id, self::REFERENCE_KEY, true ) ),
			self::QUOTE                => self::sanitize_quote( get_post_meta( $post_id, self::QUOTE, true ) ),
			self::ROLE                 => self::sanitize_role( get_post_meta( $post_id, self::ROLE, true ) ),
			self::FEATURED_ON_HOMEPAGE => (bool) get_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, true ),
			self::ACCENT_ON_HOMEPAGE   => (bool) get_post_meta( $post_id, self::ACCENT_ON_HOMEPAGE, true ),
		);
	}

	/** Return a posted field value or its stored value. */
	private static function posted_or_stored( $meta_key, $post_id ): string {
		$field = 'hse_' . $meta_key;
		$value = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : get_post_meta( $post_id, $meta_key, true );
		if ( self::REFERENCE_KEY === $meta_key ) {
			return self::sanitize_reference_key( $value );
		}

		return self::QUOTE === $meta_key ? self::sanitize_quote( $value ) : self::sanitize_role( $value );
	}

	/** Return whether a stored Reference is complete. */
	private static function is_complete( $post_id ): bool {
		$fields = self::get_public_fields( $post_id );

		return '' !== trim( get_the_title( $post_id ) ) && '' !== $fields[ self::REFERENCE_KEY ] && '' !== $fields[ self::QUOTE ] && '' !== $fields[ self::ROLE ];
	}

	/** Guard invalid stable key writes at the metadata boundary. */
	private static function guard_key_write( $check, $object_id, $meta_key, $meta_value ) {
		if ( null !== $check || self::REFERENCE_KEY !== $meta_key || ReferencePostType::POST_TYPE !== get_post_type( $object_id ) ) {
			return $check;
		}

		return is_wp_error( self::validate_reference_key( $meta_value, $object_id ) ) ? false : null;
	}

	/** Determine whether another Reference uses a stable key. */
	private static function reference_key_exists( $key, $post_id, $locale ): bool {
		$matches = get_posts(
			array(
				'post_type'      => ReferencePostType::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post__not_in'   => $post_id ? array( $post_id ) : array(),
				'meta_query'     => array(
					array(
						'key'   => self::REFERENCE_KEY,
						'value' => $key,
					),
					ContentLocale::query_clause( $locale ),
				),
				'no_found_rows'  => true,
			)
		);

		return ! empty( $matches );
	}

	/** Count other published Homepage References. */
	private static function featured_count( $exclude_id ): int {
		$locale = ContentLocale::get_posted_or_stored_locale( $exclude_id );
		$matches = get_posts(
			array(
				'post_type'      => ReferencePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post__not_in'   => $exclude_id ? array( $exclude_id ) : array(),
				'meta_query'     => array(
					array(
						'key'   => self::FEATURED_ON_HOMEPAGE,
						'value' => '1',
					),
					ContentLocale::query_clause( $locale ),
				),
				'no_found_rows'  => true,
			)
		);

		return count( $matches );
	}

	/** Reject publication and retain a draft. */
	private static function reject_publish( $data, $error_code ) {
		$data['post_status']     = 'draft';
		self::$admin_error_code = $error_code;

		return $data;
	}

	/** Move invalid published content to draft without recursion. */
	private static function move_to_draft( $post_id ): void {
		remove_action( 'save_post_hse_reference', array( self::class, 'save_meta_box' ), 10 );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		add_action( 'save_post_hse_reference', array( self::class, 'save_meta_box' ), 10, 2 );
	}

	/** Sanitize and bound multiline text. */
	private static function sanitize_textarea( $value, $max_length ): string {
		$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';

		return self::bound( $value, $max_length );
	}

	/** Bound a UTF-8 string. */
	private static function bound( $value, $max_length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_length ) : substr( $value, 0, $max_length );
	}
}
