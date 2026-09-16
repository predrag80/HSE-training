<?php
/**
 * Contact REST delivery checks for execution with `wp eval-file`.
 *
 * No email is sent: WordPress mail is intercepted before transport.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

$failures = array();

/** Record a failed assertion without stopping remaining checks. */
function hse_contact_rest_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

$mail_calls = array();
add_filter(
	'hse_contact_recipient',
	static function () {
		return 'contact-recipient@example.com';
	}
);
add_filter(
	'pre_wp_mail',
	static function ( $return, $attributes ) use ( &$mail_calls ) {
		$mail_calls[] = $attributes;
		return true;
	},
	10,
	2
);

$client_address         = 'contact-test-' . wp_generate_uuid4();
$_SERVER['REMOTE_ADDR'] = $client_address;
$rate_limit_key         = 'hse_contact_' . substr( hash_hmac( 'sha256', $client_address, wp_salt( 'nonce' ) ), 0, 32 );
$valid_request          = new WP_REST_Request( 'POST', '/hse/v1/contact' );
$valid_request->set_header( 'Origin', 'http://localhost:4321' );
$valid_request->set_body_params(
	array(
		'name'            => 'Predrag Vuckovic',
		'email'           => 'predrag@example.com',
		'phone'           => '+381 61 123 456',
		'message'         => 'Please send more information about the next course date.',
		'locale'          => 'en',
		'company_website' => '',
	)
);

$response = rest_do_request( $valid_request );
$mail     = isset( $mail_calls[0] ) ? $mail_calls[0] : array( 'to' => '', 'message' => '', 'headers' => array() );
hse_contact_rest_test_assert( 202 === $response->get_status(), 'A valid enquiry is accepted.' );
hse_contact_rest_test_assert( 1 === count( $mail_calls ), 'A valid enquiry invokes WordPress mail once.' );
hse_contact_rest_test_assert( 'contact-recipient@example.com' === $mail['to'], 'The server-configured recipient is used.' );
hse_contact_rest_test_assert( false !== strpos( $mail['message'], '<!doctype html>' ), 'The branded HTML template is delivered.' );
hse_contact_rest_test_assert( in_array( 'Reply-To: predrag@example.com', $mail['headers'], true ), 'The visitor is configured as Reply-To.' );

$invalid_request = new WP_REST_Request( 'POST', '/hse/v1/contact' );
$invalid_request->set_header( 'Origin', 'http://localhost:4321' );
$invalid_request->set_body_params(
	array(
		'name'            => 'P',
		'email'           => 'not-an-email',
		'message'         => 'Short',
		'locale'          => 'en',
		'company_website' => '',
	)
);
$invalid_response = rest_do_request( $invalid_request );
hse_contact_rest_test_assert( 400 === $invalid_response->get_status(), 'Invalid public input is rejected.' );
hse_contact_rest_test_assert( 1 === count( $mail_calls ), 'Invalid public input never invokes mail delivery.' );

$trap_request = new WP_REST_Request( 'POST', '/hse/v1/contact' );
$trap_request->set_body_params( array( 'company_website' => 'https://spam.example' ) );
$trap_response = rest_do_request( $trap_request );
hse_contact_rest_test_assert( 202 === $trap_response->get_status(), 'The honeypot is silently accepted.' );
hse_contact_rest_test_assert( 1 === count( $mail_calls ), 'The honeypot never invokes mail delivery.' );

$origin_request = new WP_REST_Request( 'POST', '/hse/v1/contact' );
$origin_request->set_header( 'Origin', 'https://untrusted.example' );
$origin_response = rest_do_request( $origin_request );
hse_contact_rest_test_assert( 403 === $origin_response->get_status(), 'An untrusted browser origin is rejected.' );

rest_do_request( $valid_request );
rest_do_request( $valid_request );
$limited_response = rest_do_request( $valid_request );
hse_contact_rest_test_assert( 429 === $limited_response->get_status(), 'The fourth accepted enquiry within the window is rate limited.' );
hse_contact_rest_test_assert( 3 === count( $mail_calls ), 'Rate limiting prevents the fourth delivery attempt.' );

delete_transient( $rate_limit_key );

if ( $failures ) {
	WP_CLI::error( sprintf( '%d contact REST integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All contact REST integration checks passed. No email was sent.' );
