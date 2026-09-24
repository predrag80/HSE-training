<?php
/**
 * Branded checkout and order-confirmation presentation.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Owns the temporary checkout shell without modifying WooCommerce templates. */
final class CommercePresentation {
	public const BUYER_TYPE_META         = '_hse_buyer_type';
	public const COMPANY_TAX_ID_META     = '_hse_company_tax_id';
	public const COMPANY_REG_NUMBER_META = '_hse_company_registration_number';

	private const COPY = array(
		'en' => array(
			'page_title'            => 'Secure checkout',
			'checkout_eyebrow'      => 'Course enrolment',
			'checkout_title'        => 'Complete your order.',
			'checkout_intro'        => 'Enter your billing details, review the course and continue to secure card payment.',
			'confirmation_eyebrow'  => 'Order confirmation',
			'confirmation_title'    => 'Your order status.',
			'confirmation_intro'    => 'Review the current payment status and your order details below.',
			'secure'                => 'Secure card payment via RaiAccept',
			'back'                  => 'Back to HSE Training',
			'contact'               => 'Need help? Contact us',
			'privacy'               => 'Privacy Policy',
			'terms'                 => 'Purchase Terms',
			'place_order'           => 'Continue to secure payment',
			'billing_details'       => 'Billing details',
			'buyer_type'            => 'Customer type',
			'buyer_individual'      => 'Individual',
			'buyer_company'         => 'Legal entity / Company',
			'order_review'          => 'Order summary',
			'additional_information' => 'Additional information',
			'product'               => 'Course',
			'subtotal'              => 'Subtotal',
			'total'                 => 'Total',
			'payment_title'         => 'Card payment',
			'payment_description'   => 'Pay securely with an accepted credit or debit card.',
			'have_coupon'           => 'Have a coupon?',
			'coupon_prompt'         => 'Click here to enter your code',
			'coupon_code'           => 'Coupon code',
			'apply_coupon'          => 'Apply coupon',
			'required'              => 'required',
			'optional'              => 'optional',
			'select_option'         => 'Select an option…',
			'first_name'            => 'First name',
			'last_name'             => 'Last name',
			'company'               => 'Company name',
			'tax_id'                => 'Tax identification number (PIB)',
			'registration_number'    => 'Company registration number',
			'company_required'       => 'Enter the company name.',
			'tax_id_required'        => 'Enter the tax identification number.',
			'registration_required'  => 'Enter the company registration number.',
			'tax_id_invalid'         => 'Enter a valid tax identification number.',
			'registration_invalid'   => 'Enter a valid company registration number.',
			'country'               => 'Country / Region',
			'address'               => 'Street address',
			'address_placeholder'   => 'House number and street name',
			'address_2_placeholder' => 'Apartment, suite, unit, etc. (optional)',
			'city'                  => 'Town / City',
			'state'                 => 'Region',
			'postcode'              => 'Postcode / ZIP',
			'phone'                 => 'Phone',
			'email'                 => 'Email address',
			'order_notes'           => 'Order notes',
			'order_notes_hint'      => 'Optional information about your enrolment.',
			'order_number'          => 'Order number',
			'date'                  => 'Date',
			'quantity'              => 'Quantity',
			'payment_method'        => 'Payment method',
			'billing_address'       => 'Billing address',
			'actions'               => 'Actions',
			'pay'                   => 'Pay',
			'cancel'                => 'Cancel',
			'coupon_aria'           => 'Enter your coupon code',
			'update_country'        => 'Update country / region',
			'privacy_notice'        => 'Your personal data will be used to process this order and as described in our <a href="%s">Privacy Policy</a>.',
			'terms_consent'         => 'I have read and agree to the <a href="%s">Purchase Terms</a> and <a href="%s">Privacy Policy</a>.',
			'terms_required'        => 'Please accept the Purchase Terms and Privacy Policy before continuing.',
			'next_steps_title'      => 'What happens next?',
			'next_steps_paid'       => 'Payment has been confirmed. Our team will contact you by email with the next steps for course enrolment.',
			'next_steps_pending'    => 'Your order has been received. We are waiting for the payment provider to confirm the transaction.',
			'next_steps_failed'     => 'Payment was not completed. Please try again or contact us if you need assistance.',
			'order_received_paid'   => 'Thank you. Your payment has been confirmed.',
			'order_received_pending'=> 'Thank you. Your order has been received and payment confirmation is pending.',
			'order_received_failed' => 'Your payment was not completed.',
			'payment_security'      => 'Secure card payments',
			'bank_provider'         => 'Payment provider',
			'accepted_cards'        => 'Accepted cards',
			'secure_authentication' => '3-D Secure authentication',
		),
		'sr' => array(
			'page_title'            => 'Bezbedno plaćanje',
			'checkout_eyebrow'      => 'Prijava za kurs',
			'checkout_title'        => 'Završite porudžbinu.',
			'checkout_intro'        => 'Unesite podatke, proverite izabrani kurs i nastavite na bezbedno kartično plaćanje.',
			'confirmation_eyebrow'  => 'Potvrda porudžbine',
			'confirmation_title'    => 'Status vaše porudžbine.',
			'confirmation_intro'    => 'U nastavku možete proveriti trenutni status plaćanja i podatke o porudžbini.',
			'secure'                => 'Bezbedno kartično plaćanje putem RaiAccept-a',
			'back'                  => 'Nazad na HSE Training',
			'contact'               => 'Potrebna vam je pomoć? Kontaktirajte nas',
			'privacy'               => 'Politika privatnosti',
			'terms'                 => 'Uslovi kupovine',
			'place_order'           => 'Nastavite na bezbedno plaćanje',
			'billing_details'       => 'Podaci o kupcu',
			'buyer_type'            => 'Tip kupca',
			'buyer_individual'      => 'Fizičko lice',
			'buyer_company'         => 'Pravno lice',
			'order_review'          => 'Pregled porudžbine',
			'additional_information' => 'Dodatne informacije',
			'product'               => 'Kurs',
			'subtotal'              => 'Međuzbir',
			'total'                 => 'Ukupno',
			'payment_title'         => 'Plaćanje karticom',
			'payment_description'   => 'Platite bezbedno podržanom kreditnom ili debitnom karticom.',
			'have_coupon'           => 'Imate kupon?',
			'coupon_prompt'         => 'Kliknite ovde da unesete kod',
			'coupon_code'           => 'Kod kupona',
			'apply_coupon'          => 'Primeni kupon',
			'required'              => 'obavezno',
			'optional'              => 'opciono',
			'select_option'         => 'Izaberite opciju…',
			'first_name'            => 'Ime',
			'last_name'             => 'Prezime',
			'company'               => 'Naziv kompanije',
			'tax_id'                => 'PIB',
			'registration_number'    => 'Matični broj',
			'company_required'       => 'Unesite naziv kompanije.',
			'tax_id_required'        => 'Unesite PIB.',
			'registration_required'  => 'Unesite matični broj.',
			'tax_id_invalid'         => 'Unesite ispravan PIB.',
			'registration_invalid'   => 'Unesite ispravan matični broj.',
			'country'               => 'Država / region',
			'address'               => 'Adresa',
			'address_placeholder'   => 'Ulica i broj',
			'address_2_placeholder' => 'Stan, sprat, jedinica i slično (opciono)',
			'city'                  => 'Grad',
			'state'                 => 'Region',
			'postcode'              => 'Poštanski broj',
			'phone'                 => 'Telefon',
			'email'                 => 'Email adresa',
			'order_notes'           => 'Napomena',
			'order_notes_hint'      => 'Opcione informacije u vezi sa prijavom.',
			'order_number'          => 'Broj porudžbine',
			'date'                  => 'Datum',
			'quantity'              => 'Količina',
			'payment_method'        => 'Način plaćanja',
			'billing_address'       => 'Adresa kupca',
			'actions'               => 'Akcije',
			'pay'                   => 'Plati',
			'cancel'                => 'Otkaži',
			'coupon_aria'           => 'Unesite kod kupona',
			'update_country'        => 'Ažurirajte državu / region',
			'privacy_notice'        => 'Vaši lični podaci biće korišćeni za obradu porudžbine i na način opisan u našoj <a href="%s">Politici privatnosti</a>.',
			'terms_consent'         => 'Pročitao/la sam i prihvatam <a href="%s">Uslove kupovine</a> i <a href="%s">Politiku privatnosti</a>.',
			'terms_required'        => 'Pre nastavka prihvatite Uslove kupovine i Politiku privatnosti.',
			'next_steps_title'      => 'Šta sledi?',
			'next_steps_paid'       => 'Plaćanje je potvrđeno. Naš tim će vam poslati email sa sledećim koracima za prijavu na kurs.',
			'next_steps_pending'    => 'Porudžbina je primljena. Čekamo potvrdu transakcije od procesora plaćanja.',
			'next_steps_failed'     => 'Plaćanje nije završeno. Pokušajte ponovo ili nas kontaktirajte ako vam je potrebna pomoć.',
			'order_received_paid'   => 'Hvala. Vaše plaćanje je potvrđeno.',
			'order_received_pending'=> 'Hvala. Porudžbina je primljena i čeka se potvrda plaćanja.',
			'order_received_failed' => 'Plaćanje nije završeno.',
			'payment_security'      => 'Bezbedno kartično plaćanje',
			'bank_provider'         => 'Banka prihvatilac',
			'accepted_cards'        => 'Prihvaćene kartice',
			'secure_authentication' => '3-D Secure zaštita',
		),
	);

