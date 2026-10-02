<?php
/**
 * Checkout locale and presentation checks for execution with `wp eval-file`.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;

use HSETraining\Headless\Commerce\CommerceLocale;
use HSETraining\Headless\Commerce\CommerceCheckoutSource;
use HSETraining\Headless\Commerce\CommerceAdminCompatibility;
use HSETraining\Headless\Commerce\CommerceBokaPosEmailCompatibility;
use HSETraining\Headless\Commerce\CommerceBankTransfer;
use HSETraining\Headless\Commerce\CommerceOrderNumber;
use HSETraining\Headless\Commerce\CommercePresentation;
use HSETraining\Headless\Commerce\CommerceCustomerEmail;
use HSETraining\Headless\Commerce\CommerceRaiAcceptRetryCompatibility;
use HSETraining\Headless\Commerce\CommerceRaiAcceptRetryGateway;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$failures = array();

hse_commerce_presentation_test_assert(
	CommerceAdminCompatibility::is_order_editor_request(
		'woocommerce_page_wc-orders',
		'woocommerce_page_wc-orders',
		'wc-orders',
		'edit'
	)
		&& CommerceAdminCompatibility::is_order_editor_request( 'post.php', 'shop_order', '', '' )
		&& ! CommerceAdminCompatibility::is_order_editor_request( 'post.php', 'post', '', '' ),
	'The BokaPOS compatibility asset is limited to HPOS and legacy order editors.'
);
hse_commerce_presentation_test_assert(
	0 === has_action( 'wp_ajax_woocommerce_refund_line_items', array( CommerceAdminCompatibility::class, 'validate_fiscal_refund_lines' ) )
		&& CommerceAdminCompatibility::has_refund_line_quantity( array( 123 => '1' ) )
		&& ! CommerceAdminCompatibility::has_refund_line_quantity( array() )
		&& ! CommerceAdminCompatibility::has_refund_line_quantity( array( 123 => '0' ) ),
	'The fiscal-refund guard runs before WooCommerce and requires a positive refunded item quantity.'
);
hse_commerce_presentation_test_assert(
	false !== has_action( 'bokapos_order_fiscalized', array( CommerceBokaPosEmailCompatibility::class, 'prepare_receipt_email' ) )
		&& CommerceBokaPosEmailCompatibility::ensure_wordpress_file_helpers()
		&& function_exists( 'wp_tempnam' ),
	'The update-safe BokaPOS e-mail compatibility layer prepares PDF helpers before provider delivery.'
);
hse_commerce_presentation_test_assert(
	false !== has_action( 'woocommerce_before_pay_action', array( CommerceRaiAcceptRetryCompatibility::class, 'prepare_fresh_payment_attempt' ) ),
	'The update-safe RaiAccept retry compatibility runs immediately before an order-pay attempt.'
);
hse_commerce_presentation_test_assert(
	array( CommerceRaiAcceptRetryGateway::class ) === CommerceRaiAcceptRetryCompatibility::replace_gateway_class( array( 'RaiAccept_Gateway' ) )
		&& class_exists( CommerceRaiAcceptRetryGateway::class, false )
		&& is_subclass_of( CommerceRaiAcceptRetryGateway::class, 'RaiAccept_Gateway' ),
	'The official RaiAccept checkout gateway is replaced by an update-safe child that preserves the provider payment flow.'
);

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

/** Minimal order collaborator for public order-number display checks. */
final class HseCommercePresentationTestNumberOrder {
	private $number;

	public function __construct( $number ) {
		$this->number = $number;
	}

	public function get_meta( $key, $single = false ) {
		unset( $single );
		return CommerceOrderNumber::ORDER_META === $key ? $this->number : '';
	}

	public function update_meta_data( $key, $value ) {
		unset( $key, $value );
	}

	public function save_meta_data() {}
}

