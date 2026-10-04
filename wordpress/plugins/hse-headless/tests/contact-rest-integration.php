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
hse_contact_rest_test_assert( 'info@hsetraining.rs' === $mail['to'], 'Contact enquiries always use the approved HSE recipient.' );
hse_contact_rest_test_assert( false !== strpos( $mail['message'], '<!doctype html>' ), 'The branded HTML template is delivered.' );
hse_contact_rest_test_assert( in_array( 'Reply-To: predrag@example.com', $mail['headers'], true ), 'The visitor is configured as Reply-To.' );
hse_contact_rest_test_assert(
	'production' === HSETraining\Headless\Contact\ContactRestController::monitoring_environment_for_origin( 'https://hsetraining.rs' )
		&& 'non-production' === HSETraining\Headless\Contact\ContactRestController::monitoring_environment_for_origin( 'https://staging.hsetraining.rs' ),
	'Contact monitoring distinguishes production from preview origins.'
);

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

$mail_calls             = array();
$turnstile_http_calls   = array();
$turnstile_http_result  = array(
	'success'  => true,
	'hostname' => 'hsetraining.rs',
	'action'   => 'contact',
);
$client_address         = '192.0.2.10';
$_SERVER['REMOTE_ADDR'] = $client_address;
$accepted_limit_key     = 'hse_contact_' . substr( hash_hmac( 'sha256', $client_address, wp_salt( 'nonce' ) ), 0, 32 );
$attempt_limit_key      = 'hse_contact_attempt_' . substr( hash_hmac( 'sha256', $client_address, wp_salt( 'nonce' ) ), 0, 32 );

$required_filter = static function () {
	return true;
};
$secret_filter = static function () {
	return '1x0000000000000000000000000000000AA';
};
$turnstile_http_filter = static function ( $preempt, $arguments, $url ) use ( &$turnstile_http_calls, &$turnstile_http_result ) {
	if ( 'https://challenges.cloudflare.com/turnstile/v0/siteverify' !== $url ) {
		return $preempt;
	}

	$turnstile_http_calls[] = $arguments;
	if ( is_wp_error( $turnstile_http_result ) ) {
		return $turnstile_http_result;
	}

	return array(
		'headers'  => array(),
		'body'     => wp_json_encode( $turnstile_http_result ),
		'response' => array(
			'code'    => 200,
			'message' => 'OK',
		),
		'cookies'  => array(),
		'filename' => null,
	);
};

add_filter( 'hse_turnstile_required', $required_filter );
add_filter( 'hse_turnstile_secret', $secret_filter );
add_filter( 'pre_http_request', $turnstile_http_filter, 10, 3 );

$turnstile_request = static function ( $token = 'test-token' ) {
	$request = new WP_REST_Request( 'POST', '/hse/v1/contact' );
	$request->set_header( 'Origin', 'https://hsetraining.rs' );
	$request->set_body_params(
		array(
			'name'             => 'Predrag Vuckovic',
			'email'            => 'predrag@example.com',
			'phone'            => '+381 61 123 456',
			'message'          => 'Please send more information about the next course date.',
			'locale'           => 'en',
			'company_website'  => '',
			'turnstile_token' => $token,
		)
	);
	return $request;
};

$verified_response = rest_do_request( $turnstile_request() );
$verification_body = $turnstile_http_calls[0]['body'] ?? array();
hse_contact_rest_test_assert( 202 === $verified_response->get_status(), 'A valid Turnstile token permits delivery.' );
hse_contact_rest_test_assert( 1 === count( $mail_calls ), 'A verified enquiry invokes mail once.' );
hse_contact_rest_test_assert(
	'test-token' === ( $verification_body['response'] ?? '' )
		&& '192.0.2.10' === ( $verification_body['remoteip'] ?? '' )
		&& '' !== ( $verification_body['idempotency_key'] ?? '' ),
	'Turnstile verification sends the token, client address, and an idempotency key server-side.'
);

$missing_response = rest_do_request( $turnstile_request( '' ) );
hse_contact_rest_test_assert( 403 === $missing_response->get_status(), 'A missing Turnstile token is rejected when enforcement is enabled.' );
hse_contact_rest_test_assert( 1 === count( $mail_calls ), 'A missing token never invokes mail delivery.' );

$turnstile_http_result = array(
	'success'     => false,
	'error-codes' => array( 'invalid-input-response' ),
);
$rejected_response = rest_do_request( $turnstile_request( 'rejected-token' ) );
hse_contact_rest_test_assert( 403 === $rejected_response->get_status(), 'A Cloudflare-rejected token is rejected locally.' );

$turnstile_http_result = array(
	'success'  => true,
	'hostname' => 'untrusted.example',
	'action'   => 'contact',
);
$hostname_response = rest_do_request( $turnstile_request( 'wrong-host-token' ) );
hse_contact_rest_test_assert( 403 === $hostname_response->get_status(), 'A token for another hostname is rejected.' );

$turnstile_http_result = array(
	'success'  => true,
	'hostname' => 'hsetraining.rs',
	'action'   => 'checkout',
);
$action_response = rest_do_request( $turnstile_request( 'wrong-action-token' ) );
hse_contact_rest_test_assert( 403 === $action_response->get_status(), 'A token for another action is rejected.' );

$turnstile_http_result = new WP_Error( 'http_request_failed', 'Simulated timeout.' );
$unavailable_response  = rest_do_request( $turnstile_request( 'timeout-token' ) );
hse_contact_rest_test_assert( 503 === $unavailable_response->get_status(), 'A Turnstile transport failure fails closed with a retryable response.' );
hse_contact_rest_test_assert( 1 === count( $mail_calls ), 'No failed Turnstile path invokes mail delivery.' );

$http_call_count = count( $turnstile_http_calls );
set_transient( $attempt_limit_key, 10, 10 * MINUTE_IN_SECONDS );
$attempt_limited_response = rest_do_request( $turnstile_request( 'rate-limited-token' ) );
hse_contact_rest_test_assert( 429 === $attempt_limited_response->get_status(), 'Turnstile verification attempts are independently rate limited.' );
hse_contact_rest_test_assert( $http_call_count === count( $turnstile_http_calls ), 'Attempt limiting runs before contacting Cloudflare.' );

remove_filter( 'pre_http_request', $turnstile_http_filter, 10 );
remove_filter( 'hse_turnstile_secret', $secret_filter );
remove_filter( 'hse_turnstile_required', $required_filter );
delete_transient( $accepted_limit_key );
delete_transient( $attempt_limit_key );

if ( $failures ) {
	WP_CLI::error( sprintf( '%d contact REST integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All contact REST integration checks passed. No email was sent.' );
