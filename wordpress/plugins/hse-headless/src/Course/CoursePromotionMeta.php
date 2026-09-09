<?php
/**
 * Homepage promotion fields for Course posts.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Course;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Owns the optional Course representation used by the Homepage. */
final class CoursePromotionMeta {
	public const HOMEPAGE_LABEL       = 'homepage_label';
	public const HOMEPAGE_CTA_LABEL   = 'homepage_cta_label';
	public const FEATURED_ON_HOMEPAGE = 'featured_on_homepage';
	public const MAX_FEATURED         = 3;

	private const NONCE_ACTION     = 'hse_save_course_promotion';
	private const NONCE_NAME       = 'hse_course_promotion_nonce';
	private const MAX_LABEL_LENGTH = 120;

	/** @var string */
	private static $admin_error_code = '';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'add_meta_boxes_course', array( self::class, 'register_meta_box' ) );
		add_action( 'save_post_course', array( self::class, 'save_meta_box' ), 20, 2 );
		add_filter( 'wp_insert_post_data', array( self::class, 'validate_publish' ), 20, 4 );
		add_filter( 'rest_pre_insert_course', array( self::class, 'validate_rest_write' ), 20, 2 );
		add_filter( 'rest_course_collection_params', array( self::class, 'register_collection_params' ) );
		add_filter( 'rest_course_query', array( self::class, 'filter_collection_query' ), 20, 2 );
		add_filter( 'redirect_post_location', array( self::class, 'add_admin_error_to_redirect' ), 20, 2 );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/** Register the public promotion metadata. */
	public static function register_meta(): void {
		foreach ( array( self::HOMEPAGE_LABEL, self::HOMEPAGE_CTA_LABEL ) as $meta_key ) {
			register_post_meta(
				CoursePostType::POST_TYPE,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'sanitize_callback' => array( self::class, 'sanitize_label' ),
					'auth_callback'     => array( CourseMeta::class, 'can_edit_meta' ),
					'revisions_enabled' => true,
					'show_in_rest'      => array(
						'schema' => array(
							'type'      => 'string',
							'maxLength' => self::MAX_LABEL_LENGTH,
						),
					),
				)
			);
		}

		register_post_meta(
			CoursePostType::POST_TYPE,
			self::FEATURED_ON_HOMEPAGE,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'sanitize_callback' => array( self::class, 'sanitize_boolean' ),
				'auth_callback'     => array( CourseMeta::class, 'can_edit_meta' ),
				'revisions_enabled' => true,
				'show_in_rest'      => true,
			)
		);
	}

	/** Sanitize a card label or CTA label. */
	public static function sanitize_label( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = sanitize_text_field( (string) $value );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, self::MAX_LABEL_LENGTH ) : substr( $value, 0, self::MAX_LABEL_LENGTH );
	}

	/** Normalize a WordPress checkbox value. */
	public static function sanitize_boolean( $value ): bool {
		return rest_sanitize_boolean( $value );
	}

	/** Register the Homepage promotion editor. */
	public static function register_meta_box(): void {
		add_meta_box(
			'hse-course-homepage-promotion',
			__( 'Homepage promotion', 'hse-headless' ),
			array( self::class, 'render_meta_box' ),
			CoursePostType::POST_TYPE,
			'normal',
			'default'
		);
	}

	/** Render Homepage promotion fields. */
	public static function render_meta_box( $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$featured = (bool) get_post_meta( $post->ID, self::FEATURED_ON_HOMEPAGE, true );
		?>
		<p>
			<label for="hse-course-homepage-label"><strong><?php esc_html_e( 'Card label', 'hse-headless' ); ?></strong></label><br>
			<input class="widefat" id="hse-course-homepage-label" maxlength="120" name="hse_<?php echo esc_attr( self::HOMEPAGE_LABEL ); ?>" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, self::HOMEPAGE_LABEL, true ) ); ?>">
		</p>
		<p>
			<label for="hse-course-homepage-cta"><strong><?php esc_html_e( 'CTA label', 'hse-headless' ); ?></strong></label><br>
			<input class="widefat" id="hse-course-homepage-cta" maxlength="120" name="hse_<?php echo esc_attr( self::HOMEPAGE_CTA_LABEL ); ?>" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, self::HOMEPAGE_CTA_LABEL, true ) ); ?>">
		</p>
		<p>
			<label><input name="hse_<?php echo esc_attr( self::FEATURED_ON_HOMEPAGE ); ?>" type="checkbox" value="1" <?php checked( $featured ); ?>> <?php esc_html_e( 'Show this Course on the Homepage', 'hse-headless' ); ?></label>
		</p>
		<p class="description"><?php esc_html_e( 'A promoted Course also requires a short description, visible price, Featured image, and Page Attributes → Order. At most three published Courses may be promoted.', 'hse-headless' ); ?></p>
		<?php
	}

	/** Save promotion fields from the native editor. */
	public static function save_meta_box( $post_id, $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_string( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		foreach ( array( self::HOMEPAGE_LABEL, self::HOMEPAGE_CTA_LABEL ) as $meta_key ) {
			$field = 'hse_' . $meta_key;
			$value = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
			update_post_meta( $post_id, $meta_key, self::sanitize_label( $value ) );
		}
		$featured = isset( $_POST[ 'hse_' . self::FEATURED_ON_HOMEPAGE ] );
		update_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, $featured );

		if ( 'publish' === $post->post_status && $featured && ! self::is_complete( $post_id ) ) {
			self::$admin_error_code = 'hse_course_promotion_incomplete';
			self::move_to_draft( $post_id );
		}
	}

	/** Prevent incomplete or excessive Homepage promotions from publishing. */
	public static function validate_publish( $data, $postarr, $unsanitized_postarr, $update ) {
		unset( $unsanitized_postarr, $update );
		if ( CoursePostType::POST_TYPE !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}

		$post_id  = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$featured = isset( $_POST[ 'hse_' . self::FEATURED_ON_HOMEPAGE ] ) ? true : (bool) get_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, true );
		if ( ! $featured ) {
			return $data;
		}

		if ( ! self::candidate_is_complete( $data, $post_id ) ) {
			return self::reject_publish( $data, 'hse_course_promotion_incomplete' );
		}
		if ( self::MAX_FEATURED <= self::featured_count( $post_id ) ) {
			return self::reject_publish( $data, 'hse_course_promotion_limit' );
		}

		return $data;
	}

	/** Reject incomplete or excessive promotion changes made through REST. */
	public static function validate_rest_write( $prepared_post, $request ) {
		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}
		$post_id = (int) $request->get_param( 'id' );
		$status  = isset( $prepared_post->post_status ) ? $prepared_post->post_status : get_post_status( $post_id );
		$meta    = $request->get_param( 'meta' );
		$meta    = is_array( $meta ) ? $meta : array();
		$featured = array_key_exists( self::FEATURED_ON_HOMEPAGE, $meta )
			? self::sanitize_boolean( $meta[ self::FEATURED_ON_HOMEPAGE ] )
			: (bool) get_post_meta( $post_id, self::FEATURED_ON_HOMEPAGE, true );

		if ( 'publish' !== $status || ! $featured ) {
			return $prepared_post;
		}

		$title = isset( $prepared_post->post_title ) ? trim( $prepared_post->post_title ) : trim( get_the_title( $post_id ) );
		$required = array(
			self::HOMEPAGE_LABEL         => array( self::class, 'sanitize_label' ),
			self::HOMEPAGE_CTA_LABEL     => array( self::class, 'sanitize_label' ),
			CourseMeta::SHORT_DESCRIPTION => array( CourseMeta::class, 'sanitize_short_description' ),
			CourseMeta::VISIBLE_PRICE     => array( CourseMeta::class, 'sanitize_visible_price' ),
		);
		foreach ( $required as $meta_key => $callback ) {
			$value = array_key_exists( $meta_key, $meta ) ? $meta[ $meta_key ] : get_post_meta( $post_id, $meta_key, true );
			if ( '' === call_user_func( $callback, $value ) ) {
				return new \WP_Error( 'hse_course_promotion_incomplete', __( 'Homepage Courses require complete promotion fields.', 'hse-headless' ), array( 'status' => 400 ) );
			}
		}
		$thumbnail_id = $request->has_param( 'featured_media' ) ? (int) $request->get_param( 'featured_media' ) : (int) get_post_thumbnail_id( $post_id );
		if ( '' === $title || 0 >= $thumbnail_id || ! wp_attachment_is_image( $thumbnail_id ) ) {
			return new \WP_Error( 'hse_course_promotion_incomplete', __( 'Homepage Courses require a title and Featured image.', 'hse-headless' ), array( 'status' => 400 ) );
		}
		if ( self::MAX_FEATURED <= self::featured_count( $post_id ) ) {
			return new \WP_Error( 'hse_course_promotion_limit', __( 'Too many Courses are selected for the Homepage.', 'hse-headless' ), array( 'status' => 400 ) );
		}

		return $prepared_post;
	}

	/** Add the Homepage filter and menu order to the REST collection schema. */
	public static function register_collection_params( $params ) {
		$params[ self::FEATURED_ON_HOMEPAGE ] = array(
			'description' => __( 'Limit results to Homepage-promoted Courses.', 'hse-headless' ),
			'type'        => 'boolean',
		);
		if ( isset( $params['orderby']['enum'] ) && ! in_array( 'menu_order', $params['orderby']['enum'], true ) ) {
			$params['orderby']['enum'][] = 'menu_order';
		}

		return $params;
	}

	/** Map the Homepage filter to a parameterized metadata query. */
	public static function filter_collection_query( $args, $request ) {
		if ( $request->has_param( self::FEATURED_ON_HOMEPAGE ) && rest_sanitize_boolean( $request->get_param( self::FEATURED_ON_HOMEPAGE ) ) ) {
			$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
			$meta_query[] = array(
				'key'   => self::FEATURED_ON_HOMEPAGE,
				'value' => '1',
			);
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	/** Add a known validation code to the editor redirect. */
	public static function add_admin_error_to_redirect( $location, $post_id ) {
		if ( self::$admin_error_code && CoursePostType::POST_TYPE === get_post_type( $post_id ) ) {
			$location = add_query_arg( 'hse_course_promotion_error', self::$admin_error_code, $location );
			self::$admin_error_code = '';
		}

		return $location;
	}

	/** Render Homepage promotion validation notices. */
	public static function render_admin_notice(): void {
		if ( ! isset( $_GET['hse_course_promotion_error'] ) ) {
			return;
		}
		$code     = sanitize_key( wp_unslash( $_GET['hse_course_promotion_error'] ) );
		$messages = array(
			'hse_course_promotion_incomplete' => __( 'Course was saved as a draft. Homepage Courses require card and CTA labels, short description, visible price, and a Featured image.', 'hse-headless' ),
			'hse_course_promotion_limit'      => sprintf( __( 'Course was saved as a draft because no more than %d Courses may appear on the Homepage.', 'hse-headless' ), self::MAX_FEATURED ),
		);
		if ( isset( $messages[ $code ] ) ) {
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $messages[ $code ] ) );
		}
	}

	/** Return whether saved promotion content is complete. */
	private static function is_complete( $post_id ): bool {
		return '' !== get_post_meta( $post_id, self::HOMEPAGE_LABEL, true )
			&& '' !== get_post_meta( $post_id, self::HOMEPAGE_CTA_LABEL, true )
			&& '' !== get_post_meta( $post_id, CourseMeta::SHORT_DESCRIPTION, true )
			&& '' !== get_post_meta( $post_id, CourseMeta::VISIBLE_PRICE, true )
			&& wp_attachment_is_image( get_post_thumbnail_id( $post_id ) );
	}

	/** Return whether editor input or stored promotion data is complete. */
	private static function candidate_is_complete( $data, $post_id ): bool {
		if ( '' === trim( (string) ( $data['post_title'] ?? '' ) ) ) {
			return false;
		}
		$fields = array(
			self::HOMEPAGE_LABEL       => array( self::class, 'sanitize_label' ),
			self::HOMEPAGE_CTA_LABEL   => array( self::class, 'sanitize_label' ),
			CourseMeta::SHORT_DESCRIPTION => array( CourseMeta::class, 'sanitize_short_description' ),
			CourseMeta::VISIBLE_PRICE     => array( CourseMeta::class, 'sanitize_visible_price' ),
		);
		foreach ( $fields as $meta_key => $callback ) {
			$field = 'hse_' . $meta_key;
			$value = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : get_post_meta( $post_id, $meta_key, true );
			if ( '' === call_user_func( $callback, $value ) ) {
				return false;
			}
		}
		$thumbnail_id = isset( $_POST['_thumbnail_id'] ) ? (int) wp_unslash( $_POST['_thumbnail_id'] ) : (int) get_post_thumbnail_id( $post_id );

		return 0 < $thumbnail_id && wp_attachment_is_image( $thumbnail_id );
	}

	/** Count other published Homepage-promoted Courses. */
	private static function featured_count( $exclude_id ): int {
		$locale = ContentLocale::get_posted_or_stored_locale( $exclude_id );
		$matches = get_posts(
			array(
				'post_type'      => CoursePostType::POST_TYPE,
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

	/** Move incomplete published content to draft without recursion. */
	private static function move_to_draft( $post_id ): void {
		remove_action( 'save_post_course', array( self::class, 'save_meta_box' ), 20 );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		add_action( 'save_post_course', array( self::class, 'save_meta_box' ), 20, 2 );
	}
}
