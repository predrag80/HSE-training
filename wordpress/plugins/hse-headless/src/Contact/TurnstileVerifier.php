<?php
/**
 * Server-side Cloudflare Turnstile verification for the public contact form.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Contact;

defined( 'ABSPATH' ) || exit;

/** Validate short-lived Turnstile tokens without exposing the secret to browsers. */
final class TurnstileVerifier {
	private const VERIFY_URL       = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	private const MAX_TOKEN_LENGTH = 2048;

	/** Whether contact submissions must carry a valid Turnstile token. */
	public static function is_required(): bool {
		$value = self::configuration_value( 'HSE_TURNSTILE_REQUIRED', 'false' );
		$value = apply_filters( 'hse_turnstile_required', $value );

		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Verify one browser-issued token with Cloudflare.
	 *
	 * @param string $token      Browser-issued Turnstile token.
	 * @param string $request_id Random request correlation identifier.
	 * @return true|\WP_Error
	 */
	public static function verify( string $token, string $request_id ) {
		if ( ! self::is_required() ) {
			return true;
		}

		$token = trim( $token );
		if ( '' === $token || self::string_length( $token ) > self::MAX_TOKEN_LENGTH ) {
			return new \WP_Error( 'hse_turnstile_rejected' );
		}

		$secret = self::secret();
		if ( '' === $secret ) {
			return new \WP_Error( 'hse_turnstile_unavailable' );
		}

		$body = array(
			'secret'          => $secret,
			'response'        => $token,
			'idempotency_key' => sanitize_text_field( $request_id ),
		);
		$client_address = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) $_SERVER['REMOTE_ADDR'] ) : '';
		if ( '' !== $client_address && false !== filter_var( $client_address, FILTER_VALIDATE_IP ) ) {
			$body['remoteip'] = $client_address;
		}

		$response = wp_remote_post(
			self::VERIFY_URL,
			array(
				'body'        => $body,
				'timeout'     => 5,
				'redirection' => 0,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'hse_turnstile_unavailable' );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $data ) ) {
			return new \WP_Error( 'hse_turnstile_unavailable' );
		}

		if ( true !== ( $data['success'] ?? false ) ) {
			return new \WP_Error( 'hse_turnstile_rejected' );
		}

		$hostname = strtolower( trim( (string) ( $data['hostname'] ?? '' ) ) );
		if ( '' === $hostname || ! in_array( $hostname, self::allowed_hostnames(), true ) ) {
			return new \WP_Error( 'hse_turnstile_rejected' );
		}

		$action = sanitize_key( (string) ( $data['action'] ?? '' ) );
		if ( self::expected_action() !== $action ) {
			return new \WP_Error( 'hse_turnstile_rejected' );
		}

		return true;
	}

	/** Server-owned Turnstile secret. */
	private static function secret(): string {
		$value = self::configuration_value( 'HSE_TURNSTILE_SECRET_KEY' );
		return trim( (string) apply_filters( 'hse_turnstile_secret', $value ) );
	}

	/** Exact public hostnames accepted in a successful Cloudflare response. */
	private static function allowed_hostnames(): array {
		$value = self::configuration_value( 'HSE_TURNSTILE_ALLOWED_HOSTNAMES', 'hsetraining.rs,www.hsetraining.rs' );
		$value = apply_filters( 'hse_turnstile_allowed_hostnames', $value );
		$items = is_array( $value ) ? $value : explode( ',', (string) $value );

		return array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $hostname ) {
							return strtolower( trim( (string) $hostname ) );
						},
						$items
					)
				)
			)
		);
	}

	/** Stable widget action expected from the production contact form. */
	private static function expected_action(): string {
		$value = self::configuration_value( 'HSE_TURNSTILE_ACTION', 'contact' );
		return sanitize_key( (string) apply_filters( 'hse_turnstile_action', $value ) );
	}

	/** Resolve one server setting from a constant first, then the process environment. */
	private static function configuration_value( string $name, string $default = '' ): string {
		if ( defined( $name ) ) {
			return (string) constant( $name );
		}

		$value = getenv( $name );
		return false === $value ? $default : (string) $value;
	}

	/** UTF-8-safe input length with a core-PHP fallback. */
	private static function string_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
}
