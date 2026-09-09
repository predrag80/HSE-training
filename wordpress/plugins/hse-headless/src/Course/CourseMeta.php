<?php
/**
 * Course metadata, validation, admin editing, and REST lookup.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Course;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the HSE-specific fields attached to Course posts.
 */
final class CourseMeta {
	public const COURSE_KEY        = 'course_key';
	public const SHORT_DESCRIPTION = 'short_description';
	public const VISIBLE_PRICE     = 'visible_price';

	private const LOCKED_COURSE_KEY = '_hse_locked_course_key';
	private const NONCE_ACTION      = 'hse_save_course_details';
	private const NONCE_NAME        = 'hse_course_details_nonce';
	private const MAX_KEY_LENGTH    = 80;
	private const MAX_SHORT_DESCRIPTION_LENGTH = 500;
	private const MAX_VISIBLE_PRICE_LENGTH      = 100;

	/**
	 * Error code carried through the post-save redirect.
	 *
	 * @var string
	 */
	private static $admin_error_code = '';

	/**
	 * Register WordPress hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'add_meta_boxes_course', array( self::class, 'register_meta_box' ) );
		add_action( 'save_post_course', array( self::class, 'save_meta_box' ), 10, 2 );
		add_action( 'transition_post_status', array( self::class, 'lock_key_on_publish' ), 10, 3 );
		add_action( 'added_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );
		add_action( 'updated_post_meta', array( self::class, 'lock_key_after_meta_write' ), 10, 4 );

		add_filter( 'wp_insert_post_data', array( self::class, 'require_key_for_publish' ), 10, 4 );
		add_filter( 'add_post_metadata', array( self::class, 'guard_course_key_add' ), 10, 5 );
		add_filter( 'update_post_metadata', array( self::class, 'guard_course_key_update' ), 10, 5 );
		add_filter( 'rest_pre_insert_course', array( self::class, 'validate_rest_write' ), 10, 2 );
		add_filter( 'rest_course_collection_params', array( self::class, 'register_rest_collection_params' ) );
		add_filter( 'rest_course_query', array( self::class, 'filter_rest_course_query' ), 10, 2 );
		add_filter( 'redirect_post_location', array( self::class, 'add_admin_error_to_redirect' ), 10, 2 );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/**
	 * Register the public Course metadata contract.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_post_meta/
	 */
	public static function register_meta(): void {
		ContentLocale::register_post_meta( CoursePostType::POST_TYPE );
		register_rest_field(
			CoursePostType::POST_TYPE,
			'locale',
			array(
				'get_callback' => static function ( $object ) {
					return ContentLocale::get_post_locale( (int) ( $object['id'] ?? 0 ) );
				},
				'schema'       => array(
					'description' => __( 'Editorial content language.', 'hse-headless' ),
					'type'        => 'string',
					'enum'        => ContentLocale::supported(),
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$common_args = array(
			'type'              => 'string',
			'single'            => true,
			'auth_callback'     => array( self::class, 'can_edit_meta' ),
			'revisions_enabled' => true,
		);

		register_post_meta(
			CoursePostType::POST_TYPE,
			self::COURSE_KEY,
			array_merge(
				$common_args,
				array(
					'label'             => __( 'Course key', 'hse-headless' ),
					'description'       => __( 'Stable cross-system course identifier.', 'hse-headless' ),
					'sanitize_callback' => array( self::class, 'sanitize_course_key' ),
					'show_in_rest'      => array(
						'schema' => array(
							'type'      => 'string',
							'maxLength' => self::MAX_KEY_LENGTH,
						),
					),
				)
			)
		);

		register_post_meta(
			CoursePostType::POST_TYPE,
			self::SHORT_DESCRIPTION,
			array_merge(
				$common_args,
				array(
					'label'             => __( 'Short description', 'hse-headless' ),
					'description'       => __( 'Concise course summary for listings.', 'hse-headless' ),
					'sanitize_callback' => array( self::class, 'sanitize_short_description' ),
					'show_in_rest'      => array(
						'schema' => array(
							'type'      => 'string',
							'maxLength' => self::MAX_SHORT_DESCRIPTION_LENGTH,
						),
					),
				)
			)
		);

		register_post_meta(
			CoursePostType::POST_TYPE,
			self::VISIBLE_PRICE,
			array_merge(
				$common_args,
				array(
					'label'             => __( 'Visible price', 'hse-headless' ),
					'description'       => __( 'Display-only price text; not authoritative payment state.', 'hse-headless' ),
					'sanitize_callback' => array( self::class, 'sanitize_visible_price' ),
					'show_in_rest'      => array(
						'schema' => array(
							'type'      => 'string',
							'maxLength' => self::MAX_VISIBLE_PRICE_LENGTH,
						),
					),
				)
			)
		);
	}

	/**
	 * Normalize a course key to lowercase ASCII words separated by hyphens.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_course_key( $value ): string {
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
	 * Sanitize and bound a short description.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_short_description( $value ): string {
		return self::sanitize_bounded_text( $value, self::MAX_SHORT_DESCRIPTION_LENGTH, true );
	}

	/**
	 * Sanitize and bound display-only price text.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_visible_price( $value ): string {
		return self::sanitize_bounded_text( $value, self::MAX_VISIBLE_PRICE_LENGTH, false );
	}

	/**
	 * Authorize changes to registered Course metadata.
	 *
	 * @param bool   $allowed   WordPress's current authorization result.
	 * @param string $meta_key  Metadata key.
	 * @param int    $object_id Course post ID.
	 * @return bool
	 */
	public static function can_edit_meta( $allowed, $meta_key, $object_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $object_id );
	}

	/**
	 * Add the native Course details box and hide the arbitrary custom-fields box.
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_meta_box/
	 */
	public static function register_meta_box(): void {
		remove_meta_box( 'postcustom', CoursePostType::POST_TYPE, 'normal' );
		add_meta_box(
			'hse-course-details',
			__( 'Course details', 'hse-headless' ),
			array( self::class, 'render_meta_box' ),
			CoursePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the Course details fields.
	 *
	 * @param \WP_Post $post Course post.
	 */
	public static function render_meta_box( $post ): void {
		$course_key        = get_post_meta( $post->ID, self::COURSE_KEY, true );
		$short_description = get_post_meta( $post->ID, self::SHORT_DESCRIPTION, true );
		$visible_price     = get_post_meta( $post->ID, self::VISIBLE_PRICE, true );
		$is_locked         = '' !== get_post_meta( $post->ID, self::LOCKED_COURSE_KEY, true );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		ContentLocale::render_editor_field( $post->ID );
		?>
		<p>
			<label for="hse-course-key"><strong><?php esc_html_e( 'Course key', 'hse-headless' ); ?></strong></label><br>
			<input class="widefat" id="hse-course-key" name="hse_course_key" type="text" maxlength="80" value="<?php echo esc_attr( $course_key ); ?>" <?php wp_readonly( $is_locked ); ?> required>
			<span class="description">
				<?php
				echo esc_html(
					$is_locked
						? __( 'Locked because this Course has been published. It remains the permanent cross-system identifier.', 'hse-headless' )
						: __( 'Required before publication. Lowercase letters, numbers, and hyphens; input is normalized on save.', 'hse-headless' )
				);
				?>
			</span>
		</p>
		<p>
			<label for="hse-short-description"><strong><?php esc_html_e( 'Short description', 'hse-headless' ); ?></strong></label><br>
			<textarea class="widefat" id="hse-short-description" name="hse_short_description" rows="3"><?php echo esc_textarea( $short_description ); ?></textarea>
		</p>
		<p>
			<label for="hse-visible-price"><strong><?php esc_html_e( 'Visible price', 'hse-headless' ); ?></strong></label><br>
			<input class="widefat" id="hse-visible-price" name="hse_visible_price" type="text" maxlength="100" value="<?php echo esc_attr( $visible_price ); ?>">
			<span class="description"><?php esc_html_e( 'Display text only (for example, €499). The payment provider owns checkout pricing.', 'hse-headless' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Save Course fields from the WordPress editor.
	 *
	 * @param int      $post_id Course post ID.
	 * @param \WP_Post $post    Course post.
	 */
	public static function save_meta_box( $post_id, $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! is_string( $_POST[ self::NONCE_NAME ] ) ) {
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

		$raw_course_key = isset( $_POST['hse_course_key'] ) ? wp_unslash( $_POST['hse_course_key'] ) : '';
		$validation     = self::validate_course_key( $raw_course_key, $post_id, $locale );

		if ( is_wp_error( $validation ) ) {
			self::$admin_error_code = $validation->get_error_code();
		} else {
			update_post_meta( $post_id, self::COURSE_KEY, self::sanitize_course_key( $raw_course_key ) );
		}

		$short_description = isset( $_POST['hse_short_description'] ) ? self::sanitize_short_description( wp_unslash( $_POST['hse_short_description'] ) ) : '';
		$visible_price     = isset( $_POST['hse_visible_price'] ) ? self::sanitize_visible_price( wp_unslash( $_POST['hse_visible_price'] ) ) : '';

		update_post_meta( $post_id, self::SHORT_DESCRIPTION, $short_description );
		update_post_meta( $post_id, self::VISIBLE_PRICE, $visible_price );

		if ( 'publish' === $post->post_status && '' === get_post_meta( $post_id, self::COURSE_KEY, true ) ) {
			self::$admin_error_code = 'hse_course_key_required';
			remove_action( 'save_post_course', array( self::class, 'save_meta_box' ), 10 );
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				)
			);
			add_action( 'save_post_course', array( self::class, 'save_meta_box' ), 10, 2 );
		}
	}

	/**
	 * Prevent non-REST publish operations from creating a keyless Course.
	 *
	 * @param array $data                Sanitized post data.
	 * @param array $postarr             Post data.
	 * @param array $unsanitized_postarr Unsanitized post data.
	 * @param bool  $update              Whether this is an update.
	 * @return array
	 */
	public static function require_key_for_publish( $data, $postarr, $unsanitized_postarr, $update ) {
		unset( $update );

		if ( CoursePostType::POST_TYPE !== ( $data['post_type'] ?? '' )
			|| 'publish' !== ( $data['post_status'] ?? '' )
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $data;
		}

		$post_id   = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$candidate = $post_id ? get_post_meta( $post_id, self::COURSE_KEY, true ) : '';

		if ( isset( $unsanitized_postarr['meta_input'][ self::COURSE_KEY ] ) ) {
			$candidate = $unsanitized_postarr['meta_input'][ self::COURSE_KEY ];
		} elseif ( isset( $_POST[ self::NONCE_NAME ], $_POST['hse_course_key'] ) ) {
			$candidate = wp_unslash( $_POST['hse_course_key'] );
		}

		$locale_validation = ContentLocale::validate_for_post( ContentLocale::get_posted_or_stored_locale( $post_id ), $post_id );
		if ( is_wp_error( $locale_validation ) ) {
			$data['post_status']    = 'draft';
			self::$admin_error_code = $locale_validation->get_error_code();

			return $data;
		}

		$validation = self::validate_course_key( $candidate, $post_id );
		if ( is_wp_error( $validation ) ) {
			$data['post_status']        = 'draft';
			self::$admin_error_code     = $validation->get_error_code();
		}

		return $data;
	}

	/**
	 * Validate a key before it is stored or published.
	 *
	 * @param mixed $value   Candidate value.
	 * @param int   $post_id Current Course post ID, or zero for a new Course.
	 * @return true|\WP_Error
	 */
	public static function validate_course_key( $value, $post_id = 0, $locale = null ) {
		$course_key = self::sanitize_course_key( $value );
		if ( '' === $course_key ) {
			return new \WP_Error(
				'hse_course_key_required',
				__( 'Course key is required and must contain at least one letter or number.', 'hse-headless' )
			);
		}

		$locked_key = $post_id ? get_post_meta( $post_id, self::LOCKED_COURSE_KEY, true ) : '';
		if ( '' !== $locked_key && $locked_key !== $course_key ) {
			return new \WP_Error(
				'hse_course_key_immutable',
				__( 'Course key cannot change after the Course has been published.', 'hse-headless' )
			);
		}

		$locale = null === $locale ? ContentLocale::get_posted_or_stored_locale( $post_id ) : ContentLocale::sanitize( $locale );
		if ( '' === $locale ) {
			return new \WP_Error(
				'hse_content_locale_invalid',
				__( 'Choose English or Serbian as the content language.', 'hse-headless' )
			);
		}

		if ( self::course_key_exists( $course_key, $post_id, $locale ) ) {
			return new \WP_Error(
				'hse_course_key_duplicate',
				__( 'Another Course already uses this course key.', 'hse-headless' )
			);
		}

		return true;
	}

	/**
	 * Enforce the key rules for add_post_meta() and all callers built on it.
	 *
	 * @param mixed  $check      Existing short-circuit value.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Candidate value.
	 * @param bool   $unique     Whether key-level uniqueness was requested.
	 * @return mixed
	 */
	public static function guard_course_key_add( $check, $object_id, $meta_key, $meta_value, $unique ) {
		unset( $unique );

		return self::guard_course_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Enforce the key rules for update_post_meta() and all callers built on it.
	 *
	 * @param mixed  $check      Existing short-circuit value.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Candidate value.
	 * @param mixed  $prev_value Previous value constraint.
	 * @return mixed
	 */
	public static function guard_course_key_update( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		unset( $prev_value );

		return self::guard_course_key_write( $check, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Return false to short-circuit an invalid Course key write.
	 *
	 * @param mixed  $check      Existing short-circuit value.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Candidate value after registered sanitization.
	 * @return mixed
	 */
	private static function guard_course_key_write( $check, $object_id, $meta_key, $meta_value ) {
		if ( null !== $check
			|| self::COURSE_KEY !== $meta_key
			|| CoursePostType::POST_TYPE !== get_post_type( $object_id ) ) {
			return $check;
		}

		return is_wp_error( self::validate_course_key( $meta_value, $object_id ) ) ? false : null;
	}

	/**
	 * Validate core REST create and update requests before WordPress writes data.
	 *
	 * @param object|\WP_Error $prepared_post Prepared post object.
	 * @param \WP_REST_Request $request       REST request.
	 * @return object|\WP_Error
	 */
	public static function validate_rest_write( $prepared_post, $request ) {
		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		$post_id       = (int) $request->get_param( 'id' );
		$meta          = $request->get_param( 'meta' );
		$has_key_input = is_array( $meta ) && array_key_exists( self::COURSE_KEY, $meta );
		$candidate     = $has_key_input ? $meta[ self::COURSE_KEY ] : get_post_meta( $post_id, self::COURSE_KEY, true );
		$status        = isset( $prepared_post->post_status ) ? $prepared_post->post_status : get_post_status( $post_id );

		if ( $has_key_input || 'publish' === $status ) {
			$validation = self::validate_course_key( $candidate, $post_id );
			if ( is_wp_error( $validation ) ) {
				$validation->add_data( array( 'status' => 400 ) );

				return $validation;
			}
		}

		return $prepared_post;
	}

	/**
	 * Add course_key as an allowlisted core REST collection parameter.
	 *
	 * @param array $params Collection parameter schema.
	 * @return array
	 * @see https://developer.wordpress.org/reference/hooks/rest_this-post_type_collection_params/
	 */
	public static function register_rest_collection_params( $params ) {
		$params[ self::COURSE_KEY ] = array(
			'description' => __( 'Limit results to one canonical course key.', 'hse-headless' ),
			'type'        => 'string',
			'pattern'     => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
			'minLength'   => 1,
			'maxLength'   => self::MAX_KEY_LENGTH,
		);
		$params['lang'] = array_merge(
			ContentLocale::rest_argument(),
			array(
				'description' => __( 'Limit results to one content language.', 'hse-headless' ),
				'type'        => 'string',
				'enum'        => ContentLocale::supported(),
			)
		);

		return $params;
	}

	/**
	 * Map course_key into a parameterized WP_Query metadata lookup.
	 *
	 * @param array            $args    WP_Query arguments.
	 * @param \WP_REST_Request $request REST request.
	 * @return array
	 * @see https://developer.wordpress.org/reference/hooks/rest_this-post_type_query/
	 */
	public static function filter_rest_course_query( $args, $request ) {
		$locale = ContentLocale::sanitize( $request->get_param( 'lang' ) ) ?: ContentLocale::DEFAULT_LOCALE;
		$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
		$meta_query[] = ContentLocale::query_clause( $locale );

		if ( $request->has_param( self::COURSE_KEY ) ) {
			$course_key = self::sanitize_course_key( $request->get_param( self::COURSE_KEY ) );
			if ( '' === $course_key ) {
				$args['post__in'] = array( 0 );

				return $args;
			}
			$meta_query[] = array(
				'key'   => self::COURSE_KEY,
				'value' => $course_key,
			);
		}

		$args['meta_query'] = $meta_query;

		return $args;
	}

	/**
	 * Lock the current key the first time a Course reaches publish status.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Course post.
	 */
	public static function lock_key_on_publish( $new_status, $old_status, $post ): void {
		unset( $old_status );

		if ( 'publish' === $new_status && CoursePostType::POST_TYPE === $post->post_type ) {
			self::lock_course_key( $post->ID );
		}
	}

	/**
	 * Lock a key written after a Course has already transitioned to publish.
	 *
	 * @param int    $meta_id    Metadata row ID.
	 * @param int    $object_id  Course post ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value.
	 */
	public static function lock_key_after_meta_write( $meta_id, $object_id, $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );

		if ( self::COURSE_KEY === $meta_key
			&& CoursePostType::POST_TYPE === get_post_type( $object_id )
			&& 'publish' === get_post_status( $object_id ) ) {
			self::lock_course_key( $object_id );
		}
	}

	/**
	 * Add a known validation code to the Course editor redirect.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $post_id  Post ID.
	 * @return string
	 */
	public static function add_admin_error_to_redirect( $location, $post_id ) {
		if ( '' === self::$admin_error_code || CoursePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return $location;
		}

		return add_query_arg( 'hse_course_error', self::$admin_error_code, $location );
	}

	/**
	 * Render a safe, known Course validation error in wp-admin.
	 */
	public static function render_admin_notice(): void {
		if ( ! isset( $_GET['hse_course_error'] ) ) {
			return;
		}

		if ( ! is_string( $_GET['hse_course_error'] ) ) {
			return;
		}

		$error_code = sanitize_key( wp_unslash( $_GET['hse_course_error'] ) );
		$messages   = array(
			'hse_course_key_required'  => __( 'Course was saved as a draft because a valid course key is required before publication.', 'hse-headless' ),
			'hse_course_key_duplicate' => __( 'Course key was not saved because another Course already uses it.', 'hse-headless' ),
			'hse_course_key_immutable' => __( 'Course key was not changed because it became permanent when this Course was first published.', 'hse-headless' ),
			'hse_content_locale_invalid' => __( 'Course was saved as a draft because its content language is invalid.', 'hse-headless' ),
			'hse_content_locale_immutable' => __( 'Course language cannot change after first publication.', 'hse-headless' ),
		);

		if ( isset( $messages[ $error_code ] ) ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $messages[ $error_code ] ) );
		}
	}

	/**
	 * Determine whether another Course already owns a key.
	 *
	 * @param string $course_key Canonical key.
	 * @param int    $post_id    Current post ID to exclude.
	 * @return bool
	 */
	private static function course_key_exists( $course_key, $post_id, $locale ): bool {
		$query_args = array(
			'post_type'              => CoursePostType::POST_TYPE,
			'post_status'            => array_values( get_post_stati() ),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => self::COURSE_KEY,
					'value' => $course_key,
				),
				ContentLocale::query_clause( $locale ),
			),
		);

		if ( $post_id ) {
			$query_args['post__not_in'] = array( (int) $post_id );
		}

		return (bool) get_posts( $query_args );
	}

	/**
	 * Persist the immutable value once, without exposing it through REST.
	 *
	 * @param int $post_id Course post ID.
	 */
	private static function lock_course_key( $post_id ): void {
		$course_key = get_post_meta( $post_id, self::COURSE_KEY, true );
		if ( '' !== $course_key && '' === get_post_meta( $post_id, self::LOCKED_COURSE_KEY, true ) ) {
			add_post_meta( $post_id, self::LOCKED_COURSE_KEY, $course_key, true );
		}
	}

	/**
	 * Sanitize a scalar text value and cap its character length.
	 *
	 * @param mixed $value         Raw value.
	 * @param int   $length        Maximum characters.
	 * @param bool  $allow_newline Whether to retain newline characters.
	 * @return string
	 */
	private static function sanitize_bounded_text( $value, $length, $allow_newline ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = $allow_newline ? sanitize_textarea_field( (string) $value ) : sanitize_text_field( (string) $value );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}
}
