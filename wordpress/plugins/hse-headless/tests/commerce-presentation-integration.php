<?php
/**
 * Checkout locale and presentation checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Commerce\CommerceLocale;
use HSETraining\Headless\Commerce\CommercePresentation;
use HSETraining\Headless\Commerce\CommerceCustomerEmail;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$failures = array();

/** Record a failed assertion while allowing all checks to run. */
function hse_commerce_presentation_test_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
}

/** Minimal order collaborator for locale persistence checks. */
final class HseCommercePresentationTestOrder {
	public $meta = array();

	public function update_meta_data( $key, $value ) {
		$this->meta[ $key ] = $value;
	}
}

/** Minimal order collaborator for customer-facing payment-state copy checks. */
final class HseCommercePresentationTestStatusOrder {
	private $status;

	public function __construct( $status ) {
		$this->status = $status;
	}

	public function has_status( $statuses ) {
		return in_array( $this->status, (array) $statuses, true );
	}
}

hse_commerce_presentation_test_assert( 'en' === CommerceLocale::sanitize( 'de' ), 'Unsupported checkout locales resolve to English.' );
hse_commerce_presentation_test_assert( 'sr' === CommerceLocale::sanitize( 'SR' ), 'Serbian checkout locale is normalized.' );

CommerceLocale::capture( 'sr' );
hse_commerce_presentation_test_assert( 'sr' === CommerceLocale::current(), 'Captured Serbian locale becomes current.' );
hse_commerce_presentation_test_assert( 'Podaci o kupcu' === CommercePresentation::copy( 'billing_details' ), 'Serbian checkout copy is available.' );
hse_commerce_presentation_test_assert(
	false !== strpos( CommercePresentation::public_url( '/privacy-policy/' ), '/sr/privacy-policy/' ),
	'Serbian legal links preserve the public locale.'
);

$order = new HseCommercePresentationTestOrder();
CommerceLocale::persist_order_locale( $order );
hse_commerce_presentation_test_assert(
	'sr' === ( $order->meta[ CommerceLocale::ORDER_META ] ?? null ),
	'Checkout locale is attached to the order.'
);

CommerceLocale::capture( 'en' );
hse_commerce_presentation_test_assert( 'Billing details' === CommercePresentation::copy( 'billing_details' ), 'English checkout copy is available.' );
hse_commerce_presentation_test_assert(
	false === strpos( CommercePresentation::public_url( '/terms-and-conditions/' ), '/sr/' ),
	'English legal links remain unprefixed.'
);
hse_commerce_presentation_test_assert(
	'Thank you. Your payment has been confirmed.' === CommercePresentation::order_received_text( '', new HseCommercePresentationTestStatusOrder( 'processing' ) ),
	'English paid confirmation copy is explicit.'
);
hse_commerce_presentation_test_assert(
	'Thank you. Your order has been received and payment confirmation is pending.' === CommercePresentation::order_received_text( '', new HseCommercePresentationTestStatusOrder( 'pending' ) ),
	'English pending confirmation copy does not claim payment success.'
);
hse_commerce_presentation_test_assert(
	'Your payment was not completed.' === CommercePresentation::order_received_text( '', new HseCommercePresentationTestStatusOrder( 'failed' ) ),
	'English failed confirmation copy is explicit.'
);

CommerceLocale::capture( 'sr' );
hse_commerce_presentation_test_assert(
	'Hvala. Vaše plaćanje je potvrđeno.' === CommercePresentation::order_received_text( '', new HseCommercePresentationTestStatusOrder( 'completed' ) ),
	'Serbian paid confirmation copy is explicit.'
);
hse_commerce_presentation_test_assert(
	'Hvala. Porudžbina je primljena i čeka se potvrda plaćanja.' === CommercePresentation::order_received_text( '', new HseCommercePresentationTestStatusOrder( 'on-hold' ) ),
	'Serbian pending confirmation copy does not claim payment success.'
);
hse_commerce_presentation_test_assert(
	'Plaćanje nije završeno.' === CommercePresentation::order_received_text( '', new HseCommercePresentationTestStatusOrder( 'cancelled' ) ),
	'Serbian cancelled confirmation copy is explicit.'
);

CommerceLocale::capture( 'en' );

hse_commerce_presentation_test_assert(
	true === CommerceCustomerEmail::enable_cancelled_order_email( false ),
	'Cancelled payment outcomes enable a customer email notification.'
);
hse_commerce_presentation_test_assert(
	'RSD' === CommercePresentation::localize_currency_symbol( 'рсд', 'RSD' ),
	'RSD prices use an unambiguous Latin currency code.'
);

$email_order = wc_create_order();
if ( is_wp_error( $email_order ) ) {
	hse_commerce_presentation_test_assert( false, 'A temporary order can be created for email-evidence checks.' );
} else {
	$email = (object) array( 'object' => $email_order );
	CommerceCustomerEmail::record_email_delivery( true, 'customer_failed_order', $email );
	$email_order = wc_get_order( $email_order->get_id() );
	hse_commerce_presentation_test_assert(
		'' !== (string) $email_order->get_meta( '_hse_customer_email_customer_failed_order_sent_at', true ),
		'Successfully delivered payment-status email evidence is retained on the order.'
	);
	$email_order->delete( true );
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Commerce presentation integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Commerce presentation integration checks passed.' );