	/** Register presentation and validation hooks. */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_coming_soon_exclude', array( self::class, 'exclude_checkout_from_coming_soon' ) );
		add_action( 'template_redirect', array( self::class, 'prepare_classic_checkout' ), -5 );
		add_filter( 'template_include', array( self::class, 'use_checkout_shell' ), PHP_INT_MAX - 1 );
		add_filter( 'the_content', array( self::class, 'render_classic_checkout' ), 8 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_classes' ) );
		add_filter( 'document_title_parts', array( self::class, 'document_title' ) );
		add_filter( 'woocommerce_checkout_fields', array( self::class, 'localize_checkout_fields' ), 20 );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'validate_buyer_fields' ), 20, 2 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'save_buyer_fields' ), 20, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( self::class, 'render_admin_buyer_details' ) );
		add_filter( 'woocommerce_email_customer_details_fields', array( self::class, 'email_customer_details_fields' ), 20, 3 );
		add_filter( 'woocommerce_default_address_fields', array( self::class, 'localize_address_fields' ), 20 );
		add_filter( 'woocommerce_get_country_locale', array( self::class, 'localize_country_locale' ), 20 );
		add_filter( 'woocommerce_order_button_text', array( self::class, 'order_button_text' ) );
		add_filter( 'woocommerce_gateway_title', array( self::class, 'gateway_title' ), 20, 2 );
		add_filter( 'woocommerce_gateway_description', array( self::class, 'gateway_description' ), 20, 2 );
		add_filter( 'woocommerce_get_privacy_policy_text', array( self::class, 'privacy_notice' ), 20, 2 );
		add_action( 'woocommerce_review_order_before_submit', array( self::class, 'render_terms_consent' ), 15 );
		add_action( 'woocommerce_checkout_process', array( self::class, 'validate_terms_consent' ) );
		add_filter( 'woocommerce_thankyou_order_received_text', array( self::class, 'order_received_text' ), 20, 2 );
		add_action( 'woocommerce_thankyou', array( self::class, 'render_next_steps' ), 5 );
		add_filter( 'woocommerce_get_order_item_totals', array( self::class, 'localize_order_totals' ), 20, 3 );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( self::class, 'localize_order_actions' ), 20, 2 );
		add_filter( 'wp_date', array( self::class, 'localize_order_date' ), 20, 4 );
		add_filter( 'woocommerce_currency_symbol', array( self::class, 'localize_currency_symbol' ), 20, 2 );
		add_filter( 'wc_price_args', array( self::class, 'localize_price_format' ), 20, 1 );
		add_filter( 'gettext', array( self::class, 'translate_checkout_string' ), 20, 3 );
	}

	/** The explicitly enabled checkout must not be obscured by Woo store visibility. */
	public static function exclude_checkout_from_coming_soon( $excluded ): bool {
		return self::is_checkout_request() ? true : (bool) $excluded;
	}

	/** Prevent the stored Checkout Block from enqueueing unused block assets. */
	public static function prepare_classic_checkout(): void {
		if ( ! self::is_checkout_request() ) {
			return;
		}

		global $post;
		if ( $post instanceof \WP_Post ) {
			$post->post_content = '[woocommerce_checkout]';
		}
	}

	/** Return one escaped-at-output copy value for the current language. */
	public static function copy( string $key ): string {
		return self::copy_for_locale( $key, CommerceLocale::current() );
	}

	/** Return one copy value for an explicit allowlisted language. */
	public static function copy_for_locale( string $key, string $locale ): string {
		$locale = CommerceLocale::sanitize( $locale );
		return isset( self::COPY[ $locale ][ $key ] ) ? self::COPY[ $locale ][ $key ] : '';
	}

	/** Return the public Astro route for the current checkout locale. */
	public static function public_url( string $path = '/' ): string {
		$base = CommerceConfiguration::public_site_url();
		$path = '/' . ltrim( $path, '/' );
		if ( 'sr' === CommerceLocale::current() && 0 !== strpos( $path, '/sr/' ) ) {
			$path = '/sr' . $path;
		}
		return untrailingslashit( $base ) . $path;
	}

	/** Report whether the current request is an order-confirmation endpoint. */
	public static function is_confirmation(): bool {
		return function_exists( 'is_order_received_page' ) && is_order_received_page();
	}

	/** Replace the active theme with the stable, plugin-owned commerce shell. */
	public static function use_checkout_shell( $template ) {
		if ( self::is_checkout_request() ) {
			return dirname( __DIR__, 2 ) . '/templates/commerce-checkout.php';
		}
		return $template;
	}

	/** Force the classic renderer so the checkout is fully server-localizable. */
	public static function render_classic_checkout( $content ) {
		if ( self::is_checkout_request() && in_the_loop() && is_main_query() ) {
			return '[woocommerce_checkout]';
		}
		return $content;
	}

	/** Load only the assets required by the isolated checkout surface. */
	public static function enqueue_assets(): void {
		if ( ! self::is_checkout_request() ) {
			return;
		}

		$plugin_url = plugin_dir_url( dirname( __DIR__, 2 ) . '/hse-headless.php' );
		wp_enqueue_style( 'hse-commerce', $plugin_url . 'assets/commerce.css', array( 'woocommerce-layout', 'woocommerce-general' ), '0.25.0' );

		$buyer_fields_script = <<<'JS'
(function () {
	function updateCompanyFields() {
		var selected = document.querySelector('input[name="billing_customer_type"]:checked');
		var isCompany = selected && selected.value === 'company';
		['billing_company_field', 'billing_pib_field', 'billing_registration_number_field'].forEach(function (id) {
			var row = document.getElementById(id);
			if (!row) return;
			row.hidden = !isCompany;
			row.setAttribute('aria-hidden', isCompany ? 'false' : 'true');
			var input = row.querySelector('input');
			if (input) {
				input.required = Boolean(isCompany);
				input.setAttribute('aria-required', isCompany ? 'true' : 'false');
			}
		});
	}

	document.addEventListener('DOMContentLoaded', updateCompanyFields);
	document.addEventListener('change', function (event) {
		if (event.target && event.target.name === 'billing_customer_type') updateCompanyFields();
	});
	if (window.jQuery) window.jQuery(document.body).on('updated_checkout', updateCompanyFields);
})();
JS;
		wp_add_inline_script( 'wc-checkout', $buyer_fields_script, 'after' );
	}

	/** Add stable page classes for responsive commerce styling. */
	public static function body_classes( array $classes ): array {
		if ( self::is_checkout_request() ) {
			$classes[] = 'hse-commerce';
			$classes[] = 'hse-commerce--' . CommerceLocale::current();
			$classes[] = self::is_confirmation() ? 'hse-commerce--confirmation' : 'hse-commerce--checkout';
		}
		return $classes;
	}

	/** Set a localized browser title without changing the WordPress Checkout page. */
	public static function document_title( array $parts ): array {
		if ( self::is_checkout_request() ) {
			$parts['title'] = self::is_confirmation() ? self::copy( 'confirmation_title' ) : self::copy( 'page_title' );
			$parts['site']  = 'HSE Training';
		}
		return $parts;
	}

	/** Apply reviewed Serbian-Latin labels while preserving Woo field behavior. */
	public static function localize_checkout_fields( array $fields ): array {
		if ( ! self::is_checkout_request() ) {
			return $fields;
		}

		$posted_type = isset( $_POST['billing_customer_type'] )
			? self::sanitize_buyer_type( wp_unslash( $_POST['billing_customer_type'] ) )
			: 'individual';
		$fields['billing']['billing_customer_type'] = array(
			'type'     => 'radio',
			'label'    => self::copy( 'buyer_type' ),
			'required' => true,
			'options'  => array(
				'individual' => self::copy( 'buyer_individual' ),
				'company'    => self::copy( 'buyer_company' ),
			),
			'default'  => $posted_type,
			'priority' => 5,
			'class'    => array( 'form-row-wide', 'hse-buyer-type-field' ),
		);

		$labels = array(
			'billing_first_name' => array( 'first_name', '' ),
			'billing_last_name'  => array( 'last_name', '' ),
			'billing_company'    => array( 'company', '' ),
			'billing_country'    => array( 'country', '' ),
			'billing_address_1'  => array( 'address', 'address_placeholder' ),
			'billing_address_2'  => array( '', 'address_2_placeholder' ),
			'billing_city'       => array( 'city', '' ),
			'billing_state'      => array( 'state', '' ),
			'billing_postcode'   => array( 'postcode', '' ),
			'billing_phone'      => array( 'phone', '' ),
			'billing_email'      => array( 'email', '' ),
		);

		foreach ( $labels as $field_key => $copy_keys ) {
			if ( isset( $fields['billing'][ $field_key ] ) ) {
				if ( '' !== $copy_keys[0] ) {
					$fields['billing'][ $field_key ]['label'] = self::copy( $copy_keys[0] );
				}
				if ( '' !== $copy_keys[1] ) {
					$fields['billing'][ $field_key ]['placeholder'] = self::copy( $copy_keys[1] );
				}
			}
		}

		$fields['billing']['billing_company'] = array_merge(
			$fields['billing']['billing_company'] ?? array(),
			array(
				'type'         => 'text',
				'label'        => self::copy( 'company' ),
				'required'     => false,
				'priority'     => 30,
				'class'        => array( 'form-row-wide', 'hse-company-field' ),
				'autocomplete' => 'organization',
			)
		);

		$fields['billing']['billing_pib'] = array(
			'type'              => 'text',
			'label'             => self::copy( 'tax_id' ),
			'required'          => false,
			'priority'          => 31,
			'class'             => array( 'form-row-first', 'hse-company-field' ),
			'custom_attributes' => array( 'maxlength' => '32' ),
		);
		$fields['billing']['billing_registration_number'] = array(
			'type'              => 'text',
			'label'             => self::copy( 'registration_number' ),
			'required'          => false,
			'priority'          => 32,
			'class'             => array( 'form-row-last', 'hse-company-field' ),
			'custom_attributes' => array( 'maxlength' => '32' ),
		);

		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = self::copy( 'order_notes' );
			$fields['order']['order_comments']['placeholder'] = self::copy( 'order_notes_hint' );
		}

		return $fields;
	}

	/** Restrict the checkout buyer type to the two supported values. */
	public static function sanitize_buyer_type( $value ): string {
		return 'company' === sanitize_key( is_scalar( $value ) ? (string) $value : '' ) ? 'company' : 'individual';
	}

	/** Return validation messages for conditional legal-entity fields. */
	public static function validate_buyer_data( array $data ): array {
		if ( 'company' !== self::sanitize_buyer_type( $data['billing_customer_type'] ?? '' ) ) {
			return array();
		}

		$errors       = array();
		$company      = trim( sanitize_text_field( (string) ( $data['billing_company'] ?? '' ) ) );
		$tax_id       = trim( sanitize_text_field( (string) ( $data['billing_pib'] ?? '' ) ) );
		$registration = trim( sanitize_text_field( (string) ( $data['billing_registration_number'] ?? '' ) ) );
		$country      = strtoupper( sanitize_key( (string) ( $data['billing_country'] ?? '' ) ) );

		if ( '' === $company ) {
			$errors['billing_company_required'] = self::copy( 'company_required' );
		}
		if ( '' === $tax_id ) {
			$errors['billing_pib_required'] = self::copy( 'tax_id_required' );
		} elseif ( 'RS' === $country ? ! preg_match( '/^\d{9}$/', $tax_id ) : ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9 .\/-]{4,31}$/', $tax_id ) ) {
			$errors['billing_pib_invalid'] = self::copy( 'tax_id_invalid' );
		}
		if ( '' === $registration ) {
			$errors['billing_registration_number_required'] = self::copy( 'registration_required' );
		} elseif ( 'RS' === $country ? ! preg_match( '/^\d{8}$/', $registration ) : ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9 .\/-]{4,31}$/', $registration ) ) {
			$errors['billing_registration_number_invalid'] = self::copy( 'registration_invalid' );
		}

		return $errors;
	}

	/** Enforce business identity fields server-side when legal entity is selected. */
	public static function validate_buyer_fields( array $data, $errors ): void {
		if ( ! is_object( $errors ) || ! method_exists( $errors, 'add' ) ) {
			return;
		}
		foreach ( self::validate_buyer_data( $data ) as $code => $message ) {
			$errors->add( $code, $message );
		}
	}

	/** Save the normalized buyer identity on the immutable WooCommerce order. */
	public static function save_buyer_fields( $order, array $data ): void {
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return;
		}

		$type = self::sanitize_buyer_type( $data['billing_customer_type'] ?? '' );
		$order->update_meta_data( self::BUYER_TYPE_META, $type );
		if ( 'company' === $type ) {
			$order->update_meta_data( self::COMPANY_TAX_ID_META, sanitize_text_field( (string) ( $data['billing_pib'] ?? '' ) ) );
			$order->update_meta_data( self::COMPANY_REG_NUMBER_META, sanitize_text_field( (string) ( $data['billing_registration_number'] ?? '' ) ) );
			return;
		}

		if ( method_exists( $order, 'set_billing_company' ) ) {
			$order->set_billing_company( '' );
		}
		if ( method_exists( $order, 'delete_meta_data' ) ) {
			$order->delete_meta_data( self::COMPANY_TAX_ID_META );
			$order->delete_meta_data( self::COMPANY_REG_NUMBER_META );
		}
	}

	/** Return localized buyer details for emails and administration. */
	public static function order_buyer_details( $order, string $locale ): array {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return array();
		}

		$stored_type = trim( (string) $order->get_meta( self::BUYER_TYPE_META, true ) );
		if (
			'' === $stored_type
			&& method_exists( $order, 'get_billing_company' )
			&& '' !== trim( (string) $order->get_billing_company() )
		) {
			$stored_type = 'company';
		}
		$type    = self::sanitize_buyer_type( $stored_type );
		$details = array(
			'buyer_type' => array(
				'label' => self::copy_for_locale( 'buyer_type', $locale ),
				'value' => self::copy_for_locale( 'company' === $type ? 'buyer_company' : 'buyer_individual', $locale ),
			),
		);
		if ( 'company' !== $type ) {
			return $details;
		}

		$tax_id       = sanitize_text_field( (string) $order->get_meta( self::COMPANY_TAX_ID_META, true ) );
		$registration = sanitize_text_field( (string) $order->get_meta( self::COMPANY_REG_NUMBER_META, true ) );
		if ( '' !== $tax_id ) {
			$details['company_tax_id'] = array( 'label' => self::copy_for_locale( 'tax_id', $locale ), 'value' => $tax_id );
		}
		if ( '' !== $registration ) {
			$details['company_registration_number'] = array( 'label' => self::copy_for_locale( 'registration_number', $locale ), 'value' => $registration );
		}
		return $details;
	}

	/** Show buyer identity data in the WooCommerce order editor. */
	public static function render_admin_buyer_details( $order ): void {
		$locale = method_exists( $order, 'get_meta' ) ? CommerceLocale::sanitize( $order->get_meta( CommerceLocale::ORDER_META, true ) ) : 'en';
		foreach ( self::order_buyer_details( $order, $locale ) as $detail ) {
			echo '<p><strong>' . esc_html( $detail['label'] ) . ':</strong> ' . esc_html( $detail['value'] ) . '</p>';
		}
	}

	/** Add buyer identity data to WooCommerce's standard merchant emails. */
	public static function email_customer_details_fields( array $fields, $sent_to_admin, $order ): array {
		unset( $sent_to_admin );
		$locale = method_exists( $order, 'get_meta' ) ? CommerceLocale::sanitize( $order->get_meta( CommerceLocale::ORDER_META, true ) ) : 'en';
		return array_merge( $fields, self::order_buyer_details( $order, $locale ) );
	}

	/** Localize the address data also used by Woo's country-change JavaScript. */
	public static function localize_address_fields( array $fields ): array {
		if ( ! self::is_checkout_request() ) {
			return $fields;
		}

		$labels = array(
			'first_name' => array( 'first_name', '' ),
			'last_name'  => array( 'last_name', '' ),
			'company'    => array( 'company', '' ),
			'country'    => array( 'country', '' ),
			'address_1'  => array( 'address', 'address_placeholder' ),
			'address_2'  => array( '', 'address_2_placeholder' ),
			'city'       => array( 'city', '' ),
			'state'      => array( 'state', '' ),
			'postcode'   => array( 'postcode', '' ),
			'phone'      => array( 'phone', '' ),
		);

		foreach ( $labels as $field_key => $copy_keys ) {
			if ( isset( $fields[ $field_key ] ) ) {
				if ( '' !== $copy_keys[0] ) {
					$fields[ $field_key ]['label'] = self::copy( $copy_keys[0] );
				}
				if ( '' !== $copy_keys[1] ) {
					$fields[ $field_key ]['placeholder'] = self::copy( $copy_keys[1] );
				}
			}
		}

		return $fields;
	}

	/** Override country-specific labels that would otherwise replace localized defaults. */
	public static function localize_country_locale( array $locales ): array {
		if ( ! self::is_checkout_request() ) {
			return $locales;
		}

		foreach ( $locales as $country => $fields ) {
			foreach ( array( 'city' => 'city', 'state' => 'state', 'postcode' => 'postcode' ) as $field => $copy_key ) {
				if ( isset( $fields[ $field ]['label'] ) ) {
					$locales[ $country ][ $field ]['label'] = self::copy( $copy_key );
				}
			}
		}
		return $locales;
	}

	/** Localize the final action without modifying the gateway request. */
	public static function order_button_text(): string {
		return self::copy( 'place_order' );
	}

	/** Localize the configured RaiAccept label on this isolated surface. */
	public static function gateway_title( $title, $gateway_id ) {
		return self::is_checkout_request() && 'raiaccept' === $gateway_id ? self::copy( 'payment_title' ) : $title;
	}

	/** Localize the RaiAccept helper text without changing gateway behavior. */
	public static function gateway_description( $description, $gateway_id ) {
		return self::is_checkout_request() && 'raiaccept' === $gateway_id ? self::copy( 'payment_description' ) : $description;
	}

	/** Link the data-use notice to the public Astro legal page. */
	public static function privacy_notice( $text, $type ) {
		if ( self::is_checkout_request() && 'checkout' === $type ) {
			return sprintf( self::copy( 'privacy_notice' ), esc_url( self::public_url( '/privacy-policy/' ) ) );
		}
		return $text;
	}

	/** Render the required legal acknowledgement against public legal pages. */
	public static function render_terms_consent(): void {
		if ( self::is_confirmation() ) {
			return;
		}

		$checked = isset( $_POST['hse_terms_consent'] );
		$text    = sprintf(
			self::copy( 'terms_consent' ),
			esc_url( self::public_url( '/terms-and-conditions/' ) ),
			esc_url( self::public_url( '/privacy-policy/' ) )
		);
		?>
		<p class="form-row validate-required hse-commerce__consent">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="hse_terms_consent">
				<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="hse_terms_consent" id="hse_terms_consent" value="1" <?php checked( $checked ); ?> required />
				<span><?php echo wp_kses_post( $text ); ?></span>
				<abbr class="required" title="required">*</abbr>
			</label>
		</p>
		<?php
	}

	/** Reject checkout server-side when the legal acknowledgement is missing. */
	public static function validate_terms_consent(): void {
		if ( ! isset( $_POST['hse_terms_consent'] ) ) {
			wc_add_notice( self::copy( 'terms_required' ), 'error' );
		}
	}

	/** Keep the return page accurate if the provider notification is still pending. */
	public static function order_received_text( $text, $order ) {
		return self::copy( 'order_received_' . self::order_state( $order ) );
	}

	/** Explain the operational next step without implying LMS access is automatic. */
	public static function render_next_steps( $order_id ): void {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
		if ( ! $order ) {
			return;
		}

		$state = self::order_state( $order );
		?>
		<section class="hse-commerce__next-steps hse-commerce__next-steps--<?php echo esc_attr( $state ); ?>">
			<h2><?php echo esc_html( self::copy( 'next_steps_title' ) ); ?></h2>
			<p><?php echo esc_html( self::copy( 'next_steps_' . $state ) ); ?></p>
		</section>
		<?php
	}

	/** Localize generated order-total labels that do not pass through gettext. */
	public static function localize_order_totals( array $totals, $order, $tax_display ): array {
		if ( ! self::is_confirmation() || 'sr' !== CommerceLocale::current() ) {
			return $totals;
		}

		$labels = array(
			'cart_subtotal'  => self::copy( 'subtotal' ) . ':',
			'order_total'    => self::copy( 'total' ) . ':',
			'payment_method' => self::copy( 'payment_method' ) . ':',
		);

		foreach ( $labels as $key => $label ) {
			if ( isset( $totals[ $key ] ) ) {
				$totals[ $key ]['label'] = $label;
			}
		}

		return $totals;
	}

	/** Localize the optional actions shown while an order is still pending. */
	public static function localize_order_actions( array $actions, $order ): array {
		if ( ! self::is_confirmation() ) {
			return $actions;
		}

		foreach ( array( 'pay' => 'pay', 'cancel' => 'cancel' ) as $action => $copy_key ) {
			if ( isset( $actions[ $action ] ) ) {
				$actions[ $action ]['name'] = self::copy( $copy_key );
			}
		}

		return $actions;
	}

	/** Present the order date in an unambiguous Serbian format. */
	public static function localize_order_date( $date, $format, $timestamp, $timezone ) {
		if ( ! self::is_confirmation() || 'sr' !== CommerceLocale::current() || ! function_exists( 'wc_date_format' ) || wc_date_format() !== $format ) {
			return $date;
		}

		$zone = $timezone instanceof \DateTimeZone ? $timezone : wp_timezone();
		$date = new \DateTimeImmutable( '@' . (int) $timestamp );
		return $date->setTimezone( $zone )->format( 'd.m.Y.' );
	}

	/** Keep RSD unambiguous and format the amount for the selected checkout language. */
	public static function localize_currency_symbol( $symbol, $currency ) {
		if ( CommerceConfiguration::is_enabled() && 'RSD' === strtoupper( (string) $currency ) ) {
			return 'RSD';
		}
		return $symbol;
	}

	/** Use reviewed English and Serbian-Latin separators on the isolated commerce flow. */
	public static function localize_price_format( array $args ): array {
		if ( ! self::is_checkout_request() ) {
			return $args;
		}

		$args['price_format'] = '%2$s %1$s';
		if ( 'sr' === CommerceLocale::current() ) {
			$args['decimal_separator']  = ',';
			$args['thousand_separator'] = '.';
		} else {
			$args['decimal_separator']  = '.';
			$args['thousand_separator'] = ',';
		}
		return $args;
	}

	/** Translate the small set of classic Woo headings owned by this page. */
	public static function translate_checkout_string( $translation, $text, $domain ) {
		if ( 'woocommerce' !== $domain || ! self::is_checkout_request() ) {
			return $translation;
		}

		$strings = array(
			'Billing details' => 'billing_details',
			'Your order'      => 'order_review',
			'Order details'   => 'order_review',
			'Additional information' => 'additional_information',
			'Product'         => 'product',
			'Subtotal'        => 'subtotal',
			'Total'           => 'total',
			'Total:'          => 'total',
			'Have a coupon?'  => 'have_coupon',
			'Click here to enter your code' => 'coupon_prompt',
			'Coupon code'     => 'coupon_code',
			'Apply coupon'    => 'apply_coupon',
			'Enter your coupon code' => 'coupon_aria',
			'Update country / region' => 'update_country',
			'required'        => 'required',
			'optional'        => 'optional',
			'Select an option…' => 'select_option',
			'Order number:'   => 'order_number',
			'Date:'           => 'date',
			'Email:'          => 'email',
			'Payment method:' => 'payment_method',
			'Quantity'        => 'quantity',
			'Billing address' => 'billing_address',
			'Actions'         => 'actions',
			'Pay'             => 'pay',
			'Cancel'          => 'cancel',
		);
		return isset( $strings[ $text ] ) ? self::copy( $strings[ $text ] ) : $translation;
	}

	/** Map Woo statuses to honest customer-facing confirmation states. */
	private static function order_state( $order ): string {
		if ( is_object( $order ) && method_exists( $order, 'has_status' ) ) {
			if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
				return 'paid';
			}
			if ( $order->has_status( array( 'failed', 'cancelled', 'refunded' ) ) ) {
				return 'failed';
			}
		}
		return 'pending';
	}

	/** Limit theme replacement and translations to the enabled checkout exception. */
	private static function is_checkout_request(): bool {
		if ( ! CommerceConfiguration::is_enabled() ) {
			return false;
		}

		return CommerceLocale::is_checkout_ajax() || ( function_exists( 'is_checkout' ) && is_checkout() );
	}
}
