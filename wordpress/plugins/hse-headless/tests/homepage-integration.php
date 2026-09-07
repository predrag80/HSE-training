<?php
/**
 * Homepage Hero Slide and REST checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Homepage\HeroSlideMeta;
use HSETraining\Headless\Homepage\HeroSlidePostType;
use HSETraining\Headless\Homepage\HomepageRestController;
use HSETraining\Headless\Company\CompanyPageSettings;
use HSETraining\Headless\Service\ServiceMeta;
use HSETraining\Headless\Service\ServicePostType;

if ( ! class_exists( HeroSlidePostType::class ) || ! class_exists( HeroSlideMeta::class ) || ! class_exists( HomepageRestController::class ) ) {
	WP_CLI::error( 'Homepage implementation is not loaded.' );
}

$failures          = array();
$created_ids       = array();
$attachment_id     = 0;
$original_user     = get_current_user_id();
$company_existed   = false !== get_option( CompanyPageSettings::OPTION_NAME, false );
$company_original  = get_option( CompanyPageSettings::OPTION_NAME, array() );
$published_before  = get_posts(
	array(
		'post_type'      => HeroSlidePostType::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
$published_services_before = get_posts(
	array(
		'post_type'      => ServicePostType::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
$created_service_id = 0;

/**
 * Record a failed assertion without stopping cleanup.
 *
 * @param bool   $condition Whether the assertion passed.
 * @param string $message   Failure message.
 */
