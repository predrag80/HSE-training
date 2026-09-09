<?php
/**
 * Company Page settings and REST checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Company\CompanyPageRestController;
use HSETraining\Headless\Company\CompanyPageSettings;
use HSETraining\Headless\Content\ContentLocale;

if ( ! class_exists( CompanyPageSettings::class ) || ! class_exists( CompanyPageRestController::class ) ) {
	WP_CLI::error( 'Company Page implementation is not loaded.' );
}

$failures       = array();
$attachment_id  = 0;
$option_name    = CompanyPageSettings::option_name( ContentLocale::DEFAULT_LOCALE );
$option_existed = false !== get_option( $option_name, false );
$original_value = get_option( $option_name, array() );

/** Record a failed assertion without stopping cleanup. */
function hse_company_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

try {
	$invalid = CompanyPageSettings::sanitize_settings(
		array(
			'hero_title'            => '<b>About company</b>',
			'company_intro_cta_url' => 'javascript:alert(1)',
			'unknown'               => 'discard me',
		)
	);
	hse_company_test_assert( 'About company' === $invalid['hero_title'], 'Company Page text is sanitized.' );
	hse_company_test_assert( '' === $invalid['company_intro_cta_url'], 'Unsafe Company Page links are rejected.' );
	hse_company_test_assert( ! isset( $invalid['unknown'] ), 'Unknown Company Page settings are discarded.' );

	update_option( $option_name, array() );
	$unconfigured = CompanyPageRestController::get_company_page();
	hse_company_test_assert( is_wp_error( $unconfigured ), 'An incomplete Company Page is not published.' );
	hse_company_test_assert( 503 === ( $unconfigured->get_error_data()['status'] ?? 0 ), 'An incomplete Company Page returns HTTP 503.' );

	$temp_file = wp_tempnam( 'hse-company-test.png' );
	$png       = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true );
	if ( ! $temp_file || false === $png || false === file_put_contents( $temp_file, $png ) ) {
		throw new RuntimeException( 'Could not create the temporary Company Page image.' );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_status'    => 'inherit',
			'post_title'     => 'Company Page integration image',
		),
		$temp_file,
		0,
		true
	);
	if ( is_wp_error( $attachment_id ) ) {
		throw new RuntimeException( 'Could not register the temporary Company Page image.' );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $temp_file ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Company integration image' );

	update_option(
		$option_name,
		CompanyPageSettings::sanitize_settings(
			array(
				'hero_eyebrow' => 'Business profile', 'hero_title' => 'About company',
				'about_eyebrow' => "Company's vision", 'about_headline' => 'Safety starts with people.',
				'about_description' => 'Canonical shared company profile.',
				'about_primary_cta_label' => 'About company', 'about_primary_cta_url' => '/company/',
				'about_secondary_cta_label' => 'How we work', 'about_secondary_cta_url' => '#about',
				'about_signature_label' => 'Training and consultancy',
				'about_primary_image_id' => $attachment_id, 'about_secondary_image_id' => $attachment_id,
				'value_training_title' => 'Training', 'value_training_description' => 'Training description.',
				'value_management_title' => 'Management', 'value_management_description' => 'Management description.',
				'value_consultancy_title' => 'Consultancy', 'value_consultancy_description' => 'Consultancy description.',
				'company_intro_cta_label' => 'Explore services', 'company_intro_cta_url' => '/#services',
			)
		)
	);

	$response = CompanyPageRestController::get_company_page();
	hse_company_test_assert( ! is_wp_error( $response ), 'A configured Company Page returns a public document.' );
	$data = is_wp_error( $response ) ? array() : $response->get_data();
	hse_company_test_assert( 'company' === ( $data['page_key'] ?? null ), 'Company Page uses a stable page key.' );
	hse_company_test_assert( 'en' === ( $data['locale'] ?? null ), 'Company Page defaults to English.' );
	hse_company_test_assert( 'Safety starts with people.' === ( $data['profile']['headline'] ?? null ), 'Company endpoint contains the shared profile.' );
	hse_company_test_assert( 3 === count( $data['profile']['values'] ?? array() ), 'Company endpoint contains three value highlights.' );
	hse_company_test_assert( ! isset( $data['profile']['primary_image']['id'] ), 'WordPress attachment IDs do not cross the Company API boundary.' );
} finally {
	if ( $option_existed ) {
		update_option( $option_name, $original_value );
	} else {
		delete_option( $option_name );
	}

	if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
		wp_delete_attachment( $attachment_id, true );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Company Page integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Company Page integration checks passed.' );