hse_commerce_presentation_test_assert( 'en' === CommerceLocale::sanitize( 'de' ), 'Unsupported checkout locales resolve to English.' );
hse_commerce_presentation_test_assert( 'sr' === CommerceLocale::sanitize( 'SR' ), 'Serbian checkout locale is normalized.' );
hse_commerce_presentation_test_assert(
	'dev' === CommerceCheckoutSource::sanitize( 'DEV' )
		&& 'production' === CommerceCheckoutSource::sanitize( 'PRODUCTION' )
		&& 'staging' === CommerceCheckoutSource::sanitize( 'unexpected' ),
	'Checkout source accepts only dev, staging and production.'
);
hse_commerce_presentation_test_assert(
	-11 === has_action( 'template_redirect', array( CommerceCheckoutSource::class, 'bootstrap_checkout_source' ) )
		&& 19 === has_action( 'wp_loaded', array( CommerceCheckoutSource::class, 'bootstrap_checkout_ajax_source' ) ),
	'Checkout source is restored before HTML and AJAX checkout rendering.'
);
$source_order = new HseCommercePresentationTestOrder();
$source_order->update_meta_data( CommerceCheckoutSource::ORDER_META, 'dev' );
hse_commerce_presentation_test_assert(
	'dev' === CommerceCheckoutSource::for_order( $source_order ),
	'Checkout source is read from immutable order metadata.'
);
hse_commerce_presentation_test_assert(
	'HSE-000001' === CommerceOrderNumber::format( 1 )
		&& 'HSE-1000000' === CommerceOrderNumber::format( 1000000 ),
	'Public order numbers use the stable HSE prefix and at least six digits.'
);
hse_commerce_presentation_test_assert(
	'HSE-000042' === CommerceOrderNumber::filter( '1436', new HseCommercePresentationTestNumberOrder( 'HSE-000042' ) )
		&& '1436' === CommerceOrderNumber::filter( '1436', new HseCommercePresentationTestNumberOrder( 'invalid' ) ),
	'Only a valid stored HSE order number replaces the internal WooCommerce ID.'
);

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
	'Retry your payment.' === CommercePresentation::copy( 'retry_title' )
		&& 'Continue to secure payment' === CommercePresentation::copy( 'retry_action' )
		&& 'Review your order' === CommercePresentation::copy( 'retry_review' )
		&& 'Choose payment method' === CommercePresentation::copy( 'retry_payment' ),
	'English order-pay page explains the secure retry step.'
);
hse_commerce_presentation_test_assert(
	'Review and confirm' === CommercePresentation::copy( 'checkout_confirmations' )
		&& 'Immediate course access' === CommercePresentation::copy( 'digital_consent_title' ),
	'English checkout confirmations have clear visual headings.'
);
hse_commerce_presentation_test_assert(
	'Direct bank transfer' === CommercePresentation::copy( 'bank_transfer_title' )
		&& 'Place order with obligation to pay' === CommercePresentation::copy( 'place_order_bank' )
		&& 'Order and pay' === CommercePresentation::copy( 'place_order_card' ),
	'English bank-transfer checkout copy is available.'
);
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
hse_commerce_presentation_test_assert(
	'paid' === CommercePresentation::confirmation_state( new HseCommercePresentationTestStatusOrder( 'completed' ) )
		&& 'pending' === CommercePresentation::confirmation_state( new HseCommercePresentationTestStatusOrder( 'on-hold' ) )
		&& 'failed' === CommercePresentation::confirmation_state( new HseCommercePresentationTestStatusOrder( 'cancelled' ) ),
	'Order confirmation state matches the underlying WooCommerce payment state.'
);
hse_commerce_presentation_test_assert(
	'Payment confirmed.' === CommercePresentation::copy( 'confirmation_title_paid' )
		&& 'Check your inbox' === CommercePresentation::copy( 'next_step_paid_1_title' ),
	'English confirmation page uses state-aware headings and actionable next steps.'
);

CommerceLocale::capture( 'sr' );
hse_commerce_presentation_test_assert(
	'Direktna uplata na račun' === CommercePresentation::copy( 'bank_transfer_title' )
		&& 'Potvrdite porudžbinu sa obavezom plaćanja' === CommercePresentation::copy( 'place_order_bank' )
		&& 'Poručite i platite' === CommercePresentation::copy( 'place_order_card' ),
	'Serbian bank-transfer checkout copy is localized.'
);
hse_commerce_presentation_test_assert(
	'Ponovite plaćanje.' === CommercePresentation::copy( 'retry_title' )
		&& 'Nastavite na bezbedno plaćanje' === CommercePresentation::copy( 'retry_action' )
		&& 'Proverite porudžbinu' === CommercePresentation::copy( 'retry_review' )
		&& 'Izaberite način plaćanja' === CommercePresentation::copy( 'retry_payment' ),
	'Serbian order-pay page explains the secure retry step.'
);
hse_commerce_presentation_test_assert(
	'Proverite i potvrdite' === CommercePresentation::copy( 'checkout_confirmations' )
		&& 'Trenutni pristup kursu' === CommercePresentation::copy( 'digital_consent_title' ),
	'Serbian checkout confirmations have clear visual headings.'
);

