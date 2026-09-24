<?php
/**
 * Branded merchant notification for a new WooCommerce order.
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
$billing          = $order->get_formatted_billing_address();
$billing_mail     = $order->get_billing_email();
$billing_phone    = $order->get_billing_phone();
$buyer_details    = CommercePresentation::order_buyer_details( $order, 'sr' );
$payment_method   = $order->get_payment_method_title();
$transaction_id   = $order->get_transaction_id();
$price_args       = array( 'currency' => $order->get_currency() );
$status           = wc_get_order_status_name( $order->get_status() );
$is_paid          = $order->is_paid();
$status_label     = $is_paid ? $copy['payment_confirmed'] : $copy['order_received'];
$status_bg        = $is_paid ? '#e9f7f0' : '#fff4e5';
$status_border    = $is_paid ? '#9fd9bd' : '#efc27a';
$status_color     = $is_paid ? '#147a58' : '#9a5b00';
$order_admin_url  = method_exists( $order, 'get_edit_order_url' ) ? $order->get_edit_order_url() : '';
$language_label   = 'sr' === $order_locale ? $copy['language_sr'] : $copy['language_en'];
?>
<!doctype html>
<html lang="sr">
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
			.mobile-pad { padding-right: 24px !important; padding-left: 24px !important; }
			.mobile-block { display: block !important; width: 100% !important; box-sizing: border-box !important; }
			.mobile-gap { display: block !important; width: 100% !important; height: 12px !important; }
			.mobile-title { font-size: 29px !important; line-height: 35px !important; }
			.mobile-button { display: block !important; width: auto !important; text-align: center !important; }
			.mobile-header-right { padding-top: 10px !important; text-align: left !important; }
			.mobile-qty { width: 42px !important; padding-right: 6px !important; padding-left: 6px !important; }
			.mobile-price { width: 82px !important; padding-right: 6px !important; padding-left: 6px !important; font-size: 11px !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
			.mobile-price * { white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
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
					<tr><td height="7" bgcolor="#dd6531" style="height:7px;background-color:#dd6531;font-size:0;line-height:0;">&nbsp;</td></tr>
					<tr>
						<td class="mobile-pad" bgcolor="#0f2f4b" style="padding:30px 42px;background-color:#0f2f4b;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;">
								<tr>
									<td class="mobile-block" valign="top"><p style="margin:0;color:#ffffff;font-size:24px;font-weight:700;line-height:1.2;"><?php echo esc_html( $copy['company'] ); ?></p><p style="margin:5px 0 0;color:#c9d4dd;font-size:11px;line-height:1.5;"><?php echo esc_html( $copy['tagline'] ); ?></p></td>
									<td class="mobile-block mobile-header-right" align="right" valign="top"><p style="margin:0;color:#f1a07d;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;"><?php echo esc_html( $email_heading ); ?></p><p style="margin:6px 0 0;color:#ffffff;font-size:12px;">#<?php echo esc_html( $order->get_order_number() ); ?><br><?php echo esc_html( $order_date ); ?></p></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:38px 42px 26px;">
							<h1 class="mobile-title" style="margin:0;color:#0f2f4b;font-size:34px;font-weight:700;line-height:40px;letter-spacing:-1px;"><?php echo esc_html( $copy['title'] ); ?></h1>
							<p style="margin:11px 0 0;color:#52606d;font-size:14px;line-height:1.65;"><?php echo esc_html( $copy['intro'] ); ?></p>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 28px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;">
								<tr>
									<td class="mobile-block" width="49%" valign="top" bgcolor="#f2f5f8" style="width:49%;padding:20px;background-color:#f2f5f8;border:1px solid #d9e2ea;">
										<p style="margin:0 0 10px;color:#6f7885;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;"><?php echo esc_html( $copy['customer'] ); ?></p>
										<div style="color:#172433;font-size:13px;line-height:1.55;"><?php echo $billing ? wp_kses_post( $billing ) : '&mdash;'; ?><?php if ( $billing_mail ) : ?><br><a href="mailto:<?php echo esc_attr( $billing_mail ); ?>" style="color:#245c91;text-decoration:underline;"><?php echo esc_html( $billing_mail ); ?></a><?php endif; ?><?php if ( $billing_phone ) : ?><br><?php echo esc_html( $billing_phone ); ?><?php endif; ?><?php foreach ( $buyer_details as $detail ) : ?><br><strong><?php echo esc_html( $detail['label'] ); ?>:</strong> <?php echo esc_html( $detail['value'] ); ?><?php endforeach; ?></div>
									</td>
									<td class="mobile-gap" width="2%" style="width:2%;font-size:1px;line-height:1px;">&nbsp;</td>
									<td class="mobile-block" width="49%" valign="top" bgcolor="#eaf2f8" style="width:49%;padding:20px;background-color:#eaf2f8;border:1px solid #d4e1eb;">
										<p style="margin:0 0 10px;color:#6f7885;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;"><?php echo esc_html( $copy['order_details'] ); ?></p>
										<p style="margin:0;color:#172433;font-size:13px;line-height:1.65;"><strong><?php echo esc_html( $copy['payment_method'] ); ?>:</strong> <?php echo esc_html( $payment_method ?: '-' ); ?><br><strong><?php echo esc_html( $copy['order_number'] ); ?>:</strong> #<?php echo esc_html( $order->get_order_number() ); ?><br><strong><?php echo esc_html( $copy['order_date'] ); ?>:</strong> <?php echo esc_html( $order_date ); ?><br><strong><?php echo esc_html( $copy['order_status'] ); ?>:</strong> <?php echo esc_html( $status ); ?><br><strong><?php echo esc_html( $copy['order_language'] ); ?>:</strong> <?php echo esc_html( $language_label ); ?><?php if ( $transaction_id ) : ?><br><strong><?php echo esc_html( $copy['transaction_id'] ); ?>:</strong> <?php echo esc_html( $transaction_id ); ?><?php endif; ?></p>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 8px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;border:1px solid #d9e2ea;">
								<thead><tr bgcolor="#0f2f4b" style="background-color:#0f2f4b;color:#ffffff;"><th align="left" style="padding:13px 15px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;"><?php echo esc_html( $copy['description'] ); ?></th><th class="mobile-qty" align="center" width="64" style="padding:13px 10px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;"><?php echo esc_html( $copy['quantity'] ); ?></th><th class="mobile-price" align="right" width="120" style="padding:13px 15px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;"><?php echo esc_html( $copy['amount'] ); ?></th></tr></thead>
								<tbody><?php foreach ( $order->get_items( 'line_item' ) as $item ) : ?><tr><td style="padding:16px 15px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;line-height:1.5;overflow-wrap:anywhere;"><?php echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $order_locale ) ); ?></td><td class="mobile-qty" align="center" style="padding:16px 10px;border-bottom:1px solid #d9e2ea;color:#34404d;font-size:13px;"><?php echo esc_html( $item->get_quantity() ); ?></td><td class="mobile-price" align="right" style="padding:16px 15px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;font-weight:700;"><?php echo wp_kses_post( wc_price( $order->get_line_total( $item, true, true ), $price_args ) ); ?></td></tr><?php endforeach; ?></tbody>
							</table>
						</td>
					</tr>
					<tr><td class="mobile-pad" style="padding:0 42px 24px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr><td align="right" style="padding:11px 15px;color:#34404d;font-size:13px;"><?php echo esc_html( $copy['subtotal'] ); ?></td><td class="mobile-price" align="right" width="120" style="padding:11px 15px;color:#172433;font-size:13px;"><?php echo wp_kses_post( wc_price( $order->get_subtotal(), $price_args ) ); ?></td></tr><tr><td align="right" style="border-top:1px solid #9eabb7;padding:12px 15px;color:#172433;font-size:14px;font-weight:700;"><?php echo esc_html( $copy['total'] ); ?></td><td class="mobile-price" align="right" width="120" style="border-top:1px solid #9eabb7;padding:12px 15px;color:#172433;font-size:14px;font-weight:700;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td></tr></table></td></tr>
					<tr><td class="mobile-pad" style="padding:0 42px 28px;"><div style="padding:14px 18px;background-color:<?php echo esc_attr( $status_bg ); ?>;border:1px solid <?php echo esc_attr( $status_border ); ?>;color:<?php echo esc_attr( $status_color ); ?>;font-size:13px;font-weight:700;letter-spacing:.7px;text-align:center;">&#10003; <?php echo esc_html( $status_label ); ?></div></td></tr>
					<?php if ( $order_admin_url ) : ?><tr><td class="mobile-pad" align="center" style="padding:0 42px 34px;"><table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;"><tr><td bgcolor="#dd6531" style="background-color:#dd6531;"><a class="mobile-button" href="<?php echo esc_url( $order_admin_url ); ?>" style="display:inline-block;padding:14px 24px;color:#ffffff;font-size:12px;font-weight:700;letter-spacing:.7px;text-decoration:none;"> <?php echo esc_html( $copy['view_order'] ); ?> &nbsp;&rarr;</a></td></tr></table></td></tr><?php endif; ?>
					<tr><td class="mobile-pad" style="padding:18px 42px;border-top:1px solid #d9e2ea;color:#6f7885;font-size:11px;line-height:1.5;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr><td class="mobile-footer"><?php echo esc_html( $copy['company'] ); ?></td><td class="mobile-footer" align="right"><?php echo esc_html( $copy['website'] ); ?> &middot; <?php echo esc_html( $copy['email'] ); ?></td></tr></table></td></tr>
				</table>
				<!--[if mso]></td></tr></table><![endif]-->
			</td>
		</tr>
	</table>
</body>
</html>
