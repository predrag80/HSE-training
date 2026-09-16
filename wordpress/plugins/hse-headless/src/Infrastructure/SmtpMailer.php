<?php
/**
 * Configure WordPress mail delivery from server-owned SMTP secrets.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Infrastructure;

defined( 'ABSPATH' ) || exit;

final class SmtpMailer {
	/** @var bool Avoid repeating the same configuration warning for every message. */
	private static $configuration_warning_logged = false;

	/** Register WordPress hooks. */
	public static function register_hooks() {
		add_action( 'phpmailer_init', array( self::class, 'configure_phpmailer' ) );
	}

	/**
	 * Read SMTP settings from wp-config constants first, then environment variables.
	 *
	 * @return array<string, int|string>
	 */
	private static function get_configuration() {
		return array(
			'host'     => self::read_setting( 'HSE_SMTP_HOST' ),
			'port'     => (int) self::read_setting( 'HSE_SMTP_PORT' ),
			'secure'   => strtolower( trim( self::read_setting( 'HSE_SMTP_SECURE' ) ) ),
			'username' => self::read_setting( 'HSE_SMTP_USERNAME' ),
			'password' => self::read_setting( 'HSE_SMTP_PASSWORD', false ),
			'from'     => self::read_setting( 'HSE_MAIL_FROM' ),
			'to'       => self::read_setting( 'HSE_MAIL_TO' ),
		);
	}

	/**
	 * Validate an SMTP configuration without exposing secret values.
	 *
	 * @param array<string, mixed> $configuration SMTP configuration.
	 * @return string[]
	 */
	public static function validate_configuration( $configuration ) {
		$errors = array();
		$host   = isset( $configuration['host'] ) ? (string) $configuration['host'] : '';
		$port   = isset( $configuration['port'] ) ? (int) $configuration['port'] : 0;
		$secure = isset( $configuration['secure'] ) ? (string) $configuration['secure'] : '';

		if ( '' === $host || preg_match( '/\s/', $host ) ) {
			$errors[] = 'HSE_SMTP_HOST must be a valid mail host.';
		}
		if ( $port < 1 || $port > 65535 ) {
			$errors[] = 'HSE_SMTP_PORT must be between 1 and 65535.';
		}
		if ( ! in_array( $secure, array( 'tls', 'ssl' ), true ) ) {
			$errors[] = 'HSE_SMTP_SECURE must be tls or ssl.';
		}
		if ( empty( $configuration['username'] ) ) {
			$errors[] = 'HSE_SMTP_USERNAME is required.';
		}
		if ( empty( $configuration['password'] ) ) {
			$errors[] = 'HSE_SMTP_PASSWORD is required.';
		}
		if ( empty( $configuration['from'] ) || ! is_email( (string) $configuration['from'] ) ) {
			$errors[] = 'HSE_MAIL_FROM must be a valid email address.';
		}
		if ( empty( $configuration['to'] ) || ! is_email( (string) $configuration['to'] ) ) {
			$errors[] = 'HSE_MAIL_TO must be a valid email address.';
		}

		return $errors;
	}

	/**
	 * Configure the PHPMailer instance when every required setting is valid.
	 *
	 * @param object $phpmailer WordPress PHPMailer instance.
	 */
	public static function configure_phpmailer( $phpmailer ) {
		$configuration = self::get_configuration();
		$errors        = self::validate_configuration( $configuration );

		if ( $errors ) {
			if ( self::has_configuration_value( $configuration ) && ! self::$configuration_warning_logged ) {
				error_log( '[HSE Headless] SMTP configuration was not applied: ' . implode( ' ', $errors ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				self::$configuration_warning_logged = true;
			}
			return;
		}

		self::apply_configuration( $phpmailer, $configuration );
	}

	/**
	 * Apply a validated configuration to a PHPMailer-compatible object.
	 *
	 * Kept separate from the hook callback so the transport mapping can be tested
	 * without sending a message or loading real credentials.
	 *
	 * @param object               $phpmailer     PHPMailer-compatible object.
	 * @param array<string, mixed> $configuration Valid SMTP configuration.
	 */
	public static function apply_configuration( $phpmailer, $configuration ) {
		$phpmailer->isSMTP();
		$phpmailer->Host        = (string) $configuration['host'];
		$phpmailer->Port        = (int) $configuration['port'];
		$phpmailer->SMTPAuth    = true;
		$phpmailer->SMTPSecure  = (string) $configuration['secure'];
		$phpmailer->SMTPAutoTLS = true;
		$phpmailer->Username    = (string) $configuration['username'];
		$phpmailer->Password    = (string) $configuration['password'];
		$phpmailer->CharSet     = 'UTF-8';
		$phpmailer->From        = (string) $configuration['from'];
		$phpmailer->FromName    = 'HSE Training';
	}

	/** Return the configured contact-form recipient, or an empty string. */
	public static function get_recipient() {
		$configuration = self::get_configuration();
		$recipient     = self::validate_configuration( $configuration ) ? '' : (string) $configuration['to'];
		$recipient     = sanitize_email( (string) apply_filters( 'hse_contact_recipient', $recipient ) );

		return is_email( $recipient ) ? $recipient : '';
	}

	/**
	 * Authenticate with the configured SMTP server without sending a message.
	 *
	 * This method is intended for an explicitly invoked WP-CLI health check. It
	 * never includes credentials or a server response in its public error text.
	 *
	 * @return true|\WP_Error
	 */
	public static function test_connection() {
		$configuration = self::get_configuration();
		$errors        = self::validate_configuration( $configuration );

		if ( $errors ) {
			return new \WP_Error( 'hse_smtp_configuration_invalid', implode( ' ', $errors ) );
		}

		self::load_phpmailer();
		$mailer = new \PHPMailer\PHPMailer\PHPMailer( true );
		self::apply_configuration( $mailer, $configuration );
		$mailer->Timeout   = 10;
		$mailer->SMTPDebug = 0;

		try {
			if ( ! $mailer->smtpConnect() ) {
				return new \WP_Error( 'hse_smtp_connection_failed', 'SMTP authentication failed.' );
			}
		} catch ( \Throwable $exception ) {
			return new \WP_Error( 'hse_smtp_connection_failed', 'SMTP authentication failed.' );
		} finally {
			$mailer->smtpClose();
		}

		return true;
	}

	/**
	 * Read one setting without writing it to WordPress options or the database.
	 *
	 * @param string $name Configuration name.
	 * @param bool   $trim Whether surrounding whitespace should be removed.
	 * @return string
	 */
	private static function read_setting( $name, $trim = true ) {
		$value = defined( $name ) ? constant( $name ) : getenv( $name );
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = (string) $value;
		return $trim ? trim( $value ) : $value;
	}

	/** Load WordPress' bundled PHPMailer classes for standalone health checks. */
	private static function load_phpmailer() {
		if ( class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			return;
		}

		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
	}

	/**
	 * Determine whether configuration was attempted at all.
	 *
	 * @param array<string, mixed> $configuration SMTP configuration.
	 * @return bool
	 */
	private static function has_configuration_value( $configuration ) {
		foreach ( $configuration as $value ) {
			if ( '' !== $value && 0 !== $value ) {
				return true;
			}
		}

		return false;
	}
}
