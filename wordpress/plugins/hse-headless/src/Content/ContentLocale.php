<?php
/**
 * Shared locale rules for repository-owned editorial content.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Content;

defined( 'ABSPATH' ) || exit;

/** Owns the allowlisted locale value used by translated CMS records. */
final class ContentLocale {
	public const META_KEY       = 'content_locale';
	public const DEFAULT_LOCALE = 'en';
	public const SERBIAN_LOCALE = 'sr';

	private const LOCKED_META_KEY = '_hse_locked_content_locale';
	private const FIELD_NAME      = 'hse_content_locale';
	private const POST_TYPES      = array( 'course', 'hero_slide', 'hse_service', 'hse_reference' );

	/** Register metadata-boundary protections shared by localized post types. */
	public static function register_hooks(): void {
		add_filter( 'add_post_metadata', array( self::class, 'guard_locale_add' ), 10, 5 );
		add_filter( 'update_post_metadata', array( self::class, 'guard_locale_update' ), 10, 5 );
		add_action( 'transition_post_status', array( self::class, 'lock_on_publish' ), 10, 3 );
		add_action( 'added_post_meta', array( self::class, 'lock_after_meta_write' ), 10, 4 );
		add_action( 'updated_post_meta', array( self::class, 'lock_after_meta_write' ), 10, 4 );
		add_action( 'restrict_manage_posts', array( self::class, 'render_admin_filter' ), 10, 2 );
		add_action( 'pre_get_posts', array( self::class, 'filter_admin_query' ) );
		foreach ( self::POST_TYPES as $post_type ) {
			add_filter( 'manage_' . $post_type . '_posts_columns', array( self::class, 'add_admin_column' ) );
			add_action( 'manage_' . $post_type . '_posts_custom_column', array( self::class, 'render_admin_column' ), 10, 2 );
		}
	}

