<?php
/**
 * Shared email-client compatible layout for unsuccessful payments.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCheckoutSource;
use HSETraining\Headless\Commerce\CommerceCustomerEmail;

defined( 'ABSPATH' ) || exit;

$outcome       = isset( $outcome ) && 'cancelled' === $outcome ? 'cancelled' : 'failed';
$cancelled     = 'cancelled' === $outcome;
$locale        = CommerceCustomerEmail::order_locale( $order );
$copy          = CommerceCustomerEmail::unsuccessful_payment_copy( $locale, $outcome );
$created       = $order->get_date_created();
$order_date    = $created ? $created->date_i18n( 'sr' === $locale ? 'd.m.Y.' : 'j F Y' ) : '';
$price_args    = array( 'currency' => $order->get_currency() );
$intro         = sprintf( $copy['intro'], $order->get_order_number() );
$payment_title = $order->get_payment_method_title();
$retry_url     = '';

if ( ! $cancelled && method_exists( $order, 'get_checkout_payment_url' ) ) {
	$retry_url = add_query_arg(
		array(
			'lang'       => $locale,
			'hse_source' => CommerceCheckoutSource::for_order( $order ),
		),
		$order->get_checkout_payment_url()
	);
}
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
			.mobile-qty { width: 42px !important; padding-right: 6px !important; padding-left: 6px !important; }
			.mobile-price { width: 88px !important; padding-right: 7px !important; padding-left: 7px !important; font-size: 11px !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
			.mobile-price * { white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
			.mobile-footer { display: block !important; width: 100% !important; padding-top: 4px !important; text-align: left !important; }
			.mobile-button { display: block !important; box-sizing: border-box !important; width: 100% !important; text-align: center !important; }
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
					<tr><td height="7" bgcolor="#b33a2f" style="height:7px;background-color:#b33a2f;font-size:0;line-height:0;">&nbsp;</td></tr>
					<tr>
						<td class="mobile-pad" style="padding:34px 42px 28px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;">
								<tr>
									<td class="mobile-block" valign="top" style="padding:0 0 18px;"><p style="margin:0;color:#0f2f4b;font-size:25px;font-weight:700;line-height:1.2;letter-spacing:-0.4px;"><?php echo esc_html( $copy['company'] ); ?></p><p style="margin:5px 0 0;color:#6f7885;font-size:12px;line-height:1.5;"><?php echo esc_html( $copy['tagline'] ); ?></p></td>
									<td class="mobile-block mobile-header-right" align="right" valign="top" style="padding:0 0 18px;"><p style="margin:0;color:#b33a2f;font-size:13px;font-weight:700;letter-spacing:1px;line-height:1.3;text-transform:uppercase;"><?php echo esc_html( $email_heading ); ?></p><p style="margin:5px 0 0;color:#172433;font-size:12px;line-height:1.5;">#<?php echo esc_html( $order->get_order_number() ); ?><br><?php echo esc_html( $order_date ); ?></p></td>
								</tr>
							</table>
							<div style="height:5px;background-color:#b33a2f;font-size:0;line-height:0;">&nbsp;</div>
						</td>
					</tr>
					<tr><td class="mobile-pad" style="padding:12px 42px 22px;"><h1 class="mobile-title" style="margin:0;color:#0f2f4b;font-size:34px;font-weight:700;line-height:40px;letter-spacing:-1px;"><?php echo esc_html( $copy['title'] ); ?></h1><p style="margin:10px 0 0;color:#34404d;font-size:14px;line-height:1.65;"><?php echo esc_html( $intro ); ?></p></td></tr>
					<tr><td class="mobile-pad" style="padding:0 42px 22px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#fff4f2" style="width:100%;border-collapse:collapse;background-color:#fff4f2;border-left:5px solid #b33a2f;"><tr><td style="padding:17px 19px;"><p style="margin:0;color:#8f2f27;font-size:12px;font-weight:700;letter-spacing:.8px;line-height:1.3;text-transform:uppercase;"><?php echo esc_html( $copy['status'] ); ?></p><p style="margin:8px 0 0;color:#34404d;font-size:13px;line-height:1.65;"><?php echo esc_html( $copy['reassurance'] ); ?></p></td></tr></table></td></tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 10px;">
							<p style="margin:0 0 10px;color:#0f2f4b;font-size:15px;font-weight:700;"><?php echo esc_html( $copy['order_summary'] ); ?></p>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;border:1px solid #d9e2ea;">
								<thead><tr bgcolor="#0f2f4b" style="background-color:#0f2f4b;color:#ffffff;"><th align="left" style="padding:12px 14px;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['description'] ); ?></th><th class="mobile-qty" align="center" width="60" style="padding:12px 8px;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['quantity'] ); ?></th><th class="mobile-price" align="right" width="120" style="padding:12px 14px;font-size:11px;font-weight:700;text-transform:uppercase;"><?php echo esc_html( $copy['amount'] ); ?></th></tr></thead>
								<tbody><?php foreach ( $order->get_items( 'line_item' ) as $item ) : ?><tr><td style="padding:15px 14px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;line-height:1.5;"><?php echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ); ?></td><td class="mobile-qty" align="center" style="padding:15px 8px;border-bottom:1px solid #d9e2ea;color:#34404d;font-size:13px;"><?php echo esc_html( $item->get_quantity() ); ?></td><td class="mobile-price" align="right" style="padding:15px 14px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;font-weight:700;"><?php echo wp_kses_post( wc_price( $order->get_line_total( $item, true, true ), $price_args ) ); ?></td></tr><?php endforeach; ?></tbody>
							</table>
						</td>
					</tr>
					<tr><td class="mobile-pad" style="padding:0 42px 24px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;"><?php if ( $payment_title ) : ?><tr><td style="padding:12px 14px;border-bottom:1px solid #d9e2ea;color:#6f7885;font-size:12px;"><?php echo esc_html( $copy['payment_method'] ); ?></td><td align="right" style="padding:12px 14px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:12px;font-weight:700;"><?php echo esc_html( $payment_title ); ?></td></tr><?php endif; ?><tr><td style="padding:14px;color:#172433;font-size:14px;font-weight:700;"><?php echo esc_html( $copy['total'] ); ?></td><td class="mobile-price" align="right" width="120" style="padding:14px;color:#172433;font-size:14px;font-weight:700;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td></tr></table></td></tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 30px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#eef4f8" style="width:100%;border-collapse:collapse;background-color:#eef4f8;">
								<tr><td align="center" style="padding:22px 24px;"><p style="margin:0;color:#0f2f4b;font-size:16px;font-weight:700;line-height:1.4;"><?php echo esc_html( $copy['retry_title'] ); ?></p><p style="margin:8px 0 0;color:#586473;font-size:13px;line-height:1.65;"><?php echo esc_html( $copy['retry_text'] ); ?></p><?php if ( $retry_url ) : ?><table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:18px auto 0;"><tr><td bgcolor="#d96838" style="background-color:#d96838;"><a class="mobile-button" href="<?php echo esc_url( $retry_url ); ?>" style="display:inline-block;padding:14px 26px;color:#ffffff;font-size:13px;font-weight:700;line-height:1.2;text-decoration:none;text-transform:uppercase;"><?php echo esc_html( $copy['retry_button'] ); ?> &rarr;</a></td></tr></table><?php endif; ?></td></tr>
							</table>
						</td>
					</tr>
					<tr><td class="mobile-pad" align="center" style="padding:0 42px 32px;"><p style="margin:0;color:#6f7885;font-size:13px;line-height:1.6;"><?php echo esc_html( $copy['help'] ); ?></p></td></tr>
					<tr><td class="mobile-pad" style="padding:18px 42px;border-top:1px solid #d9e2ea;color:#6f7885;font-size:11px;line-height:1.5;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr><td class="mobile-footer"><?php echo esc_html( $copy['company'] ); ?> &middot; <?php echo esc_html( $copy['country'] ); ?></td><td class="mobile-footer" align="right"><?php echo esc_html( $copy['website'] ); ?> &middot; <?php echo esc_html( $copy['email'] ); ?></td></tr></table></td></tr>
				</table>
				<!--[if mso]></td></tr></table><![endif]-->
			</td>
		</tr>
	</table>
</body>
</html>
