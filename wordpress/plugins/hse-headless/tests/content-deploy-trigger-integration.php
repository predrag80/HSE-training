<?php
/**
 * Content-triggered production deploy checks for execution with `wp eval-file`.
 *
 * GitHub is never contacted: the HTTP request is intercepted before transport.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Infrastructure\ContentDeployTrigger;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

// wp eval-file includes this file inside a method, so bind the global the
// assertion helper records into; otherwise failures never fail the command.
global $failures;
$failures = array();

/** Record a failed assertion while allowing the remaining checks to run. */
function hse_content_deploy_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

hse_content_deploy_test_assert(
	false !== has_action( ContentDeployTrigger::CRON_HOOK, array( ContentDeployTrigger::class, 'dispatch' ) ),
	'The content deploy cron callback is registered.'
);
hse_content_deploy_test_assert(
	ContentDeployTrigger::is_public_content_post_type( 'hero_slide' )
		&& ContentDeployTrigger::is_public_content_post_type( 'course' )
		&& ! ContentDeployTrigger::is_public_content_post_type( 'attachment' )
		&& ! ContentDeployTrigger::is_public_content_post_type( 'shop_order' ),
	'Only editorial post types trigger public rebuilds through post saves.'
);
hse_content_deploy_test_assert(
	ContentDeployTrigger::should_queue_post_save( new WP_Post( (object) array( 'post_type' => 'hse_resource', 'post_status' => 'publish' ) ) )
		&& ! ContentDeployTrigger::should_queue_post_save( new WP_Post( (object) array( 'post_type' => 'hse_resource', 'post_status' => 'draft' ) ) ),
	'Draft editorial saves do not queue public rebuilds, while published content does.'
);
hse_content_deploy_test_assert(
	100 === has_action( 'added_option', array( ContentDeployTrigger::class, 'handle_option_add' ) )
		&& 100 === has_action( 'edit_attachment', array( ContentDeployTrigger::class, 'handle_attachment_edit' ) )
		&& false === has_action( 'add_attachment', array( ContentDeployTrigger::class, 'handle_attachment_edit' ) ),
	'First settings saves and Media Library edits queue rebuilds, while new uploads do not.'
);
hse_content_deploy_test_assert(
	ContentDeployTrigger::is_public_content_option( 'hse_company_page_en' )
		&& ContentDeployTrigger::is_public_content_option( 'hse_course_page_nebosh_sr' )
		&& ContentDeployTrigger::is_public_content_option( 'hse_legal_page_privacy_en' )
		&& ! ContentDeployTrigger::is_public_content_option( 'woocommerce_gateway_order' ),
	'Only public editorial settings trigger public rebuilds.'
);

$captured_request = array();
$configuration_filter = static function () {
	return array(
		'enabled'    => true,
		'token'      => 'test-token-not-a-secret',
		'repository' => 'predrag80/HSE-training',
		'workflow'   => 'deploy-production.yml',
		'ref'        => 'production',
	);
};
$http_filter = static function ( $preempt, $args, $url ) use ( &$captured_request ) {
	$captured_request = array( 'args' => $args, 'url' => $url );
	return array(
		'headers'  => array(),
		'body'     => '',
		'response' => array( 'code' => 204, 'message' => 'No Content' ),
		'cookies'  => array(),
		'filename' => null,
	);
};

/** Allow another queue in this request and report whether a WordPress action queued a build. */
function hse_content_deploy_test_queues( callable $action ): bool {
	$queued_in_request = new ReflectionProperty( ContentDeployTrigger::class, 'queued_in_request' );
	$queued_in_request->setAccessible( true );
	$queued_in_request->setValue( null, false );
	wp_clear_scheduled_hook( ContentDeployTrigger::CRON_HOOK );

	$action();

	$queued = false !== wp_next_scheduled( ContentDeployTrigger::CRON_HOOK );
	wp_clear_scheduled_hook( ContentDeployTrigger::CRON_HOOK );
	return $queued;
}

add_filter( 'hse_content_deploy_configuration', $configuration_filter );
wp_clear_scheduled_hook( ContentDeployTrigger::CRON_HOOK );
ContentDeployTrigger::queue();
hse_content_deploy_test_assert(
	false !== wp_next_scheduled( ContentDeployTrigger::CRON_HOOK ),
	'An editorial change queues one delayed production build.'
);
wp_clear_scheduled_hook( ContentDeployTrigger::CRON_HOOK );

$probe_option = 'hse_legal_page_deploy_probe_en';
delete_option( $probe_option );
hse_content_deploy_test_assert(
	hse_content_deploy_test_queues(
		static function () use ( $probe_option ) {
			update_option( $probe_option, array( 'title' => 'Probe' ), false );
		}
	),
	'The first save of a public settings document queues a production build.'
);
delete_option( $probe_option );

$probe_attachment_id = 0;
hse_content_deploy_test_assert(
	! hse_content_deploy_test_queues(
		static function () use ( &$probe_attachment_id ) {
			$probe_attachment_id = wp_insert_attachment(
				array(
					'post_title'     => 'HSE deploy probe',
					'post_mime_type' => 'image/png',
					'post_status'    => 'inherit',
				)
			);
		}
	),
	'A new Media Library upload alone does not queue a production build.'
);
hse_content_deploy_test_assert(
	$probe_attachment_id > 0 && hse_content_deploy_test_queues(
		static function () use ( $probe_attachment_id ) {
			wp_update_post(
				array(
					'ID'         => $probe_attachment_id,
					'post_title' => 'HSE deploy probe edited',
				)
			);
		}
	),
	'Editing an existing Media Library item queues a production build.'
);
if ( $probe_attachment_id > 0 ) {
	wp_delete_attachment( $probe_attachment_id, true );
}

add_filter( 'pre_http_request', $http_filter, 10, 3 );
ContentDeployTrigger::dispatch();
remove_filter( 'pre_http_request', $http_filter, 10 );
remove_filter( 'hse_content_deploy_configuration', $configuration_filter );

$body = json_decode( (string) ( $captured_request['args']['body'] ?? '' ), true );
hse_content_deploy_test_assert(
	'https://api.github.com/repos/predrag80/HSE-training/actions/workflows/deploy-production.yml/dispatches' === ( $captured_request['url'] ?? '' ),
	'The dispatch target is the fixed production workflow.'
);
hse_content_deploy_test_assert(
	'production' === ( $body['ref'] ?? '' ),
	'The workflow dispatch always builds the production branch.'
);
hse_content_deploy_test_assert(
	0 === strpos( (string) ( $captured_request['args']['headers']['Authorization'] ?? '' ), 'Bearer ' ),
	'The server-owned credential is sent only as the GitHub authorization header.'
);

delete_option( 'hse_content_deploy_status' );
delete_option( 'hse_content_deploy_retry_count' );

if ( $failures ) {
	WP_CLI::error( sprintf( '%d content deploy integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All content deploy integration checks passed. GitHub was not contacted.' );
