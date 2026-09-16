<?php
/**
 * SMTP configuration checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Infrastructure\SmtpMailer;

if ( ! class_exists( SmtpMailer::class ) ) {
	WP_CLI::error( 'SMTP mailer implementation is not loaded.' );
}

$failures = array();

/** Record a failed assertion without stopping remaining checks. */
function hse_smtp_mailer_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

final class HseSmtpTestMailer {
	public $used_smtp = false;
	public $Host;
	public $Port;
	public $SMTPAuth;
	public $SMTPSecure;
	public $SMTPAutoTLS;
	public $Username;
	public $Password;
	public $CharSet;
	public $From;
	public $FromName;

	public function isSMTP() {
		$this->used_smtp = true;
	}
}

$valid_configuration = array(
	'host'     => 'mail.hsetraining.rs',
	'port'     => 587,
	'secure'   => 'tls',
	'username' => 'website@hsetraining.rs',
	'password' => 'integration-test-secret',
	'from'     => 'website@hsetraining.rs',
	'to'       => 'info@hsetraining.rs',
);

hse_smtp_mailer_test_assert( array() === SmtpMailer::validate_configuration( $valid_configuration ), 'The recommended SMTP configuration is valid.' );

$invalid_configuration           = $valid_configuration;
$invalid_configuration['port']   = 70000;
$invalid_configuration['secure'] = 'none';
$invalid_configuration['from']   = 'not-an-email';
$invalid_errors                  = SmtpMailer::validate_configuration( $invalid_configuration );
hse_smtp_mailer_test_assert( 3 === count( $invalid_errors ), 'Invalid port, encryption, and sender settings are rejected.' );

$mailer = new HseSmtpTestMailer();
SmtpMailer::apply_configuration( $mailer, $valid_configuration );
hse_smtp_mailer_test_assert( $mailer->used_smtp, 'The mailer switches to authenticated SMTP.' );
hse_smtp_mailer_test_assert( 'mail.hsetraining.rs' === $mailer->Host, 'The configured SMTP host is applied.' );
hse_smtp_mailer_test_assert( 587 === $mailer->Port, 'The configured SMTP port is applied.' );
hse_smtp_mailer_test_assert( true === $mailer->SMTPAuth, 'SMTP authentication is enabled.' );
hse_smtp_mailer_test_assert( 'tls' === $mailer->SMTPSecure, 'STARTTLS is applied.' );
hse_smtp_mailer_test_assert( 'integration-test-secret' === $mailer->Password, 'The configured credential reaches PHPMailer without persistence.' );
hse_smtp_mailer_test_assert( 'UTF-8' === $mailer->CharSet, 'UTF-8 email content is enabled.' );
hse_smtp_mailer_test_assert( 'website@hsetraining.rs' === $mailer->From, 'The dedicated website sender is applied.' );
hse_smtp_mailer_test_assert( 10 === has_action( 'phpmailer_init', array( SmtpMailer::class, 'configure_phpmailer' ) ), 'The SMTP hook is registered.' );

if ( $failures ) {
	WP_CLI::error( sprintf( '%d SMTP mailer integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All SMTP mailer integration checks passed.' );
