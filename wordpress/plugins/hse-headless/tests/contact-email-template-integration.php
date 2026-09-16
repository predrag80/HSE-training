<?php
/** Contact email rendering checks for execution with `wp eval-file`. */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Contact\ContactEmailTemplate;

if ( ! class_exists( ContactEmailTemplate::class ) ) {
	WP_CLI::error( 'Contact email template implementation is not loaded.' );
}

$failures = array();

/** Record a failed assertion without stopping remaining checks. */
function hse_contact_email_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

$enquiry = array(
	'name'        => '<script>alert("x")</script> Predrag',
	'email'       => 'predrag@example.com',
	'phone'       => '+381 60 123 4567',
	'message'     => "Please send course information.\nThank you.",
	'locale'      => 'en',
	'received_at' => '15 Sep 2026, 10:30 CEST',
);

$html = ContactEmailTemplate::render_html( $enquiry );
$text = ContactEmailTemplate::render_text( $enquiry );

hse_contact_email_test_assert( false !== strpos( $html, '<!doctype html>' ), 'The HTML document is rendered.' );
hse_contact_email_test_assert( false !== strpos( $html, 'HSE Training DOO' ), 'The company identity is present.' );
hse_contact_email_test_assert( false !== strpos( $html, '#292d36' ) && false !== strpos( $html, '#dd6531' ), 'The HSE brand palette is present.' );
hse_contact_email_test_assert( false !== strpos( $html, 'role="presentation"' ), 'Presentation tables are used for email-client compatibility.' );
hse_contact_email_test_assert( false !== strpos( $html, '<!--[if mso]>' ), 'Outlook-specific rendering support is present.' );
hse_contact_email_test_assert( false === strpos( $html, '<script>alert' ), 'Untrusted HTML is not rendered.' );
hse_contact_email_test_assert( false !== strpos( $html, 'alert(&quot;x&quot;)' ), 'Untrusted text is safely escaped.' );
hse_contact_email_test_assert( false !== strpos( $html, 'mailto:predrag@example.com' ), 'The reply action uses the validated sender email.' );
hse_contact_email_test_assert( false !== strpos( $text, 'Please send course information.' ), 'The plain-text alternative includes the message.' );
hse_contact_email_test_assert( false === strpos( $text, '<script>' ), 'The plain-text alternative is sanitized.' );

if ( $failures ) {
	WP_CLI::error( sprintf( '%d contact email integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All contact email template integration checks passed.' );