	/** Register private locale metadata for one supported post type. */
	public static function register_post_meta( $post_type ): void {
		register_post_meta(
			$post_type,
			self::META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => self::DEFAULT_LOCALE,
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'auth_callback'     => static function ( $allowed, $meta_key, $object_id ) {
					unset( $allowed, $meta_key );

					return current_user_can( 'edit_post', $object_id );
				},
				'revisions_enabled' => true,
				'show_in_rest'      => false,
			)
		);
	}

	/** Return the locale when it is explicitly supported, otherwise an empty value. */
	public static function sanitize( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$locale = strtolower( sanitize_key( (string) $value ) );

		return in_array( $locale, self::supported(), true ) ? $locale : '';
	}

	/** @return array<int, string> */
	public static function supported(): array {
		return array( self::DEFAULT_LOCALE, self::SERBIAN_LOCALE );
	}

	/** Return a localized label suitable for WordPress editor controls. */
	public static function label( $locale ): string {
		return self::SERBIAN_LOCALE === $locale
			? __( 'Serbian', 'hse-headless' )
			: __( 'English', 'hse-headless' );
	}

	/** Read a stored locale, treating pre-migration records as English. */
	public static function get_post_locale( $post_id ): string {
		$locale = self::sanitize( get_post_meta( $post_id, self::META_KEY, true ) );

		return $locale ?: self::DEFAULT_LOCALE;
	}

	/** Read the submitted locale or the current stored/default value. */
	public static function get_posted_or_stored_locale( $post_id ): string {
		if ( isset( $_POST[ self::FIELD_NAME ] ) ) {
			return self::sanitize( wp_unslash( $_POST[ self::FIELD_NAME ] ) );
		}

		return self::get_post_locale( $post_id );
	}

	/** Validate and store the editor locale. */
	public static function save_post_locale( $post_id ) {
		$locale     = self::get_posted_or_stored_locale( $post_id );
		$validation = self::validate_for_post( $locale, $post_id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		if ( false === update_post_meta( $post_id, self::META_KEY, $locale )
			&& $locale !== self::get_post_locale( $post_id ) ) {
			return new \WP_Error( 'hse_content_locale_not_saved', __( 'Content language could not be saved.', 'hse-headless' ) );
		}

		return $locale;
	}

	/** Render the language selector; a published record keeps its locked locale. */
	public static function render_editor_field( $post_id ): void {
		$stored_locale = self::sanitize( get_post_meta( $post_id, self::META_KEY, true ) );
		$request_locale = isset( $_GET['lang'] ) ? self::sanitize( wp_unslash( $_GET['lang'] ) ) : '';
		$locale = $stored_locale ?: ( $request_locale ?: self::DEFAULT_LOCALE );
		$locked = '' !== get_post_meta( $post_id, self::LOCKED_META_KEY, true );
		?>
		<p>
			<label for="hse-content-locale"><strong><?php esc_html_e( 'Content language', 'hse-headless' ); ?></strong></label><br>
			<select id="hse-content-locale" name="<?php echo esc_attr( self::FIELD_NAME ); ?>" <?php disabled( $locked ); ?>>
				<?php foreach ( self::supported() as $supported_locale ) : ?>
					<option value="<?php echo esc_attr( $supported_locale ); ?>" <?php selected( $locale, $supported_locale ); ?>><?php echo esc_html( self::label( $supported_locale ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( $locked ) : ?>
				<input name="<?php echo esc_attr( self::FIELD_NAME ); ?>" type="hidden" value="<?php echo esc_attr( $locale ); ?>">
				<span class="description"><?php esc_html_e( 'Language is locked after first publication.', 'hse-headless' ); ?></span>
			<?php else : ?>
				<span class="description"><?php esc_html_e( 'Choose the language before publishing this record.', 'hse-headless' ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}

	/** Render an allowlisted locale filter on supported post lists. */
	public static function render_admin_filter( $post_type, $which ): void {
		unset( $which );
		if ( ! in_array( $post_type, self::POST_TYPES, true ) ) {
			return;
		}

		$selected = isset( $_GET['lang'] ) ? self::sanitize( wp_unslash( $_GET['lang'] ) ) : '';
		?>
		<label class="screen-reader-text" for="hse-content-locale-filter"><?php esc_html_e( 'Filter by content language', 'hse-headless' ); ?></label>
		<select id="hse-content-locale-filter" name="lang">
			<option value=""><?php esc_html_e( 'All content languages', 'hse-headless' ); ?></option>
			<?php foreach ( self::supported() as $locale ) : ?>
				<option value="<?php echo esc_attr( $locale ); ?>" <?php selected( $selected, $locale ); ?>><?php echo esc_html( self::label( $locale ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/** Restrict a supported admin collection to its selected locale. */
	public static function filter_admin_query( $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$locale    = isset( $_GET['lang'] ) ? self::sanitize( wp_unslash( $_GET['lang'] ) ) : '';
		if ( ! in_array( $post_type, self::POST_TYPES, true ) || '' === $locale ) {
			return;
		}

		$query->set( 'meta_query', array( self::query_clause( $locale ) ) );
	}

	/** Add a language column to localized content lists. */
	public static function add_admin_column( $columns ): array {
		$columns['hse_content_locale'] = __( 'Language', 'hse-headless' );

		return $columns;
	}

	/** Render the language column. */
	public static function render_admin_column( $column, $post_id ): void {
		if ( 'hse_content_locale' === $column ) {
			echo esc_html( self::label( self::get_post_locale( $post_id ) ) );
		}
	}

	/** Return the validated REST locale or a public bad-request error. */
	public static function from_rest_request( $request ) {
		$raw_locale = $request instanceof \WP_REST_Request ? $request->get_param( 'lang' ) : null;
		$locale     = self::sanitize( null === $raw_locale || '' === $raw_locale ? self::DEFAULT_LOCALE : $raw_locale );

		if ( '' === $locale ) {
			return new \WP_Error(
				'hse_invalid_content_locale',
				__( 'The lang parameter must be en or sr.', 'hse-headless' ),
				array( 'status' => 400 )
			);
		}

		return $locale;
	}

	/** Return the allowlisted REST route argument definition. */
	public static function rest_argument(): array {
		return array(
			'default'           => self::DEFAULT_LOCALE,
			'sanitize_callback' => array( self::class, 'sanitize' ),
			'validate_callback' => static function ( $value ) {
				return '' !== ContentLocale::sanitize( $value );
			},
		);
	}

	/** Build a WP_Query clause that also recognizes legacy English records. */
	public static function query_clause( $locale ): array {
		$locale = self::sanitize( $locale );
		if ( self::DEFAULT_LOCALE !== $locale ) {
			return array(
				'key'   => self::META_KEY,
				'value' => $locale,
			);
		}

		return array(
			'relation' => 'OR',
			array(
				'key'   => self::META_KEY,
				'value' => self::DEFAULT_LOCALE,
			),
			array(
				'key'     => self::META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/** Validate support and immutability after first publication. */
	public static function validate_for_post( $locale, $post_id ) {
		$locale = self::sanitize( $locale );
		if ( '' === $locale ) {
			return new \WP_Error( 'hse_content_locale_invalid', __( 'Choose English or Serbian as the content language.', 'hse-headless' ) );
		}

		$locked = $post_id ? self::sanitize( get_post_meta( $post_id, self::LOCKED_META_KEY, true ) ) : '';
		if ( '' !== $locked && $locked !== $locale ) {
			return new \WP_Error( 'hse_content_locale_immutable', __( 'Content language cannot change after publication.', 'hse-headless' ) );
		}

		return true;
	}

	/** Guard add_post_meta locale writes. */
	public static function guard_locale_add( $check, $object_id, $meta_key, $meta_value, $unique ) {
		unset( $unique );

		return self::guard_locale_write( $check, $object_id, $meta_key, $meta_value );
	}

	/** Guard update_post_meta locale writes. */
	public static function guard_locale_update( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		unset( $prev_value );

		return self::guard_locale_write( $check, $object_id, $meta_key, $meta_value );
	}

	/** Lock a locale when supported content is first published. */
	public static function lock_on_publish( $new_status, $old_status, $post ): void {
		unset( $old_status );
		if ( 'publish' !== $new_status || ! in_array( $post->post_type, self::POST_TYPES, true ) ) {
			return;
		}

		self::lock( $post->ID );
	}

	/** Lock locale metadata added after an already-published record. */
	public static function lock_after_meta_write( $meta_id, $object_id, $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( self::META_KEY === $meta_key && 'publish' === get_post_status( $object_id ) ) {
			self::lock( $object_id );
		}
	}

	/** Reject unsupported or post-publication locale changes at the metadata boundary. */
	private static function guard_locale_write( $check, $object_id, $meta_key, $meta_value ) {
		if ( null !== $check || self::META_KEY !== $meta_key || ! in_array( get_post_type( $object_id ), self::POST_TYPES, true ) ) {
			return $check;
		}

		return is_wp_error( self::validate_for_post( $meta_value, $object_id ) ) ? false : null;
	}

	/** Persist the immutable locale marker. */
	private static function lock( $post_id ): void {
		if ( '' !== get_post_meta( $post_id, self::LOCKED_META_KEY, true ) ) {
			return;
		}

		add_post_meta( $post_id, self::LOCKED_META_KEY, self::get_post_locale( $post_id ), true );
	}
}
