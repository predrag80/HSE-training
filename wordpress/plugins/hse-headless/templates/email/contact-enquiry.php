<?php
/**
 * Table-based contact email compatible with Gmail, Outlook, and Apple Mail.
 *
 * @var array<string, mixed> $data Normalized template data.
 *
 * @package HSETraining\Headless
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html lang="<?php echo esc_attr( $data['locale'] ); ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="x-apple-disable-message-reformatting">
	<meta name="color-scheme" content="light">
	<meta name="supported-color-schemes" content="light">
	<title><?php echo esc_html( $data['labels']['title'] ); ?></title>
	<!--[if mso]>
	<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
	<![endif]-->
	<style>
		@media only screen and (max-width: 640px) {
			.email-shell { width: 100% !important; }
			.mobile-pad { padding-right: 24px !important; padding-left: 24px !important; }
			.mobile-title { font-size: 30px !important; line-height: 36px !important; }
			.mobile-button { display: block !important; width: auto !important; }
		}
	</style>
</head>
<body style="width:100%;margin:0;padding:0;background-color:#f2f2f0;color:#292d36;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
	<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;line-height:1px;font-size:1px;">
		<?php echo esc_html( $data['labels']['preheader'] ); ?>
	</div>
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f2f2f0" style="width:100%;border-collapse:collapse;background-color:#f2f2f0;">
		<tr>
			<td align="center" style="padding:36px 16px;">
				<!--[if mso]><table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
				<table class="email-shell" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:640px;border-collapse:collapse;background-color:#ffffff;box-shadow:0 18px 50px rgba(41,45,54,0.12);">
					<tr>
						<td height="6" bgcolor="#dd6531" style="height:6px;background-color:#dd6531;font-size:0;line-height:0;">&nbsp;</td>
					</tr>
					<tr>
						<td class="mobile-pad" bgcolor="#292d36" style="padding:30px 48px;background-color:#292d36;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;">
								<tr>
									<td valign="middle" style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
										<div style="font-size:25px;font-weight:700;line-height:28px;letter-spacing:-0.7px;">HSE <span style="color:#f2a27e;">TRAINING</span></div>
										<div style="padding-top:4px;color:#d7d8dc;font-size:10px;font-weight:700;line-height:14px;letter-spacing:1.8px;">DOO · BELGRADE</div>
									</td>
									<td align="right" valign="middle" style="color:#f2a27e;font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;line-height:14px;letter-spacing:1.5px;">
										<?php echo esc_html( $data['labels']['eyebrow'] ); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:46px 48px 20px;background-color:#ffffff;">
							<h1 class="mobile-title" style="margin:0;color:#292d36;font-family:Arial,Helvetica,sans-serif;font-size:38px;font-weight:700;line-height:44px;letter-spacing:-1.4px;">
								<?php echo esc_html( $data['labels']['title'] ); ?>
							</h1>
							<p style="margin:14px 0 0;color:#6b6e7b;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:25px;">
								<?php echo esc_html( $data['labels']['intro'] ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:12px 48px 8px;background-color:#ffffff;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;border-top:1px solid #e3e3e0;">
								<tr>
									<td width="34%" valign="top" style="padding:16px 12px 16px 0;border-bottom:1px solid #e3e3e0;color:#808291;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;line-height:18px;letter-spacing:1px;text-transform:uppercase;">
										<?php echo esc_html( $data['labels']['name'] ); ?>
									</td>
									<td valign="top" style="padding:16px 0;border-bottom:1px solid #e3e3e0;color:#292d36;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;line-height:22px;">
										<?php echo esc_html( $data['name'] ); ?>
									</td>
								</tr>
								<tr>
									<td width="34%" valign="top" style="padding:16px 12px 16px 0;border-bottom:1px solid #e3e3e0;color:#808291;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;line-height:18px;letter-spacing:1px;text-transform:uppercase;">
										<?php echo esc_html( $data['labels']['email'] ); ?>
									</td>
									<td valign="top" style="padding:16px 0;border-bottom:1px solid #e3e3e0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:22px;">
										<?php if ( '' !== $data['email_href'] ) : ?>
											<a href="<?php echo esc_url( $data['email_href'] ); ?>" style="color:#b84c20;font-weight:700;text-decoration:underline;text-underline-offset:3px;"><?php echo esc_html( $data['email'] ); ?></a>
										<?php else : ?>
											<span style="color:#292d36;"><?php echo esc_html( $data['email'] ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td width="34%" valign="top" style="padding:16px 12px 16px 0;border-bottom:1px solid #e3e3e0;color:#808291;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;line-height:18px;letter-spacing:1px;text-transform:uppercase;">
										<?php echo esc_html( $data['labels']['phone'] ); ?>
									</td>
									<td valign="top" style="padding:16px 0;border-bottom:1px solid #e3e3e0;color:#292d36;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:22px;">
										<?php echo esc_html( $data['phone'] ); ?>
									</td>
								</tr>
								<tr>
									<td width="34%" valign="top" style="padding:16px 12px 16px 0;color:#808291;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;line-height:18px;letter-spacing:1px;text-transform:uppercase;">
										<?php echo esc_html( $data['labels']['language'] ); ?>
									</td>
									<td valign="top" style="padding:16px 0;color:#292d36;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:22px;">
										<?php echo esc_html( $data['language'] ); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td class="mobile-pad" style="padding:22px 48px 6px;background-color:#ffffff;">
							<div style="padding:22px 24px;background-color:#fbebe3;border-left:5px solid #dd6531;">
								<div style="margin-bottom:10px;color:#b84c20;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;line-height:16px;letter-spacing:1px;text-transform:uppercase;">
									<?php echo esc_html( $data['labels']['message'] ); ?>
								</div>
								<div style="color:#292d36;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:26px;">
									<?php echo nl2br( esc_html( $data['message'] ) ); ?>
								</div>
							</div>
						</td>
					</tr>
					<?php if ( '' !== $data['email_href'] ) : ?>
					<tr>
						<td class="mobile-pad" style="padding:26px 48px 40px;background-color:#ffffff;">
							<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
								<tr>
									<td bgcolor="#292d36" style="background-color:#292d36;">
										<a class="mobile-button" href="<?php echo esc_url( $data['email_href'] ); ?>" style="display:inline-block;padding:15px 24px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;line-height:16px;letter-spacing:0.8px;text-decoration:none;text-transform:uppercase;">
											<?php echo esc_html( $data['labels']['reply'] ); ?> &nbsp;→
										</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td class="mobile-pad" bgcolor="#292d36" style="padding:26px 48px;background-color:#292d36;">
							<p style="margin:0;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;line-height:20px;">HSE Training DOO</p>
							<p style="margin:5px 0 0;color:#c8c9ce;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:18px;">Braće Radovanović 17/5, Lamela C · 11000 Belgrade, Serbia</p>
							<p style="margin:5px 0 0;color:#c8c9ce;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:18px;">
								<?php echo esc_html( $data['labels']['received'] ); ?>: <?php echo esc_html( $data['received_at'] ); ?>
							</p>
						</td>
					</tr>
				</table>
				<p style="max-width:640px;margin:16px auto 0;color:#808291;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:17px;text-align:center;">
					<?php echo esc_html( $data['labels']['automated_note'] ); ?>
				</p>
				<!--[if mso]></td></tr></table><![endif]-->
			</td>
		</tr>
	</table>
</body>
</html>
