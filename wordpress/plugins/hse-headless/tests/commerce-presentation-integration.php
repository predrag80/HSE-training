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
	CommerceCustomerEmail::DEFAULT_ADMIN_EMAIL === CommerceCustomerEmail::new_order_recipient( 'legacy-admin@example.test' ),
	'Merchant new-order notifications replace imported placeholder recipients.'
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
	'plain' === CommerceCustomerEmail::force_branded_email_type( 'plain', (object) array( 'id' => 'customer_failed_order' ), 'plain', 'email_type' )
		&& 'text/plain' === CommerceCustomerEmail::force_branded_content_type( 'text/plain', (object) array( 'id' => 'customer_failed_order' ) ),
	'Unrelated WooCommerce emails retain their configured format.'
);
hse_commerce_presentation_test_assert(
	'HSE TRAINING D.O.O.' === ( CommerceCustomerEmail::merchant_copy()['company'] ?? '' )
		&& 'OTVORI PORUDŽBINU' === ( CommerceCustomerEmail::merchant_copy()['view_order'] ?? '' ),
	'Merchant notification copy contains the HSE identity and order action.'
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
				&& false !== strpos( $merchant_html, 'OTVORI PORUDŽBINU' )
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
