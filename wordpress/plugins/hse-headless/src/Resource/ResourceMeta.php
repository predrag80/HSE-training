<?php
/**
 * Free Resource metadata and editor fields.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Resource;

use HSETraining\Headless\Content\ContentLocale;

defined( 'ABSPATH' ) || exit;

/** Owns metadata used by the public Free Resources page. */
final class ResourceMeta {
	public const RESOURCE_KEY  = 'resource_key';
	public const TYPE          = 'resource_type';
	public const SUMMARY       = 'resource_summary';
	public const TOPIC         = 'resource_topic';
	public const EXTERNAL_URL  = 'resource_external_url';
	public const ATTACHMENT_ID = 'resource_attachment_id';
	public const FEATURED      = 'resource_featured';

	private const NONCE_ACTION = 'hse_save_resource_details';
	private const NONCE_NAME   = 'hse_resource_details_nonce';
	private const ERROR_QUERY  = 'hse_resource_error';

	/** @var string */
	private static $admin_error_code = '';

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'add_meta_boxes_hse_resource', array( self::class, 'register_meta_box' ) );
		add_action( 'save_post_hse_resource', array( self::class, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_media' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'validate_publish' ), 10, 4 );
		add_filter( 'redirect_post_location', array( self::class, 'add_admin_error_to_redirect' ), 10, 2 );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/** Return allowed resource types. */
	public static function types(): array {
		return array(
			'link'      => __( 'Web link', 'hse-headless' ),
			'video'     => __( 'Training video', 'hse-headless' ),
			'document'  => __( 'Document', 'hse-headless' ),
			'procedure' => __( 'Procedure', 'hse-headless' ),
			'standard'  => __( 'Standard', 'hse-headless' ),
		);
	}

	/** Return a display label for one type. */
	public static function type_label( $type ): string {
		$types = self::types();

		return $types[ $type ] ?? '';
	}

	/** Register private sanitized metadata. */
	public static function register_meta(): void {
		ContentLocale::register_post_meta( ResourcePostType::POST_TYPE );
		$definitions = array(
			self::RESOURCE_KEY  => array( 'string', 'sanitize_key' ),
			self::TYPE          => array( 'string', array( self::class, 'sanitize_type' ) ),
			self::SUMMARY       => array( 'string', 'sanitize_textarea_field' ),
			self::TOPIC         => array( 'string', 'sanitize_text_field' ),
			self::EXTERNAL_URL  => array( 'string', 'esc_url_raw' ),
			self::ATTACHMENT_ID => array( 'integer', 'absint' ),
			self::FEATURED      => array( 'boolean', 'rest_sanitize_boolean' ),
		);

		foreach ( $definitions as $meta_key => $definition ) {
			register_post_meta(
				ResourcePostType::POST_TYPE,
				$meta_key,
				array(
					'type'              => $definition[0],
					'single'            => true,
					'sanitize_callback' => $definition[1],
					'auth_callback'     => static function ( $allowed, $key, $object_id ) {
						unset( $allowed, $key );
						return current_user_can( 'edit_post', $object_id );
					},
					'revisions_enabled' => true,
					'show_in_rest'      => false,
				)
			);
		}
	}

	/** Allow only known type values. */
	public static function sanitize_type( $value ): string {
		$value = sanitize_key( $value );

		return isset( self::types()[ $value ] ) ? $value : 'link';
	}

	/** Add the custom editor panel. */
	public static function register_meta_box(): void {
		remove_meta_box( 'postcustom', ResourcePostType::POST_TYPE, 'normal' );
		add_meta_box(
			'hse-resource-details',
			__( 'Resource details', 'hse-headless' ),
			array( self::class, 'render_meta_box' ),
			ResourcePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/** Load the native media picker on the resource editor. */
	public static function enqueue_media( $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && ResourcePostType::POST_TYPE === $screen->post_type ) {
			wp_enqueue_media();
		}
	}

	/** Render all Resource fields. */
	public static function render_meta_box( $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		ContentLocale::render_editor_field( $post->ID );
		$type          = self::sanitize_type( get_post_meta( $post->ID, self::TYPE, true ) );
		$attachment_id = absint( get_post_meta( $post->ID, self::ATTACHMENT_ID, true ) );
		$file_name     = $attachment_id ? basename( (string) get_attached_file( $attachment_id ) ) : '';
		?>
		<p><label for="hse-resource-key"><strong><?php esc_html_e( 'Resource key', 'hse-headless' ); ?></strong></label><br>
		<input class="widefat" id="hse-resource-key" name="hse_<?php echo esc_attr( self::RESOURCE_KEY ); ?>" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, self::RESOURCE_KEY, true ) ); ?>" required>
		<span class="description"><?php esc_html_e( 'Use the same key for the English and Serbian versions.', 'hse-headless' ); ?></span></p>
		<p><label for="hse-resource-type"><strong><?php esc_html_e( 'Resource type', 'hse-headless' ); ?></strong></label><br>
		<select id="hse-resource-type" name="hse_<?php echo esc_attr( self::TYPE ); ?>">
		<?php foreach ( self::types() as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
		</select></p>
		<p><label for="hse-resource-summary"><strong><?php esc_html_e( 'Short description', 'hse-headless' ); ?></strong></label><br>
		<textarea class="widefat" id="hse-resource-summary" maxlength="500" name="hse_<?php echo esc_attr( self::SUMMARY ); ?>" rows="4"><?php echo esc_textarea( get_post_meta( $post->ID, self::SUMMARY, true ) ); ?></textarea></p>
		<p><label for="hse-resource-topic"><strong><?php esc_html_e( 'Topic', 'hse-headless' ); ?></strong></label><br>
		<input class="widefat" id="hse-resource-topic" maxlength="120" name="hse_<?php echo esc_attr( self::TOPIC ); ?>" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, self::TOPIC, true ) ); ?>"></p>
		<p><label for="hse-resource-url"><strong><?php esc_html_e( 'External URL', 'hse-headless' ); ?></strong></label><br>
		<input class="widefat" id="hse-resource-url" name="hse_<?php echo esc_attr( self::EXTERNAL_URL ); ?>" type="url" value="<?php echo esc_attr( get_post_meta( $post->ID, self::EXTERNAL_URL, true ) ); ?>">
		<span class="description"><?php esc_html_e( 'Use for webpages, videos or externally hosted files.', 'hse-headless' ); ?></span></p>
		<p><strong><?php esc_html_e( 'Media Library file', 'hse-headless' ); ?></strong><br>
		<input id="hse-resource-attachment-id" name="hse_<?php echo esc_attr( self::ATTACHMENT_ID ); ?>" type="hidden" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
		<button class="button" id="hse-resource-file-select" type="button"><?php esc_html_e( 'Choose file', 'hse-headless' ); ?></button>
		<button class="button-link-delete" id="hse-resource-file-remove" type="button"<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove file', 'hse-headless' ); ?></button>
		<span id="hse-resource-file-name"><?php echo esc_html( $file_name ); ?></span></p>
		<p><label><input name="hse_<?php echo esc_attr( self::FEATURED ); ?>" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, self::FEATURED, true ) ); ?>> <?php esc_html_e( 'Feature this resource', 'hse-headless' ); ?></label></p>
		<p class="description"><?php esc_html_e( 'Provide either an external URL or a Media Library file. Use Page Attributes → Order to control display order. The main editor can contain a longer description.', 'hse-headless' ); ?></p>
		<script>
		(function () {
			const initializeMediaPicker = function () {
				const select = document.getElementById('hse-resource-file-select');
				const remove = document.getElementById('hse-resource-file-remove');
				const input = document.getElementById('hse-resource-attachment-id');
				const name = document.getElementById('hse-resource-file-name');
				if (!select || !remove || !input || !name || !window.wp || !window.wp.media) return;
				select.addEventListener('click', function () {
					const frame = window.wp.media({ title: '<?php echo esc_js( __( 'Choose resource file', 'hse-headless' ) ); ?>', button: { text: '<?php echo esc_js( __( 'Use this file', 'hse-headless' ) ); ?>' }, multiple: false });
					frame.on('select', function () {
						const file = frame.state().get('selection').first().toJSON();
						input.value = file.id;
						name.textContent = file.filename || file.title;
						remove.hidden = false;
					});
					frame.open();
				});
				remove.addEventListener('click', function () { input.value = ''; name.textContent = ''; remove.hidden = true; });
			};
			if ('loading' === document.readyState) {
				document.addEventListener('DOMContentLoaded', initializeMediaPicker, { once: true });
			} else {
				initializeMediaPicker();
			}
		}());
		</script>
		<?php
	}

	/** Save editor fields. */
	public static function save_meta_box( $post_id, $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_string( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		ContentLocale::save_post_locale( $post_id );
		$sanitizers = array(
			self::RESOURCE_KEY  => 'sanitize_key',
			self::TYPE          => array( self::class, 'sanitize_type' ),
			self::SUMMARY       => 'sanitize_textarea_field',
			self::TOPIC         => 'sanitize_text_field',
			self::EXTERNAL_URL  => 'esc_url_raw',
			self::ATTACHMENT_ID => 'absint',
		);
		foreach ( $sanitizers as $key => $sanitizer ) {
			$field = 'hse_' . $key;
			$value = isset( $_POST[ $field ] ) ? call_user_func( $sanitizer, wp_unslash( $_POST[ $field ] ) ) : '';
			update_post_meta( $post_id, $key, $value );
		}
		update_post_meta( $post_id, self::FEATURED, isset( $_POST[ 'hse_' . self::FEATURED ] ) );

		if ( 'publish' === $post->post_status && ! self::is_complete( $post_id ) ) {
			self::$admin_error_code = 'hse_resource_incomplete';
			remove_action( 'save_post_hse_resource', array( self::class, 'save_meta_box' ), 10 );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
			add_action( 'save_post_hse_resource', array( self::class, 'save_meta_box' ), 10, 2 );
		}
	}

	/** Prevent incomplete Resources from briefly entering the published state. */
	public static function validate_publish( $data, $postarr, $unsanitized_postarr, $update ) {
		unset( $unsanitized_postarr, $update );

		if ( ResourcePostType::POST_TYPE !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}

		$post_id       = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$title         = trim( (string) ( $data['post_title'] ?? '' ) );
		$key           = sanitize_key( self::posted_or_stored( self::RESOURCE_KEY, $post_id ) );
		$external_url  = esc_url_raw( self::posted_or_stored( self::EXTERNAL_URL, $post_id ) );
		$attachment_id = absint( self::posted_or_stored( self::ATTACHMENT_ID, $post_id ) );

		if ( '' === $title || '' === $key || ( '' === $external_url && 0 >= $attachment_id ) ) {
			$data['post_status']     = 'draft';
			self::$admin_error_code = 'hse_resource_incomplete';
		}

		return $data;
	}

	/** Add a stable publication error to the native editor redirect. */
	public static function add_admin_error_to_redirect( $location, $post_id ) {
		if ( self::$admin_error_code && ResourcePostType::POST_TYPE === get_post_type( $post_id ) ) {
			$location = add_query_arg( self::ERROR_QUERY, self::$admin_error_code, $location );
			self::$admin_error_code = '';
		}

		return $location;
	}

	/** Explain exactly why a Resource could not be published. */
	public static function render_admin_notice(): void {
		if ( ! isset( $_GET[ self::ERROR_QUERY ], $_GET['post'] ) ) {
			return;
		}

		$error_code = sanitize_key( wp_unslash( $_GET[ self::ERROR_QUERY ] ) );
		$post_id    = absint( $_GET['post'] );
		if ( 'hse_resource_incomplete' !== $error_code || ResourcePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$missing = self::missing_requirements( $post_id );
		$message = $missing
			? sprintf(
				/* translators: %s: comma-separated list of missing Resource requirements. */
				__( 'Free Resource was saved as a draft and was not published. Complete: %s.', 'hse-headless' ),
				implode( ', ', $missing )
			)
			: __( 'Free Resource was saved as a draft because its publication requirements were not complete.', 'hse-headless' );

		printf( '<div class="notice notice-error is-dismissible"><p><strong>%s</strong></p></div>', esc_html( $message ) );
	}

	/** Return the editor requirements that are still missing. */
	public static function missing_requirements( $post_id ): array {
		$missing = array();
		if ( '' === trim( get_the_title( $post_id ) ) ) {
			$missing[] = __( 'Title', 'hse-headless' );
		}
		if ( '' === sanitize_key( get_post_meta( $post_id, self::RESOURCE_KEY, true ) ) ) {
			$missing[] = __( 'Resource key', 'hse-headless' );
		}
		$url           = esc_url_raw( get_post_meta( $post_id, self::EXTERNAL_URL, true ) );
		$attachment_id = absint( get_post_meta( $post_id, self::ATTACHMENT_ID, true ) );
		if ( '' === $url && 0 >= $attachment_id ) {
			$missing[] = __( 'External URL or Media Library file', 'hse-headless' );
		}

		return $missing;
	}

	/** Check minimum public data. */
	public static function is_complete( $post_id ): bool {
		return array() === self::missing_requirements( $post_id );
	}

	/** Return a posted editor value or the currently stored metadata value. */
	private static function posted_or_stored( $meta_key, $post_id ) {
		$field = 'hse_' . $meta_key;

		return isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : get_post_meta( $post_id, $meta_key, true );
	}
}
