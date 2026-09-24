<?php
/**
 * Localized HSE Training payment receipt for a completed order.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCustomerEmail;
use HSETraining\Headless\Commerce\CommercePresentation;

defined( 'ABSPATH' ) || exit;

$locale      = CommerceCustomerEmail::order_locale( $order );
$copy        = CommerceCustomerEmail::receipt_copy( $locale );
$created     = $order->get_date_created();
$order_date  = $created ? $created->date_i18n( 'sr' === $locale ? 'd.m.Y.' : 'j F Y' ) : '';
$currency    = $order->get_currency();
$billing     = $order->get_formatted_billing_address();
$billing_mail = $order->get_billing_email();
$billing_phone = $order->get_billing_phone();
$buyer_details = CommercePresentation::order_buyer_details( $order, $locale );
$payment_method = $order->get_payment_method_title();
$price_args  = array( 'currency' => $currency );

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:0 0 28px;">
	<tr>
		<td style="padding:0 0 18px;vertical-align:top;">
			<p style="margin:0;color:#292d36;font-size:22px;font-weight:700;line-height:1.25;"><?php echo esc_html( $copy['company'] ); ?></p>
			<p style="margin:5px 0 0;color:#737783;font-size:13px;line-height:1.45;"><?php echo esc_html( $copy['tagline'] ); ?></p>
		</td>
		<td align="right" style="padding:0 0 18px;vertical-align:top;">
			<p style="margin:0;color:#d5683f;font-size:13px;font-weight:700;letter-spacing:1.2px;line-height:1.3;text-transform:uppercase;"><?php echo esc_html( $email_heading ); ?></p>
			<p style="margin:7px 0 0;color:#737783;font-size:13px;line-height:1.45;">#<?php echo esc_html( $order->get_order_number() ); ?> · <?php echo esc_html( $order_date ); ?></p>
		</td>
	</tr>
</table>

<h2 style="margin:0 0 12px;color:#292d36;font-size:28px;font-weight:700;line-height:1.25;"><?php echo esc_html( $copy['thanks'] ); ?></h2>
<p style="margin:0 0 28px;color:#555b67;font-size:15px;line-height:1.7;"><?php echo esc_html( $copy['intro'] ); ?></p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:0 0 28px;">
	<tr>
		<td width="48%" style="background:#f7f7f7;border-left:4px solid #d5683f;padding:18px;vertical-align:top;">
			<p style="margin:0 0 10px;color:#292d36;font-size:13px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;"><?php echo esc_html( $copy['billed_to'] ); ?></p>
			<p style="margin:0;color:#555b67;font-size:14px;line-height:1.6;">
				<?php echo $billing ? wp_kses_post( $billing ) : '&mdash;'; ?>
				<?php if ( $billing_mail ) : ?><br><?php echo esc_html( $billing_mail ); ?><?php endif; ?>
				<?php if ( $billing_phone ) : ?><br><?php echo esc_html( $billing_phone ); ?><?php endif; ?>
				<?php foreach ( $buyer_details as $detail ) : ?><br><strong><?php echo esc_html( $detail['label'] ); ?>:</strong> <?php echo esc_html( $detail['value'] ); ?><?php endforeach; ?>
			</p>
		</td>
		<td width="4%" style="font-size:1px;line-height:1px;">&nbsp;</td>
		<td width="48%" style="background:#f7f7f7;border-left:4px solid #292d36;padding:18px;vertical-align:top;">
			<p style="margin:0 0 10px;color:#292d36;font-size:13px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;"><?php echo esc_html( $copy['payment_details'] ); ?></p>
			<p style="margin:0;color:#555b67;font-size:14px;line-height:1.7;">
				<strong><?php echo esc_html( $copy['payment_method'] ); ?>:</strong> <?php echo esc_html( $payment_method ?: 'Card' ); ?><br>
				<strong><?php echo esc_html( $copy['order_number'] ); ?>:</strong> #<?php echo esc_html( $order->get_order_number() ); ?><br>
				<strong><?php echo esc_html( $copy['order_date'] ); ?>:</strong> <?php echo esc_html( $order_date ); ?>
			</p>
		</td>
	</tr>
</table>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:0 0 8px;">
	<thead>
		<tr style="background:#292d36;color:#ffffff;">
			<th align="left" style="padding:12px 14px;font-size:13px;font-weight:700;"><?php echo esc_html( $copy['description'] ); ?></th>
			<th align="center" width="70" style="padding:12px 14px;font-size:13px;font-weight:700;"><?php echo esc_html( $copy['quantity'] ); ?></th>
			<th align="right" width="130" style="padding:12px 14px;font-size:13px;font-weight:700;"><?php echo esc_html( $copy['amount'] ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $order->get_items( 'line_item' ) as $item ) : ?>
			<tr>
				<td style="border-bottom:1px solid #e1e2e5;padding:14px;color:#292d36;font-size:14px;line-height:1.5;"><?php echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ); ?></td>
				<td align="center" style="border-bottom:1px solid #e1e2e5;padding:14px;color:#555b67;font-size:14px;"><?php echo esc_html( $item->get_quantity() ); ?></td>
				<td align="right" style="border-bottom:1px solid #e1e2e5;padding:14px;color:#292d36;font-size:14px;font-weight:700;"><?php echo wp_kses_post( wc_price( $order->get_line_total( $item, true, true ), $price_args ) ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">
	<tr>
		<td align="right" style="padding:8px 14px;color:#737783;font-size:14px;"><?php echo esc_html( $copy['subtotal'] ); ?></td>
		<td align="right" width="130" style="padding:8px 14px;color:#292d36;font-size:14px;"><?php echo wp_kses_post( wc_price( $order->get_subtotal(), $price_args ) ); ?></td>
	</tr>
	<tr>
		<td align="right" style="border-top:2px solid #292d36;padding:11px 14px;color:#292d36;font-size:16px;font-weight:700;"><?php echo esc_html( $copy['total_paid'] ); ?></td>
		<td align="right" width="130" style="border-top:2px solid #292d36;padding:11px 14px;color:#292d36;font-size:16px;font-weight:700;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
	</tr>
</table>

<p style="margin:0 0 24px;text-align:center;"><span style="display:inline-block;background:#e7f5ec;border:1px solid #58a874;color:#216e3d;padding:10px 18px;font-size:13px;font-weight:700;letter-spacing:.8px;"><?php echo esc_html( $copy['payment_received'] ); ?></span></p>
<p style="margin:0 0 28px;color:#555b67;font-size:14px;line-height:1.7;text-align:center;"><?php echo esc_html( $copy['next_steps'] ); ?></p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;border-top:1px solid #e1e2e5;">
	<tr>
		<td align="center" style="padding:18px 0 0;color:#737783;font-size:12px;line-height:1.6;">
			<?php echo esc_html( $copy['company'] ); ?> · <?php echo esc_html( $copy['website'] ); ?> · <?php echo esc_html( $copy['email'] ); ?>
		</td>
	</tr>
</table>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
