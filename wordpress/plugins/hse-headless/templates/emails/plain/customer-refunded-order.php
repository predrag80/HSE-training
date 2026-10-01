<?php
/**
 * Localized plain-text customer refund confirmation.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCustomerEmail;

defined( 'ABSPATH' ) || exit;

$locale        = CommerceCustomerEmail::order_locale( $order );
$is_partial    = ! empty( $partial_refund );
$copy          = CommerceCustomerEmail::refund_copy( $locale, $is_partial );
$refund_order  = $refund instanceof WC_Order_Refund ? $refund : null;
$refund_amount = $refund_order ? abs( (float) $refund_order->get_amount() ) : abs( (float) $order->get_total_refunded() );
$refund_date   = $refund_order && $refund_order->get_date_created() ? $refund_order->get_date_created() : $order->get_date_modified();
$date_text     = $refund_date ? $refund_date->date_i18n( 'sr' === $locale ? 'd.m.Y.' : 'j F Y' ) : '';
$price_args    = array( 'currency' => $order->get_currency() );
$refund_items  = $refund_order ? $refund_order->get_items( 'line_item' ) : array();
$display_items = $refund_items ?: $order->get_items( 'line_item' );

echo esc_html( $copy['company'] ) . "\n";
echo esc_html( $copy['tagline'] ) . "\n\n";
echo esc_html( $email_heading ) . "\n";
echo str_repeat( '=', 48 ) . "\n\n";
echo esc_html( sprintf( $copy['intro'], $order->get_order_number() ) ) . "\n\n";
echo esc_html( $copy['status'] ) . "\n";
echo esc_html( $copy['amount'] ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $refund_amount, $price_args ) ) ) . "\n";
echo esc_html( $copy['refund_date'] ) . ': ' . esc_html( $date_text ) . "\n";
echo esc_html( $copy['order_number'] ) . ': #' . esc_html( $order->get_order_number() ) . "\n\n";
echo esc_html( $copy['order_details'] ) . "\n";
echo esc_html( $copy['description'] ) . ' | ' . esc_html( $copy['quantity'] ) . ' | ' . esc_html( $copy['line_amount'] ) . "\n";
echo str_repeat( '-', 48 ) . "\n";

foreach ( $display_items as $item ) {
	$item_quantity = abs( (float) $item->get_quantity() );
	$item_amount   = $refund_items ? abs( (float) $item->get_total() + (float) $item->get_total_tax() ) : abs( (float) $order->get_line_total( $item, true, true ) );
	echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ) . ' | ';
	echo esc_html( wc_format_decimal( $item_quantity ) ) . ' | ';
	echo esc_html( wp_strip_all_tags( wc_price( $item_amount, $price_args ) ) ) . "\n";
}

echo "\n" . esc_html( $copy['timing'] ) . "\n";
echo esc_html( $copy['fiscal_notice'] ) . "\n\n";
echo esc_html( $copy['help'] ) . "\n\n";
echo esc_html( $copy['company'] ) . ' · ' . esc_html( $copy['country'] ) . ' · ' . esc_html( $copy['website'] ) . ' · ' . esc_html( $copy['email'] ) . "\n";
