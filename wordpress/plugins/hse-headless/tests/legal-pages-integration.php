<?php
/**
 * Legal Page settings and REST checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Content\ContentLocale;
use HSETraining\Headless\Legal\LegalPageRestController;
use HSETraining\Headless\Legal\LegalPageSettings;

defined( 'ABSPATH' ) || exit;

/** Fail the command with a readable assertion. */
function hse_legal_pages_test_assert( $condition, $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$option_names = array();
$originals    = array();

foreach ( LegalPageSettings::PAGE_KEYS as $page_key ) {
	foreach ( ContentLocale::supported() as $locale ) {
		$option_name              = LegalPageSettings::option_name( $page_key, $locale );
		$option_names[]            = $option_name;
		$originals[ $option_name ] = get_option( $option_name, null );
	}
}

try {
	$sanitized = LegalPageSettings::sanitize_settings(
		array(
			'meta_title'          => '<b>Privacy</b>',
			'meta_description'    => 'Privacy description.',
			'eyebrow'             => 'Legal information',
			'title'               => 'Privacy Policy',
			'intro'               => 'Privacy intro.',
			'last_updated'        => 'Last updated today',
			'section_1_title'     => 'Controller',
			'section_1_body'      => '<p>Allowed.</p><script>alert(1)</script>',
			'unknown_public_data' => 'discard me',
		)
	);
	hse_legal_pages_test_assert( 'Privacy' === $sanitized['meta_title'], 'Legal Page plain text is sanitized.' );
	hse_legal_pages_test_assert( false === strpos( $sanitized['section_1_body'], '<script' ), 'Unsafe Legal Page HTML is rejected.' );
	hse_legal_pages_test_assert( ! isset( $sanitized['unknown_public_data'] ), 'Unknown Legal Page settings are discarded.' );

	delete_option( LegalPageSettings::option_name( 'privacy', ContentLocale::DEFAULT_LOCALE ) );
	$incomplete = LegalPageSettings::get_public_document( 'privacy', ContentLocale::DEFAULT_LOCALE );
	hse_legal_pages_test_assert( is_wp_error( $incomplete ), 'An unconfigured Legal Page is not published.' );
	hse_legal_pages_test_assert( 503 === ( $incomplete->get_error_data()['status'] ?? 0 ), 'An unconfigured Legal Page returns HTTP 503.' );

	$english = array(
		'meta_title'       => 'Privacy Policy',
		'meta_description' => 'Privacy description.',
		'eyebrow'          => 'Legal information',
		'title'             => 'Privacy Policy',
		'intro'             => 'Privacy intro.',
		'last_updated'      => 'Last updated today',
		'section_1_title'   => 'Controller',
		'section_1_body'    => '<p>HSE Training is the controller.</p>',
	);
	update_option( LegalPageSettings::option_name( 'privacy', 'en' ), $english );

	$serbian                       = $english;
	$serbian['meta_title']         = 'Politika privatnosti';
	$serbian['title']              = 'Politika privatnosti';
	$serbian['section_1_title']    = 'Rukovalac';
	$serbian['section_1_body']     = '<p>HSE Training je rukovalac.</p>';
	update_option( LegalPageSettings::option_name( 'privacy', 'sr' ), $serbian );

	$request = new WP_REST_Request( 'GET', '/hse/v1/legal-pages/privacy' );
	$request->set_param( 'page_key', 'privacy' );
	$response = LegalPageRestController::get_legal_page( $request );
	$data     = $response->get_data();
	hse_legal_pages_test_assert( 'privacy' === ( $data['page_key'] ?? null ), 'Legal Page REST preserves its stable page key.' );
	hse_legal_pages_test_assert( 'en' === ( $data['locale'] ?? null ), 'Legal Page REST defaults to English.' );
	hse_legal_pages_test_assert( 'Controller' === ( $data['content']['sections'][0]['title'] ?? null ), 'Legal Page REST returns structured sections.' );
	hse_legal_pages_test_assert( ! isset( $data['content']['id'] ), 'WordPress IDs do not cross the Legal Page API boundary.' );

	$serbian_request = new WP_REST_Request( 'GET', '/hse/v1/legal-pages/privacy' );
	$serbian_request->set_param( 'page_key', 'privacy' );
	$serbian_request->set_param( 'lang', 'sr' );
	$serbian_response = LegalPageRestController::get_legal_page( $serbian_request );
	$serbian_data     = $serbian_response->get_data();
	hse_legal_pages_test_assert( 'sr' === ( $serbian_data['locale'] ?? null ), 'Serbian Legal Page content is isolated.' );
	hse_legal_pages_test_assert( 'Rukovalac' === ( $serbian_data['content']['sections'][0]['title'] ?? null ), 'Serbian Legal Page content is returned.' );

	echo "Legal Page integration checks passed.\n";
} finally {
	foreach ( $option_names as $option_name ) {
		if ( null === $originals[ $option_name ] ) {
			delete_option( $option_name );
		} else {
			update_option( $option_name, $originals[ $option_name ] );
		}
	}
}
