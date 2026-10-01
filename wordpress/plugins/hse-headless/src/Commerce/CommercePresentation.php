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
	public const BUYER_TYPE_META              = '_hse_buyer_type';
	public const COMPANY_TAX_ID_META          = '_hse_company_tax_id';
	public const COMPANY_REG_NUMBER_META      = '_hse_company_registration_number';
	public const DIGITAL_CONSENT_META         = '_hse_digital_delivery_consent';
	public const DIGITAL_CONSENT_AT_META      = '_hse_digital_delivery_consent_at';
	public const DIGITAL_CONSENT_VERSION_META = '_hse_digital_delivery_consent_version';
	public const DIGITAL_CONSENT_VERSION      = '2026-09-29';
	private const BOKAPOS_BUYER_ID_META       = '_bokapos_buyer_id';

	private const VALIDATION_COPY_KEYS = array(
		'' => array(
			'The following problems were found:' => 'validation_summary',
			'%s is a required field.' => 'validation_required',
			"'%s' is not a valid country code." => 'validation_country',
			'%1$s is not valid. You can look up the correct Eircode <a target="_blank" href="%2$s">here</a>.' => 'validation_eircode',
			'%s is not a valid postcode / ZIP.' => 'validation_postcode',
			'%s is not a valid phone number.' => 'validation_phone',
			'%s is not a valid email address.' => 'validation_email',
			'%1$s is not valid. Please enter one of the following: %2$s' => 'validation_state',
			'Please enter an address to continue.' => 'validation_address',
		),
		'checkout-validation' => array(
			'Billing %s'  => 'validation_billing_field',
			'Shipping %s' => 'validation_shipping_field',
		),
	);

	private const COPY = array(
		'en' => array(
			'page_title'            => 'Secure checkout',
			'retry_page_title'      => 'Retry payment',
			'checkout_eyebrow'      => 'Course enrolment',
			'checkout_title'        => 'Complete your order.',
			'checkout_intro'        => 'Enter your billing details, review the course and choose your preferred payment method.',
			'retry_eyebrow'         => 'Secure payment retry',
			'retry_title'           => 'Retry your payment.',
			'retry_intro'           => 'Review the existing order, confirm the payment method and continue to the secure RaiAccept payment page.',
			'retry_action'          => 'Continue to secure payment',
			'retry_review'          => 'Review your order',
			'retry_payment'         => 'Choose payment method',
			'retry_start_failed'    => 'A new secure payment attempt could not be started. Please try again.',
			'confirmation_eyebrow'  => 'Order confirmation',
			'confirmation_title'    => 'Your order status.',
			'confirmation_intro'    => 'Review the current payment status and your order details below.',
			'confirmation_title_paid' => 'Payment confirmed.',
			'confirmation_intro_paid' => 'Your order is complete. We have sent the payment confirmation and the next course-access steps to your email address.',
			'confirmation_title_pending' => 'Order received.',
			'confirmation_intro_pending' => 'We have received your order. Payment confirmation is still pending and the current instructions are shown below.',
			'confirmation_title_failed' => 'Payment not completed.',
			'confirmation_intro_failed' => 'No successful payment has been confirmed. Review the status below or contact us if you need assistance.',
			'secure'                => 'Secure checkout',
			'back'                  => 'Back to HSE Training',
			'contact'               => 'Need help? Contact us',
			'privacy'               => 'Privacy Policy',
			'terms'                 => 'Purchase Terms',
			'withdrawal_form'       => 'Withdrawal form',
			'place_order'           => 'Order and pay',
			'place_order_card'      => 'Order and pay',
			'place_order_bank'      => 'Place order with obligation to pay',
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
			'bank_transfer_title'   => 'Direct bank transfer',
			'bank_transfer_description' => 'Pay directly into our bank account. Payment details will be shown after the order is placed and sent by email.',
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
			'foreign_tax_id'        => 'Tax/VAT identification number',
			'registration_number'    => 'Company registration number',
			'company_required'       => 'Enter the company name.',
			'tax_id_required'        => 'Enter the tax identification number.',
			'registration_required'  => 'Enter the company registration number.',
			'tax_id_invalid'         => 'Enter a valid Serbian PIB with nine digits and a valid check digit.',
			'foreign_tax_id_invalid' => 'Enter a valid Tax/VAT identification number.',
			'registration_invalid'   => 'Enter a valid company registration number.',
			'validation_summary'      => 'The following problems were found:',
			'validation_required'     => '%s is a required field.',
			'validation_country'      => "'%s' is not a valid country code.",
			'validation_eircode'      => '%1$s is not valid. You can look up the correct Eircode <a target="_blank" href="%2$s">here</a>.',
			'validation_postcode'     => '%s is not a valid postcode / ZIP.',
			'validation_phone'        => '%s is not a valid phone number.',
			'validation_email'        => '%s is not a valid email address.',
			'validation_state'        => '%1$s is not valid. Please enter one of the following: %2$s',
			'validation_address'      => 'Please enter an address to continue.',
			'validation_billing_field' => 'Billing %s',
			'validation_shipping_field' => 'Shipping %s',
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
			'bank_name'             => 'Bank',
			'account_number'        => 'Account number',
			'iban'                  => 'IBAN',
			'swift_bic'             => 'SWIFT/BIC',
			'billing_address'       => 'Billing address',
			'actions'               => 'Actions',
			'pay'                   => 'Pay',
			'cancel'                => 'Cancel',
			'coupon_aria'           => 'Enter your coupon code',
			'update_country'        => 'Update country / region',
			'privacy_notice'        => 'Your personal data will be used to process this order and as described in our <a href="%s">Privacy Policy</a>.',
			'terms_consent'         => 'I have read and agree to the <a href="%s">Purchase Terms</a> and <a href="%s">Privacy Policy</a>.',
			'checkout_confirmations' => 'Review and confirm',
			'checkout_confirmations_help' => 'Both confirmations are required before you can place the order.',
			'terms_consent_title'   => 'Terms and privacy',
			'terms_required'        => 'Please accept the Purchase Terms and Privacy Policy before continuing.',
			'digital_consent_title' => 'Immediate course access',
			'digital_consent'       => 'I expressly request immediate delivery of the digital course after confirmed payment. I understand that once digital delivery begins, I lose the right to withdraw to the extent provided by applicable law.',
			'digital_consent_required' => 'Please confirm that you request immediate digital delivery and acknowledge the effect on your right to withdraw.',
			'digital_consent_admin' => 'Immediate digital delivery consent',
			'digital_consent_yes'   => 'Accepted',
			'digital_consent_time'  => 'Consent recorded at',
			'digital_consent_version' => 'Consent text version',
			'next_steps_title'      => 'What happens next?',
			'next_steps_paid'       => 'Payment has been confirmed. Follow the instructions sent by email to create your account on the HSE e-learning platform and begin the course immediately.',
			'next_steps_pending'    => 'Your order has been received. We are waiting for the payment provider to confirm the transaction.',
			'next_steps_failed'     => 'Payment was not completed. Please try again or contact us if you need assistance.',
			'next_step_paid_1_title' => 'Check your inbox',
			'next_step_paid_1_text' => 'Your confirmation and fiscal receipt are sent to the email address entered at checkout.',
			'next_step_paid_2_title' => 'Create your learning account',
			'next_step_paid_2_text' => 'Follow the supplied instructions to create your account on the HSE e-learning platform.',
			'next_step_paid_3_title' => 'Start your course',
			'next_step_paid_3_text' => 'Course access begins immediately and remains available for the stated access period.',
			'next_step_pending_1_title' => 'Keep your order number',
			'next_step_pending_1_text' => 'Use it whenever you contact HSE Training about this order.',
			'next_step_pending_2_title' => 'Follow the payment instructions',
			'next_step_pending_2_text' => 'Complete the required payment step or wait for the payment provider confirmation.',
			'next_step_pending_3_title' => 'Watch your inbox',
			'next_step_pending_3_text' => 'We will email you when payment is confirmed and the course can be accessed.',
			'next_step_failed_1_title' => 'Review the payment status',
			'next_step_failed_1_text' => 'No completed charge has been confirmed for this order.',
			'next_step_failed_2_title' => 'Try again safely',
			'next_step_failed_2_text' => 'Return to the course page when you are ready to start a new payment attempt.',
			'next_step_failed_3_title' => 'Contact support',
			'next_step_failed_3_text' => 'Contact HSE Training if you need help before trying again.',
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
			'retry_page_title'      => 'Ponovno plaćanje',
			'checkout_eyebrow'      => 'Prijava za kurs',
			'checkout_title'        => 'Završite porudžbinu.',
			'checkout_intro'        => 'Unesite podatke, proverite izabrani kurs i izaberite željeni način plaćanja.',
			'retry_eyebrow'         => 'Bezbedan ponovni pokušaj',
			'retry_title'           => 'Ponovite plaćanje.',
			'retry_intro'           => 'Proverite postojeću porudžbinu, potvrdite način plaćanja i nastavite na bezbednu RaiAccept stranicu.',
			'retry_action'          => 'Nastavite na bezbedno plaćanje',
			'retry_review'          => 'Proverite porudžbinu',
			'retry_payment'         => 'Izaberite način plaćanja',
			'retry_start_failed'    => 'Novi bezbedan pokušaj plaćanja nije mogao da bude pokrenut. Pokušajte ponovo.',
			'confirmation_eyebrow'  => 'Potvrda porudžbine',
			'confirmation_title'    => 'Status vaše porudžbine.',
			'confirmation_intro'    => 'U nastavku možete proveriti trenutni status plaćanja i podatke o porudžbini.',
			'confirmation_title_paid' => 'Plaćanje je potvrđeno.',
			'confirmation_intro_paid' => 'Porudžbina je završena. Potvrdu plaćanja i naredne korake za pristup kursu poslali smo na vašu email adresu.',
			'confirmation_title_pending' => 'Porudžbina je primljena.',
			'confirmation_intro_pending' => 'Primili smo porudžbinu. Potvrda plaćanja se još čeka, a trenutna uputstva nalaze se u nastavku.',
			'confirmation_title_failed' => 'Plaćanje nije završeno.',
			'confirmation_intro_failed' => 'Uspešno plaćanje nije potvrđeno. Proverite status u nastavku ili nas kontaktirajte ako vam je potrebna pomoć.',
			'secure'                => 'Bezbedna kupovina',
			'back'                  => 'Nazad na HSE Training',
			'contact'               => 'Potrebna vam je pomoć? Kontaktirajte nas',
			'privacy'               => 'Politika privatnosti',
			'terms'                 => 'Uslovi kupovine',
			'withdrawal_form'       => 'Obrazac za odustanak',
			'place_order'           => 'Poručite i platite',
			'place_order_card'      => 'Poručite i platite',
			'place_order_bank'      => 'Potvrdite porudžbinu sa obavezom plaćanja',
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
			'bank_transfer_title'   => 'Direktna uplata na račun',
			'bank_transfer_description' => 'Uplatite direktno na naš bankovni račun. Podaci za uplatu biće prikazani nakon kreiranja porudžbine i poslati emailom.',
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
			'foreign_tax_id'        => 'Poreski/VAT identifikacioni broj',
			'registration_number'    => 'Matični broj',
			'company_required'       => 'Unesite naziv kompanije.',
			'tax_id_required'        => 'Unesite PIB.',
			'registration_required'  => 'Unesite matični broj.',
			'tax_id_invalid'         => 'Unesite ispravan PIB sa devet cifara i važećom kontrolnom cifrom.',
			'foreign_tax_id_invalid' => 'Unesite ispravan poreski/VAT identifikacioni broj.',
			'registration_invalid'   => 'Unesite ispravan matični broj.',
			'validation_summary'      => 'Pronađeni su sledeći problemi:',
			'validation_required'     => 'Polje %s je obavezno.',
			'validation_country'      => '„%s“ nije ispravan kod države.',
			'validation_eircode'      => '%1$s nije ispravno. Ispravan Eircode možete pronaći <a target="_blank" href="%2$s">ovde</a>.',
			'validation_postcode'     => '%s nije ispravan poštanski broj.',
			'validation_phone'        => '%s nije ispravan broj telefona.',
			'validation_email'        => '%s nije ispravna email adresa.',
			'validation_state'        => '%1$s nije ispravno. Unesite jednu od sledećih vrednosti: %2$s',
			'validation_address'      => 'Unesite adresu da biste nastavili.',
			'validation_billing_field' => '%s',
			'validation_shipping_field' => 'Adresa za dostavu — %s',
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
			'bank_name'             => 'Banka',
			'account_number'        => 'Broj računa',
			'iban'                  => 'IBAN',
			'swift_bic'             => 'SWIFT/BIC',
			'billing_address'       => 'Adresa kupca',
			'actions'               => 'Akcije',
			'pay'                   => 'Plati',
			'cancel'                => 'Otkaži',
			'coupon_aria'           => 'Unesite kod kupona',
			'update_country'        => 'Ažurirajte državu / region',
			'privacy_notice'        => 'Vaši lični podaci biće korišćeni za obradu porudžbine i na način opisan u našoj <a href="%s">Politici privatnosti</a>.',
			'terms_consent'         => 'Pročitao/la sam i prihvatam <a href="%s">Uslove kupovine</a> i <a href="%s">Politiku privatnosti</a>.',
			'checkout_confirmations' => 'Proverite i potvrdite',
			'checkout_confirmations_help' => 'Obe potvrde su obavezne pre slanja porudžbine.',
			'terms_consent_title'   => 'Uslovi i privatnost',
			'terms_required'        => 'Pre nastavka prihvatite Uslove kupovine i Politiku privatnosti.',
			'digital_consent_title' => 'Trenutni pristup kursu',
			'digital_consent'       => 'Izričito zahtevam da isporuka digitalnog kursa počne odmah nakon potvrđenog plaćanja. Razumem da početkom digitalne isporuke gubim pravo na odustanak u meri propisanoj važećim zakonom.',
			'digital_consent_required' => 'Potvrdite da zahtevate trenutnu digitalnu isporuku i da razumete njen uticaj na pravo na odustanak.',
			'digital_consent_admin' => 'Saglasnost za trenutnu digitalnu isporuku',
			'digital_consent_yes'   => 'Prihvaćena',
			'digital_consent_time'  => 'Vreme evidentiranja saglasnosti',
			'digital_consent_version' => 'Verzija teksta saglasnosti',
			'next_steps_title'      => 'Šta sledi?',
			'next_steps_paid'       => 'Plaćanje je potvrđeno. Pratite uputstvo poslato emailom, kreirajte nalog na HSE e-learning platformi i odmah započnite kurs.',
			'next_steps_pending'    => 'Porudžbina je primljena. Čekamo potvrdu transakcije od procesora plaćanja.',
			'next_steps_failed'     => 'Plaćanje nije završeno. Pokušajte ponovo ili nas kontaktirajte ako vam je potrebna pomoć.',
			'next_step_paid_1_title' => 'Proverite email',
			'next_step_paid_1_text' => 'Potvrda i fiskalni račun šalju se na email adresu unetu prilikom kupovine.',
			'next_step_paid_2_title' => 'Kreirajte nalog za učenje',
			'next_step_paid_2_text' => 'Pratite dostavljeno uputstvo i kreirajte nalog na HSE e-learning platformi.',
			'next_step_paid_3_title' => 'Započnite kurs',
			'next_step_paid_3_text' => 'Pristup kursu počinje odmah i traje tokom navedenog perioda pristupa.',
			'next_step_pending_1_title' => 'Sačuvajte broj porudžbine',
			'next_step_pending_1_text' => 'Navedite ga kada kontaktirate HSE Training u vezi sa ovom porudžbinom.',
			'next_step_pending_2_title' => 'Pratite uputstvo za plaćanje',
			'next_step_pending_2_text' => 'Završite potrebni korak plaćanja ili sačekajte potvrdu procesora plaćanja.',
			'next_step_pending_3_title' => 'Pratite email',
			'next_step_pending_3_text' => 'Obavestićemo vas kada plaćanje bude potvrđeno i kurs bude dostupan.',
			'next_step_failed_1_title' => 'Proverite status plaćanja',
			'next_step_failed_1_text' => 'Za ovu porudžbinu nije potvrđeno uspešno zaduženje.',
			'next_step_failed_2_title' => 'Pokušajte ponovo bezbedno',
			'next_step_failed_2_text' => 'Vratite se na stranicu kursa kada budete spremni za novi pokušaj plaćanja.',
			'next_step_failed_3_title' => 'Kontaktirajte podršku',
			'next_step_failed_3_text' => 'Kontaktirajte HSE Training ako vam je potrebna pomoć pre novog pokušaja.',
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
		add_filter( 'woocommerce_pay_order_button_text', array( self::class, 'pay_order_button_text' ) );
		add_filter( 'woocommerce_gateway_title', array( self::class, 'gateway_title' ), 20, 2 );
		add_filter( 'woocommerce_gateway_description', array( self::class, 'gateway_description' ), 20, 2 );
		add_filter( 'woocommerce_bacs_account_fields', array( self::class, 'filter_bacs_account_fields' ), 20, 2 );
		add_filter( 'woocommerce_get_privacy_policy_text', array( self::class, 'privacy_notice' ), 20, 2 );
		add_action( 'woocommerce_review_order_before_submit', array( self::class, 'render_terms_consent' ), 15 );
		add_action( 'woocommerce_checkout_process', array( self::class, 'validate_terms_consent' ) );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'save_legal_consents' ), 30, 2 );
		add_filter( 'woocommerce_thankyou_order_received_text', array( self::class, 'order_received_text' ), 20, 2 );
		add_action( 'woocommerce_thankyou', array( self::class, 'render_next_steps' ), 5 );
		add_filter( 'woocommerce_get_order_item_totals', array( self::class, 'localize_order_totals' ), 20, 3 );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( self::class, 'localize_order_actions' ), 20, 2 );
		add_filter( 'wp_date', array( self::class, 'localize_order_date' ), 20, 4 );
		add_filter( 'woocommerce_currency_symbol', array( self::class, 'localize_currency_symbol' ), 20, 2 );
		add_filter( 'wc_price_args', array( self::class, 'localize_price_format' ), 20, 1 );
		add_filter( 'gettext', array( self::class, 'translate_checkout_string' ), 20, 3 );
		add_filter( 'gettext_with_context', array( self::class, 'translate_checkout_context_string' ), 20, 4 );
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

	/** Report whether the checkout is securely retrying payment for an existing order. */
	public static function is_payment_retry(): bool {
		return function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page();
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
		wp_enqueue_style( 'hse-commerce', $plugin_url . 'assets/commerce.css', array( 'woocommerce-layout', 'woocommerce-general' ), '0.28.4' );

		wp_add_inline_script(
			'wc-checkout',
			'window.hseCheckoutBuyerFields = ' . wp_json_encode(
				array(
					'domesticTaxLabel' => self::copy( 'tax_id' ),
					'foreignTaxLabel'  => self::copy( 'foreign_tax_id' ),
					'cardButtonLabel'  => self::copy( 'place_order_card' ),
					'bankButtonLabel'  => self::copy( 'place_order_bank' ),
					'retryReviewLabel' => self::copy( 'retry_review' ),
					'retryPaymentLabel' => self::copy( 'retry_payment' ),
				)
			) . ';',
			'before'
		);

		$buyer_fields_script = <<<'JS'
(function () {
	var fieldCopy = window.hseCheckoutBuyerFields || {};

	function updateFieldLabel(row, text) {
		var label = row.querySelector('label');
		if (!label || !text) return;
		Array.prototype.some.call(label.childNodes, function (node) {
			if (node.nodeType !== 3 || !node.textContent.trim()) return false;
			node.textContent = text + ' ';
			return true;
		});
	}

	function updateRequiredMarker(row, required) {
		var label = row.querySelector('label');
		if (!label) return;
		var requiredMarker = label.querySelector('.required');
		var optionalMarker = label.querySelector('.optional');

		if (required) {
			if (optionalMarker) optionalMarker.hidden = true;
			if (!requiredMarker) {
				requiredMarker = document.createElement('abbr');
				requiredMarker.className = 'required';
				requiredMarker.title = 'required';
				requiredMarker.textContent = '*';
				label.appendChild(document.createTextNode(' '));
				label.appendChild(requiredMarker);
			} else {
				requiredMarker.hidden = false;
			}
			return;
		}

		if (requiredMarker) requiredMarker.hidden = true;
		if (optionalMarker) optionalMarker.hidden = false;
	}

	function updateCompanyFields() {
		var selected = document.querySelector('input[name="billing_customer_type"]:checked');
		var isCompany = selected && selected.value === 'company';
		var country = document.getElementById('billing_country');
		var isDomestic = !country || country.value === 'RS';
		['billing_company_field', 'billing_pib_field', 'billing_registration_number_field'].forEach(function (id) {
			var row = document.getElementById(id);
			if (!row) return;
			row.hidden = !isCompany;
			row.setAttribute('aria-hidden', isCompany ? 'false' : 'true');
			var input = row.querySelector('input');
			var isRequiredCompanyField = isCompany && id !== 'billing_registration_number_field';
			if (input) {
				input.required = Boolean(isRequiredCompanyField);
				input.setAttribute('aria-required', isRequiredCompanyField ? 'true' : 'false');
				if (id === 'billing_pib_field') {
					input.inputMode = isDomestic ? 'numeric' : 'text';
					input.maxLength = isDomestic ? 9 : 32;
					if (isDomestic) input.setAttribute('pattern', '[0-9]{9}');
					else input.removeAttribute('pattern');
				}
			}
			if (id === 'billing_pib_field') {
				updateFieldLabel(row, isDomestic ? fieldCopy.domesticTaxLabel : fieldCopy.foreignTaxLabel);
			}
			updateRequiredMarker(row, Boolean(isRequiredCompanyField));
		});
	}

	function updatePaymentAction() {
		var selected = document.querySelector('input[name="payment_method"]:checked');
		var button = document.getElementById('place_order');
		if (!button) return;
		var label = selected && selected.value === 'bacs' ? fieldCopy.bankButtonLabel : fieldCopy.cardButtonLabel;
		if (label) {
			button.textContent = label;
			button.value = label;
		}
	}

	function enhancePaymentMethods() {
		document.querySelectorAll('#payment li.wc_payment_method').forEach(function (method) {
			var label = method.querySelector(':scope > label');
			if (!label || label.querySelector('.hse-payment-method__title')) return;

			var titleParts = [];
			Array.prototype.slice.call(label.childNodes).forEach(function (node) {
				if (node.nodeType !== 3 || !node.textContent.trim()) return;
				titleParts.push(node.textContent.trim());
				node.remove();
			});

			if (titleParts.length) {
				var title = document.createElement('span');
				title.className = 'hse-payment-method__title';
				title.textContent = titleParts.join(' ');
				label.insertBefore(title, label.firstChild);
			}

			var images = Array.prototype.slice.call(label.querySelectorAll(':scope > img'));
			if (images.length) {
				var logos = document.createElement('span');
				logos.className = 'hse-payment-method__logos';
				images.forEach(function (image) { logos.appendChild(image); });
				label.appendChild(logos);
			}
		});
	}

	function prepareCheckoutLayout() {
		var form = document.querySelector('form.checkout');
		var billingHeading = document.querySelector('.woocommerce-billing-fields > h3');
		var heading = document.getElementById('order_review_heading');
		var review = document.getElementById('order_review');
		if (!form || !heading || !review) return;

		function addStepBadge(target, number) {
			if (!target || target.querySelector('.hse-checkout-step')) return;
			var badge = document.createElement('span');
			badge.className = 'hse-checkout-step';
			badge.setAttribute('aria-hidden', 'true');
			badge.textContent = number;
			target.insertBefore(badge, target.firstChild);
		}

		addStepBadge(billingHeading, '1');
		addStepBadge(heading, '2');
		if (heading.parentElement === review.parentElement && heading.parentElement.classList.contains('hse-checkout-summary')) return;

		var summary = document.createElement('div');
		summary.className = 'hse-checkout-summary';
		form.insertBefore(summary, heading);
		summary.appendChild(heading);
		summary.appendChild(review);
	}

	function prepareRetryLayout() {
		if (!document.body.classList.contains('hse-commerce--retry')) return;
		var form = document.querySelector('form#order_review');
		if (!form || form.classList.contains('hse-retry-layout')) return;
		var table = form.querySelector(':scope > table.shop_table');
		var payment = form.querySelector(':scope > #payment');
		if (!table || !payment) return;

		function createCard(className, number, title) {
			var card = document.createElement('section');
			card.className = 'hse-retry-card ' + className;
			var heading = document.createElement('h2');
			var badge = document.createElement('span');
			badge.className = 'hse-checkout-step';
			badge.setAttribute('aria-hidden', 'true');
			badge.textContent = number;
			heading.appendChild(badge);
			heading.appendChild(document.createTextNode(title));
			card.appendChild(heading);
			return card;
		}

		var reviewCard = createCard('hse-retry-card--review', '1', fieldCopy.retryReviewLabel || 'Review your order');
		var paymentCard = createCard('hse-retry-card--payment', '2', fieldCopy.retryPaymentLabel || 'Choose payment method');
		form.insertBefore(reviewCard, table);
		reviewCard.appendChild(table);
		form.insertBefore(paymentCard, payment);
		paymentCard.appendChild(payment);
		form.classList.add('hse-retry-layout');
	}

	document.addEventListener('DOMContentLoaded', function () {
		prepareCheckoutLayout();
		prepareRetryLayout();
		updateCompanyFields();
		updatePaymentAction();
		enhancePaymentMethods();
	});
	document.addEventListener('click', function (event) {
		if (!event.target || !(event.target instanceof Element)) return;
		var method = event.target.closest('li.wc_payment_method');
		if (!method || event.target.closest('a, button, input, label, select, textarea')) return;
		var radio = method.querySelector(':scope > input[type="radio"]');
		if (radio && !radio.checked) radio.click();
	});
	document.addEventListener('change', function (event) {
		if (event.target && (event.target.name === 'billing_customer_type' || event.target.id === 'billing_country')) updateCompanyFields();
		if (event.target && event.target.name === 'payment_method') updatePaymentAction();
	});
	if (window.jQuery) window.jQuery(document.body).on('updated_checkout', function () {
		prepareCheckoutLayout();
		prepareRetryLayout();
		updateCompanyFields();
		updatePaymentAction();
		enhancePaymentMethods();
	});
})();
JS;
		wp_add_inline_script( 'wc-checkout', $buyer_fields_script, 'after' );
	}

	/** Add stable page classes for responsive commerce styling. */
	public static function body_classes( array $classes ): array {
		if ( self::is_checkout_request() ) {
			$classes[] = 'hse-commerce';
			$classes[] = 'hse-commerce--' . CommerceLocale::current();
			if ( self::is_confirmation() ) {
				$classes[] = 'hse-commerce--confirmation';
			} elseif ( self::is_payment_retry() ) {
				$classes[] = 'hse-commerce--retry';
			} else {
				$classes[] = 'hse-commerce--checkout';
			}
		}
		return $classes;
	}

	/** Set a localized browser title without changing the WordPress Checkout page. */
	public static function document_title( array $parts ): array {
		if ( self::is_checkout_request() ) {
			$parts['title'] = self::is_confirmation()
				? self::copy( 'confirmation_title' )
				: ( self::is_payment_retry() ? self::copy( 'retry_page_title' ) : self::copy( 'page_title' ) );
			$parts['site']  = 'HSE Training';
		}
		return $parts;
	}

	/** Apply reviewed Serbian-Latin labels while preserving Woo field behavior. */
	public static function localize_checkout_fields( array $fields ): array {
		if ( ! self::is_checkout_request() ) {
			return $fields;
		}

		/* HSE owns buyer type and PIB; remove the duplicate optional BokaPOS controls. */
		unset( $fields['billing']['billing_bokapos_company'], $fields['billing']['billing_bokapos_pib'] );

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

	/** Validate a Serbian nine-digit PIB, including its ISO 7064 MOD 11,10 check digit. */
	public static function is_valid_serbian_pib( $value ): bool {
		$pib = trim( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ) );
		if ( ! preg_match( '/^\d{9}$/', $pib ) ) {
			return false;
		}

		$sum = 10;
		for ( $index = 0; $index < 8; $index++ ) {
			$sum = ( $sum + (int) $pib[ $index ] ) % 10;
			if ( 0 === $sum ) {
				$sum = 10;
			}
			$sum = ( $sum * 2 ) % 11;
		}

		return ( ( 11 - $sum ) % 10 ) === (int) $pib[8];
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
		} elseif ( 'RS' === $country && ! self::is_valid_serbian_pib( $tax_id ) ) {
			$errors['billing_pib_invalid'] = self::copy( 'tax_id_invalid' );
		} elseif ( 'RS' !== $country && ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9 .\/-]{4,31}$/', $tax_id ) ) {
			$errors['billing_pib_invalid'] = self::copy( 'foreign_tax_id_invalid' );
		}
		if ( '' !== $registration && ( 'RS' === $country ? ! preg_match( '/^\d{8}$/', $registration ) : ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9 .\/-]{4,31}$/', $registration ) ) ) {
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
			$tax_id  = trim( sanitize_text_field( (string) ( $data['billing_pib'] ?? '' ) ) );
			$country = strtoupper( sanitize_key( (string) ( $data['billing_country'] ?? '' ) ) );
			$order->update_meta_data( self::COMPANY_TAX_ID_META, $tax_id );
			$order->update_meta_data( self::COMPANY_REG_NUMBER_META, sanitize_text_field( (string) ( $data['billing_registration_number'] ?? '' ) ) );
			if ( 'RS' !== $country && '' !== $tax_id ) {
				$order->update_meta_data( self::BOKAPOS_BUYER_ID_META, '40:' . $tax_id );
			} elseif ( method_exists( $order, 'delete_meta_data' ) ) {
				$order->delete_meta_data( self::BOKAPOS_BUYER_ID_META );
			}
			return;
		}

		if ( method_exists( $order, 'set_billing_company' ) ) {
			$order->set_billing_company( '' );
		}
		if ( method_exists( $order, 'delete_meta_data' ) ) {
			$order->delete_meta_data( self::COMPANY_TAX_ID_META );
			$order->delete_meta_data( self::COMPANY_REG_NUMBER_META );
			$order->delete_meta_data( self::BOKAPOS_BUYER_ID_META );
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

		if ( method_exists( $order, 'get_meta' ) && 'yes' === $order->get_meta( self::DIGITAL_CONSENT_META, true ) ) {
			$recorded_at = sanitize_text_field( (string) $order->get_meta( self::DIGITAL_CONSENT_AT_META, true ) );
			$version     = sanitize_text_field( (string) $order->get_meta( self::DIGITAL_CONSENT_VERSION_META, true ) );
			echo '<p><strong>' . esc_html( self::copy_for_locale( 'digital_consent_admin', $locale ) ) . ':</strong> ' . esc_html( self::copy_for_locale( 'digital_consent_yes', $locale ) ) . '</p>';
			if ( '' !== $recorded_at ) {
				echo '<p><strong>' . esc_html( self::copy_for_locale( 'digital_consent_time', $locale ) ) . ':</strong> ' . esc_html( $recorded_at ) . '</p>';
			}
			if ( '' !== $version ) {
				echo '<p><strong>' . esc_html( self::copy_for_locale( 'digital_consent_version', $locale ) ) . ':</strong> ' . esc_html( $version ) . '</p>';
			}
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

	/** Make the order-pay action explicit before RaiAccept opens. */
	public static function pay_order_button_text( $text = '' ): string {
		return self::is_checkout_request() && self::is_payment_retry()
			? self::copy( 'retry_action' )
			: ( is_scalar( $text ) ? (string) $text : '' );
	}

	/** Localize the configured RaiAccept label on this isolated surface. */
	public static function gateway_title( $title, $gateway_id ) {
		if ( ! self::is_checkout_request() ) {
			return $title;
		}
		if ( 'raiaccept' === $gateway_id ) {
			return self::copy( 'payment_title' );
		}
		return 'bacs' === $gateway_id ? self::copy( 'bank_transfer_title' ) : $title;
	}

	/** Localize the RaiAccept helper text without changing gateway behavior. */
	public static function gateway_description( $description, $gateway_id ) {
		if ( ! self::is_checkout_request() ) {
			return $description;
		}
		if ( 'raiaccept' === $gateway_id ) {
			return self::copy( 'payment_description' );
		}
		return 'bacs' === $gateway_id ? self::copy( 'bank_transfer_description' ) : $description;
	}

	/** Keep domestic and international bank-transfer instructions unambiguous. */
	public static function filter_bacs_account_fields( array $fields, $order_id ): array {
		$order   = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
		$country = is_object( $order ) && method_exists( $order, 'get_billing_country' )
			? strtoupper( sanitize_key( (string) $order->get_billing_country() ) )
			: '';
		$locale  = is_object( $order ) && method_exists( $order, 'get_meta' )
			? CommerceLocale::sanitize( (string) $order->get_meta( CommerceLocale::ORDER_META, true ) )
			: CommerceLocale::current();

		return self::bacs_account_fields_for_country( $fields, $country, $locale );
	}

	/** Return only the account identifiers required for one billing country. */
	public static function bacs_account_fields_for_country( array $fields, string $country, string $locale ): array {
		$country     = strtoupper( sanitize_key( $country ) );
		$locale      = CommerceLocale::sanitize( $locale );
		$is_domestic = '' !== $country ? 'RS' === $country : 'sr' === $locale;

		if ( $is_domestic ) {
			unset( $fields['iban'], $fields['bic'], $fields['sort_code'] );
		} else {
			unset( $fields['account_number'], $fields['sort_code'] );
		}

		$labels = array(
			'bank_name'      => 'bank_name',
			'account_number' => 'account_number',
			'iban'           => 'iban',
			'bic'            => 'swift_bic',
		);
		foreach ( $labels as $field => $copy_key ) {
			if ( isset( $fields[ $field ] ) ) {
				$fields[ $field ]['label'] = self::copy_for_locale( $copy_key, $locale );
			}
		}

		return $fields;
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

		$checked         = self::posted_checkbox_is_checked( 'hse_terms_consent' );
		$digital_checked = self::posted_checkbox_is_checked( 'hse_digital_delivery_consent' );
		$text            = sprintf(
			self::copy( 'terms_consent' ),
			esc_url( self::public_url( '/terms-and-conditions/' ) ),
			esc_url( self::public_url( '/privacy-policy/' ) )
		);
		?>
		<div class="hse-commerce__consents" role="group" aria-labelledby="hse-checkout-confirmations-title" aria-describedby="hse-checkout-confirmations-help">
			<h3 id="hse-checkout-confirmations-title"><?php echo esc_html( self::copy( 'checkout_confirmations' ) ); ?></h3>
			<p id="hse-checkout-confirmations-help" class="hse-commerce__consents-help"><?php echo esc_html( self::copy( 'checkout_confirmations_help' ) ); ?></p>
			<p class="form-row validate-required hse-commerce__consent">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="hse_terms_consent">
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="hse_terms_consent" id="hse_terms_consent" value="1" <?php checked( $checked ); ?> required />
					<span class="hse-commerce__consent-copy"><strong><?php echo esc_html( self::copy( 'terms_consent_title' ) ); ?></strong><span><?php echo wp_kses_post( $text ); ?></span></span>
					<abbr class="required" title="<?php echo esc_attr( self::copy( 'required' ) ); ?>">*</abbr>
				</label>
			</p>
			<p class="form-row validate-required hse-commerce__consent hse-commerce__consent--digital">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="hse_digital_delivery_consent">
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="hse_digital_delivery_consent" id="hse_digital_delivery_consent" value="1" <?php checked( $digital_checked ); ?> required />
					<span class="hse-commerce__consent-copy"><strong><?php echo esc_html( self::copy( 'digital_consent_title' ) ); ?></strong><span><?php echo esc_html( self::copy( 'digital_consent' ) ); ?></span></span>
					<abbr class="required" title="<?php echo esc_attr( self::copy( 'required' ) ); ?>">*</abbr>
				</label>
			</p>
		</div>
		<?php
	}

	/** Reject checkout server-side when the legal acknowledgement is missing. */
	public static function validate_terms_consent(): void {
		if ( ! self::posted_checkbox_is_checked( 'hse_terms_consent' ) ) {
			wc_add_notice( self::copy( 'terms_required' ), 'error' );
		}
		if ( ! self::posted_checkbox_is_checked( 'hse_digital_delivery_consent' ) ) {
			wc_add_notice( self::copy( 'digital_consent_required' ), 'error' );
		}
	}

	/** Persist the explicit digital-delivery acknowledgement with its wording version. */
	public static function save_legal_consents( $order, array $data ): void {
		unset( $data );
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return;
		}

		if ( self::posted_checkbox_is_checked( 'hse_digital_delivery_consent' ) ) {
			$order->update_meta_data( self::DIGITAL_CONSENT_META, 'yes' );
			$order->update_meta_data( self::DIGITAL_CONSENT_AT_META, gmdate( 'c' ) );
			$order->update_meta_data( self::DIGITAL_CONSENT_VERSION_META, self::DIGITAL_CONSENT_VERSION );
		}
	}

	/** Accept only the explicit checkbox value rendered by this checkout. */
	private static function posted_checkbox_is_checked( string $name ): bool {
		return isset( $_POST[ $name ] ) && '1' === sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
	}

	/** Keep the return page accurate if the provider notification is still pending. */
	public static function order_received_text( $text, $order ) {
		return self::copy( 'order_received_' . self::confirmation_state( $order ) );
	}

	/** Explain the operational next step without implying LMS access is automatic. */
	public static function render_next_steps( $order_id ): void {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
		if ( ! $order ) {
			return;
		}

		$state = self::confirmation_state( $order );
		$icon  = 'paid' === $state ? '✓' : ( 'failed' === $state ? '!' : '…' );
		?>
		<section class="hse-commerce__next-steps hse-commerce__next-steps--<?php echo esc_attr( $state ); ?>">
			<header class="hse-commerce__next-steps-header">
				<span class="hse-commerce__status-icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
				<div>
					<h2><?php echo esc_html( self::copy( 'next_steps_title' ) ); ?></h2>
					<p><?php echo esc_html( self::copy( 'next_steps_' . $state ) ); ?></p>
				</div>
			</header>
			<ol class="hse-commerce__next-steps-list">
				<?php for ( $step = 1; $step <= 3; $step++ ) : ?>
					<li>
						<span class="hse-commerce__step-number" aria-hidden="true"><?php echo esc_html( str_pad( (string) $step, 2, '0', STR_PAD_LEFT ) ); ?></span>
						<div>
							<strong><?php echo esc_html( self::copy( 'next_step_' . $state . '_' . $step . '_title' ) ); ?></strong>
							<p><?php echo esc_html( self::copy( 'next_step_' . $state . '_' . $step . '_text' ) ); ?></p>
						</div>
					</li>
				<?php endfor; ?>
			</ol>
			<a class="hse-commerce__support-link" href="<?php echo esc_url( self::public_url( '/contact/#contact-form' ) ); ?>">
				<?php echo esc_html( self::copy( 'contact' ) ); ?> <span aria-hidden="true">→</span>
			</a>
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
		if ( isset( $strings[ $text ] ) ) {
			return self::copy( $strings[ $text ] );
		}

		return isset( self::VALIDATION_COPY_KEYS[''][ $text ] )
			? self::localized_validation_text( (string) $text, '', CommerceLocale::current() )
			: $translation;
	}

	/** Translate Woo checkout validation prefixes that use gettext context. */
	public static function translate_checkout_context_string( $translation, $text, $context, $domain ) {
		if (
			'woocommerce' !== $domain
			|| ! self::is_checkout_request()
			|| ! isset( self::VALIDATION_COPY_KEYS[ $context ][ $text ] )
		) {
			return $translation;
		}

		return self::localized_validation_text( (string) $text, (string) $context, CommerceLocale::current() );
	}

	/** Return a deterministic validation translation for tests and gettext filters. */
	public static function localized_validation_text( string $text, string $context, string $locale ): string {
		$key = self::VALIDATION_COPY_KEYS[ $context ][ $text ] ?? '';
		return '' !== $key ? self::copy_for_locale( $key, $locale ) : $text;
	}

	/** Map Woo statuses to honest customer-facing confirmation states. */
	public static function confirmation_state( $order ): string {
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
