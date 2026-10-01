<?php
/**
 * Customer payment notifications and minimal delivery evidence.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Ensures every terminal payment outcome can notify the customer and be audited. */
final class CommerceCustomerEmail {
	public const DEFAULT_ADMIN_EMAIL = 'info@hsetraining.rs';
	public const DEFAULT_DEV_ADMIN_EMAIL = 'predo.vuckovic@gmail.com';
	private const COMPLETED_EMAIL_CLAIM_PREFIX = 'hse_customer_completed_email_claim_';
	private const COMPLETED_EMAIL_CLAIM_TTL = 600;

	private const CUSTOMER_ORDER_EMAILS = array(
		'bokapos_receipt',
		'customer_on_hold_order',
		'customer_completed_order',
		'customer_failed_order',
		'customer_cancelled_order',
		'customer_refunded_order',
		'customer_partially_refunded_order',
	);

	/** Register WooCommerce email hooks. */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_email_get_option', array( self::class, 'force_branded_email_type' ), 999, 5 );
		add_filter( 'woocommerce_email_content_type', array( self::class, 'force_branded_content_type' ), 999, 3 );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', array( self::class, 'disable_processing_order_email' ), 20, 3 );
		add_filter( 'woocommerce_email_enabled_customer_invoice', array( self::class, 'disable_customer_invoice_email' ), 20, 3 );
		add_filter( 'woocommerce_order_actions', array( self::class, 'remove_customer_order_details_action' ), 20, 2 );
		add_filter( 'woocommerce_email_enabled_customer_cancelled_order', array( self::class, 'enable_cancelled_order_email' ), 20, 3 );
		add_filter( 'woocommerce_email_recipient_new_order', array( self::class, 'new_order_recipient' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_customer_failed_order', array( self::class, 'failed_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_customer_failed_order', array( self::class, 'failed_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_failed_order', array( self::class, 'remove_unsuccessful_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_customer_cancelled_order', array( self::class, 'cancelled_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_customer_cancelled_order', array( self::class, 'cancelled_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_cancelled_order', array( self::class, 'remove_unsuccessful_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_customer_on_hold_order', array( self::class, 'on_hold_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_customer_on_hold_order', array( self::class, 'on_hold_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_on_hold_order', array( self::class, 'remove_on_hold_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_customer_completed_order', array( self::class, 'completed_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_customer_completed_order', array( self::class, 'completed_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_completed_order', array( self::class, 'remove_completed_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_customer_refunded_order', array( self::class, 'refunded_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_customer_refunded_order', array( self::class, 'refunded_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_refunded_order', array( self::class, 'remove_refunded_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_partially_refunded_order', array( self::class, 'remove_refunded_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_bokapos_receipt', array( self::class, 'fiscal_receipt_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_bokapos_receipt', array( self::class, 'fiscal_receipt_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_bokapos_receipt', array( self::class, 'remove_fiscal_receipt_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_new_order', array( self::class, 'merchant_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_new_order', array( self::class, 'merchant_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_new_order', array( self::class, 'remove_merchant_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_attachments', array( self::class, 'attach_international_bank_instructions' ), 20, 4 );
		add_filter( 'woocommerce_locate_template', array( self::class, 'locate_completed_order_template' ), 20, 3 );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', array( self::class, 'prevent_duplicate_completed_order_email' ), 999, 3 );
		add_action( 'woocommerce_email_sent', array( self::class, 'record_email_delivery' ), 20, 3 );
		add_action( 'woocommerce_before_delete_order', array( self::class, 'delete_completed_email_claim' ), 20, 1 );
	}

	/** Send the final WooCommerce purchase confirmation only once per order. */
	public static function prevent_duplicate_completed_order_email( $enabled, $order = null, $email = null ): bool {
		$enabled = (bool) $enabled;
		if ( ! $enabled || ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return $enabled;
		}

		$email_id = is_object( $email ) && isset( $email->id ) && is_scalar( $email->id )
			? sanitize_key( (string) $email->id )
			: '';
		if ( 'customer_completed_order' !== $email_id ) {
			return $enabled;
		}

		if ( '' !== trim( (string) $order->get_meta( self::delivery_meta_key( $email_id ), true ) ) ) {
			return false;
		}

		return self::claim_completed_email_delivery( $order );
	}

	/** Atomically reserve completed-order delivery across concurrent gateway callbacks. */
	private static function claim_completed_email_delivery( $order ): bool {
		if ( ! method_exists( $order, 'get_id' ) ) {
			return false;
		}

		$order_id = absint( $order->get_id() );
		if ( $order_id < 1 ) {
			return false;
		}

		$claim_key = self::completed_email_claim_key( $order_id );
		$claim     = get_option( $claim_key, false );
		if ( 'sent' === $claim ) {
			return false;
		}

		if ( false !== $claim ) {
			$claimed_at = absint( $claim );
			if ( $claimed_at > 0 && ( time() - $claimed_at ) < self::COMPLETED_EMAIL_CLAIM_TTL ) {
				return false;
			}
			delete_option( $claim_key );
		}

		/* add_option() is atomic because option_name is a unique database key. */
		return add_option( $claim_key, time(), '', false );
	}

	/** Ensure branded order notifications render their HTML templates. */
	public static function force_branded_email_type( $option, $email, $value = null, $key = '', $empty_value = null ): string {
		unset( $value, $empty_value );
		if ( CommerceConfiguration::is_enabled() && 'email_type' === $key && self::is_branded_email( $email ) ) {
			return 'html';
		}

		return is_scalar( $option ) ? (string) $option : '';
	}

	/** Keep SMTP and WooCommerce aligned on the MIME type for branded emails. */
	public static function force_branded_content_type( $content_type, $email, $default_content_type = '' ): string {
		unset( $default_content_type );
		if ( CommerceConfiguration::is_enabled() && self::is_branded_email( $email ) ) {
			return 'text/html';
		}

		return is_scalar( $content_type ) ? (string) $content_type : '';
	}

	/** Successful checkout sends only the final completed receipt to the customer. */
	public static function disable_processing_order_email( $enabled, $order = null, $email = null ): bool {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? false : (bool) $enabled;
	}

	/** Prevent WooCommerce's generic paid invoice from duplicating the completed receipt. */
	public static function disable_customer_invoice_email( $enabled, $order = null, $email = null ): bool {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? false : (bool) $enabled;
	}

	/** Remove the generic order-details action that can be submitted with an order update. */
	public static function remove_customer_order_details_action( $actions, $order = null ): array {
		unset( $order );
		if ( ! is_array( $actions ) ) {
			return array();
		}

		if ( CommerceConfiguration::is_enabled() ) {
			unset( $actions['send_order_details'] );
		}

		return $actions;
	}

	/** The bank requires an electronic confirmation for an unsuccessful outcome too. */
	public static function enable_cancelled_order_email( $enabled, $order = null, $email = null ): bool {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? true : (bool) $enabled;
	}

	/** Localize the declined-payment subject from the immutable checkout language. */
	public static function failed_order_subject( $subject, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $subject ) ? (string) $subject : '';
		}

