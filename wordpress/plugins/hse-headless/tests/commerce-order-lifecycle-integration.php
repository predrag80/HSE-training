<?php
/**
 * Payment-driven order lifecycle checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Commerce\CommerceOrderLifecycle;
use HSETraining\Headless\Commerce\CommerceProductSync;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$failures = array();

/** Record a failed assertion while allowing all lifecycle checks to run. */
function hse_commerce_order_lifecycle_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

/** Minimal product collaborator for lifecycle checks. */
final class HseCommerceLifecycleTestProduct {
	private $id;
	private $virtual;

	public function __construct( $id, $virtual ) {
		$this->id      = $id;
		$this->virtual = $virtual;
	}

	public function get_id() {
		return $this->id;
	}

	public function is_virtual() {
		return $this->virtual;
	}
}

/** Minimal order-item collaborator for lifecycle checks. */
final class HseCommerceLifecycleTestItem {
	private $product;

	public function __construct( $product ) {
		$this->product = $product;
	}

	public function get_product() {
		return $this->product;
	}
}

/** Minimal order collaborator for lifecycle checks. */
final class HseCommerceLifecycleTestOrder {
	private $gateway;
	private $items;
	private $status;
	private $paid;
	private $status_note = '';

	public function __construct( $gateway, array $items, $status = 'processing', $paid = true ) {
		$this->gateway = $gateway;
		$this->items   = $items;
		$this->status  = $status;
		$this->paid    = $paid;
	}

	public function get_payment_method() {
		return $this->gateway;
	}

	public function get_items( $type = '' ) {
		unset( $type );
		return $this->items;
	}

	public function get_status() {
		return $this->status;
	}

	public function is_paid() {
		return $this->paid;
	}

	public function update_status( $status, $note = '', $manual = false ) {
		unset( $manual );
		$this->status      = $status;
		$this->status_note = $note;
	}

	public function get_status_note() {
		return $this->status_note;
	}
}

$product_id = wp_insert_post(
	array(
		'post_type'   => 'product',
		'post_status' => 'draft',
		'post_title'  => 'Temporary lifecycle product',
	),
	true
);

try {
	if ( is_wp_error( $product_id ) ) {
		throw new RuntimeException( $product_id->get_error_message() );
	}

	update_post_meta( $product_id, CommerceProductSync::SYNCED_META, '1' );
	$course_item = new HseCommerceLifecycleTestItem( new HseCommerceLifecycleTestProduct( $product_id, true ) );

	hse_commerce_order_lifecycle_test_assert(
		'completed' === CommerceOrderLifecycle::payment_complete_status(
			'processing',
			1,
			new HseCommerceLifecycleTestOrder( CommerceOrderLifecycle::CARD_GATEWAY, array( $course_item ) )
		),
		'A verified RaiAccept payment containing only a synchronized virtual Course completes immediately.'
	);
	hse_commerce_order_lifecycle_test_assert(
		'on-hold' === CommerceOrderLifecycle::payment_complete_status(
			'on-hold',
			2,
			new HseCommerceLifecycleTestOrder( 'bacs', array( $course_item ) )
		),
		'Direct bank transfer remains on hold until the merchant confirms receipt of funds.'
	);
	hse_commerce_order_lifecycle_test_assert(
		'processing' === CommerceOrderLifecycle::payment_complete_status(
			'processing',
			3,
			new HseCommerceLifecycleTestOrder(
				CommerceOrderLifecycle::CARD_GATEWAY,
				array( new HseCommerceLifecycleTestItem( new HseCommerceLifecycleTestProduct( $product_id, false ) ) )
			)
		),
		'A non-virtual product is not completed by the Course-only rule.'
	);
	hse_commerce_order_lifecycle_test_assert(
		'processing' === CommerceOrderLifecycle::payment_complete_status(
			'processing',
			4,
			new HseCommerceLifecycleTestOrder( CommerceOrderLifecycle::CARD_GATEWAY, array() )
		),
		'An empty order is not completed by the Course-only rule.'
	);

	$direct_processing_order = new HseCommerceLifecycleTestOrder(
		CommerceOrderLifecycle::CARD_GATEWAY,
		array( $course_item )
	);
	CommerceOrderLifecycle::complete_processing_card_order( 5, $direct_processing_order );
	hse_commerce_order_lifecycle_test_assert(
		'completed' === $direct_processing_order->get_status()
			&& false !== strpos( $direct_processing_order->get_status_note(), 'RaiAccept' ),
		'A verified RaiAccept order set directly to processing is completed for immediate digital delivery.'
	);

	$unpaid_processing_order = new HseCommerceLifecycleTestOrder(
		CommerceOrderLifecycle::CARD_GATEWAY,
		array( $course_item ),
		'processing',
		false
	);
	CommerceOrderLifecycle::complete_processing_card_order( 6, $unpaid_processing_order );
	hse_commerce_order_lifecycle_test_assert(
		'processing' === $unpaid_processing_order->get_status(),
		'An unpaid RaiAccept order is never auto-completed.'
	);

	$bank_processing_order = new HseCommerceLifecycleTestOrder( 'bacs', array( $course_item ) );
	CommerceOrderLifecycle::complete_processing_card_order( 7, $bank_processing_order );
	hse_commerce_order_lifecycle_test_assert(
		'processing' === $bank_processing_order->get_status(),
		'A bank-transfer order is never auto-completed by the card-payment fallback.'
	);
} catch ( Throwable $error ) {
	hse_commerce_order_lifecycle_test_assert( false, 'Order lifecycle test failed: ' . $error->getMessage() );
} finally {
	if ( $product_id && ! is_wp_error( $product_id ) ) {
		wp_delete_post( $product_id, true );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d order lifecycle check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Commerce order lifecycle checks passed.' );
