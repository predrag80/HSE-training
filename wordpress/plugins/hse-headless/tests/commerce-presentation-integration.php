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
	false === CommerceCustomerEmail::disable_processing_order_email( true ),
	'Customer processing emails are disabled so the successful flow sends only the completed receipt.'
);
hse_commerce_presentation_test_assert(
	CommerceCustomerEmail::DEFAULT_ADMIN_EMAIL === CommerceCustomerEmail::new_order_recipient( 'admin@hsetraining.test' ),
	'Merchant new-order notifications replace imported placeholder recipients.'
);
hse_commerce_presentation_test_assert(
	'RSD' === CommercePresentation::localize_currency_symbol( 'рсд', 'RSD' ),
	'RSD prices use an unambiguous Latin currency code.'
);

$email_order = wc_create_order();
if ( is_wp_error( $email_order ) ) {
	hse_commerce_presentation_test_assert( false, 'A temporary order can be created for email-evidence checks.' );
} else {
	$email_order->update_meta_data( CommerceLocale::ORDER_META, 'sr' );
	$email_order->save();
	hse_commerce_presentation_test_assert(
		'Potvrda o plaćanju za porudžbinu #' . $email_order->get_order_number() === CommerceCustomerEmail::completed_order_subject( 'Fallback', $email_order ),
		'Completed receipt subject follows the Serbian order locale.'
	);
	hse_commerce_presentation_test_assert(
		'Potvrda o plaćanju' === CommerceCustomerEmail::completed_order_heading( 'Fallback', $email_order ),
		'Completed receipt heading follows the Serbian order locale.'
	);
	hse_commerce_presentation_test_assert(
		'Ukupno plaćeno' === ( CommerceCustomerEmail::receipt_copy( 'sr' )['total_paid'] ?? '' ),
		'Serbian receipt labels are available without relying on the administrator locale.'
	);
	$receipt_template = CommerceCustomerEmail::locate_completed_order_template(
		'/tmp/fallback.php',
		'emails/customer-completed-order.php'
	);
	hse_commerce_presentation_test_assert(
		is_readable( $receipt_template ) && false !== strpos( $receipt_template, 'hse-headless/templates/emails/customer-completed-order.php' ),
		'The plugin-owned completed receipt template is selected.'
	);
	$completed_email = WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order'] ?? null;
	if ( $completed_email ) {
		$receipt_html = wc_get_template_html(
			'emails/customer-completed-order.php',
			array(
				'order'              => $email_order,
				'email_heading'      => CommerceCustomerEmail::completed_order_heading( '', $email_order ),
				'additional_content' => '',
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $completed_email,
			)
		);
		hse_commerce_presentation_test_assert(
			false !== strpos( $receipt_html, 'PLAĆANJE PRIMLJENO' ) && false !== strpos( $receipt_html, 'Ukupno plaćeno' ),
			'The Serbian completed receipt renders its payment status and total labels.'
		);
	} else {
		hse_commerce_presentation_test_assert( false, 'WooCommerce completed-order email is available for receipt rendering.' );
	}

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
