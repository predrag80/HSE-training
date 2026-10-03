<?php
/**
 * Queue a production Astro rebuild after public CMS content changes.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Infrastructure;

defined( 'ABSPATH' ) || exit;

/** Connects approved editorial changes to the production GitHub Actions build. */
final class ContentDeployTrigger {
	public const CRON_HOOK = 'hse_content_deploy_dispatch';
	public const ENABLED_CONSTANT = 'HSE_CONTENT_DEPLOY_ENABLED';
	public const TOKEN_CONSTANT = 'HSE_CONTENT_DEPLOY_GITHUB_TOKEN';

	private const STATUS_OPTION = 'hse_content_deploy_status';
	private const RETRY_OPTION = 'hse_content_deploy_retry_count';
	private const NOTICE_PREFIX = 'hse_content_deploy_notice_';
	private const REPOSITORY = 'predrag80/HSE-training';
	private const WORKFLOW = 'deploy-production.yml';
	private const REF = 'production';
	private const QUEUE_DELAY = 20;
	private const RETRY_DELAY = 5 * MINUTE_IN_SECONDS;
	private const MAX_RETRIES = 3;

	/** @var bool Collapse all changes made in one request into one queued build. */
	private static $queued_in_request = false;

	/** Register content, scheduler, and administrator feedback hooks. */
	public static function register_hooks(): void {
		add_action( 'save_post', array( self::class, 'handle_post_save' ), 100, 3 );
		add_action( 'transition_post_status', array( self::class, 'handle_status_transition' ), 100, 3 );
		add_action( 'updated_option', array( self::class, 'handle_option_update' ), 100, 3 );
		add_action( self::CRON_HOOK, array( self::class, 'dispatch' ) );
		add_action( 'admin_notices', array( self::class, 'render_admin_notice' ) );
	}

	/** Queue a rebuild after an approved editorial record is saved. */
	public static function handle_post_save( $post_id, $post, $update ): void {
		unset( $update );

		if ( ! $post instanceof \WP_Post
			|| ! self::is_public_content_post_type( $post->post_type )
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id ) ) {
			return;
		}