$_POST['hse_digital_delivery_consent'] = '1';
$consent_order = new HseCommercePresentationTestOrder();
CommercePresentation::save_legal_consents( $consent_order, array() );
unset( $_POST['hse_digital_delivery_consent'] );
hse_commerce_presentation_test_assert(
	'yes' === ( $consent_order->meta[ CommercePresentation::DIGITAL_CONSENT_META ] ?? '' )
		&& CommercePresentation::DIGITAL_CONSENT_VERSION === ( $consent_order->meta[ CommercePresentation::DIGITAL_CONSENT_VERSION_META ] ?? '' )
		&& '' !== ( $consent_order->meta[ CommercePresentation::DIGITAL_CONSENT_AT_META ] ?? '' ),
	'Immediate digital-delivery consent is stored with its UTC time and wording version.'
);
$bank_fields = array(
	'bank_name'      => array( 'label' => 'Bank', 'value' => 'Example bank' ),
	'account_number' => array( 'label' => 'Account number', 'value' => '123' ),
	'sort_code'      => array( 'label' => 'Sort code', 'value' => '456' ),
	'iban'           => array( 'label' => 'IBAN', 'value' => 'RS00' ),
	'bic'            => array( 'label' => 'BIC', 'value' => 'EXAMPLE' ),
);
$domestic_bank_fields = CommercePresentation::bacs_account_fields_for_country( $bank_fields, 'RS', 'sr' );
hse_commerce_presentation_test_assert(
	isset( $domestic_bank_fields['bank_name'], $domestic_bank_fields['account_number'] )
		&& ! isset( $domestic_bank_fields['iban'], $domestic_bank_fields['bic'], $domestic_bank_fields['sort_code'] )
		&& 'Broj računa' === $domestic_bank_fields['account_number']['label'],
	'Domestic bank-transfer instructions show the local account number without IBAN or SWIFT/BIC.'
);
$foreign_bank_fields = CommercePresentation::bacs_account_fields_for_country( $bank_fields, 'DE', 'en' );
hse_commerce_presentation_test_assert(
	isset( $foreign_bank_fields['bank_name'], $foreign_bank_fields['iban'], $foreign_bank_fields['bic'] )
		&& ! isset( $foreign_bank_fields['account_number'], $foreign_bank_fields['sort_code'] )
		&& 'SWIFT/BIC' === $foreign_bank_fields['bic']['label'],
	'International bank-transfer instructions show IBAN and SWIFT/BIC without the domestic account number.'
);
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
	CommercePresentation::is_valid_serbian_pib( '108205616' )
		&& ! CommercePresentation::is_valid_serbian_pib( '123456789' )
		&& ! CommercePresentation::is_valid_serbian_pib( '10820561' ),
	'Serbian PIB validation checks both the nine-digit structure and the check digit.'
);
hse_commerce_presentation_test_assert(
	array() === CommercePresentation::validate_buyer_data( array( 'billing_customer_type' => 'individual' ) ),
	'Individual checkout does not require company identifiers.'
);
hse_commerce_presentation_test_assert(
	'Pronađeni su sledeći problemi:' === CommercePresentation::localized_validation_text( 'The following problems were found:', '', 'sr' )
		&& 'Polje %s je obavezno.' === CommercePresentation::localized_validation_text( '%s is a required field.', '', 'sr' )
		&& '%s' === CommercePresentation::localized_validation_text( 'Billing %s', 'checkout-validation', 'sr' ),
	'Serbian checkout validation summary, required notice, and billing prefix are localized.'
);
hse_commerce_presentation_test_assert(
	'%s nije ispravna email adresa.' === CommercePresentation::localized_validation_text( '%s is not a valid email address.', '', 'sr' )
		&& '%s nije ispravan broj telefona.' === CommercePresentation::localized_validation_text( '%s is not a valid phone number.', '', 'sr' )
		&& '%s nije ispravan poštanski broj.' === CommercePresentation::localized_validation_text( '%s is not a valid postcode / ZIP.', '', 'sr' ),
	'Serbian email, phone, and postcode validation messages are localized.'
);
$missing_company = CommercePresentation::validate_buyer_data(
	array(
		'billing_customer_type' => 'company',
		'billing_country'       => 'RS',
	)
);
hse_commerce_presentation_test_assert(
	2 === count( $missing_company )
		&& isset( $missing_company['billing_company_required'], $missing_company['billing_pib_required'] )
		&& ! isset( $missing_company['billing_registration_number_required'] ),
	'Legal-entity checkout requires company name and PIB while keeping the registration number optional.'
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
	'billing_pib'                  => '108205616',
);
hse_commerce_presentation_test_assert(
	array() === CommercePresentation::validate_buyer_data( $valid_company_data ),
	'A Serbian legal entity passes validation without an optional registration number.'
);
$valid_company_data['billing_registration_number'] = '12345678';
$buyer_order = new HseCommercePresentationTestOrder();
CommercePresentation::save_buyer_fields( $buyer_order, $valid_company_data );
$buyer_details = CommercePresentation::order_buyer_details( $buyer_order, 'en' );
hse_commerce_presentation_test_assert(
	'Legal entity / Company' === ( $buyer_details['buyer_type']['value'] ?? '' )
		&& '108205616' === ( $buyer_details['company_tax_id']['value'] ?? '' )
		&& '12345678' === ( $buyer_details['company_registration_number']['value'] ?? '' ),
	'Legal-entity identity is normalized for order and email presentation.'
);
hse_commerce_presentation_test_assert(
	! isset( $buyer_order->meta['_bokapos_buyer_id'] ),
	'A Serbian legal entity remains mapped through the BokaPOS 10:PIB shortcut.'
);
$foreign_company_data = array(
	'billing_customer_type'       => 'company',
	'billing_company'             => 'Example GmbH',
	'billing_country'             => 'DE',
	'billing_pib'                 => 'DE123456789',
);
hse_commerce_presentation_test_assert(
	array() === CommercePresentation::validate_buyer_data( $foreign_company_data ),
	'A foreign legal entity accepts a bounded alphanumeric Tax/VAT identifier.'
);
$foreign_buyer_order = new HseCommercePresentationTestOrder();
CommercePresentation::save_buyer_fields( $foreign_buyer_order, $foreign_company_data );
hse_commerce_presentation_test_assert(
	'DE123456789' === ( $foreign_buyer_order->meta[ CommercePresentation::COMPANY_TAX_ID_META ] ?? '' )
		&& '40:DE123456789' === ( $foreign_buyer_order->meta['_bokapos_buyer_id'] ?? '' ),
	'A foreign Tax/VAT identifier is stored with the official BokaPOS 40:TIN buyer prefix.'
);
CommercePresentation::save_buyer_fields( $foreign_buyer_order, array( 'billing_customer_type' => 'individual' ) );
hse_commerce_presentation_test_assert(
	! isset( $foreign_buyer_order->meta[ CommercePresentation::COMPANY_TAX_ID_META ] )
		&& ! isset( $foreign_buyer_order->meta[ CommercePresentation::COMPANY_REG_NUMBER_META ] )
		&& ! isset( $foreign_buyer_order->meta['_bokapos_buyer_id'] ),
	'Switching a foreign order to an individual clears every BokaPOS company identifier.'
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
	false === CommerceCustomerEmail::disable_customer_invoice_email( true ),
	'Generic customer invoice emails are disabled so an order update cannot duplicate the completed receipt.'
);
hse_commerce_presentation_test_assert(
	array( 'send_order_details_admin' => 'Resend new order notification' ) === CommerceCustomerEmail::remove_customer_order_details_action(
		array(
			'send_order_details'       => 'Send order details to customer',
			'send_order_details_admin' => 'Resend new order notification',
		)
	),
	'The customer order-details action is removed while the merchant resend action remains available.'
);
hse_commerce_presentation_test_assert(
	CommerceCustomerEmail::DEFAULT_ADMIN_EMAIL === CommerceCustomerEmail::new_order_recipient( 'legacy-admin@example.test' ),
	'Staging merchant notifications replace imported placeholder recipients.'
);
hse_commerce_presentation_test_assert(
	CommerceCustomerEmail::DEFAULT_DEV_ADMIN_EMAIL === CommerceCustomerEmail::new_order_recipient( 'legacy-admin@example.test', $source_order ),
	'Dev merchant notifications use the dedicated development recipient.'
);
$merchant_email_stub = (object) array( 'id' => 'new_order' );
hse_commerce_presentation_test_assert(
	'html' === CommerceCustomerEmail::force_branded_email_type( 'plain', $merchant_email_stub, 'plain', 'email_type' )
		&& 'text/html' === CommerceCustomerEmail::force_branded_content_type( 'text/plain', $merchant_email_stub ),
	'Merchant new-order notifications force the branded HTML body and MIME type.'
);
hse_commerce_presentation_test_assert(
	'html' === CommerceCustomerEmail::force_branded_email_type( 'plain', (object) array( 'id' => 'bokapos_receipt' ), 'plain', 'email_type' )
		&& 'text/html' === CommerceCustomerEmail::force_branded_content_type( 'text/plain', (object) array( 'id' => 'bokapos_receipt' ) ),
	'BokaPOS receipt notifications force the localized branded HTML body and MIME type.'
);
hse_commerce_presentation_test_assert(
	'html' === CommerceCustomerEmail::force_branded_email_type( 'plain', (object) array( 'id' => 'customer_on_hold_order' ), 'plain', 'email_type' )
		&& 'text/html' === CommerceCustomerEmail::force_branded_content_type( 'text/plain', (object) array( 'id' => 'customer_on_hold_order' ) ),
	'Direct-bank-transfer instructions force the branded HTML body and MIME type.'
);
hse_commerce_presentation_test_assert(
	'html' === CommerceCustomerEmail::force_branded_email_type( 'plain', (object) array( 'id' => 'customer_failed_order' ), 'plain', 'email_type' )
		&& 'text/html' === CommerceCustomerEmail::force_branded_content_type( 'text/plain', (object) array( 'id' => 'customer_failed_order' ) )
		&& 'html' === CommerceCustomerEmail::force_branded_email_type( 'plain', (object) array( 'id' => 'customer_cancelled_order' ), 'plain', 'email_type' ),
	'Failed and cancelled payment notifications force the branded HTML body and MIME type.'
);
hse_commerce_presentation_test_assert(
	'html' === CommerceCustomerEmail::force_branded_email_type( 'plain', (object) array( 'id' => 'customer_refunded_order' ), 'plain', 'email_type' )
		&& 'html' === CommerceCustomerEmail::force_branded_email_type( 'plain', (object) array( 'id' => 'customer_partially_refunded_order' ), 'plain', 'email_type' )
		&& 'text/html' === CommerceCustomerEmail::force_branded_content_type( 'text/plain', (object) array( 'id' => 'customer_refunded_order' ) ),
	'Full and partial refund notifications force the branded HTML body and MIME type.'
);
hse_commerce_presentation_test_assert(
	'HSE TRAINING D.O.O.' === ( CommerceCustomerEmail::merchant_copy()['company'] ?? '' )
		&& ! isset( CommerceCustomerEmail::merchant_copy()['view_order'] ),
	'Merchant notification copy contains the HSE identity without an unreliable admin link.'
);
hse_commerce_presentation_test_assert(
	'RSD' === CommercePresentation::localize_currency_symbol( 'рсд', 'RSD' ),
	'RSD prices use an unambiguous Latin currency code.'
);

