<?php
/**
 * Plain-text merchant notification for a new WooCommerce order.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCustomerEmail;
use HSETraining\Headless\Commerce\CommercePresentation;

defined( 'ABSPATH' ) || exit;

$copy             = CommerceCustomerEmail::merchant_copy();
$order_locale     = CommerceCustomerEmail::order_locale( $order );
$created          = $order->get_date_created();
$order_date       = $created ? $created->date_i18n( 'd.m.Y. H:i' ) : '';
$billing          = trim( wp_strip_all_tags( str_replace( '<br/>', "\n", $order->get_formatted_billing_address() ) ) );
$buyer_details    = CommercePresentation::order_buyer_details( $order, 'sr' );
$payment_method   = $order->get_payment_method_title();
$transaction_id   = $order->get_transaction_id();
$price_args       = array( 'currency' => $order->get_currency() );
$status           = wc_get_order_status_name( $order->get_status() );
$language_label   = 'sr' === $order_locale ? $copy['language_sr'] : $copy['language_en'];
$order_admin_url  = method_exists( $order, 'get_edit_order_url' ) ? $order->get_edit_order_url() : '';

echo esc_html( $copy['company'] ) . "\n";
echo esc_html( $copy['tagline'] ) . "\n\n";
echo esc_html( $email_heading ) . "\n";
echo '#' . esc_html( $order->get_order_number() ) . ' · ' . esc_html( $order_date ) . "\n";
echo str_repeat( '=', 52 ) . "\n\n";
echo esc_html( $copy['title'] ) . "\n";
echo esc_html( $copy['intro'] ) . "\n\n";

echo esc_html( $copy['customer'] ) . "\n";
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

echo "\n" . esc_html( $copy['order_details'] ) . "\n";
echo esc_html( $copy['payment_method'] ) . ': ' . esc_html( $payment_method ?: '-' ) . "\n";
echo esc_html( $copy['order_number'] ) . ': #' . esc_html( $order->get_order_number() ) . "\n";
echo esc_html( $copy['order_date'] ) . ': ' . esc_html( $order_date ) . "\n";
echo esc_html( $copy['order_status'] ) . ': ' . esc_html( $status ) . "\n";
echo esc_html( $copy['order_language'] ) . ': ' . esc_html( $language_label ) . "\n";
if ( $transaction_id ) {
	echo esc_html( $copy['transaction_id'] ) . ': ' . esc_html( $transaction_id ) . "\n";
}

echo "\n" . esc_html( $copy['description'] ) . ' | ' . esc_html( $copy['quantity'] ) . ' | ' . esc_html( $copy['amount'] ) . "\n";
echo str_repeat( '-', 52 ) . "\n";
foreach ( $order->get_items( 'line_item' ) as $item ) {
	echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $order_locale ) ) . ' | ';
	echo esc_html( $item->get_quantity() ) . ' | ';
	echo esc_html( wp_strip_all_tags( wc_price( $order->get_line_total( $item, true, true ), $price_args ) ) ) . "\n";
}

echo "\n" . esc_html( $copy['subtotal'] ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $order->get_subtotal(), $price_args ) ) ) . "\n";
echo esc_html( $copy['total'] ) . ': ' . esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ) . "\n\n";
echo esc_html( $order->is_paid() ? $copy['payment_confirmed'] : $copy['order_received'] ) . "\n";
if ( $order_admin_url ) {
	echo esc_html( $copy['view_order'] ) . ': ' . esc_url( $order_admin_url ) . "\n";
}
echo "\n" . esc_html( $copy['company'] ) . ' · ' . esc_html( $copy['website'] ) . ' · ' . esc_html( $copy['email'] ) . "\n";
