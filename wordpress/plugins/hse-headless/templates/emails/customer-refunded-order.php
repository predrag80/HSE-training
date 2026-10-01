<?php
/**
 * Localized, email-client compatible customer refund confirmation.
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
$currency      = $order->get_currency();
$price_args    = array( 'currency' => $currency );
$refund_items  = $refund_order ? $refund_order->get_items( 'line_item' ) : array();
$display_items = $refund_items ?: $order->get_items( 'line_item' );
$intro         = sprintf( $copy['intro'], $order->get_order_number() );
?>
<!doctype html>
<html lang="<?php echo esc_attr( $locale ); ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="x-apple-disable-message-reformatting">
	<meta name="color-scheme" content="light">
	<meta name="supported-color-schemes" content="light">
	<title><?php echo esc_html( $email_heading ); ?></title>
	<!--[if mso]>
	<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
	<![endif]-->
	<style>
		@media only screen and (max-width: 680px) {
			.email-shell { width: 100% !important; max-width: 100% !important; min-width: 0 !important; }
			.mobile-pad { padding-right: 24px !important; padding-left: 24px !important; }
			.mobile-block { display: block !important; width: 100% !important; box-sizing: border-box !important; }
			.mobile-header-right { padding-top: 10px !important; text-align: left !important; }
			.mobile-title { font-size: 30px !important; line-height: 36px !important; }
			.mobile-qty { width: 42px !important; padding-right: 6px !important; padding-left: 6px !important; }
			.mobile-price { width: 88px !important; padding-right: 6px !important; padding-left: 6px !important; font-size: 11px !important; overflow-wrap: anywhere !important; }
			.mobile-footer { display: block !important; width: 100% !important; padding-top: 4px !important; text-align: left !important; }
		}
	</style>
</head>
<body style="width:100%;margin:0;padding:0;background-color:#f3f5f7;color:#172433;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
	<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;font-size:1px;line-height:1px;"><?php echo esc_html( $copy['preheader'] ); ?></div>
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f3f5f7" style="width:100%;border-collapse:collapse;background-color:#f3f5f7;">
		<tr><td align="center" style="padding:32px 14px;">
			<!--[if mso]><table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
			<table class="email-shell" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:680px;table-layout:fixed;border-collapse:collapse;background-color:#ffffff;box-shadow:0 16px 44px rgba(15,47,75,0.10);">
				<tr><td height="7" bgcolor="#245c91" style="height:7px;background-color:#245c91;font-size:0;line-height:0;">&nbsp;</td></tr>
				<tr><td class="mobile-pad" style="padding:34px 42px 28px;">
					<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr>
						<td class="mobile-block" valign="top" style="padding:0 0 18px;"><p style="margin:0;color:#0f2f4b;font-size:25px;font-weight:700;line-height:1.2;"><?php echo esc_html( $copy['company'] ); ?></p><p style="margin:5px 0 0;color:#6f7885;font-size:12px;line-height:1.5;"><?php echo esc_html( $copy['tagline'] ); ?></p></td>
						<td class="mobile-block mobile-header-right" align="right" valign="top" style="padding:0 0 18px;"><p style="margin:0;color:#245c91;font-size:13px;font-weight:700;letter-spacing:1px;line-height:1.3;text-transform:uppercase;"><?php echo esc_html( $email_heading ); ?></p><p style="margin:5px 0 0;color:#172433;font-size:12px;line-height:1.5;">#<?php echo esc_html( $order->get_order_number() ); ?><br><?php echo esc_html( $date_text ); ?></p></td>
					</tr></table>
					<div style="height:5px;background-color:#245c91;font-size:0;line-height:0;">&nbsp;</div>
				</td></tr>
				<tr><td class="mobile-pad" style="padding:12px 42px 24px;"><h1 class="mobile-title" style="margin:0;color:#0f2f4b;font-size:34px;font-weight:700;line-height:40px;letter-spacing:-1px;"><?php echo esc_html( $copy['title'] ); ?></h1><p style="margin:10px 0 0;color:#34404d;font-size:14px;line-height:1.65;"><?php echo esc_html( $intro ); ?></p></td></tr>
				<tr><td class="mobile-pad" style="padding:0 42px 26px;">
					<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#edf6f2" style="width:100%;table-layout:fixed;border-collapse:collapse;background-color:#edf6f2;border:1px solid #a7d9c0;"><tr>
						<td class="mobile-block" style="padding:18px 20px;color:#147a58;font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;">&#10003; <?php echo esc_html( $copy['status'] ); ?></td>
						<td class="mobile-block" align="right" style="padding:18px 20px;color:#0f2f4b;font-size:22px;font-weight:700;"><?php echo wp_kses_post( wc_price( $refund_amount, $price_args ) ); ?></td>
					</tr></table>
				</td></tr>
				<tr><td class="mobile-pad" style="padding:0 42px 18px;">
					<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr>
						<td class="mobile-block" width="50%" bgcolor="#f2f5f8" style="padding:17px 20px;background-color:#f2f5f8;border:1px solid #d9e2ea;"><p style="margin:0;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['order_number'] ); ?></p><p style="margin:7px 0 0;color:#172433;font-size:14px;font-weight:700;">#<?php echo esc_html( $order->get_order_number() ); ?></p></td>
						<td class="mobile-block" width="50%" bgcolor="#f2f5f8" style="padding:17px 20px;background-color:#f2f5f8;border:1px solid #d9e2ea;"><p style="margin:0;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['refund_date'] ); ?></p><p style="margin:7px 0 0;color:#172433;font-size:14px;font-weight:700;"><?php echo esc_html( $date_text ); ?></p></td>
					</tr></table>
				</td></tr>
				<tr><td class="mobile-pad" style="padding:0 42px 10px;"><p style="margin:0;color:#0f2f4b;font-size:15px;font-weight:700;"><?php echo esc_html( $copy['order_details'] ); ?></p></td></tr>
				<tr><td class="mobile-pad" style="padding:0 42px 24px;">
					<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;border:1px solid #d9e2ea;">
						<thead><tr bgcolor="#0f2f4b" style="background-color:#0f2f4b;color:#ffffff;"><th align="left" style="padding:13px 15px;font-size:11px;text-transform:uppercase;"><?php echo esc_html( $copy['description'] ); ?></th><th class="mobile-qty" align="center" width="64" style="padding:13px 10px;font-size:11px;text-transform:uppercase;"><?php echo esc_html( $copy['quantity'] ); ?></th><th class="mobile-price" align="right" width="120" style="padding:13px 15px;font-size:11px;text-transform:uppercase;"><?php echo esc_html( $copy['line_amount'] ); ?></th></tr></thead>
						<tbody><?php foreach ( $display_items as $item ) :
							$item_quantity = abs( (float) $item->get_quantity() );
							$item_amount   = $refund_items ? abs( (float) $item->get_total() + (float) $item->get_total_tax() ) : abs( (float) $order->get_line_total( $item, true, true ) );
							?><tr><td style="padding:16px 15px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;line-height:1.5;overflow-wrap:anywhere;"><?php echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ); ?></td><td class="mobile-qty" align="center" style="padding:16px 10px;border-bottom:1px solid #d9e2ea;color:#34404d;font-size:13px;"><?php echo esc_html( wc_format_decimal( $item_quantity ) ); ?></td><td class="mobile-price" align="right" style="padding:16px 15px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;font-weight:700;"><?php echo wp_kses_post( wc_price( $item_amount, $price_args ) ); ?></td></tr><?php endforeach; ?></tbody>
					</table>
				</td></tr>
				<tr><td class="mobile-pad" style="padding:0 42px 30px;"><div style="padding:18px 20px;background-color:#fff8f4;border:1px solid #efc6b4;color:#34404d;font-size:13px;line-height:1.65;"><p style="margin:0 0 9px;"><?php echo esc_html( $copy['timing'] ); ?></p><p style="margin:0;"><?php echo esc_html( $copy['fiscal_notice'] ); ?></p></div></td></tr>
				<tr><td class="mobile-pad" align="center" style="padding:0 42px 34px;"><p style="margin:0;color:#6f7885;font-size:13px;line-height:1.6;"><?php echo esc_html( $copy['help'] ); ?></p></td></tr>
				<tr><td class="mobile-pad" style="padding:18px 42px;border-top:1px solid #d9e2ea;color:#6f7885;font-size:11px;line-height:1.5;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr><td class="mobile-footer"><?php echo esc_html( $copy['company'] ); ?> &middot; <?php echo esc_html( $copy['country'] ); ?></td><td class="mobile-footer" align="right"><?php echo esc_html( $copy['website'] ); ?> &middot; <?php echo esc_html( $copy['email'] ); ?></td></tr></table></td></tr>
			</table>
			<!--[if mso]></td></tr></table><![endif]-->
		</td></tr>
	</table>
</body>
</html>
