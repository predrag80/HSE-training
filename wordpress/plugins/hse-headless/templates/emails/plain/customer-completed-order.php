<?php
/**
 * Localized plain-text HSE Training payment receipt.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCustomerEmail;
use HSETraining\Headless\Commerce\CommercePresentation;

defined( 'ABSPATH' ) || exit;

$locale         = CommerceCustomerEmail::order_locale( $order );
$copy           = CommerceCustomerEmail::receipt_copy( $locale );
$created        = $order->get_date_created();
$order_date     = $created ? $created->date_i18n( 'sr' === $locale ? 'd.m.Y.' : 'j F Y' ) : '';
$billing        = trim( wp_strip_all_tags( str_replace( '<br/>', "\n", $order->get_formatted_billing_address() ) ) );
$payment_method = $order->get_payment_method_title();
$price_args     = array( 'currency' => $order->get_currency() );
$buyer_details  = CommercePresentation::order_buyer_details( $order, $locale );
$item_names     = array();

foreach ( $order->get_items( 'line_item' ) as $receipt_item ) {
	$item_names[] = CommerceCustomerEmail::localized_item_name( $receipt_item, $locale );
}

$item_summary = $item_names ? implode( ', ', $item_names ) : '-';

echo esc_html( $copy['company'] ) . "\n";
echo esc_html( $copy['tagline'] ) . "\n\n";
echo esc_html( $email_heading ) . "\n";
echo '#' . esc_html( $order->get_order_number() ) . ' · ' . esc_html( $order_date ) . "\n";
echo str_repeat( '=', 48 ) . "\n\n";
echo esc_html( $copy['thanks'] ) . "\n";
echo esc_html( sprintf( $copy['intro'], $item_summary ) ) . "\n\n";

echo esc_html( $copy['billed_to'] ) . "\n";
echo ( $billing ? esc_html( $billing ) : '-' ) . "\n";
if ( $order->get_billing_email() ) {
	echo esc_html( $order->get_billing_email() ) . "\n";
}
if ( $order->get_billing_phone() ) {
	echo esc_html( $order->get_billing_phone() ) . "\n";
}
foreach ( $buyer_details as $detail ) {
	echo esc_html( $detail['label'] ) . ': ' . esc_html( $detail['value'] ) . "\n";
}

echo "\n" . esc_html( $copy['payment_details'] ) . "\n";
echo esc_html( $copy['payment_method'] ) . ': ' . esc_html( $payment_method ?: 'Card' ) . "\n";
echo esc_html( $copy['order_number'] ) . ': #' . esc_html( $order->get_order_number() ) . "\n";
echo esc_html( $copy['order_date'] ) . ': ' . esc_html( $order_date ) . "\n\n";

echo esc_html( $copy['description'] ) . ' | ' . esc_html( $copy['quantity'] ) . ' | ' . esc_html( $copy['amount'] ) . "\n";
echo str_repeat( '-', 48 ) . "\n";
foreach ( $order->get_items( 'line_item' ) as $item ) {
	echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ) . ' | ';
	echo esc_html( $item->get_quantity() ) . ' | ';
	echo esc_html( wp_strip_all_tags( wc_price( $order->get_line_total( $item, true, true ), $price_args ) ) ) . "\n";
}

echo "\n" . esc_html( $copy['subtotal'] ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $order->get_subtotal(), $price_args ) ) ) . "\n";
echo esc_html( $copy['total_paid'] ) . ': ' . esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ) . "\n\n";
echo esc_html( $copy['payment_received'] ) . "\n\n";
echo esc_html( $copy['closing_heading'] ) . "\n";
echo esc_html( $copy['next_steps'] ) . "\n\n";
echo esc_html( $copy['company'] ) . ' · ' . esc_html( $copy['country'] ) . ' · ' . esc_html( $copy['website'] ) . ' · ' . esc_html( $copy['email'] ) . "\n";
