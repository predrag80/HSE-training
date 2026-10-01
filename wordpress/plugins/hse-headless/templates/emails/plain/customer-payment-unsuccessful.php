<?php
/**
 * Shared plain-text layout for unsuccessful payments.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCheckoutSource;
use HSETraining\Headless\Commerce\CommerceCustomerEmail;

defined( 'ABSPATH' ) || exit;

$outcome   = isset( $outcome ) && 'cancelled' === $outcome ? 'cancelled' : 'failed';
$cancelled = 'cancelled' === $outcome;
$locale    = CommerceCustomerEmail::order_locale( $order );
$copy      = CommerceCustomerEmail::unsuccessful_payment_copy( $locale, $outcome );
$retry_url = '';

if ( ! $cancelled && method_exists( $order, 'get_checkout_payment_url' ) ) {
	$retry_url = add_query_arg(
		array(
			'lang'       => $locale,
			'hse_source' => CommerceCheckoutSource::for_order( $order ),
		),
		$order->get_checkout_payment_url()
	);
}

echo esc_html( $copy['company'] ) . "\n";
echo esc_html( $copy['tagline'] ) . "\n";
echo "========================================\n\n";
echo esc_html( $copy['title'] ) . "\n";
echo esc_html( sprintf( $copy['intro'], $order->get_order_number() ) ) . "\n\n";
echo esc_html( $copy['status'] ) . "\n";
echo esc_html( $copy['reassurance'] ) . "\n\n";
echo esc_html( $copy['order_summary'] ) . "\n";

foreach ( $order->get_items( 'line_item' ) as $item ) {
	echo '- ' . esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ) . ' x ' . esc_html( $item->get_quantity() ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $order->get_line_total( $item, true, true ), array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
}

echo esc_html( $copy['total'] ) . ': ' . esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ) . "\n\n";
echo esc_html( $copy['retry_title'] ) . "\n";
echo esc_html( $copy['retry_text'] ) . "\n";
if ( $retry_url ) {
	echo esc_url( $retry_url ) . "\n";
}
echo "\n" . esc_html( $copy['help'] ) . "\n\n";
echo esc_html( $copy['company'] . ' · ' . $copy['country'] ) . "\n";
echo esc_html( $copy['website'] . ' · ' . $copy['email'] ) . "\n";
