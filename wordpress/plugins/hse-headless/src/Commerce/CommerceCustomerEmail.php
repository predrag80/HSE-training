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

	private const CUSTOMER_ORDER_EMAILS = array(
		'customer_on_hold_order',
		'customer_completed_order',
		'customer_failed_order',
		'customer_cancelled_order',
		'customer_refunded_order',
	);

	/** Register WooCommerce email hooks. */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_email_enabled_customer_processing_order', array( self::class, 'disable_processing_order_email' ), 20, 3 );
		add_filter( 'woocommerce_email_enabled_customer_cancelled_order', array( self::class, 'enable_cancelled_order_email' ), 20, 3 );
		add_filter( 'woocommerce_email_recipient_new_order', array( self::class, 'new_order_recipient' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_customer_completed_order', array( self::class, 'completed_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_customer_completed_order', array( self::class, 'completed_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_customer_completed_order', array( self::class, 'remove_completed_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_email_subject_new_order', array( self::class, 'merchant_order_subject' ), 20, 3 );
		add_filter( 'woocommerce_email_heading_new_order', array( self::class, 'merchant_order_heading' ), 20, 3 );
		add_filter( 'woocommerce_email_additional_content_new_order', array( self::class, 'remove_merchant_order_additional_content' ), 20, 3 );
		add_filter( 'woocommerce_locate_template', array( self::class, 'locate_completed_order_template' ), 20, 3 );
		add_action( 'woocommerce_email_sent', array( self::class, 'record_email_delivery' ), 20, 3 );
	}

	/** Successful checkout sends only the final completed receipt to the customer. */
	public static function disable_processing_order_email( $enabled, $order = null, $email = null ): bool {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? false : (bool) $enabled;
	}

	/** The bank requires an electronic confirmation for an unsuccessful outcome too. */
	public static function enable_cancelled_order_email( $enabled, $order = null, $email = null ): bool {
		unset( $order, $email );
		return CommerceConfiguration::is_enabled() ? true : (bool) $enabled;
	}

	/** Keep merchant order notifications away from imported placeholder addresses. */
	public static function new_order_recipient( $recipient, $order = null, $email = null ): string {
		unset( $order, $email );
		if ( ! CommerceConfiguration::is_enabled() ) {
			return sanitize_email( is_scalar( $recipient ) ? (string) $recipient : '' );
		}

		$configured = defined( 'HSE_COMMERCE_ADMIN_EMAIL' ) ? sanitize_email( (string) constant( 'HSE_COMMERCE_ADMIN_EMAIL' ) ) : '';
		return is_email( $configured ) ? $configured : self::DEFAULT_ADMIN_EMAIL;
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
			'emails/customer-completed-order.php',
			'emails/plain/customer-completed-order.php',
			'emails/admin-new-order.php',
			'emails/plain/admin-new-order.php',
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
				'closing_heading'  => 'Radujemo se što ćemo vas podržati tokom vašeg NEBOSH usavršavanja.',
				'next_steps'       => 'Hvala što ste izabrali HSE Training D.O.O. za svoje profesionalne obuke iz bezbednosti i zdravlja na radu.',
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
			'closing_heading'  => 'We look forward to supporting you throughout your NEBOSH journey.',
			'next_steps'       => 'Thank you for choosing HSE Training D.O.O. for your professional health and safety training.',
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
			'view_order'          => 'OTVORI PORUDŽBINU',
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
		if ( true !== $sent || ! in_array( $email_id, self::CUSTOMER_ORDER_EMAILS, true ) || ! is_object( $email ) || ! isset( $email->object ) ) {
			return;
		}

		$order = $email->object;
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) || ! method_exists( $order, 'save' ) ) {
			return;
		}

		$timestamp = gmdate( 'c' );
		$order->update_meta_data( '_hse_customer_email_' . $email_id . '_sent_at', $timestamp );
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
	}

	/** Test the minimum Woo order interface used by receipt filters. */
	private static function is_order( $order ): bool {
		return is_object( $order )
			&& method_exists( $order, 'get_meta' )
			&& method_exists( $order, 'get_order_number' );
	}
}
