<?php
/**
 * Localized, email-client compatible bank-transfer instructions.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceBankTransfer;
use HSETraining\Headless\Commerce\CommerceCustomerEmail;

defined( 'ABSPATH' ) || exit;

$locale     = CommerceCustomerEmail::order_locale( $order );
$copy       = CommerceBankTransfer::copy( $locale );
$payment    = CommerceBankTransfer::instructions_for_order( $order );
$created    = $order->get_date_created();
$order_date = $created ? $created->date_i18n( 'sr' === $locale ? 'd.m.Y.' : 'j F Y' ) : '';
$price_args = array( 'currency' => $order->get_currency() );
$intro      = sprintf( $copy['intro'], $order->get_order_number() );
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
			.email-shell td, .email-shell p, .email-shell h1 { overflow-wrap: anywhere !important; }
			.mobile-pad { padding-right: 22px !important; padding-left: 22px !important; }
			.mobile-block { display: block !important; width: 100% !important; box-sizing: border-box !important; }
			.mobile-header-right { padding-top: 10px !important; text-align: left !important; }
			.mobile-title { font-size: 29px !important; line-height: 35px !important; }
			.mobile-label { width: 42% !important; }
			.mobile-price { width: 92px !important; padding-right: 7px !important; padding-left: 7px !important; font-size: 11px !important; }
			.mobile-footer { display: block !important; width: 100% !important; padding-top: 4px !important; text-align: left !important; }
		}
	</style>
</head>
<body style="width:100%;margin:0;padding:0;background-color:#f3f5f7;color:#172433;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
	<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;font-size:1px;line-height:1px;"><?php echo esc_html( $copy['preheader'] ); ?></div>
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f3f5f7" style="width:100%;border-collapse:collapse;background-color:#f3f5f7;">
		<tr>
			<td align="center" style="padding:32px 14px;">
				<!--[if mso]><table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
				<table class="email-shell" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:680px;table-layout:fixed;border-collapse:collapse;background-color:#ffffff;box-shadow:0 16px 44px rgba(15,47,75,0.10);">
					<tr><td height="7" bgcolor="#d96838" style="height:7px;background-color:#d96838;font-size:0;line-height:0;">&nbsp;</td></tr>
					<tr>
						<td class="mobile-pad" style="padding:34px 42px 28px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;">
								<tr>
									<td class="mobile-block" valign="top" style="padding:0 0 18px;"><p style="margin:0;color:#0f2f4b;font-size:25px;font-weight:700;line-height:1.2;letter-spacing:-0.4px;"><?php echo esc_html( $copy['company'] ); ?></p><p style="margin:5px 0 0;color:#6f7885;font-size:12px;line-height:1.5;"><?php echo esc_html( $copy['tagline'] ); ?></p></td>
									<td class="mobile-block mobile-header-right" align="right" valign="top" style="padding:0 0 18px;"><p style="margin:0;color:#d96838;font-size:13px;font-weight:700;letter-spacing:1px;line-height:1.3;text-transform:uppercase;"><?php echo esc_html( $email_heading ); ?></p><p style="margin:5px 0 0;color:#172433;font-size:12px;line-height:1.5;">#<?php echo esc_html( $order->get_order_number() ); ?><br><?php echo esc_html( $order_date ); ?></p></td>
								</tr>
							</table>
							<div style="height:5px;background-color:#d96838;font-size:0;line-height:0;">&nbsp;</div>
						</td>
					</tr>
					<tr><td class="mobile-pad" style="padding:12px 42px 24px;"><h1 class="mobile-title" style="margin:0;color:#0f2f4b;font-size:34px;font-weight:700;line-height:40px;letter-spacing:-1px;"><?php echo esc_html( $copy['title'] ); ?></h1><p style="margin:10px 0 0;color:#34404d;font-size:14px;line-height:1.65;"><?php echo esc_html( $intro ); ?></p></td></tr>
					<?php if ( $payment ) : ?>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 28px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#fff8f4" style="width:100%;table-layout:fixed;border-collapse:collapse;background-color:#fff8f4;border:2px solid #d96838;">
								<tr><td colspan="2" bgcolor="#292d36" style="padding:14px 18px;background-color:#292d36;color:#ffffff;font-size:14px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;"><?php echo esc_html( $payment['form_title'] ); ?></td></tr>
								<tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['payer'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:13px;font-weight:700;"><?php echo esc_html( $payment['payer'] ?: '-' ); ?></td></tr>
								<tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['recipient'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:13px;font-weight:700;"><?php echo esc_html( $payment['recipient'] ?: $copy['company'] ); ?><br><span style="font-weight:400;"><?php echo esc_html( $payment['recipient_address'] ); ?></span><br><span style="font-weight:400;">PIB: <?php echo esc_html( $payment['seller_tax_id'] ); ?> · MB: <?php echo esc_html( $payment['seller_reg_number'] ); ?></span></td></tr>
								<?php if ( $payment['bank_name'] ) : ?><tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['bank'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:13px;"><?php echo esc_html( $payment['bank_name'] ); ?></td></tr><?php endif; ?>
								<?php if ( $payment['is_domestic'] ) : ?>
									<tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['account'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:14px;font-weight:700;"><?php echo esc_html( $payment['account_number'] ?: '-' ); ?></td></tr>
									<tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['payment_code'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:13px;font-weight:700;"><?php echo esc_html( $payment['payment_code'] ); ?></td></tr>
								<?php else : ?>
									<tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['iban'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:13px;font-weight:700;overflow-wrap:anywhere;"><?php echo esc_html( $payment['iban'] ?: '-' ); ?></td></tr>
									<tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['bic'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:13px;font-weight:700;"><?php echo esc_html( $payment['bic'] ?: '-' ); ?></td></tr>
								<?php endif; ?>
								<tr><td class="mobile-label" width="38%" style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['purpose_label'] ); ?></td><td style="padding:13px 18px;border-bottom:1px solid #eaded8;color:#172433;font-size:13px;line-height:1.5;"><?php echo esc_html( $payment['purpose'] ); ?></td></tr>
								<tr><td class="mobile-label" width="38%" style="padding:15px 18px;color:#6f7885;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['amount'] ); ?></td><td style="padding:15px 18px;color:#172433;font-size:18px;font-weight:700;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td></tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>
					<?php if ( $payment && ! $payment['is_domestic'] ) : ?><tr><td class="mobile-pad" style="padding:0 42px 20px;"><div style="padding:14px 18px;background-color:#fff8f4;border-left:4px solid #d96838;color:#34404d;font-size:13px;line-height:1.65;"><?php echo esc_html( $copy['attachment_notice'] ); ?></div></td></tr><?php endif; ?>
					<tr><td class="mobile-pad" style="padding:0 42px 26px;"><div style="padding:16px 18px;background-color:#eef4f8;border-left:4px solid #245c91;color:#34404d;font-size:13px;line-height:1.65;"><?php echo esc_html( $copy['notice'] ); ?></div></td></tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 10px;">
							<p style="margin:0 0 10px;color:#0f2f4b;font-size:15px;font-weight:700;"><?php echo esc_html( $copy['order_summary'] ); ?></p>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;border:1px solid #d9e2ea;">
								<thead><tr bgcolor="#0f2f4b" style="background-color:#0f2f4b;color:#ffffff;"><th align="left" style="padding:12px 14px;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['description'] ); ?></th><th align="center" width="60" style="padding:12px 8px;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['quantity'] ); ?></th><th class="mobile-price" align="right" width="120" style="padding:12px 14px;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['line_amount'] ); ?></th></tr></thead>
								<tbody><?php foreach ( $order->get_items( 'line_item' ) as $item ) : ?><tr><td style="padding:15px 14px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;line-height:1.5;"><?php echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ); ?></td><td align="center" style="padding:15px 8px;border-bottom:1px solid #d9e2ea;color:#34404d;font-size:13px;"><?php echo esc_html( $item->get_quantity() ); ?></td><td class="mobile-price" align="right" style="padding:15px 14px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;font-weight:700;"><?php echo wp_kses_post( wc_price( $order->get_line_total( $item, true, true ), $price_args ) ); ?></td></tr><?php endforeach; ?></tbody>
							</table>
						</td>
					</tr>
					<tr><td class="mobile-pad" style="padding:0 42px 28px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td align="right" style="padding:12px 14px;color:#172433;font-size:14px;font-weight:700;"><?php echo esc_html( $copy['total'] ); ?></td><td class="mobile-price" align="right" width="120" style="padding:12px 14px;color:#172433;font-size:14px;font-weight:700;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td></tr></table></td></tr>
					<tr><td class="mobile-pad" align="center" style="padding:4px 42px 34px;"><p style="margin:0;color:#6f7885;font-size:13px;line-height:1.6;"><?php echo esc_html( $copy['help'] ); ?></p></td></tr>
					<tr><td class="mobile-pad" style="padding:18px 42px;border-top:1px solid #d9e2ea;color:#6f7885;font-size:11px;line-height:1.5;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr><td class="mobile-footer"><?php echo esc_html( $copy['company'] ); ?> &middot; <?php echo esc_html( $copy['country'] ); ?></td><td class="mobile-footer" align="right"><?php echo esc_html( $copy['website'] ); ?> &middot; <?php echo esc_html( $copy['email'] ); ?></td></tr></table></td></tr>
				</table>
				<!--[if mso]></td></tr></table><![endif]-->
			</td>
		</tr>
	</table>
</body>
</html>