function hse_homepage_test_assert( $condition, $message ) {
	global $failures;

	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

/**
 * Create a complete temporary Hero Slide.
 *
 * @param string $key           Stable key.
 * @param int    $order         Display order.
 * @param int    $attachment_id Featured image ID.
 * @return int
 */
function hse_homepage_test_create_slide( $key, $order, $attachment_id ) {
	$post_id = wp_insert_post(
		array(
			'post_type'   => HeroSlidePostType::POST_TYPE,
			'post_status' => 'draft',
			'post_title'  => 'Homepage integration ' . $key,
			'menu_order'  => $order,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Could not create a temporary Hero Slide.' );
	}

	$fields = array(
		HeroSlideMeta::SLIDE_KEY          => $key,
		HeroSlideMeta::LEADING_TITLE      => 'Slide ' . $order,
		HeroSlideMeta::EMPHASIZED_TITLE   => 'heading',
		HeroSlideMeta::PRIMARY_CTA_LABEL  => 'Contact us',
		HeroSlideMeta::PRIMARY_CTA_URL    => '#contact',
		HeroSlideMeta::MESSAGE_PREFIX     => 'Supporting message',
		HeroSlideMeta::MESSAGE_LINK_LABEL => 'Learn more',
		HeroSlideMeta::MESSAGE_LINK_URL   => '/consulting/',
	);

	foreach ( $fields as $meta_key => $value ) {
		update_post_meta( $post_id, $meta_key, $value );
	}

	set_post_thumbnail( $post_id, $attachment_id );

	return $post_id;
}

try {
	hse_homepage_test_assert( post_type_exists( HeroSlidePostType::POST_TYPE ), 'Hero Slide CPT is registered.' );
	hse_homepage_test_assert( post_type_supports( HeroSlidePostType::POST_TYPE, 'thumbnail' ), 'Hero Slides support featured images.' );
	hse_homepage_test_assert( post_type_supports( HeroSlidePostType::POST_TYPE, 'page-attributes' ), 'Hero Slides support explicit ordering.' );
	hse_homepage_test_assert( 'homepage-main' === HeroSlideMeta::sanitize_slide_key( 'Homepage Main' ), 'Slide keys normalize to canonical form.' );
	hse_homepage_test_assert( '' === HeroSlideMeta::sanitize_link_url( 'javascript:alert(1)' ), 'Unsafe Hero Slide links are rejected.' );
	hse_homepage_test_assert( '/consulting/' === HeroSlideMeta::sanitize_link_url( '/consulting/' ), 'Same-site Hero Slide links are accepted.' );

	foreach ( $published_before as $published_id ) {
		wp_update_post(
			array(
				'ID'          => $published_id,
				'post_status' => 'draft',
			)
		);
	}
	foreach ( $published_services_before as $published_service_id ) {
		wp_update_post( array( 'ID' => $published_service_id, 'post_status' => 'draft' ) );
	}

	$unconfigured = HomepageRestController::get_homepage( new WP_REST_Request( 'GET', '/hse/v1/homepage' ) );
	hse_homepage_test_assert( is_wp_error( $unconfigured ), 'A Homepage without published slides does not return a partial document.' );
	hse_homepage_test_assert( 503 === ( $unconfigured->get_error_data()['status'] ?? 0 ), 'A Homepage without published slides returns HTTP 503.' );

	$temp_file = wp_tempnam( 'hse-homepage-test.png' );
	if ( ! $temp_file ) {
		throw new RuntimeException( 'Could not create the temporary Homepage image.' );
	}

	$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true );
	if ( false === $png || false === file_put_contents( $temp_file, $png ) ) {
		throw new RuntimeException( 'Could not write the temporary Homepage image.' );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_status'    => 'inherit',
			'post_title'     => 'Homepage integration test image',
		),
		$temp_file,
		0,
		true
	);

	if ( is_wp_error( $attachment_id ) ) {
		throw new RuntimeException( 'Could not register the temporary Homepage image.' );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $temp_file ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Homepage integration image' );
	update_option(
		CompanyPageSettings::OPTION_NAME,
		CompanyPageSettings::sanitize_settings(
			array(
				'hero_eyebrow' => 'Business profile', 'hero_title' => 'About company',
				'about_eyebrow' => "Company's vision", 'about_headline' => 'Safety starts with people.',
				'about_description' => 'Shared company description.',
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

	$created_service_id = wp_insert_post(
		array(
			'post_type'   => ServicePostType::POST_TYPE,
			'post_status' => 'draft',
			'post_title'  => 'Homepage integration Service',
			'menu_order'  => 10,
		),
		true
	);
	if ( is_wp_error( $created_service_id ) ) {
		throw new RuntimeException( 'Could not create the Homepage integration Service.' );
	}
	$service_fields = array(
		ServiceMeta::SERVICE_KEY          => 'hse-homepage-service-' . strtolower( wp_generate_password( 8, false, false ) ),
		ServiceMeta::CARD_LABEL           => 'Capability',
		ServiceMeta::SHORT_DESCRIPTION    => 'Homepage service description',
		ServiceMeta::DETAILED_DESCRIPTION => 'Detailed Homepage integration service description.',
		ServiceMeta::CTA_LABEL            => 'Enquire',
		ServiceMeta::CTA_URL              => '/contact/',
		ServiceMeta::FEATURED_ON_HOMEPAGE => true,
	);
	foreach ( $service_fields as $meta_key => $value ) {
		update_post_meta( $created_service_id, $meta_key, $value );
	}
	set_post_thumbnail( $created_service_id, $attachment_id );
	wp_update_post( array( 'ID' => $created_service_id, 'post_status' => 'publish' ) );

	$key_prefix = 'hse-hero-test-' . strtolower( wp_generate_password( 8, false, false ) );
	$first_id   = hse_homepage_test_create_slide( $key_prefix . '-later', 20, $attachment_id );
	$second_id  = hse_homepage_test_create_slide( $key_prefix . '-first', 10, $attachment_id );
	$created_ids[] = $first_id;
	$created_ids[] = $second_id;

	wp_update_post( array( 'ID' => $first_id, 'post_status' => 'publish' ) );
	wp_update_post( array( 'ID' => $second_id, 'post_status' => 'publish' ) );
	hse_homepage_test_assert( 'publish' === get_post_status( $first_id ), 'A complete Hero Slide can be published.' );
	hse_homepage_test_assert( false === update_post_meta( $first_id, HeroSlideMeta::SLIDE_KEY, $key_prefix . '-changed' ), 'A published slide key cannot be changed.' );

	$duplicate_id = hse_homepage_test_create_slide( $key_prefix . '-duplicate', 30, $attachment_id );
	$created_ids[] = $duplicate_id;
	hse_homepage_test_assert( false === update_post_meta( $duplicate_id, HeroSlideMeta::SLIDE_KEY, $key_prefix . '-later' ), 'Duplicate slide keys are rejected.' );

	for ( $index = 3; $index <= HeroSlideMeta::MAX_PUBLISHED; $index++ ) {
		$post_id       = hse_homepage_test_create_slide( $key_prefix . '-' . $index, $index * 10, $attachment_id );
		$created_ids[] = $post_id;
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
	}

	$overflow_id = hse_homepage_test_create_slide( $key_prefix . '-overflow', 100, $attachment_id );
	$created_ids[] = $overflow_id;
	wp_update_post( array( 'ID' => $overflow_id, 'post_status' => 'publish' ) );
	hse_homepage_test_assert( 'draft' === get_post_status( $overflow_id ), 'A sixth Hero Slide cannot be published.' );

	wp_set_current_user( 0 );
	$response = HomepageRestController::get_homepage( new WP_REST_Request( 'GET', '/hse/v1/homepage' ) );
	hse_homepage_test_assert( ! is_wp_error( $response ), 'Anonymous visitors can read published Hero Slides.' );

	$data = is_wp_error( $response ) ? array() : $response->get_data();
	hse_homepage_test_assert( 1 === ( $data['schema_version'] ?? null ), 'Homepage REST contract remains version 1.' );
	hse_homepage_test_assert( 'home' === ( $data['page_key'] ?? null ), 'Homepage REST contract uses the stable home key.' );
	hse_homepage_test_assert( HeroSlideMeta::MAX_PUBLISHED === count( $data['hero']['slides'] ?? array() ), 'Homepage REST returns all five published slides.' );
	hse_homepage_test_assert( $key_prefix . '-first' === ( $data['hero']['slides'][0]['slide_key'] ?? null ), 'Homepage REST orders slides by the WordPress Order field.' );
	hse_homepage_test_assert( 1 === ( $data['hero']['slides'][0]['image']['width'] ?? null ), 'Homepage REST resolves image dimensions.' );
	hse_homepage_test_assert( ! isset( $data['hero']['slides'][0]['image']['id'] ), 'WordPress attachment IDs do not cross the CMS boundary.' );
	hse_homepage_test_assert( 1 === count( $data['featured_services'] ?? array() ), 'Homepage REST contains the featured Service collection.' );
	hse_homepage_test_assert( ! isset( $data['featured_services'][0]['image']['id'] ), 'Service attachment IDs do not cross the Homepage API boundary.' );
} finally {
	wp_set_current_user( $original_user );

	foreach ( $created_ids as $created_id ) {
		wp_delete_post( $created_id, true );
	}
	if ( $created_service_id && ! is_wp_error( $created_service_id ) ) {
		wp_delete_post( $created_service_id, true );
	}

	if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
		wp_delete_attachment( $attachment_id, true );
	}
	if ( $company_existed ) {
		update_option( CompanyPageSettings::OPTION_NAME, $company_original );
	} else {
		delete_option( CompanyPageSettings::OPTION_NAME );
	}

	// Avoid a stale per-request published-count cache while restoring fixtures.
	remove_filter( 'wp_insert_post_data', array( HeroSlideMeta::class, 'validate_publish' ), 10 );
	foreach ( $published_before as $published_id ) {
		wp_update_post(
			array(
				'ID'          => $published_id,
				'post_status' => 'publish',
			)
		);
	}
	add_filter( 'wp_insert_post_data', array( HeroSlideMeta::class, 'validate_publish' ), 10, 4 );

	remove_filter( 'wp_insert_post_data', array( ServiceMeta::class, 'validate_publish' ), 10 );
	foreach ( $published_services_before as $published_service_id ) {
		wp_update_post( array( 'ID' => $published_service_id, 'post_status' => 'publish' ) );
	}
	add_filter( 'wp_insert_post_data', array( ServiceMeta::class, 'validate_publish' ), 10, 4 );
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Homepage integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Homepage integration checks passed.' );
