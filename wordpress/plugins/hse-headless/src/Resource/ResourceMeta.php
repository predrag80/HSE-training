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

	/** Register WordPress hooks. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'add_meta_boxes_hse_resource', array( self::class, 'register_meta_box' ) );
		add_action( 'save_post_hse_resource', array( self::class, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_media' ) );
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
			remove_action( 'save_post_hse_resource', array( self::class, 'save_meta_box' ), 10 );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
			add_action( 'save_post_hse_resource', array( self::class, 'save_meta_box' ), 10, 2 );
		}
	}

	/** Check minimum public data. */
	public static function is_complete( $post_id ): bool {
		$key            = sanitize_key( get_post_meta( $post_id, self::RESOURCE_KEY, true ) );
		$url            = esc_url_raw( get_post_meta( $post_id, self::EXTERNAL_URL, true ) );
		$attachment_id  = absint( get_post_meta( $post_id, self::ATTACHMENT_ID, true ) );

		return '' !== trim( get_the_title( $post_id ) ) && '' !== $key && ( '' !== $url || 0 < $attachment_id );
	}
}