		$format = 'sr' === self::order_locale( $order )
			? 'Plaćanje nije uspelo za porudžbinu #%s'
			: 'Payment failed for order #%s';

		return sprintf( $format, $order->get_order_number() );
	}

	/** Localize the prominent declined-payment heading. */
	public static function failed_order_heading( $heading, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $heading ) ? (string) $heading : '';
		}

		return 'sr' === self::order_locale( $order ) ? 'Plaćanje nije uspelo' : 'Payment failed';
	}

	/** Localize the cancelled-order subject from the immutable checkout language. */
	public static function cancelled_order_subject( $subject, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $subject ) ? (string) $subject : '';
		}

		$format = 'sr' === self::order_locale( $order )
			? 'Porudžbina #%s je otkazana'
			: 'Order #%s has been cancelled';

		return sprintf( $format, $order->get_order_number() );
	}

	/** Localize the prominent cancelled-order heading. */
	public static function cancelled_order_heading( $heading, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $heading ) ? (string) $heading : '';
		}

		return 'sr' === self::order_locale( $order ) ? 'Porudžbina je otkazana' : 'Order cancelled';
	}

	/** Plugin-owned unsuccessful-payment templates contain all closing guidance. */
	public static function remove_unsuccessful_order_additional_content( $content, $order = null, $email = null ): string {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? '' : ( is_scalar( $content ) ? (string) $content : '' );
	}

	/** Keep merchant order notifications away from imported placeholder addresses. */
	public static function new_order_recipient( $recipient, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() ) {
			return sanitize_email( is_scalar( $recipient ) ? (string) $recipient : '' );
		}

		if ( 'dev' === CommerceCheckoutSource::for_order( $order ) ) {
			$configured_dev = defined( 'HSE_COMMERCE_DEV_ADMIN_EMAIL' ) ? sanitize_email( (string) constant( 'HSE_COMMERCE_DEV_ADMIN_EMAIL' ) ) : '';
			return is_email( $configured_dev ) ? $configured_dev : self::DEFAULT_DEV_ADMIN_EMAIL;
		}

		$configured = defined( 'HSE_COMMERCE_ADMIN_EMAIL' ) ? sanitize_email( (string) constant( 'HSE_COMMERCE_ADMIN_EMAIL' ) ) : '';
		return is_email( $configured ) ? $configured : self::DEFAULT_ADMIN_EMAIL;
	}

	/** Localize the direct-bank-transfer instructions subject. */
	public static function on_hold_order_subject( $subject, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! CommerceBankTransfer::is_bank_transfer_order( $order ) ) {
			return is_scalar( $subject ) ? (string) $subject : '';
		}

		$format = 'sr' === self::order_locale( $order )
			? 'Podaci za uplatu porudžbine #%s'
			: 'Bank transfer instructions for order #%s';

		return sprintf( $format, $order->get_order_number() );
	}

	/** Localize the prominent bank-transfer email heading. */
	public static function on_hold_order_heading( $heading, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! CommerceBankTransfer::is_bank_transfer_order( $order ) ) {
			return is_scalar( $heading ) ? (string) $heading : '';
		}

		return 'sr' === self::order_locale( $order ) ? 'Podaci za uplatu' : 'Bank transfer instructions';
	}

	/** The plugin-owned transfer template contains all closing guidance. */
	public static function remove_on_hold_order_additional_content( $content, $order = null, $email = null ): string {
		unset( $email );
		return CommerceConfiguration::is_enabled() && CommerceBankTransfer::is_bank_transfer_order( $order )
			? ''
			: ( is_scalar( $content ) ? (string) $content : '' );
	}

	/** Attach the official Raiffeisen EUR instructions only to foreign BACS customer emails. */
	public static function attach_international_bank_instructions( $attachments, $email_id, $order, $email = null ): array {
		unset( $email );
		$attachments = is_array( $attachments ) ? $attachments : array();
		if ( ! CommerceConfiguration::is_enabled()
			|| 'customer_on_hold_order' !== (string) $email_id
			|| ! CommerceBankTransfer::is_bank_transfer_order( $order )
			|| 'RS' === strtoupper( (string) $order->get_billing_country() ) ) {
			return $attachments;
		}

		$instructions = CommerceBankTransfer::euro_instructions_path();
		if ( is_readable( $instructions ) && ! in_array( $instructions, $attachments, true ) ) {
			$attachments[] = $instructions;
		}

		return $attachments;
	}

	/** Localize the completed receipt subject from the immutable order locale. */
	public static function completed_order_subject( $subject, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $subject ) ? (string) $subject : '';
		}

		$format = 'sr' === self::order_locale( $order )
			? 'Potvrda o plaćanju za porudžbinu #%s'
			: 'Payment receipt for order #%s';

		return sprintf( $format, $order->get_order_number() );
	}

	/** Localize the prominent completed receipt heading. */
	public static function completed_order_heading( $heading, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $heading ) ? (string) $heading : '';
		}

		return 'sr' === self::order_locale( $order ) ? 'Potvrda o plaćanju' : 'Payment receipt';
	}

	/** The receipt template owns its closing copy so Woo settings cannot mix languages. */
	public static function remove_completed_order_additional_content( $content, $order = null, $email = null ): string {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? '' : ( is_scalar( $content ) ? (string) $content : '' );
	}

	/** Localize the full or partial refund subject from the immutable order locale. */
	public static function refunded_order_subject( $subject, $order = null, $email = null ): string {
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $subject ) ? (string) $subject : '';
		}

		$partial = is_object( $email ) && ! empty( $email->partial_refund );
		if ( 'sr' === self::order_locale( $order ) ) {
			$format = $partial ? 'Delimična refundacija porudžbine #%s' : 'Refundacija porudžbine #%s';
		} else {
			$format = $partial ? 'Partial refund for order #%s' : 'Refund for order #%s';
		}

		return sprintf( $format, $order->get_order_number() );
	}

	/** Localize the prominent refund heading. */
	public static function refunded_order_heading( $heading, $order = null, $email = null ): string {
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $heading ) ? (string) $heading : '';
		}

		$partial = is_object( $email ) && ! empty( $email->partial_refund );
		if ( 'sr' === self::order_locale( $order ) ) {
			return $partial ? 'Delimična refundacija je izvršena' : 'Refundacija je izvršena';
		}

		return $partial ? 'Your partial refund is complete' : 'Your refund is complete';
	}

	/** The plugin-owned refund template contains all closing guidance. */
	public static function remove_refunded_order_additional_content( $content, $order = null, $email = null ): string {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? '' : ( is_scalar( $content ) ? (string) $content : '' );
	}

	/** Localize the separate BokaPOS receipt subject from the checkout language. */
	public static function fiscal_receipt_subject( $subject, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $subject ) ? (string) $subject : '';
		}

		$format = 'sr' === self::fiscal_receipt_locale( $order )
			? 'Fiskalni račun za porudžbinu #%s'
			: 'Fiscal receipt for order #%s';

		return sprintf( $format, $order->get_order_number() );
	}

	/** Localize the prominent BokaPOS receipt heading. */
	public static function fiscal_receipt_heading( $heading, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $heading ) ? (string) $heading : '';
		}

		return 'sr' === self::fiscal_receipt_locale( $order ) ? 'Vaš fiskalni račun' : 'Your fiscal receipt';
	}

	/** The localized fiscal template owns its closing copy. */
	public static function remove_fiscal_receipt_additional_content( $content, $order = null, $email = null ): string {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? '' : ( is_scalar( $content ) ? (string) $content : '' );
	}

	/** Give the merchant notification a useful, branded subject. */
	public static function merchant_order_subject( $subject, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $subject ) ? (string) $subject : '';
		}

		return sprintf( 'Nova porudžbina #%s - HSE Training', $order->get_order_number() );
	}

	/** Use a concise heading inside the merchant notification. */
	public static function merchant_order_heading( $heading, $order = null, $email = null ): string {
		unset( $email );
		if ( ! CommerceConfiguration::is_enabled() || ! self::is_order( $order ) ) {
			return is_scalar( $heading ) ? (string) $heading : '';
		}

		return 'Nova porudžbina';
	}

	/** The plugin-owned merchant template contains all required closing copy. */
	public static function remove_merchant_order_additional_content( $content, $order = null, $email = null ): string {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? '' : ( is_scalar( $content ) ? (string) $content : '' );
	}

	/** Use plugin-owned, update-safe HTML and plain-text order email templates. */
	public static function locate_completed_order_template( $template, $template_name, $template_path = '' ): string {
		unset( $template_path );
		if ( ! CommerceConfiguration::is_enabled() || ! is_string( $template_name ) ) {
			return is_string( $template ) ? $template : '';
		}

		$owned = array(
			'emails/customer-on-hold-order.php',
			'emails/plain/customer-on-hold-order.php',
			'emails/customer-completed-order.php',
			'emails/plain/customer-completed-order.php',
			'emails/customer-failed-order.php',
			'emails/plain/customer-failed-order.php',
			'emails/customer-cancelled-order.php',
			'emails/plain/customer-cancelled-order.php',
			'emails/customer-refunded-order.php',
			'emails/plain/customer-refunded-order.php',
			'emails/admin-new-order.php',
			'emails/plain/admin-new-order.php',
			'emails/bokapos-receipt.php',
			'emails/plain/bokapos-receipt.php',
		);
		if ( ! in_array( $template_name, $owned, true ) ) {
			return is_string( $template ) ? $template : '';
		}

		$plugin_template = dirname( __DIR__, 2 ) . '/templates/' . $template_name;
		return is_readable( $plugin_template ) ? $plugin_template : ( is_string( $template ) ? $template : '' );
	}

	/** Return the allowlisted language stored when the checkout order was created. */
	public static function order_locale( $order ): string {
		if ( ! self::is_order( $order ) ) {
			return 'en';
		}

		return CommerceLocale::sanitize( $order->get_meta( CommerceLocale::ORDER_META, true ) );
	}

	/** Foreign buyers always receive the fiscal-receipt wrapper in English. */
	public static function fiscal_receipt_locale( $order ): string {
		if ( self::is_order( $order ) && method_exists( $order, 'get_billing_country' ) ) {
			$country = strtoupper( trim( (string) $order->get_billing_country() ) );
			if ( '' !== $country && 'RS' !== $country ) {
				return 'en';
			}
		}

		return self::order_locale( $order );
	}

	/** Return receipt labels without depending on the WordPress administrator locale. */
	public static function receipt_copy( string $locale ): array {
		if ( 'sr' === CommerceLocale::sanitize( $locale ) ) {
			return array(
				'company'          => 'HSE TRAINING D.O.O.',
				'tagline'          => 'Bezbednost, zdravlje i profesionalne obuke',
				'preheader'        => 'Vaša potvrda o uspešnom plaćanju i porudžbini.',
				'thanks'           => 'Hvala na kupovini',
				'intro'            => 'Ova potvrda potvrđuje vaše plaćanje i porudžbinu za: %s.',
				'billed_to'        => 'Podaci kupca',
				'payment_details'  => 'Detalji plaćanja',
				'payment_method'   => 'Način plaćanja',
				'order_number'     => 'Broj porudžbine',
				'order_date'       => 'Datum porudžbine',
				'description'      => 'Opis',
				'quantity'         => 'Količina',
				'amount'           => 'Iznos',
				'subtotal'         => 'Međuzbir',
				'total_paid'       => 'Ukupno plaćeno',
				'payment_received' => 'PLAĆANJE PRIMLJENO',
				'access_heading'    => 'Trenutni pristup digitalnom kursu',
				'access_instructions' => 'Pratite dostavljeno uputstvo ili link, kreirajte nalog na HSE e-learning platformi i odmah započnite kurs.',
				'consent_confirmation' => 'Prilikom kupovine izričito ste zatražili da digitalna isporuka počne odmah nakon potvrđenog plaćanja i potvrdili da razumete uticaj početka isporuke na pravo na odustanak.',
				'closing_heading'  => 'Radujemo se što ćemo vas podržati tokom vašeg NEBOSH usavršavanja.',
				'next_steps'       => 'Ako uputstvo za kreiranje naloga ne stigne ili pristup ne radi, kontaktirajte info@hsetraining.rs i navedite broj porudžbine.',
				'website'          => 'hsetraining.rs',
				'email'            => 'info@hsetraining.rs',
				'country'          => 'Srbija',
			);
		}

		return array(
			'company'          => 'HSE TRAINING D.O.O.',
			'tagline'          => 'Health, Safety & Professional Training',
			'preheader'        => 'Your payment and order confirmation from HSE Training.',
			'thanks'           => 'Thank you for your purchase',
			'intro'            => 'This receipt confirms your payment and order for: %s.',
			'billed_to'        => 'Billed to',
			'payment_details'  => 'Payment details',
			'payment_method'   => 'Payment method',
			'order_number'     => 'Order number',
			'order_date'       => 'Order date',
			'description'      => 'Description',
			'quantity'         => 'Qty',
			'amount'           => 'Amount',
			'subtotal'         => 'Subtotal',
			'total_paid'       => 'Total paid',
			'payment_received' => 'PAYMENT RECEIVED',
			'access_heading'    => 'Immediate digital course access',
			'access_instructions' => 'Follow the supplied instructions or link, create your account on the HSE e-learning platform and begin the course immediately.',
			'consent_confirmation' => 'At checkout you expressly requested that digital delivery begin immediately after confirmed payment and acknowledged the effect that starting delivery has on your right to withdraw.',
			'closing_heading'  => 'We look forward to supporting you throughout your NEBOSH journey.',
			'next_steps'       => 'If the account-creation instructions do not arrive or access does not work, contact info@hsetraining.rs and quote your order number.',
			'website'          => 'hsetraining.rs',
			'email'            => 'info@hsetraining.rs',
			'country'          => 'Serbia',
		);
	}

	/** Return localized guidance for a failed or cancelled payment attempt. */
	public static function unsuccessful_payment_copy( string $locale, string $outcome = 'failed' ): array {
		$locale    = CommerceLocale::sanitize( $locale );
		$cancelled = 'cancelled' === $outcome;

		if ( 'sr' === $locale ) {
			return array(
				'company'        => 'HSE TRAINING D.O.O.',
				'tagline'        => 'Bezbednost, zdravlje i profesionalne obuke',
				'preheader'      => $cancelled ? 'Vaša porudžbina je otkazana.' : 'Kartično plaćanje nije potvrđeno.',
				'title'          => $cancelled ? 'Porudžbina je otkazana' : 'Plaćanje nije završeno',
				'intro'          => $cancelled
					? 'Porudžbina #%s je otkazana i neće biti obrađena.'
					: 'Nismo uspeli da potvrdimo kartično plaćanje za porudžbinu #%s.',
				'status'         => $cancelled ? 'PORUDŽBINA OTKAZANA' : 'PLAĆANJE ODBIJENO',
				'reassurance'    => $cancelled
					? 'Za ovu porudžbinu neće biti omogućen pristup kursu niti izdat fiskalni račun.'
					: 'Za ovu porudžbinu nije evidentirano uspešno plaćanje. Pristup kursu nije aktiviran i fiskalni račun nije izdat.',
				'order_summary'  => 'Pregled pokušaja kupovine',
				'description'    => 'Opis',
				'quantity'       => 'Količina',
				'amount'         => 'Iznos',
				'total'          => 'Ukupno',
				'payment_method' => 'Način plaćanja',
				'retry_title'    => $cancelled ? 'Želite da pokušate ponovo?' : 'Pokušajte ponovo bezbedno',
				'retry_text'     => $cancelled
					? 'Vratite se na HSE Training stranicu kursa i pokrenite novu kupovinu kada budete spremni.'
					: 'Kliknite na dugme ispod, proverite postojeću porudžbinu i nastavite na bezbednu RaiAccept stranicu. Ako se problem ponovi, pokušajte drugom karticom ili kontaktirajte svoju banku.',
				'retry_button'   => 'Pregledajte i ponovite plaćanje',
				'help'           => 'Ako vam je potrebna pomoć, pišite na info@hsetraining.rs i navedite broj porudžbine.',
				'website'        => 'hsetraining.rs',
				'email'          => 'info@hsetraining.rs',
				'country'        => 'Srbija',
			);
		}

		return array(
			'company'        => 'HSE TRAINING D.O.O.',
			'tagline'        => 'Health, Safety & Professional Training',
			'preheader'      => $cancelled ? 'Your order has been cancelled.' : 'Your card payment could not be confirmed.',
			'title'          => $cancelled ? 'Your order has been cancelled' : 'Your payment was not completed',
			'intro'          => $cancelled
				? 'Order #%s has been cancelled and will not be processed.'
				: 'We could not confirm the card payment for order #%s.',
			'status'         => $cancelled ? 'ORDER CANCELLED' : 'PAYMENT DECLINED',
			'reassurance'    => $cancelled
				? 'Course access will not be enabled and no fiscal receipt will be issued for this order.'
				: 'No successful payment was recorded for this order. Course access has not been activated and no fiscal receipt has been issued.',
			'order_summary'  => 'Purchase attempt summary',
			'description'    => 'Description',
			'quantity'       => 'Qty',
			'amount'         => 'Amount',
			'total'          => 'Total',
			'payment_method' => 'Payment method',
			'retry_title'    => $cancelled ? 'Would you like to try again?' : 'Try again securely',
			'retry_text'     => $cancelled
				? 'Return to the HSE Training course page and start a new purchase when you are ready.'
				: 'Use the button below to review the existing order and continue to the secure RaiAccept page. If the issue continues, try another card or contact your bank.',
			'retry_button'   => 'Review and retry payment',
			'help'           => 'If you need help, email info@hsetraining.rs and quote your order number.',
			'website'        => 'hsetraining.rs',
			'email'          => 'info@hsetraining.rs',
			'country'        => 'Serbia',
		);
	}

	/** Return localized customer guidance for a full or partial refund. */
	public static function refund_copy( string $locale, bool $partial = false ): array {
		if ( 'sr' === CommerceLocale::sanitize( $locale ) ) {
			return array(
				'company'          => 'HSE TRAINING D.O.O.',
				'tagline'          => 'Bezbednost, zdravlje i profesionalne obuke',
				'preheader'        => $partial ? 'Delimična refundacija vaše porudžbine je obrađena.' : 'Refundacija vaše porudžbine je obrađena.',
				'title'            => $partial ? 'Delimična refundacija je izvršena' : 'Refundacija je izvršena',
				'intro'            => $partial
					? 'Delimična refundacija za porudžbinu #%s poslata je na originalni način plaćanja.'
					: 'Refundacija za porudžbinu #%s poslata je na originalni način plaćanja.',
				'status'           => $partial ? 'DELIMIČNA REFUNDACIJA IZVRŠENA' : 'REFUNDACIJA IZVRŠENA',
				'amount'           => 'Refundirani iznos',
				'refund_date'      => 'Datum refundacije',
				'order_number'     => 'Broj porudžbine',
				'order_details'    => 'Detalji porudžbine',
				'description'      => 'Opis',
				'quantity'         => 'Količina',
				'line_amount'      => 'Iznos',
				'timing'           => 'Vreme knjiženja zavisi od banke izdavaoca kartice i može potrajati nekoliko radnih dana.',
				'fiscal_notice'    => 'Ako je za kupovinu izdat fiskalni račun, fiskalni dokument refundacije stiže u posebnoj poruci nakon uspešne BokaPOS fiskalizacije.',
				'help'             => 'Ako imate pitanje, pišite na info@hsetraining.rs i navedite broj porudžbine.',
				'website'          => 'hsetraining.rs',
				'email'            => 'info@hsetraining.rs',
				'country'          => 'Srbija',
			);
		}

		return array(
			'company'          => 'HSE TRAINING D.O.O.',
			'tagline'          => 'Health, Safety & Professional Training',
			'preheader'        => $partial ? 'A partial refund for your order has been processed.' : 'A refund for your order has been processed.',
			'title'            => $partial ? 'Your partial refund is complete' : 'Your refund is complete',
			'intro'            => $partial
				? 'A partial refund for order #%s has been sent to the original payment method.'
				: 'The refund for order #%s has been sent to the original payment method.',
			'status'           => $partial ? 'PARTIAL REFUND PROCESSED' : 'REFUND PROCESSED',
			'amount'           => 'Refund amount',
			'refund_date'      => 'Refund date',
			'order_number'     => 'Order number',
			'order_details'    => 'Order details',
			'description'      => 'Description',
			'quantity'         => 'Qty',
			'line_amount'      => 'Amount',
			'timing'           => 'Posting time depends on your card issuer and may take several business days.',
			'fiscal_notice'    => 'If a fiscal receipt was issued for the purchase, the fiscal refund document is sent separately after successful BokaPOS fiscalization.',
			'help'             => 'If you have a question, email info@hsetraining.rs and quote your order number.',
			'website'          => 'hsetraining.rs',
			'email'            => 'info@hsetraining.rs',
			'country'          => 'Serbia',
		);
	}

	/** Return fiscal-receipt labels without translating the official PFR document. */
	public static function fiscal_receipt_copy( string $locale ): array {
		if ( 'sr' === CommerceLocale::sanitize( $locale ) ) {
			return array(
				'company'          => 'HSE TRAINING D.O.O.',
				'tagline'          => 'Bezbednost, zdravlje i profesionalne obuke',
				'preheader'        => 'Vaš fiskalni račun i link za proveru kod Poreske uprave.',
				'title'            => 'Fiskalni račun je izdat',
				'intro'            => 'Fiskalni račun za porudžbinu #%s je uspešno izdat.',
				'receipt_number'   => 'PFR broj računa',
				'receipt_time'     => 'Vreme izdavanja',
				'amount'           => 'Ukupan iznos',
				'verify'           => 'Proverite račun kod Poreske uprave',
				'download'         => 'Preuzmite zvanični PDF račun',
				'order_summary'    => 'Pregled porudžbine',
				'description'      => 'Opis',
				'quantity'         => 'Količina',
				'line_amount'      => 'Iznos',
				'official_notice'  => 'Priloženi PDF je zvanični fiskalni dokument. Njegov sadržaj i jezik generiše sistem Poreske uprave i ostaju u propisanom izvornom obliku.',
				'keep_receipt'     => 'Sačuvajte ovaj mejl i fiskalni račun za svoju evidenciju.',
				'website'          => 'hsetraining.rs',
				'email'            => 'info@hsetraining.rs',
				'country'          => 'Srbija',
			);
		}

		return array(
			'company'          => 'HSE TRAINING D.O.O.',
			'tagline'          => 'Health, Safety & Professional Training',
			'preheader'        => 'Your fiscal receipt and Tax Administration verification link.',
			'title'            => 'Your fiscal receipt is ready',
			'intro'            => 'The fiscal receipt for order #%s has been successfully issued.',
			'receipt_number'   => 'PFR receipt number',
			'receipt_time'     => 'Issue time',
			'amount'           => 'Total amount',
			'verify'           => 'Verify with the Serbian Tax Administration',
			'download'         => 'Download the official fiscal receipt PDF',
			'order_summary'    => 'Order summary',
			'description'      => 'Description',
			'quantity'         => 'Qty',
			'line_amount'      => 'Amount',
			'official_notice'  => 'The attached PDF is the official fiscal document. Its content and language are generated by the Serbian Tax Administration and remain in the legally prescribed original format.',
			'keep_receipt'     => 'Please keep this email and fiscal receipt for your records.',
			'website'          => 'hsetraining.rs',
			'email'            => 'info@hsetraining.rs',
			'country'          => 'Serbia',
		);
	}

	/** Return seller-facing labels for the branded new-order notification. */
	public static function merchant_copy(): array {
		return array(
			'company'             => 'HSE TRAINING D.O.O.',
			'tagline'             => 'Health, Safety & Professional Training',
			'preheader'           => 'Nova WooCommerce porudžbina je primljena.',
			'title'               => 'Primljena je nova porudžbina',
			'intro'               => 'U nastavku su podaci potrebni za proveru uplate i dalju obradu prijave na kurs.',
			'customer'            => 'Podaci kupca',
			'order_details'       => 'Detalji porudžbine',
			'payment_method'      => 'Način plaćanja',
			'order_number'        => 'Broj porudžbine',
			'order_date'          => 'Datum porudžbine',
			'order_status'        => 'Status',
			'order_language'      => 'Jezik kupovine',
			'transaction_id'      => 'ID transakcije',
			'description'         => 'Opis',
			'quantity'            => 'Kol.',
			'amount'              => 'Iznos',
			'subtotal'            => 'Međuzbir',
			'total'               => 'Ukupno',
			'payment_confirmed'   => 'PLAĆANJE POTVRĐENO',
			'order_received'      => 'PORUDŽBINA PRIMLJENA',
			'language_en'         => 'Engleski',
			'language_sr'         => 'Srpski',
			'website'             => 'hsetraining.rs',
			'email'               => self::DEFAULT_ADMIN_EMAIL,
		);
	}

	/** Prefer the localized Course title retained on the synchronized product. */
	public static function localized_item_name( $item, string $locale ): string {
		$fallback = is_object( $item ) && method_exists( $item, 'get_name' ) ? (string) $item->get_name() : '';
		if ( 'sr' !== CommerceLocale::sanitize( $locale ) || ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
			return $fallback;
		}

		$localized = get_post_meta( (int) $item->get_product_id(), '_hse_course_title_sr', true );
		return is_string( $localized ) && '' !== trim( $localized ) ? $localized : $fallback;
	}

	/** Store only the notification type and UTC time after the mailer reports success. */
	public static function record_email_delivery( $sent, $email_id, $email ): void {
		$email_id = sanitize_key( is_scalar( $email_id ) ? (string) $email_id : '' );
		if ( ! in_array( $email_id, self::CUSTOMER_ORDER_EMAILS, true ) || ! is_object( $email ) || ! isset( $email->object ) ) {
			return;
		}

		$order = $email->object;
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) || ! method_exists( $order, 'save' ) ) {
			return;
		}
		if ( true !== $sent ) {
			self::release_failed_completed_email_claim( $email_id, $order );
			return;
		}

		$timestamp = gmdate( 'c' );
		$order->update_meta_data( self::delivery_meta_key( $email_id ), $timestamp );
		if ( method_exists( $order, 'add_order_note' ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: WooCommerce email identifier, 2: UTC timestamp. */
					__( 'Customer notification %1$s sent successfully at %2$s UTC.', 'hse-headless' ),
					$email_id,
					$timestamp
				),
				false
			);
		}
		$order->save();

		if ( 'customer_completed_order' === $email_id && method_exists( $order, 'get_id' ) ) {
			update_option( self::completed_email_claim_key( absint( $order->get_id() ) ), 'sent', false );
		}
	}

	/** Remove a pending claim after a failed mail send so a later callback can retry. */
	private static function release_failed_completed_email_claim( string $email_id, $order ): void {
		if ( 'customer_completed_order' !== $email_id || ! method_exists( $order, 'get_id' ) ) {
			return;
		}

		$claim_key = self::completed_email_claim_key( absint( $order->get_id() ) );
		if ( 'sent' !== get_option( $claim_key, false ) ) {
			delete_option( $claim_key );
		}
	}

	/** Delete the cross-request claim when WooCommerce permanently deletes an order. */
	public static function delete_completed_email_claim( $order_id ): void {
		$order_id = absint( $order_id );
		if ( $order_id > 0 ) {
			delete_option( self::completed_email_claim_key( $order_id ) );
		}
	}

	/** Test the minimum Woo order interface used by receipt filters. */
	private static function is_order( $order ): bool {
		return is_object( $order )
			&& method_exists( $order, 'get_meta' )
			&& method_exists( $order, 'get_order_number' );
	}

	/** Return the private delivery-evidence key for an allowlisted email type. */
	private static function delivery_meta_key( string $email_id ): string {
		return '_hse_customer_email_' . sanitize_key( $email_id ) . '_sent_at';
	}

	/** Return the non-autoloaded option key used as an atomic send claim. */
	private static function completed_email_claim_key( int $order_id ): string {
		return self::COMPLETED_EMAIL_CLAIM_PREFIX . $order_id;
	}

	/** Report whether an email uses a plugin-owned branded HTML template. */
	private static function is_branded_email( $email ): bool {
		if ( ! is_object( $email ) || ! isset( $email->id ) || ! is_scalar( $email->id ) ) {
			return false;
		}

		return in_array( (string) $email->id, array( 'new_order', 'customer_on_hold_order', 'customer_completed_order', 'customer_failed_order', 'customer_cancelled_order', 'customer_refunded_order', 'customer_partially_refunded_order', 'bokapos_receipt' ), true );
	}
}