$retry_order = wc_create_order();
if ( is_wp_error( $retry_order ) ) {
	hse_commerce_presentation_test_assert( false, 'A temporary order can be created for RaiAccept retry checks.' );
} else {
	$retry_order->set_payment_method( 'raiaccept' );
	$retry_order->set_transaction_id( 'P-001-ORD-INACTIVE-TEST' );
	$retry_order->set_status( 'failed' );
	$retry_order->update_meta_data( 'raiaccept_return_url', 'https://example.test/inactive-session' );
	$retry_order->update_meta_data( 'raiaccept_iframe_visited', '123456' );
	$retry_order->save();

	hse_commerce_presentation_test_assert(
		CommerceRaiAcceptRetryCompatibility::is_retryable_order( $retry_order, 'raiaccept' )
			&& ! CommerceRaiAcceptRetryCompatibility::is_retryable_order( $retry_order, 'bacs' ),
		'Only a failed order submitted again with RaiAccept is prepared for a fresh provider retry.'
	);
	$retry_order->set_transaction_id( '' );
	hse_commerce_presentation_test_assert(
		CommerceRaiAcceptRetryCompatibility::is_retryable_order( $retry_order, 'raiaccept' ),
		'A failed retry remains recoverable when an earlier compatibility release already cleared its provider order ID.'
	);
	$retry_order->set_transaction_id( 'P-001-ORD-INACTIVE-TEST' );

	$retry_reference = CommerceRaiAcceptRetryCompatibility::build_retry_reference( $retry_order->get_id() );
	CommerceRaiAcceptRetryCompatibility::record_retry_reference(
		$retry_order,
		'P-001-ORD-INACTIVE-TEST',
		$retry_reference
	);
	$retry_order = wc_get_order( $retry_order->get_id() );

	hse_commerce_presentation_test_assert(
		'' === (string) $retry_order->get_transaction_id()
			&& $retry_reference === (string) $retry_order->get_meta( CommerceRaiAcceptRetryCompatibility::ACTIVE_REFERENCE_META, true )
			&& array( 'P-001-ORD-INACTIVE-TEST' ) === $retry_order->get_meta( CommerceRaiAcceptRetryCompatibility::PREVIOUS_ORDER_IDS_META, true )
			&& array( $retry_reference ) === $retry_order->get_meta( CommerceRaiAcceptRetryCompatibility::RETRY_REFERENCES_META, true )
			&& 1 === preg_match( '/^' . $retry_order->get_id() . '-retry-[0-9]{14}-[a-zA-Z0-9]{1,10}$/', $retry_reference )
			&& 1 === (int) $retry_order->get_meta( CommerceRaiAcceptRetryCompatibility::RETRY_COUNT_META, true )
			&& '' !== (string) $retry_order->get_meta( CommerceRaiAcceptRetryCompatibility::LAST_RETRY_META, true )
			&& '' === (string) $retry_order->get_meta( 'raiaccept_return_url', true )
			&& '' === (string) $retry_order->get_meta( 'raiaccept_iframe_visited', true ),
		'The retry hook preserves audit evidence and prepares one unique reference for the official provider order and session calls.'
	);
	$retry_order->delete( true );
}

