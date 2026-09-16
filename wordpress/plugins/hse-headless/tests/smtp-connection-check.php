<?php
/**
 * Authenticate with the configured SMTP transport without sending an email.
 *
 * Run manually with `wp eval-file` after server-owned credentials are present.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Infrastructure\SmtpMailer;

if ( ! class_exists( SmtpMailer::class ) ) {
	WP_CLI::error( 'SMTP mailer implementation is not loaded.' );
}

$result = SmtpMailer::test_connection();
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

WP_CLI::success( 'SMTP authentication succeeded. No email was sent.' );
