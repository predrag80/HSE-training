<?php
/**
 * Public contact-form submission endpoint.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Contact;

use HSETraining\Headless\Infrastructure\SmtpMailer;

defined( 'ABSPATH' ) || exit;

final class ContactRestController {
	public const REST_NAMESPACE = 'hse/v1';
	public const REST_ROUTE     = '/contact';
	private const RATE_LIMIT_MAX = 3;
	private const RATE_LIMIT_TTL = 10 * MINUTE_IN_SECONDS;

	/** Register WordPress hooks. */
	public static function register_hooks() {
		add_action( 'rest_api_init', array( self::class, 'register_route' ) );
	}

	/** Register the public contact endpoint. */
	public static function register_route() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'submit' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Validate and deliver one contact enquiry.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function submit( $request ) {
		$locale     = 'sr' === $request->get_param( 'locale' ) ? 'sr' : 'en';
		$request_id = wp_generate_uuid4();
		$content_length = (int) $request->get_header( 'content-length' );

		if ( ! self::is_allowed_origin( (string) $request->get_header( 'origin' ) ) ) {
			return self::public_error( 'hse_contact_origin_rejected', $locale, 403 );
		}
		if ( $content_length > 16384 ) {
			return self::public_error( 'hse_contact_invalid', $locale, 400 );
		}

		// Silently accept the honeypot so automated senders receive no useful signal.
		if ( '' !== trim( (string) $request->get_param( 'company_website' ) ) ) {
			return new \WP_REST_Response( array( 'accepted' => true ), 202 );
		}

		$rate_limit_key = self::rate_limit_key();
		if ( self::RATE_LIMIT_MAX <= (int) get_transient( $rate_limit_key ) ) {
			return self::public_error( 'hse_contact_rate_limited', $locale, 429 );
		}

		$enquiry = self::validate_enquiry( $request, $locale );
		if ( is_wp_error( $enquiry ) ) {
			return $enquiry;
		}

		$recipient = SmtpMailer::get_recipient();
		if ( '' === $recipient ) {
			error_log( '[HSE Headless] Contact delivery unavailable. Request ID: ' . $request_id ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return self::public_error( 'hse_contact_unavailable', $locale, 503 );
		}

		$html    = ContactEmailTemplate::render_html( $enquiry );
		$text    = ContactEmailTemplate::render_text( $enquiry );
		$subject = sprintf( '[HSE Training] Website enquiry - %s', $enquiry['name'] );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $enquiry['email'],
		);
		$set_alt_body = static function ( $phpmailer ) use ( $text ) {
			$phpmailer->AltBody = $text;
		};

		add_action( 'phpmailer_init', $set_alt_body, 20 );
		try {
			$sent = wp_mail( $recipient, $subject, $html, $headers );
		} finally {
			remove_action( 'phpmailer_init', $set_alt_body, 20 );
		}

		if ( ! $sent ) {
			error_log( '[HSE Headless] Contact delivery failed. Request ID: ' . $request_id ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return self::public_error( 'hse_contact_delivery_failed', $locale, 503 );
		}

		self::increment_rate_limit( $rate_limit_key );

		return new \WP_REST_Response(
			array(
				'accepted'  => true,
				'request_id' => $request_id,
			),
			202
		);
	}

	/**
	 * Normalize bounded public input.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @param string           $locale Request locale.
	 * @return array<string, string>|\WP_Error
	 */
	private static function validate_enquiry( $request, $locale ) {
		$name    = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$email   = sanitize_email( (string) $request->get_param( 'email' ) );
		$phone   = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );

		if (
			self::string_length( $name ) < 2 || self::string_length( $name ) > 100 ||
			! is_email( $email ) || self::string_length( $email ) > 254 ||
			self::string_length( $phone ) > 40 ||
			self::string_length( $message ) < 10 || self::string_length( $message ) > 5000
		) {
			return self::public_error( 'hse_contact_invalid', $locale, 400 );
		}

		return array(
			'name'        => $name,
			'email'       => $email,
			'phone'       => $phone,
			'message'     => $message,
			'locale'      => $locale,
			'received_at' => wp_date( 'd M Y, H:i T' ),
		);
	}

	/** Return a generic localized public error without SMTP details. */
	private static function public_error( $code, $locale, $status ) {
		$messages = array(
			'en' => array(
				'hse_contact_invalid'         => 'Please check the entered information and try again.',
				'hse_contact_rate_limited'    => 'Too many messages were sent. Please wait and try again.',
				'hse_contact_origin_rejected' => 'The message could not be accepted.',
				'hse_contact_unavailable'     => 'The message service is temporarily unavailable. Please try again later.',
				'hse_contact_delivery_failed' => 'The message could not be sent. Please try again later.',
			),
			'sr' => array(
				'hse_contact_invalid'         => 'Proverite unete podatke i pokušajte ponovo.',
				'hse_contact_rate_limited'    => 'Poslato je previše poruka. Sačekajte i pokušajte ponovo.',
				'hse_contact_origin_rejected' => 'Poruka nije mogla da bude prihvaćena.',
				'hse_contact_unavailable'     => 'Servis za slanje poruka trenutno nije dostupan. Pokušajte ponovo kasnije.',
				'hse_contact_delivery_failed' => 'Poruka nije mogla da bude poslata. Pokušajte ponovo kasnije.',
			),
		);

		return new \WP_Error( $code, $messages[ $locale ][ $code ], array( 'status' => $status ) );
	}

	/** Allow only the public site origins used by production, staging, and local development. */
	private static function is_allowed_origin( $origin ) {
		$origin = untrailingslashit( esc_url_raw( trim( $origin ) ) );
		if ( '' === $origin ) {
			return true;
		}

		$configured = defined( 'HSE_CONTACT_ALLOWED_ORIGINS' )
			? (string) constant( 'HSE_CONTACT_ALLOWED_ORIGINS' )
			: 'https://hsetraining.rs,https://www.hsetraining.rs,https://staging.hsetraining.rs';
		$allowed = array_map( 'untrailingslashit', array_map( 'trim', explode( ',', $configured ) ) );

		if ( in_array( $origin, $allowed, true ) ) {
			return true;
		}

		$host = (string) wp_parse_url( $origin, PHP_URL_HOST );
		return in_array( $host, array( 'localhost', '127.0.0.1' ), true );
	}

	/** Create a non-reversible per-client transient key without retaining the IP address. */
	private static function rate_limit_key() {
		$client_address = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		return 'hse_contact_' . substr( hash_hmac( 'sha256', $client_address, wp_salt( 'nonce' ) ), 0, 32 );
	}

	/** Increment the bounded rolling contact counter. */
	private static function increment_rate_limit( $key ) {
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::RATE_LIMIT_TTL );
	}

	/** UTF-8-safe input length with a core-PHP fallback. */
	private static function string_length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
}