$email_order = wc_create_order();
if ( is_wp_error( $email_order ) ) {
	hse_commerce_presentation_test_assert( false, 'A temporary order can be created for email-evidence checks.' );
} else {
	$email_order->update_meta_data( CommerceLocale::ORDER_META, 'sr' );
	$email_order->update_meta_data( CommercePresentation::BUYER_TYPE_META, 'company' );
	$email_order->update_meta_data( CommercePresentation::COMPANY_TAX_ID_META, '123456789' );
	$email_order->update_meta_data( CommercePresentation::COMPANY_REG_NUMBER_META, '12345678' );
	$email_order->set_payment_method( 'bacs' );
	$email_order->set_billing_country( 'RS' );
	$email_order->set_billing_company( 'Test Company' );
	$email_order->set_billing_first_name( 'Test' );
	$email_order->set_billing_last_name( 'Buyer' );
	$email_order->save();
	$email_order = wc_get_order( $email_order->get_id() );
	hse_commerce_presentation_test_assert(
		1 === preg_match( '/^HSE-[0-9]{6,}$/', $email_order->get_order_number() )
			&& '' !== (string) $email_order->get_meta( CommerceOrderNumber::SEQUENCE_META, true ),
		'New WooCommerce orders receive an immutable HSE public number.'
	);
	hse_commerce_presentation_test_assert(
		'Potvrda o plaćanju za porudžbinu #' . $email_order->get_order_number() === CommerceCustomerEmail::completed_order_subject( 'Fallback', $email_order ),
		'Completed receipt subject follows the Serbian order locale.'
	);
	hse_commerce_presentation_test_assert(
		'Plaćanje nije uspelo za porudžbinu #' . $email_order->get_order_number() === CommerceCustomerEmail::failed_order_subject( 'Fallback', $email_order )
			&& 'Plaćanje nije uspelo' === CommerceCustomerEmail::failed_order_heading( 'Fallback', $email_order )
			&& 'Porudžbina #' . $email_order->get_order_number() . ' je otkazana' === CommerceCustomerEmail::cancelled_order_subject( 'Fallback', $email_order )
			&& 'Porudžbina je otkazana' === CommerceCustomerEmail::cancelled_order_heading( 'Fallback', $email_order ),
		'Unsuccessful-payment subjects and headings follow the Serbian order locale.'
	);
	hse_commerce_presentation_test_assert(
		'Podaci za uplatu porudžbine #' . $email_order->get_order_number() === CommerceCustomerEmail::on_hold_order_subject( 'Fallback', $email_order )
			&& 'Podaci za uplatu' === CommerceCustomerEmail::on_hold_order_heading( 'Fallback', $email_order ),
		'Bank-transfer instructions subject and heading follow the Serbian order locale.'
	);
	$bank_data = CommerceBankTransfer::instructions_for_order(
		$email_order,
		array(
			'account_name'   => 'HSE TRAINING D.O.O.',
			'bank_name'      => 'Test Bank',
			'account_number' => '000-0000000000000-00',
			'iban'           => 'RS000000000000000000',
			'bic'            => 'TESTBIC',
		)
	);
	hse_commerce_presentation_test_assert(
		'221' === ( $bank_data['payment_code'] ?? '' )
			&& CommerceBankTransfer::DOMESTIC_ACCOUNT === ( $bank_data['account_number'] ?? '' )
			&& 'Nalog za prenos za pravno lice' === ( $bank_data['form_title'] ?? '' )
			&& CommerceBankTransfer::BENEFICIARY_NAME === ( $bank_data['recipient'] ?? '' )
			&& false !== strpos( (string) ( $bank_data['purpose'] ?? '' ), $email_order->get_order_number() ),
		'A Serbian legal-entity order receives the verified domestic account and approved transfer fields without a payment reference.'
	);
	hse_commerce_presentation_test_assert(
		array() === CommerceCustomerEmail::attach_international_bank_instructions( array(), 'customer_on_hold_order', $email_order ),
		'The domestic bank-transfer email does not attach the EUR instructions.'
	);
	$email_order->update_meta_data( CommercePresentation::BUYER_TYPE_META, 'individual' );
	$individual_bank_data = CommerceBankTransfer::instructions_for_order( $email_order, array() );
	hse_commerce_presentation_test_assert(
		'Uplatnica za fizičko lice' === ( $individual_bank_data['form_title'] ?? '' )
			&& 'Test Buyer' === ( $individual_bank_data['payer'] ?? '' ),
		'A Serbian individual receives the individual payment-slip variant.'
	);
	$email_order->update_meta_data( CommercePresentation::BUYER_TYPE_META, 'company' );
	$email_order->set_billing_country( 'SI' );
	$email_order->save();
	$foreign_bank_data = CommerceBankTransfer::instructions_for_order( $email_order, array() );
	$attachments       = CommerceCustomerEmail::attach_international_bank_instructions( array(), 'customer_on_hold_order', $email_order );
	hse_commerce_presentation_test_assert(
		CommerceBankTransfer::INTERNATIONAL_IBAN === ( $foreign_bank_data['iban'] ?? '' )
			&& CommerceBankTransfer::INTERNATIONAL_BIC === ( $foreign_bank_data['bic'] ?? '' )
			&& array( CommerceBankTransfer::euro_instructions_path() ) === $attachments,
		'Foreign bank-transfer emails use the official EUR account and attach the Raiffeisen instructions once.'
	);
	$email_order->set_billing_country( 'RS' );
	$email_order->save();
	hse_commerce_presentation_test_assert(
		'Potvrda o plaćanju' === CommerceCustomerEmail::completed_order_heading( 'Fallback', $email_order ),
		'Completed receipt heading follows the Serbian order locale.'
	);
	$refund_email_stub = (object) array( 'partial_refund' => false );
	hse_commerce_presentation_test_assert(
		'Refundacija porudžbine #' . $email_order->get_order_number() === CommerceCustomerEmail::refunded_order_subject( 'Fallback', $email_order, $refund_email_stub )
			&& 'Refundacija je izvršena' === CommerceCustomerEmail::refunded_order_heading( 'Fallback', $email_order, $refund_email_stub )
			&& 'DELIMIČNA REFUNDACIJA IZVRŠENA' === ( CommerceCustomerEmail::refund_copy( 'sr', true )['status'] ?? '' ),
		'Refund subjects, headings and body copy follow the order locale and refund scope.'
	);
	hse_commerce_presentation_test_assert(
		'Fiskalni račun za porudžbinu #' . $email_order->get_order_number() === CommerceCustomerEmail::fiscal_receipt_subject( 'Fallback', $email_order )
			&& 'Vaš fiskalni račun' === CommerceCustomerEmail::fiscal_receipt_heading( 'Fallback', $email_order ),
		'BokaPOS fiscal receipt subject and heading follow the Serbian order locale.'
	);
	hse_commerce_presentation_test_assert(
		'Nova porudžbina #' . $email_order->get_order_number() . ' - HSE Training' === CommerceCustomerEmail::merchant_order_subject( 'Fallback', $email_order )
			&& 'Nova porudžbina' === CommerceCustomerEmail::merchant_order_heading( 'Fallback', $email_order ),
		'Merchant subject and heading identify the new order.'
	);
	hse_commerce_presentation_test_assert(
		'Ukupno plaćeno' === ( CommerceCustomerEmail::receipt_copy( 'sr' )['total_paid'] ?? '' ),
		'Serbian receipt labels are available without relying on the administrator locale.'
	);
	hse_commerce_presentation_test_assert(
		'PLAĆANJE ODBIJENO' === ( CommerceCustomerEmail::unsuccessful_payment_copy( 'sr', 'failed' )['status'] ?? '' )
			&& 'ORDER CANCELLED' === ( CommerceCustomerEmail::unsuccessful_payment_copy( 'en', 'cancelled' )['status'] ?? '' ),
		'Failed and cancelled payment guidance is available in both checkout languages.'
	);
	hse_commerce_presentation_test_assert(
		'Proverite račun kod Poreske uprave' === ( CommerceCustomerEmail::fiscal_receipt_copy( 'sr' )['verify'] ?? '' )
			&& 'Verify with the Serbian Tax Administration' === ( CommerceCustomerEmail::fiscal_receipt_copy( 'en' )['verify'] ?? '' ),
		'Fiscal receipt labels are available in both checkout languages.'
	);
	$receipt_template = CommerceCustomerEmail::locate_completed_order_template(
		'/tmp/fallback.php',
		'emails/customer-completed-order.php'
	);
	hse_commerce_presentation_test_assert(
		is_readable( $receipt_template ) && false !== strpos( $receipt_template, 'hse-headless/templates/emails/customer-completed-order.php' ),
		'The plugin-owned completed receipt template is selected.'
	);
	$failed_template = CommerceCustomerEmail::locate_completed_order_template(
		'/tmp/fallback.php',
		'emails/customer-failed-order.php'
	);
	hse_commerce_presentation_test_assert(
		is_readable( $failed_template ) && false !== strpos( $failed_template, 'hse-headless/templates/emails/customer-failed-order.php' ),
		'The plugin-owned failed-payment template is selected.'
	);
	$refund_template = CommerceCustomerEmail::locate_completed_order_template(
		'/tmp/fallback.php',
		'emails/customer-refunded-order.php'
	);
	hse_commerce_presentation_test_assert(
		is_readable( $refund_template ) && false !== strpos( $refund_template, 'hse-headless/templates/emails/customer-refunded-order.php' ),
		'The plugin-owned customer refund template is selected.'
	);
	$bank_template = CommerceCustomerEmail::locate_completed_order_template(
		'/tmp/fallback.php',
		'emails/customer-on-hold-order.php'
	);
	hse_commerce_presentation_test_assert(
		is_readable( $bank_template ) && false !== strpos( $bank_template, 'hse-headless/templates/emails/customer-on-hold-order.php' ),
		'The plugin-owned bank-transfer instructions template is selected.'
	);
	$on_hold_email = WC()->mailer()->get_emails()['WC_Email_Customer_On_Hold_Order'] ?? null;
	if ( $on_hold_email ) {
		$bank_html = wc_get_template_html(
			'emails/customer-on-hold-order.php',
			array(
				'order'              => $email_order,
				'email_heading'      => CommerceCustomerEmail::on_hold_order_heading( '', $email_order ),
				'additional_content' => '',
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $on_hold_email,
			)
		);
		hse_commerce_presentation_test_assert(
			false !== strpos( $bank_html, 'Nalog za prenos za pravno lice' )
				&& false !== strpos( $bank_html, 'Šifra plaćanja' )
				&& false !== strpos( $bank_html, CommerceBankTransfer::BENEFICIARY_NAME )
				&& false === strpos( $bank_html, 'Poziv na broj odobrenja' )
				&& false !== strpos( $bank_html, '<!doctype html>' )
				&& false !== strpos( $bank_html, '<!--[if mso]>' ),
			'The Serbian company bank-transfer email renders approved payment details without a reference field.'
		);
	} else {
		hse_commerce_presentation_test_assert( false, 'WooCommerce on-hold email is available for transfer-instruction rendering.' );
	}
	$merchant_template = CommerceCustomerEmail::locate_completed_order_template(
		'/tmp/fallback.php',
		'emails/admin-new-order.php'
	);
	hse_commerce_presentation_test_assert(
		is_readable( $merchant_template ) && false !== strpos( $merchant_template, 'hse-headless/templates/emails/admin-new-order.php' ),
		'The plugin-owned merchant new-order template is selected.'
	);
	$fiscal_template = CommerceCustomerEmail::locate_completed_order_template(
		'/tmp/fallback.php',
		'emails/bokapos-receipt.php'
	);
	hse_commerce_presentation_test_assert(
		is_readable( $fiscal_template ) && false !== strpos( $fiscal_template, 'hse-headless/templates/emails/bokapos-receipt.php' ),
		'The plugin-owned BokaPOS receipt template is selected.'
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
				&& false !== strpos( $receipt_html, '12345678' )
				&& false !== strpos( $receipt_html, '<!doctype html>' )
				&& false !== strpos( $receipt_html, '<!--[if mso]>' ),
			'The Serbian completed receipt renders payment and legal-entity details.'
		);
	} else {
		hse_commerce_presentation_test_assert( false, 'WooCommerce completed-order email is available for receipt rendering.' );
	}

	$refunded_email = WC()->mailer()->get_emails()['WC_Email_Customer_Refunded_Order'] ?? null;
	if ( $refunded_email ) {
		$refunded_email->partial_refund = false;
		$refund_html = wc_get_template_html(
			'emails/customer-refunded-order.php',
			array(
				'order'              => $email_order,
				'refund'             => false,
				'partial_refund'     => false,
				'email_heading'      => CommerceCustomerEmail::refunded_order_heading( '', $email_order, $refunded_email ),
				'additional_content' => '',
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $refunded_email,
			)
		);
		hse_commerce_presentation_test_assert(
			false !== strpos( $refund_html, 'Refundacija je izvršena' )
				&& false !== strpos( $refund_html, 'REFUNDACIJA IZVRŠENA' )
				&& false !== strpos( $refund_html, 'BokaPOS fiskalizacije' )
				&& false !== strpos( $refund_html, '<!doctype html>' )
				&& false !== strpos( $refund_html, '<!--[if mso]>' ),
			'The Serbian customer refund email renders the branded responsive template and fiscal guidance.'
		);
	} else {
		hse_commerce_presentation_test_assert( false, 'WooCommerce refunded-order email is available for branded rendering.' );
	}

	$failed_email = WC()->mailer()->get_emails()['WC_Email_Customer_Failed_Order'] ?? null;
	if ( $failed_email ) {
		$failed_html = wc_get_template_html(
			'emails/customer-failed-order.php',
			array(
				'order'              => $email_order,
				'email_heading'      => CommerceCustomerEmail::failed_order_heading( '', $email_order ),
				'additional_content' => '',
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $failed_email,
			)
		);
		hse_commerce_presentation_test_assert(
			false !== strpos( $failed_html, 'Plaćanje nije završeno' )
				&& false !== strpos( $failed_html, 'PLAĆANJE ODBIJENO' )
				&& false !== strpos( $failed_html, 'Pregledajte i ponovite plaćanje' )
				&& false !== strpos( $failed_html, 'order-pay' )
				&& false !== strpos( $failed_html, '<!doctype html>' )
				&& false !== strpos( $failed_html, '<!--[if mso]>' ),
			'The Serbian failed-payment email renders the branded retry guidance and secure payment link.'
		);
	} else {
		hse_commerce_presentation_test_assert( false, 'WooCommerce failed-order email is available for branded rendering.' );
	}

	$new_order_email = WC()->mailer()->get_emails()['WC_Email_New_Order'] ?? null;
	if ( $new_order_email ) {
		$merchant_html = wc_get_template_html(
			'emails/admin-new-order.php',
			array(
				'order'              => $email_order,
				'email_heading'      => CommerceCustomerEmail::merchant_order_heading( '', $email_order ),
				'additional_content' => '',
				'sent_to_admin'      => true,
				'plain_text'         => false,
				'email'              => $new_order_email,
			)
		);
		hse_commerce_presentation_test_assert(
			false !== strpos( $merchant_html, 'Primljena je nova porudžbina' )
				&& false !== strpos( $merchant_html, 'Podaci kupca' )
				&& false === strpos( $merchant_html, 'OTVORI PORUDŽBINU' )
				&& false !== strpos( $merchant_html, '123456789' )
				&& false !== strpos( $merchant_html, '<!doctype html>' ),
			'The merchant email renders the branded order summary and customer details.'
		);
	} else {
		hse_commerce_presentation_test_assert( false, 'WooCommerce new-order merchant email is available for rendering.' );
	}

	$fiscal_html = wc_get_template_html(
		'emails/bokapos-receipt.php',
		array(
			'order'              => $email_order,
			'email_heading'      => CommerceCustomerEmail::fiscal_receipt_heading( '', $email_order ),
			'additional_content' => '',
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'              => $completed_email,
			'pfr_number'         => 'TEST-PFR-123',
			'pfr_time'           => '25.09.2026. 14:17',
			'verification_url'   => 'https://sandbox.suf.purs.gov.rs/v/?vl=test',
			'pdf_url'            => 'https://cms.hsetraining.rs/?bokapos_receipt=test',
		)
	);
	hse_commerce_presentation_test_assert(
		false !== strpos( $fiscal_html, 'Fiskalni račun je izdat' )
			&& false !== strpos( $fiscal_html, 'Proverite račun kod Poreske uprave' )
			&& false !== strpos( $fiscal_html, 'TEST-PFR-123' )
			&& false !== strpos( $fiscal_html, 'zvanični fiskalni dokument' )
			&& false !== strpos( $fiscal_html, '<!doctype html>' )
			&& false !== strpos( $fiscal_html, '<!--[if mso]>' ),
		'The Serbian BokaPOS receipt renders a branded localized wrapper around the official document.'
	);

	$email_order->update_meta_data( CommerceLocale::ORDER_META, 'en' );
	$email_order->save();
	hse_commerce_presentation_test_assert(
		'Payment failed for order #' . $email_order->get_order_number() === CommerceCustomerEmail::failed_order_subject( 'Fallback', $email_order )
			&& 'Payment failed' === CommerceCustomerEmail::failed_order_heading( 'Fallback', $email_order ),
		'Failed-payment subject and heading follow the English order locale.'
	);
	hse_commerce_presentation_test_assert(
		'Fiscal receipt for order #' . $email_order->get_order_number() === CommerceCustomerEmail::fiscal_receipt_subject( 'Fallback', $email_order )
			&& 'Your fiscal receipt' === CommerceCustomerEmail::fiscal_receipt_heading( 'Fallback', $email_order ),
		'BokaPOS fiscal receipt subject and heading follow the English order locale.'
	);
	$fiscal_html_en = wc_get_template_html(
		'emails/bokapos-receipt.php',
		array(
			'order'              => $email_order,
			'email_heading'      => CommerceCustomerEmail::fiscal_receipt_heading( '', $email_order ),
			'additional_content' => '',
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'              => $completed_email,
			'pfr_number'         => 'TEST-PFR-456',
			'pfr_time'           => '25 September 2026, 14:17',
			'verification_url'   => 'https://sandbox.suf.purs.gov.rs/v/?vl=test-en',
			'pdf_url'            => 'https://cms.hsetraining.rs/?bokapos_receipt=test-en',
		)
	);
	hse_commerce_presentation_test_assert(
		false !== strpos( $fiscal_html_en, 'Your fiscal receipt is ready' )
			&& false !== strpos( $fiscal_html_en, 'Verify with the Serbian Tax Administration' )
			&& false !== strpos( $fiscal_html_en, 'official fiscal document' )
			&& false !== strpos( $fiscal_html_en, 'TEST-PFR-456' ),
		'The English BokaPOS receipt renders localized guidance while retaining the official document.'
	);

	$email_order->update_meta_data( CommerceLocale::ORDER_META, 'sr' );
	$email_order->set_billing_country( 'SI' );
	$email_order->save();
	hse_commerce_presentation_test_assert(
		'en' === CommerceCustomerEmail::fiscal_receipt_locale( $email_order )
			&& 'Fiscal receipt for order #' . $email_order->get_order_number() === CommerceCustomerEmail::fiscal_receipt_subject( 'Fallback', $email_order ),
		'Foreign buyers receive the fiscal-receipt wrapper in English even if checkout locale metadata is Serbian.'
	);

	$email = (object) array( 'object' => $email_order );
	CommerceCustomerEmail::record_email_delivery( true, 'customer_failed_order', $email );
	$email_order = wc_get_order( $email_order->get_id() );
	hse_commerce_presentation_test_assert(
		'' !== (string) $email_order->get_meta( '_hse_customer_email_customer_failed_order_sent_at', true ),
		'Successfully delivered payment-status email evidence is retained on the order.'
	);
	$completed_email->object = $email_order;
	hse_commerce_presentation_test_assert(
		true === CommerceCustomerEmail::prevent_duplicate_completed_order_email( true, $email_order, $completed_email ),
		'The first completed-order customer email is allowed.'
	);
	hse_commerce_presentation_test_assert(
		false === CommerceCustomerEmail::prevent_duplicate_completed_order_email( true, $email_order, $completed_email ),
		'A concurrent completed-order callback cannot claim the same customer email.'
	);
	CommerceCustomerEmail::record_email_delivery( false, 'customer_completed_order', $completed_email );
	hse_commerce_presentation_test_assert(
		true === CommerceCustomerEmail::prevent_duplicate_completed_order_email( true, $email_order, $completed_email ),
		'A failed completed-order send releases its claim for a later retry.'
	);
	CommerceCustomerEmail::record_email_delivery( true, 'customer_completed_order', $completed_email );
	$email_order = wc_get_order( $email_order->get_id() );
	hse_commerce_presentation_test_assert(
		false === CommerceCustomerEmail::prevent_duplicate_completed_order_email( true, $email_order, $completed_email ),
		'A successfully delivered completed-order email cannot be sent again for the same order.'
	);
	hse_commerce_presentation_test_assert(
		true === CommerceCustomerEmail::prevent_duplicate_completed_order_email( true, $email_order, (object) array( 'id' => 'bokapos_receipt' ) ),
		'The completed-order safeguard does not suppress the separate BokaPOS fiscal receipt email.'
	);
	$order_id = $email_order->get_id();
	$email_order->delete( true );
	CommerceCustomerEmail::delete_completed_email_claim( $order_id );
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d Commerce presentation integration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All Commerce presentation integration checks passed.' );
