<?php
/**
 * Localized plain-text wrapper for the official BokaPOS receipt.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCustomerEmail;

defined( 'ABSPATH' ) || exit;

$locale = CommerceCustomerEmail::order_locale( $order );
$copy   = CommerceCustomerEmail::fiscal_receipt_copy( $locale );

echo esc_html( $copy['company'] ) . "\n";
echo esc_html( $copy['tagline'] ) . "\n\n";
echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";
echo esc_html( sprintf( $copy['intro'], $order->get_order_number() ) ) . "\n\n";
echo esc_html( $copy['receipt_number'] ) . ': ' . esc_html( $pfr_number ) . "\n";
echo esc_html( $copy['receipt_time'] ) . ': ' . esc_html( $pfr_time ) . "\n";
echo esc_html( $copy['amount'] ) . ': ' . esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ) . "\n\n";

if ( $verification_url ) {
	echo esc_html( $copy['verify'] ) . ': ' . esc_url( $verification_url ) . "\n";
}
if ( $pdf_url ) {
	echo esc_html( $copy['download'] ) . ': ' . esc_url( $pdf_url ) . "\n";
}

echo "\n" . esc_html( $copy['order_summary'] ) . "\n";
foreach ( $order->get_items( 'line_item' ) as $item ) {
	echo '- ' . esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ) . ' x ' . esc_html( $item->get_quantity() ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $order->get_line_total( $item, true, true ), array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
}

echo "\n" . esc_html( $copy['official_notice'] ) . "\n";
echo esc_html( $copy['keep_receipt'] ) . "\n\n";
echo esc_html( $copy['website'] ) . ' | ' . esc_html( $copy['email'] ) . "\n";
