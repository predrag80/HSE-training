<?php
/**
 * Minimal server-side Sentry transport for operational monitoring.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Infrastructure;

defined( 'ABSPATH' ) || exit;

/** Sends privacy-bounded monitoring events without adding a runtime SDK. */
final class SentryReporter {
	public const DSN_CONSTANT = 'HSE_MONITORING_SENTRY_DSN';
	public const ENVIRONMENT_CONSTANT = 'HSE_MONITORING_ENVIRONMENT';
	public const ALERT_EMAIL_CONSTANT = 'HSE_MONITORING_ALERT_EMAIL';

	private const LOGGER = 'hse.operational-monitoring';
	private const MAX_CONTEXT_LENGTH = 240;

	/** Whether a valid HTTPS Sentry DSN is available to the CMS. */
	public static function is_configured(): bool {
		return null !== self::dsn_parts();
	}

	/** Public, sanitized environment name for monitoring correlation. */
	public static function environment_name(): string {
		return self::environment();
	}

	/**
	 * Send one operational event to Sentry, falling back to administrator e-mail.
	 *
	 * Context must contain only operational identifiers and state, never customer data.
	 */
	public static function capture( string $incident, string $level, string $message, array $context = array(), string $deduplication_key = '' ): bool {
		$incident = sanitize_key( $incident );
		$level    = in_array( $level, array( 'info', 'warning', 'error', 'fatal' ), true ) ? $level : 'error';
		$message  = self::bounded_text( $message, 500 );
		$context  = self::sanitize_context( $context );

		$parts = self::dsn_parts();
		if ( null !== $parts ) {
			$event_id = '' !== $deduplication_key
				? substr( hash( 'sha256', self::environment() . ':' . $deduplication_key ), 0, 32 )
				: self::event_id();
			$payload  = array(
				'event_id'    => $event_id,
				'timestamp'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'platform'    => 'php',
				'level'       => $level,
				'logger'      => self::LOGGER,
				'environment' => self::environment(),
				'message'     => $message,
				'fingerprint' => array(
					'hse-monitoring',
					$incident,
					(string) ( $context['fingerprint_key'] ?? $context['operation_id'] ?? $context['order_id'] ?? $context['monitor_key'] ?? 'system' ),
				),
				'tags'        => self::event_tags( $incident, $context ),
				'extra'       => $context,
				'sdk'         => array(
					'name'    => 'hse-headless-monitoring',
					'version' => '0.32.0',
				),
			);
			$header   = array(
				'event_id' => $event_id,
				'dsn'      => self::dsn(),
				'sent_at'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
			$envelope = wp_json_encode( $header ) . "\n"
				. wp_json_encode( array( 'type' => 'event', 'content_type' => 'application/json' ) ) . "\n"
				. wp_json_encode( $payload );

			$response = wp_remote_post(
				$parts['envelope_url'],
				array(
					'headers'     => array( 'Content-Type' => 'application/x-sentry-envelope' ),
					'body'        => $envelope,
					'timeout'     => 5,
					'redirection' => 0,
					'data_format' => 'body',
				)
			);

			if ( ! is_wp_error( $response ) ) {
				$status = (int) wp_remote_retrieve_response_code( $response );
				if ( $status >= 200 && $status < 300 ) {
					return true;
				}
			}

			self::log( 'Sentry rejected or could not receive an operational alert.', $incident, $context );
		}

		return self::send_fallback_email( $incident, $level, $message, $context );
	}

	/** Start a Sentry Cron check-in and return its correlation id. */
	public static function start_check_in( string $slug ): string {
		$check_in_id = str_replace( '-', '', wp_generate_uuid4() );
		return self::send_check_in( $slug, 'in_progress', $check_in_id, null ) ? $check_in_id : '';
	}

	/** Complete a previously started Sentry Cron check-in. */
	public static function finish_check_in( string $slug, string $check_in_id, string $status, ?int $duration = null ): bool {
		if ( '' === $check_in_id ) {
			return false;
		}

		$status = in_array( $status, array( 'ok', 'error' ), true ) ? $status : 'error';
		return self::send_check_in( $slug, $status, $check_in_id, $duration );
	}

	/** Return public ingestion endpoints for a DSN, or null when it is invalid. */
	public static function endpoints_for_dsn( string $dsn, string $cron_slug = 'hse-bokapos-watchdog' ): ?array {
		$parts = wp_parse_url( trim( $dsn ) );
		if ( ! is_array( $parts )
			|| 'https' !== ( $parts['scheme'] ?? '' )
			|| empty( $parts['host'] )
			|| empty( $parts['user'] )
			|| empty( $parts['path'] ) ) {
			return null;
		}

		$path       = trim( (string) $parts['path'], '/' );
		$segments   = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
		$project_id = (string) array_pop( $segments );
		if ( '' === $project_id || ! ctype_digit( $project_id ) ) {
			return null;
		}

		$base = 'https://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$base .= ':' . absint( $parts['port'] );
		}
		if ( $segments ) {
			$base .= '/' . implode( '/', array_map( 'rawurlencode', $segments ) );
		}

		$key       = rawurlencode( (string) $parts['user'] );
		$slug      = sanitize_title( $cron_slug );
		$api_base  = $base . '/api/' . rawurlencode( $project_id );
		$public_dsn = 'https://' . rawurlencode( (string) $parts['user'] ) . '@' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$public_dsn .= ':' . absint( $parts['port'] );
		}
		$public_dsn .= '/' . $path;

