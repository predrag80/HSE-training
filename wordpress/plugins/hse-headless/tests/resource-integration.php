<?php
/** Free Resources checks for execution with `wp eval-file`. */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Content\ContentLocale;
use HSETraining\Headless\Resource\ResourceMeta;
use HSETraining\Headless\Resource\ResourcePostType;
use HSETraining\Headless\Resource\ResourceRestController;

if ( ! class_exists( ResourceRestController::class ) ) {
	WP_CLI::error( 'Free Resources implementation is not loaded.' );
}

// wp eval-file includes this file inside a method, so bind the global the
// assertion helper records into; otherwise failures never fail the command.
global $failures;
$failures    = array();
$created_ids = array();
$published_before = get_posts(
	array(
		'post_type'      => ResourcePostType::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

function hse_resource_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

try {
	hse_resource_test_assert( post_type_exists( ResourcePostType::POST_TYPE ), 'Free Resources CPT is registered.' );
	hse_resource_test_assert( post_type_supports( ResourcePostType::POST_TYPE, 'thumbnail' ), 'Free Resources support featured images.' );
	hse_resource_test_assert( post_type_supports( ResourcePostType::POST_TYPE, 'page-attributes' ), 'Free Resources support explicit ordering.' );
	hse_resource_test_assert( 'video' === ResourceMeta::sanitize_type( 'video' ), 'Known resource types are accepted.' );
	hse_resource_test_assert( 'link' === ResourceMeta::sanitize_type( 'unsupported' ), 'Unknown resource types fall back safely.' );
	hse_resource_test_assert(
		false !== has_filter( 'wp_insert_post_data', array( ResourceMeta::class, 'validate_publish' ) )
			&& false !== has_filter( 'redirect_post_location', array( ResourceMeta::class, 'add_admin_error_to_redirect' ) )
			&& false !== has_action( 'admin_notices', array( ResourceMeta::class, 'render_admin_notice' ) ),
		'Free Resources publication validation and administrator feedback are registered.'
	);

	$incomplete_id = wp_insert_post(
		array(
			'post_type'   => ResourcePostType::POST_TYPE,
			'post_status' => 'draft',
			'post_title'  => 'Incomplete test resource',
		),
		true
	);
	if ( is_wp_error( $incomplete_id ) ) {
		throw new RuntimeException( 'Could not create an incomplete temporary Free Resource.' );
	}
	$created_ids[] = $incomplete_id;
	update_post_meta( $incomplete_id, ResourceMeta::RESOURCE_KEY, 'incomplete-test-resource' );

	hse_resource_test_assert(
		array( 'External URL or Media Library file' ) === ResourceMeta::missing_requirements( $incomplete_id ),
		'Incomplete Resources identify the exact missing publication requirement.'
	);

	$validated_data = ResourceMeta::validate_publish(
		array(
			'post_type'   => ResourcePostType::POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => 'Incomplete test resource',
		),
		array( 'ID' => $incomplete_id ),
		array(),
		true
	);
	hse_resource_test_assert(
		'draft' === ( $validated_data['post_status'] ?? '' ),
		'An incomplete Free Resource is kept as a draft before WordPress saves it.'
	);

	$redirect = ResourceMeta::add_admin_error_to_redirect( 'post.php?post=' . $incomplete_id, $incomplete_id );
	parse_str( (string) wp_parse_url( $redirect, PHP_URL_QUERY ), $redirect_query );
	hse_resource_test_assert(
		'hse_resource_incomplete' === ( $redirect_query['hse_resource_error'] ?? '' ),
		'The editor redirect carries the publication validation error.'
	);

	$_GET['post']               = (string) $incomplete_id;
	$_GET['hse_resource_error'] = 'hse_resource_incomplete';
	ob_start();
	ResourceMeta::render_admin_notice();
	$notice = (string) ob_get_clean();
	unset( $_GET['post'], $_GET['hse_resource_error'] );
	hse_resource_test_assert(
		false !== strpos( $notice, 'External URL or Media Library file' ),
		'The editor notice tells the administrator what must be completed.'
	);

	foreach ( $published_before as $published_id ) {
		wp_update_post( array( 'ID' => $published_id, 'post_status' => 'draft' ) );
	}
	$empty_response = ResourceRestController::get_resources();
	$empty_data     = $empty_response->get_data();
	hse_resource_test_assert( array() === ( $empty_data['resources'] ?? null ), 'An empty Free Resources collection is valid.' );

	$key_prefix = 'hse-resource-test-' . strtolower( wp_generate_password( 8, false, false ) );
	$records    = array(
		array( 'en', 20, 'document' ),
		array( 'en', 10, 'video' ),
		array( 'sr', 5, 'link' ),
	);
	foreach ( $records as $index => $record ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => ResourcePostType::POST_TYPE,
				'post_status'  => 'draft',
				'post_title'   => 'Test resource ' . $index,
				'post_content' => '<p>Longer resource description.</p>',
				'menu_order'   => $record[1],
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			throw new RuntimeException( 'Could not create a temporary Free Resource.' );
		}
		$created_ids[] = $post_id;
		update_post_meta( $post_id, ContentLocale::META_KEY, $record[0] );
		update_post_meta( $post_id, ResourceMeta::RESOURCE_KEY, $key_prefix . '-' . ( $index + 1 ) );
		update_post_meta( $post_id, ResourceMeta::TYPE, $record[2] );
		update_post_meta( $post_id, ResourceMeta::SUMMARY, 'Short resource description.' );
		update_post_meta( $post_id, ResourceMeta::TOPIC, 'Risk management' );
		update_post_meta( $post_id, ResourceMeta::EXTERNAL_URL, 'https://example.com/resource-' . $index );
		update_post_meta( $post_id, ResourceMeta::FEATURED, 0 === $index );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
	}

	$response = ResourceRestController::get_resources();
	$data     = $response->get_data();
	hse_resource_test_assert( 'resources' === ( $data['collection_key'] ?? null ), 'Resources REST uses the stable collection key.' );
	hse_resource_test_assert( 2 === count( $data['resources'] ?? array() ), 'English Resources REST excludes Serbian records.' );
	hse_resource_test_assert( 'video' === ( $data['resources'][0]['type'] ?? null ), 'Resources REST uses WordPress Order.' );
	hse_resource_test_assert( ! isset( $data['resources'][0]['id'] ), 'WordPress IDs do not cross the Resources API boundary.' );

	$serbian_request = new WP_REST_Request( 'GET', '/hse/v1/resources' );
	$serbian_request->set_param( 'lang', 'sr' );
	$serbian_data = ResourceRestController::get_resources( $serbian_request )->get_data();
	hse_resource_test_assert( 'sr' === ( $serbian_data['locale'] ?? null ), 'Serbian response carries its locale.' );
	hse_resource_test_assert( 1 === count( $serbian_data['resources'] ?? array() ), 'Serbian query returns only Serbian resources.' );

	hse_resource_test_assert(
		'risk-assessment-guide' === ResourceMeta::sanitize_resource_key( ' Risk_Assessment  Guide! ' )
			&& 'dacki-csz-vodic' === ResourceMeta::sanitize_resource_key( 'Đački ČŠŽ vodič' )
			&& '' === ResourceMeta::sanitize_resource_key( '--' )
			&& ResourceMeta::MAX_KEY_LENGTH === strlen( ResourceMeta::sanitize_resource_key( str_repeat( 'a', 120 ) ) ),
		'Resource keys are normalized to the canonical format the public build accepts.'
	);

	// Publish new Resources through the editor form, as an administrator does.
	$original_user_id = get_current_user_id();
	$administrators   = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		)
	);
	wp_set_current_user( $administrators ? (int) $administrators[0] : 0 );
	$meta_reflection = new ReflectionClass( ResourceMeta::class );
	$publish_from_editor = static function ( string $title, string $locale, string $key = '' ) use ( $meta_reflection, &$created_ids ): int {
		$post_id = wp_insert_post(
			array(
				'post_type'   => ResourcePostType::POST_TYPE,
				'post_status' => 'auto-draft',
				'post_title'  => 'Auto Draft',
			)
		);
		$created_ids[] = $post_id;
		$_POST = array(
			$meta_reflection->getConstant( 'NONCE_NAME' ) => wp_create_nonce( $meta_reflection->getConstant( 'NONCE_ACTION' ) ),
			'hse_content_locale'                          => $locale,
			'hse_' . ResourceMeta::RESOURCE_KEY           => $key,
			'hse_' . ResourceMeta::TYPE                   => 'link',
			'hse_' . ResourceMeta::EXTERNAL_URL           => 'https://example.com/editor-resource',
		);
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
		$_POST = array();

		return $post_id;
	};

	try {
		$serbian_id   = $publish_from_editor( 'Probni srpski resurs', 'sr' );
		$duplicate_id = $publish_from_editor( 'Probni srpski resurs', 'sr' );
		$english_id   = $publish_from_editor( 'Probni srpski resurs', 'en' );
		$manual_id    = $publish_from_editor( 'Manual key resource', 'en', 'Manual_Key  Resource!' );
	} finally {
		$_POST = array();
		wp_set_current_user( $original_user_id );
	}

	hse_resource_test_assert(
		'publish' === get_post_status( $serbian_id )
			&& 'sr' === ContentLocale::get_post_locale( $serbian_id )
			&& 'sr' === get_post_meta( $serbian_id, '_hse_locked_content_locale', true ),
		'A new Resource published directly in Serbian keeps and locks the Serbian language.'
	);
	hse_resource_test_assert(
		'probni-srpski-resurs' === get_post_meta( $serbian_id, ResourceMeta::RESOURCE_KEY, true )
			&& 'probni-srpski-resurs-2' === get_post_meta( $duplicate_id, ResourceMeta::RESOURCE_KEY, true )
			&& 'probni-srpski-resurs' === get_post_meta( $english_id, ResourceMeta::RESOURCE_KEY, true ),
		'An empty key is generated from the title and made unique within its language.'
	);
	hse_resource_test_assert(
		'manual-key-resource' === get_post_meta( $manual_id, ResourceMeta::RESOURCE_KEY, true ),
		'A key entered by the editor is kept in canonical form.'
	);
} finally {
	foreach ( $created_ids as $created_id ) {
		wp_delete_post( $created_id, true );
	}
	foreach ( $published_before as $published_id ) {
		wp_update_post( array( 'ID' => $published_id, 'post_status' => 'publish' ) );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Free Resources integration check(s) failed.', count( $failures ) ) );
}
WP_CLI::success( 'All Free Resources integration checks passed.' );
