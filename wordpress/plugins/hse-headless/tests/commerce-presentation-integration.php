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
	public $billing_company = '';

	public function update_meta_data( $key, $value ) {
		$this->meta[ $key ] = $value;
	}

	public function get_meta( $key ) {
		return $this->meta[ $key ] ?? '';
	}

	public function delete_meta_data( $key ) {
		unset( $this->meta[ $key ] );
	}

	public function set_billing_company( $value ) {
		$this->billing_company = $value;
	}

	public function get_billing_company() {
		return $this->billing_company;
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
	'individual' === CommercePresentation::sanitize_buyer_type( 'unexpected' )
		&& 'company' === CommercePresentation::sanitize_buyer_type( 'company' ),
	'Checkout buyer type is restricted to individual or company.'
);
hse_commerce_presentation_test_assert(
	array() === CommercePresentation::validate_buyer_data( array( 'billing_customer_type' => 'individual' ) ),
	'Individual checkout does not require company identifiers.'
);
$missing_company = CommercePresentation::validate_buyer_data(
	array(
		'billing_customer_type' => 'company',
		'billing_country'       => 'RS',
	)
);
hse_commerce_presentation_test_assert(
	3 === count( $missing_company ),
	'Legal-entity checkout requires company name, PIB, and registration number.'
);
$invalid_serbian_company = CommercePresentation::validate_buyer_data(
	array(
		'billing_customer_type'        => 'company',
		'billing_company'              => 'Primer DOO',
		'billing_country'              => 'RS',
		'billing_pib'                  => '123',
		'billing_registration_number'  => '456',
	)
);
hse_commerce_presentation_test_assert(
	isset( $invalid_serbian_company['billing_pib_invalid'], $invalid_serbian_company['billing_registration_number_invalid'] ),
	'Serbian legal entities require a nine-digit PIB and eight-digit registration number.'
);
$valid_company_data = array(
	'billing_customer_type'        => 'company',
	'billing_company'              => 'Primer DOO',
	'billing_country'              => 'RS',
	'billing_pib'                  => '123456789',
	'billing_registration_number'  => '12345678',
);
hse_commerce_presentation_test_assert(
	array() === CommercePresentation::validate_buyer_data( $valid_company_data ),
	'Complete Serbian legal-entity data passes validation.'
);
$buyer_order = new HseCommercePresentationTestOrder();
CommercePresentation::save_buyer_fields( $buyer_order, $valid_company_data );
$buyer_details = CommercePresentation::order_buyer_details( $buyer_order, 'en' );
hse_commerce_presentation_test_assert(
	'Legal entity / Company' === ( $buyer_details['buyer_type']['value'] ?? '' )
		&& '123456789' === ( $buyer_details['company_tax_id']['value'] ?? '' )
		&& '12345678' === ( $buyer_details['company_registration_number']['value'] ?? '' ),
	'Legal-entity identity is normalized for order and email presentation.'
);
$legacy_company_order                  = new HseCommercePresentationTestOrder();
$legacy_company_order->billing_company = 'Existing Company DOO';
$legacy_company_details                = CommercePresentation::order_buyer_details( $legacy_company_order, 'en' );
hse_commerce_presentation_test_assert(
	'Legal entity / Company' === ( $legacy_company_details['buyer_type']['value'] ?? '' ),
	'Existing orders with a company name remain identified as legal entities.'
);
CommercePresentation::save_buyer_fields( $buyer_order, array( 'billing_customer_type' => 'individual' ) );
hse_commerce_presentation_test_assert(
	'' === $buyer_order->billing_company
		&& ! isset( $buyer_order->meta[ CommercePresentation::COMPANY_TAX_ID_META ] )
		&& ! isset( $buyer_order->meta[ CommercePresentation::COMPANY_REG_NUMBER_META ] ),
	'Individual checkout clears stale company-only order data.'
);

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
	$email_order->update_meta_data( CommercePresentation::BUYER_TYPE_META, 'company' );
	$email_order->update_meta_data( CommercePresentation::COMPANY_TAX_ID_META, '123456789' );
	$email_order->update_meta_data( CommercePresentation::COMPANY_REG_NUMBER_META, '12345678' );
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
			false !== strpos( $receipt_html, 'PLAĆANJE PRIMLJENO' )
				&& false !== strpos( $receipt_html, 'Ukupno plaćeno' )
				&& false !== strpos( $receipt_html, '123456789' )
				&& false !== strpos( $receipt_html, '12345678' ),
			'The Serbian completed receipt renders payment and legal-entity details.'
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