		return array(
			'dsn'          => $public_dsn,
			'envelope_url' => $api_base . '/envelope/',
			'cron_url'     => $api_base . '/crons/' . rawurlencode( $slug ) . '/' . $key . '/',
		);
	}

	/** Send a check-in to the Sentry monitor ingestion endpoint. */
	private static function send_check_in( string $slug, string $status, string $check_in_id, ?int $duration ): bool {
		$parts = self::dsn_parts( $slug );
		if ( null === $parts ) {
			return false;
		}

		$body = array(
			'check_in_id'  => $check_in_id,
			'monitor_slug' => sanitize_title( $slug ),
			'status'       => $status,
			'environment'  => self::environment(),
		);
		if ( null !== $duration ) {
			$body['duration'] = max( 0, $duration );
		}
		if ( 'in_progress' === $status ) {
			$body['monitor_config'] = array(
				'schedule'       => array(
					'type'  => 'interval',
					'value' => 5,
					'unit'  => 'minute',
				),
				'checkin_margin' => 2,
				'max_runtime'    => 5,
				'timezone'       => 'UTC',
			);
		}

		$header = array(
			'event_id' => self::event_id(),
			'dsn'      => self::dsn(),
			'sent_at'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'sdk'     => array(
				'name'    => 'hse-headless-monitoring',
				'version' => '0.32.0',
			),
		);
		$payload  = wp_json_encode( $body );
		$envelope = wp_json_encode( $header ) . "\n"
			. wp_json_encode(
				array(
					'type'         => 'check_in',
					'length'       => strlen( $payload ),
					'content_type' => 'application/json',
				)
			) . "\n"
			. $payload;

		$response = wp_remote_post(
			$parts['envelope_url'],
			array(
				'headers'     => array( 'Content-Type' => 'application/x-sentry-envelope' ),
				'body'        => $envelope,
				'timeout'     => 5,
				'redirection' => 0,
				'data_format' => 'body',
			)
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		return $status_code >= 200 && $status_code < 300;
	}

	/** Return parsed endpoints for the configured DSN. */
	private static function dsn_parts( string $cron_slug = 'hse-bokapos-watchdog' ): ?array {
		return self::endpoints_for_dsn( self::dsn(), $cron_slug );
	}

	/** Return the server-owned DSN without exposing it to browser code. */
	private static function dsn(): string {
		if ( defined( self::DSN_CONSTANT ) ) {
			return trim( (string) constant( self::DSN_CONSTANT ) );
		}

		$value = getenv( self::DSN_CONSTANT );
		return false === $value ? '' : trim( (string) $value );
	}

	/** Stable environment tag shared by events and heartbeat check-ins. */
	private static function environment(): string {
		if ( defined( self::ENVIRONMENT_CONSTANT ) ) {
			$value = sanitize_key( (string) constant( self::ENVIRONMENT_CONSTANT ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		$value = getenv( self::ENVIRONMENT_CONSTANT );
		if ( false !== $value && '' !== sanitize_key( (string) $value ) ) {
			return sanitize_key( (string) $value );
		}

		return function_exists( 'wp_get_environment_type' ) ? sanitize_key( wp_get_environment_type() ) : 'production';
	}

	/** Keep tags low-cardinality except for the separately fingerprinted operation. */
	private static function event_tags( string $incident, array $context ): array {
		$tags = array(
			'subsystem' => 'bokapos',
			'incident'  => $incident,
		);
		foreach ( array( 'operation_kind', 'status', 'fiscal_environment' ) as $key ) {
			if ( isset( $context[ $key ] ) && '' !== (string) $context[ $key ] ) {
				$tags[ $key ] = self::bounded_text( (string) $context[ $key ], 80 );
			}
		}
		return $tags;
	}

	/** Allow only shallow scalar operational context. */
	private static function sanitize_context( array $context ): array {
		$clean = array();
		foreach ( $context as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key || is_array( $value ) || is_object( $value ) || is_resource( $value ) ) {
				continue;
			}
			if ( is_bool( $value ) ) {
				$clean[ $key ] = $value;
				continue;
			}
			if ( is_int( $value ) || is_float( $value ) ) {
				$clean[ $key ] = $value;
				continue;
			}
			$clean[ $key ] = self::bounded_text( (string) $value, self::MAX_CONTEXT_LENGTH );
		}
		return $clean;
	}

	/** Send a bounded fallback alert through the configured WordPress transport. */
	private static function send_fallback_email( string $incident, string $level, string $message, array $context ): bool {
		$recipient = '';
		if ( defined( self::ALERT_EMAIL_CONSTANT ) ) {
			$recipient = sanitize_email( (string) constant( self::ALERT_EMAIL_CONSTANT ) );
		}
		if ( '' === $recipient && defined( 'HSE_COMMERCE_ADMIN_EMAIL' ) ) {
			$recipient = sanitize_email( (string) constant( 'HSE_COMMERCE_ADMIN_EMAIL' ) );
		}
		if ( '' === $recipient ) {
			$recipient = sanitize_email( (string) get_option( 'admin_email' ) );
		}
		if ( '' === $recipient ) {
			return false;
		}

		$subject = sprintf( '[HSE monitoring][%s][%s] %s', strtoupper( self::environment() ), strtoupper( $level ), $incident );
		$lines   = array( $message, '', 'Incident: ' . $incident, 'Environment: ' . self::environment() );
		foreach ( $context as $key => $value ) {
			$lines[] = $key . ': ' . ( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value );
		}

		return (bool) wp_mail( $recipient, $subject, implode( "\n", $lines ) );
	}

	/** Create a Sentry-compatible 32 character event id. */
	private static function event_id(): string {
		try {
			return bin2hex( random_bytes( 16 ) );
		} catch ( \Throwable $error ) {
			unset( $error );
			return str_replace( '-', '', wp_generate_uuid4() );
		}
	}

	/** Bounded UTF-8 text helper. */
	private static function bounded_text( string $value, int $limit ): string {
		$value = wp_strip_all_tags( $value, true );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $limit ) : substr( $value, 0, $limit );
	}

	/** Log only operational identifiers when the independent channel is unavailable. */
	private static function log( string $message, string $incident, array $context ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		wc_get_logger()->warning(
			$message,
			array(
				'source'       => 'hse-operational-monitoring',
				'incident'     => $incident,
				'order_id'     => absint( $context['order_id'] ?? 0 ),
				'operation_id' => absint( $context['operation_id'] ?? 0 ),
			)
		);
	}
}
