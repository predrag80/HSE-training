<?php
/**
 * Localized, email-client compatible wrapper for the official BokaPOS receipt.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceCustomerEmail;

defined( 'ABSPATH' ) || exit;

$locale     = CommerceCustomerEmail::order_locale( $order );
$copy       = CommerceCustomerEmail::fiscal_receipt_copy( $locale );
$currency   = $order->get_currency();
$price_args = array( 'currency' => $currency );
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
			.mobile-pad { padding-right: 22px !important; padding-left: 22px !important; }
			.mobile-block { display: block !important; width: 100% !important; box-sizing: border-box !important; }
			.mobile-header-right { padding-top: 10px !important; text-align: left !important; }
			.mobile-title { font-size: 28px !important; line-height: 34px !important; }
			.mobile-qty { width: 40px !important; padding-right: 5px !important; padding-left: 5px !important; }
			.mobile-price { width: 82px !important; padding-right: 6px !important; padding-left: 6px !important; font-size: 11px !important; word-break: break-word !important; }
			.mobile-button { display: block !important; box-sizing: border-box !important; width: 100% !important; margin: 0 0 10px !important; text-align: center !important; }
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
					<tr><td height="7" bgcolor="#245c91" style="height:7px;background-color:#245c91;font-size:0;line-height:0;">&nbsp;</td></tr>
					<tr>
						<td class="mobile-pad" style="padding:34px 42px 28px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;">
								<tr>
									<td class="mobile-block" valign="top" style="padding:0 0 18px;">
										<p style="margin:0;color:#0f2f4b;font-size:25px;font-weight:700;line-height:1.2;letter-spacing:-0.4px;"><?php echo esc_html( $copy['company'] ); ?></p>
										<p style="margin:5px 0 0;color:#6f7885;font-size:12px;line-height:1.5;"><?php echo esc_html( $copy['tagline'] ); ?></p>
									</td>
									<td class="mobile-block mobile-header-right" align="right" valign="top" style="padding:0 0 18px;">
										<p style="margin:0;color:#245c91;font-size:13px;font-weight:700;letter-spacing:1px;line-height:1.3;text-transform:uppercase;"><?php echo esc_html( $email_heading ); ?></p>
										<p style="margin:5px 0 0;color:#172433;font-size:12px;line-height:1.5;">#<?php echo esc_html( $order->get_order_number() ); ?></p>
									</td>
								</tr>
							</table>
							<div style="height:5px;background-color:#245c91;font-size:0;line-height:0;">&nbsp;</div>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:12px 42px 26px;">
							<h1 class="mobile-title" style="margin:0;color:#0f2f4b;font-size:34px;font-weight:700;line-height:40px;letter-spacing:-1px;"><?php echo esc_html( $copy['title'] ); ?></h1>
							<p style="margin:10px 0 0;color:#34404d;font-size:14px;line-height:1.65;"><?php echo esc_html( $intro ); ?></p>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 28px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f2f5f8" style="width:100%;border-collapse:collapse;background-color:#f2f5f8;border:1px solid #d9e2ea;">
								<tr><td style="padding:20px 20px 7px;color:#6f7885;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;"><?php echo esc_html( $copy['receipt_number'] ); ?></td></tr>
								<tr><td style="padding:0 20px 15px;color:#0f2f4b;font-family:Menlo,Consolas,monospace;font-size:16px;font-weight:700;line-height:1.5;overflow-wrap:anywhere;"><?php echo esc_html( $pfr_number ); ?></td></tr>
								<tr><td style="padding:0 20px 6px;color:#34404d;font-size:13px;line-height:1.55;"><strong><?php echo esc_html( $copy['receipt_time'] ); ?>:</strong> <?php echo esc_html( $pfr_time ); ?></td></tr>
								<tr><td style="padding:0 20px 20px;color:#34404d;font-size:13px;line-height:1.55;"><strong><?php echo esc_html( $copy['amount'] ); ?>:</strong> <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td></tr>
							</table>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 30px;">
							<?php if ( $verification_url ) : ?><a class="mobile-button" href="<?php echo esc_url( $verification_url ); ?>" style="display:inline-block;margin:0 8px 10px 0;padding:12px 17px;background-color:#245c91;color:#ffffff;font-size:12px;font-weight:700;line-height:1.4;text-decoration:none;"><?php echo esc_html( $copy['verify'] ); ?></a><?php endif; ?>
							<?php if ( $pdf_url ) : ?><a class="mobile-button" href="<?php echo esc_url( $pdf_url ); ?>" style="display:inline-block;margin:0 0 10px;padding:11px 16px;border:1px solid #245c91;color:#245c91;font-size:12px;font-weight:700;line-height:1.4;text-decoration:none;"><?php echo esc_html( $copy['download'] ); ?></a><?php endif; ?>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:0 42px 12px;">
							<p style="margin:0 0 10px;color:#0f2f4b;font-size:14px;font-weight:700;"><?php echo esc_html( $copy['order_summary'] ); ?></p>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;border:1px solid #d9e2ea;">
								<thead><tr bgcolor="#0f2f4b" style="background-color:#0f2f4b;color:#ffffff;"><th align="left" style="padding:13px 15px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;"><?php echo esc_html( $copy['description'] ); ?></th><th class="mobile-qty" align="center" width="64" style="padding:13px 10px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;"><?php echo esc_html( $copy['quantity'] ); ?></th><th class="mobile-price" align="right" width="120" style="padding:13px 15px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;"><?php echo esc_html( $copy['line_amount'] ); ?></th></tr></thead>
								<tbody><?php foreach ( $order->get_items( 'line_item' ) as $item ) : ?><tr><td style="padding:16px 15px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;line-height:1.5;overflow-wrap:anywhere;"><?php echo esc_html( CommerceCustomerEmail::localized_item_name( $item, $locale ) ); ?></td><td class="mobile-qty" align="center" style="padding:16px 10px;border-bottom:1px solid #d9e2ea;color:#34404d;font-size:13px;"><?php echo esc_html( $item->get_quantity() ); ?></td><td class="mobile-price" align="right" style="padding:16px 15px;border-bottom:1px solid #d9e2ea;color:#172433;font-size:13px;font-weight:700;"><?php echo wp_kses_post( wc_price( $order->get_line_total( $item, true, true ), $price_args ) ); ?></td></tr><?php endforeach; ?></tbody>
							</table>
						</td>
					</tr>
					<tr><td class="mobile-pad" style="padding:14px 42px 30px;"><div style="padding:16px 18px;background-color:#eef5fb;border-left:4px solid #245c91;color:#34404d;font-size:13px;line-height:1.65;"><?php echo esc_html( $copy['official_notice'] ); ?></div><p style="margin:16px 0 0;color:#6f7885;font-size:13px;line-height:1.6;"><?php echo esc_html( $copy['keep_receipt'] ); ?></p></td></tr>
					<tr><td class="mobile-pad" style="padding:18px 42px;border-top:1px solid #d9e2ea;color:#6f7885;font-size:11px;line-height:1.5;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;border-collapse:collapse;"><tr><td class="mobile-footer"><?php echo esc_html( $copy['company'] ); ?> &middot; <?php echo esc_html( $copy['country'] ); ?></td><td class="mobile-footer" align="right"><?php echo esc_html( $copy['website'] ); ?> &middot; <?php echo esc_html( $copy['email'] ); ?></td></tr></table></td></tr>
				</table>
				<!--[if mso]></td></tr></table><![endif]-->
			</td>
		</tr>
	</table>
</body>
</html>
