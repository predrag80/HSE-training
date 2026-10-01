<?php
/**
 * Localized plain-text bank-transfer instructions.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceBankTransfer;
use HSETraining\Headless\Commerce\CommerceCustomerEmail;

defined( 'ABSPATH' ) || exit;

$locale  = CommerceCustomerEmail::order_locale( $order );
$copy    = CommerceBankTransfer::copy( $locale );
$payment = CommerceBankTransfer::instructions_for_order( $order );

echo esc_html( $copy['company'] ) . "\n";
echo esc_html( $copy['tagline'] ) . "\n\n";
echo esc_html( $email_heading ) . "\n";
echo '#' . esc_html( $order->get_order_number() ) . "\n";
echo str_repeat( '=', 48 ) . "\n\n";
echo esc_html( sprintf( $copy['intro'], $order->get_order_number() ) ) . "\n\n";

if ( $payment ) {
	echo esc_html( $payment['form_title'] ) . "\n";
	echo str_repeat( '-', 48 ) . "\n";
	echo esc_html( $copy['payer'] ) . ': ' . esc_html( $payment['payer'] ?: '-' ) . "\n";
	echo esc_html( $copy['recipient'] ) . ': ' . esc_html( $payment['recipient'] ?: $copy['company'] ) . "\n";
	echo esc_html( $payment['recipient_address'] ) . "\n";
	echo 'PIB: ' . esc_html( $payment['seller_tax_id'] ) . ' | MB: ' . esc_html( $payment['seller_reg_number'] ) . "\n";
	if ( $payment['bank_name'] ) {
		echo esc_html( $copy['bank'] ) . ': ' . esc_html( $payment['bank_name'] ) . "\n";
	}
	if ( $payment['is_domestic'] ) {
		echo esc_html( $copy['account'] ) . ': ' . esc_html( $payment['account_number'] ?: '-' ) . "\n";
		echo esc_html( $copy['payment_code'] ) . ': ' . esc_html( $payment['payment_code'] ) . "\n";
	} else {
		echo esc_html( $copy['iban'] ) . ': ' . esc_html( $payment['iban'] ?: '-' ) . "\n";
		echo esc_html( $copy['bic'] ) . ': ' . esc_html( $payment['bic'] ?: '-' ) . "\n";
	}
	echo esc_html( $copy['purpose_label'] ) . ': ' . esc_html( $payment['purpose'] ) . "\n";
	echo esc_html( $copy['amount'] ) . ': ' . esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ) . "\n\n";
	if ( ! $payment['is_domestic'] ) {
		echo esc_html( $copy['attachment_notice'] ) . "\n\n";
	}
}

echo esc_html( $copy['notice'] ) . "\n\n";
echo esc_html( $copy['order_summary'] ) . "\n";
echo esc_html( $copy['description'] ) . ' | ' . esc_html( $copy['quantity'] ) . ' | ' . esc_html( $copy['line_amount'] ) . "\n";
echo str_repeat( '-', 48 ) . "\n";
foreach ( $order->get_items( 'line_item' ) as $item ) {
	echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ) . ' | ';
	echo esc_html( $item->get_quantity() ) . ' | ';
	echo esc_html( wp_strip_all_tags( wc_price( $order->get_line_total( $item, true, true ), array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
}
echo "\n" . esc_html( $copy['total'] ) . ': ' . esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ) . "\n\n";
echo esc_html( $copy['help'] ) . "\n\n";
echo esc_html( $copy['company'] ) . ' · ' . esc_html( $copy['country'] ) . ' · ' . esc_html( $copy['website'] ) . ' · ' . esc_html( $copy['email'] ) . "\n";
