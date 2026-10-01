<?php
/**
 * Direct-bank-transfer customer payment instructions.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Commerce;

defined( 'ABSPATH' ) || exit;

/** Provides localized domestic and international presentation data for BACS orders. */
final class CommerceBankTransfer {
	public const PAYMENT_CODE        = '221';
	public const BENEFICIARY_NAME    = 'HSE TRAINING DOO';
	public const BENEFICIARY_ADDRESS = 'BRAĆE RADOVANOVIĆA 17C, BEOGRAD-VRAČAR';
	public const SELLER_TAX_ID       = '108205616';
	public const SELLER_REG_NUMBER   = '20952288';
	public const BANK_NAME           = 'Raiffeisen banka ad Beograd';
	public const DOMESTIC_ACCOUNT    = '265-6040310000144-40';
	public const INTERNATIONAL_IBAN  = 'RS35265100000016397028';
	public const INTERNATIONAL_BIC   = 'RZBSRSBG';
	public const EURO_INSTRUCTIONS   = 'assets/documents/euro-payment-instructions-raiffeisen-bank.pdf';

	/** Register confirmation hooks. */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_bacs_accounts', array( self::class, 'filter_bacs_accounts' ), 20, 2 );
		add_action( 'woocommerce_thankyou_bacs', array( self::class, 'render_thankyou_instructions' ), 20, 1 );
	}

	/** Keep WooCommerce's standard account block aligned with the verified EUR details. */
	public static function filter_bacs_accounts( $accounts, $order_id = 0 ): array {
		unset( $order_id );
		if ( ! is_array( $accounts ) ) {
			return array();
		}

		foreach ( $accounts as $index => $account ) {
			if ( is_array( $account ) ) {
				$accounts[ $index ] = self::normalize_account( $account );
			}
		}

		return $accounts;
	}

	/** Return whether an order uses WooCommerce direct bank transfer. */
	public static function is_bank_transfer_order( $order ): bool {
		return self::is_order( $order )
			&& method_exists( $order, 'get_payment_method' )
			&& 'bacs' === (string) $order->get_payment_method();
	}

	/** Return email and confirmation data without exposing Woo settings elsewhere. */
	public static function instructions_for_order( $order, ?array $account = null ): array {
		if ( ! self::is_bank_transfer_order( $order ) ) {
			return array();
		}

		$locale      = CommerceCustomerEmail::order_locale( $order );
		$copy        = self::copy( $locale );
		$country     = method_exists( $order, 'get_billing_country' ) ? strtoupper( (string) $order->get_billing_country() ) : '';
		$is_domestic = 'RS' === $country;
		$buyer_type  = CommercePresentation::sanitize_buyer_type( $order->get_meta( CommercePresentation::BUYER_TYPE_META, true ) );
		$account     = is_array( $account ) ? $account : self::configured_account();
		$order_no    = method_exists( $order, 'get_order_number' ) ? (string) $order->get_order_number() : '';
		$company     = method_exists( $order, 'get_billing_company' ) ? trim( (string) $order->get_billing_company() ) : '';
		$full_name   = method_exists( $order, 'get_formatted_billing_full_name' ) ? trim( (string) $order->get_formatted_billing_full_name() ) : '';

		return array(
			'locale'            => $locale,
			'is_domestic'       => $is_domestic,
			'is_company'        => 'company' === $buyer_type,
			'form_title'        => 'company' === $buyer_type ? $copy['company_form_title'] : $copy['individual_form_title'],
			'payer'             => 'company' === $buyer_type && '' !== $company ? $company : $full_name,
			'recipient'         => self::BENEFICIARY_NAME,
			'recipient_address' => self::BENEFICIARY_ADDRESS,
			'seller_tax_id'     => self::SELLER_TAX_ID,
			'seller_reg_number' => self::SELLER_REG_NUMBER,
			'bank_name'         => self::BANK_NAME,
			'account_number'    => self::DOMESTIC_ACCOUNT,
			'iban'              => self::INTERNATIONAL_IBAN,
			'bic'               => self::INTERNATIONAL_BIC,
			'payment_code'      => $is_domestic ? self::PAYMENT_CODE : '',
			'purpose'           => sprintf( $copy['purpose'], $order_no ),
			'order_number'      => $order_no,
			'amount'            => method_exists( $order, 'get_total' ) ? (string) $order->get_total() : '',
			'currency'          => method_exists( $order, 'get_currency' ) ? (string) $order->get_currency() : 'RSD',
			'copy'              => $copy,
		);
	}

	/** Show the applicable transfer details on the order-received page. */
	public static function render_thankyou_instructions( $order_id ): void {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
		$data  = self::instructions_for_order( $order );
		if ( ! $data ) {
			return;
		}

		$copy = $data['copy'];
		?>
		<section class="hse-bank-instructions" aria-labelledby="hse-bank-instructions-title">
			<h2 id="hse-bank-instructions-title"><?php echo esc_html( $data['form_title'] ); ?></h2>
			<p><?php echo esc_html( $copy['thankyou_intro'] ); ?></p>
			<dl>
				<dt><?php echo esc_html( $copy['payer'] ); ?></dt><dd><?php echo esc_html( $data['payer'] ?: '-' ); ?></dd>
				<dt><?php echo esc_html( $copy['recipient'] ); ?></dt><dd><?php echo esc_html( $data['recipient'] ); ?><br><?php echo esc_html( $data['recipient_address'] ); ?></dd>
				<dt><?php echo esc_html( $copy['bank'] ); ?></dt><dd><?php echo esc_html( $data['bank_name'] ); ?></dd>
				<?php if ( $data['is_domestic'] ) : ?>
					<dt><?php echo esc_html( $copy['account'] ); ?></dt><dd><?php echo esc_html( $data['account_number'] ?: '-' ); ?></dd>
					<dt><?php echo esc_html( $copy['payment_code'] ); ?></dt><dd><?php echo esc_html( $data['payment_code'] ); ?></dd>
				<?php else : ?>
					<dt><?php echo esc_html( $copy['iban'] ); ?></dt><dd><?php echo esc_html( $data['iban'] ); ?></dd>
					<dt><?php echo esc_html( $copy['bic'] ); ?></dt><dd><?php echo esc_html( $data['bic'] ); ?></dd>
				<?php endif; ?>
				<dt><?php echo esc_html( $copy['purpose_label'] ); ?></dt><dd><?php echo esc_html( $data['purpose'] ); ?></dd>
				<dt><?php echo esc_html( $copy['amount'] ); ?></dt><dd><strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></dd>
			</dl>
			<?php if ( ! $data['is_domestic'] ) : ?><p><?php echo esc_html( $copy['attachment_notice'] ); ?></p><?php endif; ?>
		</section>
		<?php
	}

	/** Return labels for the stored checkout locale. */
	public static function copy( string $locale ): array {
		if ( 'sr' === CommerceLocale::sanitize( $locale ) ) {
			return array(
				'company'               => 'HSE TRAINING D.O.O.',
				'tagline'               => 'Bezbednost, zdravlje i profesionalne obuke',
				'preheader'             => 'Podaci za uplatu porudžbine direktnim bankovnim prenosom.',
				'title'                 => 'Podaci za uplatu',
				'intro'                 => 'Vaša porudžbina #%s je primljena i rezervisana do potvrde uplate.',
				'individual_form_title' => 'Uplatnica za fizičko lice',
				'company_form_title'    => 'Nalog za prenos za pravno lice',
				'payer'                 => 'Uplatilac',
				'recipient'             => 'Primalac',
				'bank'                  => 'Banka primaoca',
				'account'               => 'Račun primaoca',
				'iban'                  => 'IBAN',
				'bic'                   => 'SWIFT/BIC',
				'purpose_label'         => 'Svrha plaćanja',
				'payment_code'          => 'Šifra plaćanja',
				'amount'                => 'Iznos',
				'purpose'               => 'Plaćanje kursa – porudžbina %s',
				'notice'                => 'Pristup kursu aktivira se nakon što HSE Training potvrdi prijem sredstava.',
				'thankyou_intro'        => 'Ove podatke smo poslali i na email adresu navedenu u porudžbini.',
				'attachment_notice'     => 'Zvanične instrukcije Raiffeisen banke za EUR priloge nalaze se u PDF dokumentu priloženom uz email.',
				'order_summary'         => 'Pregled porudžbine',
				'description'           => 'Opis',
				'quantity'              => 'Količina',
				'line_amount'           => 'Iznos',
				'total'                 => 'Ukupno za uplatu',
				'help'                  => 'Nakon uplate pošaljite potvrdu na info@hsetraining.rs i navedite broj porudžbine radi brže provere.',
				'country'               => 'Srbija',
				'website'               => 'hsetraining.rs',
				'email'                 => 'info@hsetraining.rs',
			);
		}

		return array(
			'company'               => 'HSE TRAINING D.O.O.',
			'tagline'               => 'Health, Safety & Professional Training',
			'preheader'             => 'Bank-transfer instructions for your order.',
			'title'                 => 'Bank transfer instructions',
			'intro'                 => 'Your order #%s has been received and reserved until payment is confirmed.',
			'individual_form_title' => 'Payment instructions for an individual',
			'company_form_title'    => 'Payment instructions for a company',
			'payer'                 => 'Payer',
			'recipient'             => 'Beneficiary',
			'bank'                  => 'Beneficiary bank',
			'account'               => 'Account number',
			'iban'                  => 'IBAN',
			'bic'                   => 'SWIFT/BIC',
			'purpose_label'         => 'Payment purpose',
			'payment_code'          => 'Payment code',
			'amount'                => 'Amount',
			'purpose'               => 'Course payment – order %s',
			'notice'                => 'Course access is activated after HSE Training confirms receipt of funds.',
			'thankyou_intro'        => 'These instructions have also been sent to the email address used for the order.',
			'attachment_notice'     => 'The official Raiffeisen Bank instructions for EUR incoming payments are attached to the email as a PDF.',
			'order_summary'         => 'Order summary',
			'description'           => 'Description',
			'quantity'              => 'Qty',
			'line_amount'           => 'Amount',
			'total'                 => 'Total to pay',
			'help'                  => 'After payment, email proof of transfer to info@hsetraining.rs and quote the order number for faster verification.',
			'country'               => 'Serbia',
			'website'               => 'hsetraining.rs',
			'email'                 => 'info@hsetraining.rs',
		);
	}

	/** Return the first configured WooCommerce BACS beneficiary account. */
	private static function configured_account(): array {
		$accounts = get_option( 'woocommerce_bacs_accounts', array() );
		if ( ! is_array( $accounts ) || ! isset( $accounts[0] ) || ! is_array( $accounts[0] ) ) {
			return array();
		}

		return self::normalize_account( $accounts[0] );
	}

	/** Apply verified beneficiary and international identifiers to one Woo account row. */
	private static function normalize_account( array $account ): array {
		$account['account_name']   = self::BENEFICIARY_NAME;
		$account['bank_name']      = self::BANK_NAME;
		$account['account_number'] = self::DOMESTIC_ACCOUNT;
		$account['iban']           = self::INTERNATIONAL_IBAN;
		$account['bic']            = self::INTERNATIONAL_BIC;

		return $account;
	}

	/** Return the packaged official EUR incoming-payment instructions. */
	public static function euro_instructions_path(): string {
		return dirname( __DIR__, 2 ) . '/' . self::EURO_INSTRUCTIONS;
	}

	/** Test the Woo order interface required by the transfer integration. */
	private static function is_order( $order ): bool {
		return is_object( $order )
			&& method_exists( $order, 'get_meta' )
			&& method_exists( $order, 'update_meta_data' );
	}
}