		self::queue();
	}

	/** Queue when published content is unpublished, restored, or published. */
	public static function handle_status_transition( $new_status, $old_status, $post ): void {
		if ( ! $post instanceof \WP_Post
			|| ! self::is_public_content_post_type( $post->post_type )
			|| $new_status === $old_status
			|| ( 'publish' !== $new_status && 'publish' !== $old_status ) ) {
			return;
		}

		self::queue();
	}

	/** Queue when one of the locale-specific public settings documents changes. */
	public static function handle_option_update( $option, $old_value, $value ): void {
		if ( $old_value === $value || ! self::is_public_content_option( (string) $option ) ) {
			return;
		}

		self::queue();
	}

	/** Whether a post type contributes to the static public application. */
	public static function is_public_content_post_type( $post_type ): bool {
		return in_array(
			(string) $post_type,
			array( 'course', 'training', 'hse_service', 'hse_reference', 'hse_resource', 'hero_slide', 'attachment' ),
			true
		);
	}

	/** Whether an option contains content consumed by an Astro build. */
	public static function is_public_content_option( $option ): bool {
		foreach ( array( 'hse_company_page_', 'hse_course_page_', 'hse_legal_page_' ) as $prefix ) {
			if ( 0 === strpos( (string) $option, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/** Queue one debounced build on the real server-backed WP-Cron runner. */
	public static function queue(): void {
		if ( self::$queued_in_request || ! self::configuration()['enabled'] ) {
			return;
		}

		self::$queued_in_request = true;
		update_option( self::RETRY_OPTION, 0, false );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$scheduled = wp_schedule_single_event( time() + self::QUEUE_DELAY, self::CRON_HOOK );
			if ( false === $scheduled || is_wp_error( $scheduled ) ) {
				self::record_failure( 'The content rebuild could not be queued.', 0 );
				return;
			}
		}

		if ( is_admin() && get_current_user_id() ) {
			set_transient( self::NOTICE_PREFIX . get_current_user_id(), 'queued', 2 * MINUTE_IN_SECONDS );
		}
	}

	/** Dispatch the production workflow without exposing its credential. */
	public static function dispatch(): void {
		$configuration = self::configuration();
		$errors        = self::validate_configuration( $configuration );
		if ( $errors ) {
			self::record_failure( implode( ' ', $errors ), 0 );
			return;
		}

		$url      = sprintf(
			'https://api.github.com/repos/%s/actions/workflows/%s/dispatches',
			$configuration['repository'],
			rawurlencode( $configuration['workflow'] )
		);
		$response = wp_remote_post(
			$url,
			array(
				'headers'     => array(
					'Accept'               => 'application/vnd.github+json',
					'Authorization'        => 'Bearer ' . $configuration['token'],
					'Content-Type'         => 'application/json',
					'User-Agent'           => 'HSE-Training-CMS',
					'X-GitHub-Api-Version' => '2026-03-10',
				),
				'body'        => wp_json_encode( array( 'ref' => $configuration['ref'] ) ),
				'timeout'     => 10,
				'redirection' => 0,
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			self::record_failure( 'GitHub could not be reached.', 0, true );
			return;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			self::record_failure( 'GitHub rejected the content rebuild request.', $status, self::is_retryable_status( $status ) );
			return;
		}

		update_option(
			self::STATUS_OPTION,
			array(
				'status'     => 'queued',
				'timestamp'  => time(),
				'http_code'  => $status,
			),
			false
		);
		update_option( self::RETRY_OPTION, 0, false );
	}

	/** Validate server-owned dispatch configuration without exposing its token. */
	public static function validate_configuration( array $configuration ): array {
		$errors = array();
		if ( empty( $configuration['enabled'] ) ) {
			$errors[] = 'Content deploy automation is disabled.';
		}
		if ( empty( $configuration['token'] ) ) {
			$errors[] = 'The GitHub Actions credential is missing.';
		}
		if ( ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', (string) ( $configuration['repository'] ?? '' ) ) ) {
			$errors[] = 'The GitHub repository is invalid.';
		}
		if ( ! preg_match( '/^[A-Za-z0-9_.-]+\.ya?ml$/', (string) ( $configuration['workflow'] ?? '' ) ) ) {
			$errors[] = 'The GitHub workflow is invalid.';
		}
		if ( ! preg_match( '/^[A-Za-z0-9._\/-]+$/', (string) ( $configuration['ref'] ?? '' ) ) ) {
			$errors[] = 'The Git reference is invalid.';
		}

		return $errors;
	}

	/** Show concise queued/failed feedback without revealing deployment details. */
	public static function render_admin_notice(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$notice_key = self::NOTICE_PREFIX . get_current_user_id();
		if ( 'queued' === get_transient( $notice_key ) ) {
			delete_transient( $notice_key );
			echo '<div class="notice notice-info is-dismissible"><p>'
				. esc_html__( 'Content saved. The public website update is queued and should appear within a few minutes.', 'hse-headless' )
				. '</p></div>';
		}

		$status = get_option( self::STATUS_OPTION, array() );
		if ( is_array( $status ) && 'failed' === ( $status['status'] ?? '' ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'The automatic public website update failed. The CMS content is safe; ask an administrator to run the production deployment manually.', 'hse-headless' )
				. '</p></div>';
		}
	}

	/** Read the fixed production target and its server-owned feature flag/token. */
	private static function configuration(): array {
		$enabled = defined( self::ENABLED_CONSTANT )
			? (bool) constant( self::ENABLED_CONSTANT )
			: filter_var( getenv( self::ENABLED_CONSTANT ), FILTER_VALIDATE_BOOLEAN );
		$token = defined( self::TOKEN_CONSTANT ) ? constant( self::TOKEN_CONSTANT ) : getenv( self::TOKEN_CONSTANT );
		$configuration = array(
			'enabled'    => $enabled,
			'token'      => is_scalar( $token ) ? trim( (string) $token ) : '',
			'repository' => self::REPOSITORY,
			'workflow'   => self::WORKFLOW,
			'ref'        => self::REF,
		);

		/** Allow integration tests to replace non-secret dispatch configuration. */
		return apply_filters( 'hse_content_deploy_configuration', $configuration );
	}

	/** Record a bounded failure and optionally schedule a limited retry. */
	private static function record_failure( $message, $status, $retry = false ): void {
		update_option(
			self::STATUS_OPTION,
			array(
				'status'    => 'failed',
				'timestamp' => time(),
				'http_code' => absint( $status ),
			),
			false
		);
		error_log( '[HSE Headless] ' . sanitize_text_field( (string) $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		if ( ! $retry ) {
			return;
		}

		$attempt = (int) get_option( self::RETRY_OPTION, 0 ) + 1;
		update_option( self::RETRY_OPTION, $attempt, false );
		if ( $attempt <= self::MAX_RETRIES && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + self::RETRY_DELAY, self::CRON_HOOK );
		}
	}

	/** Retry temporary provider and rate-limit errors, but not invalid credentials. */
	private static function is_retryable_status( $status ): bool {
		$status = (int) $status;
		return 408 === $status || 425 === $status || 429 === $status || $status >= 500;
	}
}
