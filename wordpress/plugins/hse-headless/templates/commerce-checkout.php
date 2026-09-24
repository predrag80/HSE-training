<?php
/**
 * Isolated HSE Training shell for WooCommerce checkout and order confirmation.
 *
 * @package HSETraining\Headless
 */

use HSETraining\Headless\Commerce\CommerceLocale;
use HSETraining\Headless\Commerce\CommercePresentation;

defined( 'ABSPATH' ) || exit;

$is_confirmation = CommercePresentation::is_confirmation();
$locale           = CommerceLocale::current();
$eyebrow          = CommercePresentation::copy( $is_confirmation ? 'confirmation_eyebrow' : 'checkout_eyebrow' );
$title            = CommercePresentation::copy( $is_confirmation ? 'confirmation_title' : 'checkout_title' );
$intro            = CommercePresentation::copy( $is_confirmation ? 'confirmation_intro' : 'checkout_intro' );
$home_url         = CommercePresentation::public_url( '/' );
$plugin_file      = dirname( __DIR__ ) . '/hse-headless.php';
$payment_asset    = static function ( string $filename ) use ( $plugin_file ): string {
	return plugins_url( 'assets/payments/' . $filename, $plugin_file );
};
?>
<!doctype html>
<html lang="<?php echo esc_attr( 'sr' === $locale ? 'sr-Latn' : 'en' ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex,nofollow,noarchive" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<a class="hse-commerce__skip-link" href="#hse-commerce-content"><?php echo esc_html( 'sr' === $locale ? 'Pređite na sadržaj' : 'Skip to content' ); ?></a>
	<header class="hse-commerce__header">
		<div class="hse-commerce__header-inner">
			<a class="hse-commerce__brand" href="<?php echo esc_url( $home_url ); ?>" aria-label="HSE Training">
				<img src="<?php echo esc_url( plugins_url( 'assets/HSE_Training_logo_transparent.png', $plugin_file ) ); ?>" width="900" height="335" alt="HSE Training DOO Beograd" />
			</a>
			<span class="hse-commerce__secure">
				<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 2 4.5 5.2v5.4c0 5 3.2 9.5 7.5 11.4 4.3-1.9 7.5-6.4 7.5-11.4V5.2L12 2Zm0 2.2 5.5 2.3v4.1c0 3.8-2.2 7.4-5.5 9.1-3.3-1.7-5.5-5.3-5.5-9.1V6.5L12 4.2Zm0 3.3a2.5 2.5 0 0 0-2.5 2.5v1H9v4.8h6V11h-.5v-1A2.5 2.5 0 0 0 12 7.5Zm0 1.5c.6 0 1 .4 1 1v1h-2v-1c0-.6.4-1 1-1Z" /></svg>
				<span><?php echo esc_html( CommercePresentation::copy( 'secure' ) ); ?></span>
			</span>
		</div>
	</header>

	<main id="hse-commerce-content" class="hse-commerce__main">
		<section class="hse-commerce__hero">
			<div>
				<p class="hse-commerce__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $intro ); ?></p>
			</div>
			<a href="<?php echo esc_url( $home_url ); ?>"><span aria-hidden="true">←</span> <?php echo esc_html( CommercePresentation::copy( 'back' ) ); ?></a>
		</section>

		<section class="hse-commerce__panel" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php
			while ( have_posts() ) {
				the_post();
				the_content();
			}
			?>
		</section>
	</main>

	<section class="hse-commerce__payments" aria-labelledby="hse-commerce-payment-title">
		<h2 id="hse-commerce-payment-title"><?php echo esc_html( CommercePresentation::copy( 'payment_security' ) ); ?></h2>
		<div class="hse-commerce__payment-groups">
			<div class="hse-commerce__payment-group hse-commerce__payment-bank">
				<p><?php echo esc_html( CommercePresentation::copy( 'bank_provider' ) ); ?></p>
				<a href="https://www.raiffeisenbank.rs/" target="_blank" rel="noreferrer" aria-label="Raiffeisen Bank">
					<img src="<?php echo esc_url( $payment_asset( 'raiffeisen-bank.png' ) ); ?>" width="640" height="111" alt="Raiffeisen Bank" />
				</a>
			</div>
			<div class="hse-commerce__payment-group">
				<p><?php echo esc_html( CommercePresentation::copy( 'accepted_cards' ) ); ?></p>
				<div class="hse-commerce__payment-logos hse-commerce__payment-logos--cards">
					<img src="<?php echo esc_url( $payment_asset( 'visa.png' ) ); ?>" width="320" height="103" alt="Visa" />
					<img src="<?php echo esc_url( $payment_asset( 'mastercard.png' ) ); ?>" width="127" height="90" alt="Mastercard" />
					<img src="<?php echo esc_url( $payment_asset( 'maestro.png' ) ); ?>" width="150" height="42" alt="Maestro" />
					<img src="<?php echo esc_url( $payment_asset( 'dinacard.png' ) ); ?>" width="320" height="148" alt="DinaCard" />
				</div>
			</div>
			<div class="hse-commerce__payment-group">
				<p><?php echo esc_html( CommercePresentation::copy( 'secure_authentication' ) ); ?></p>
				<div class="hse-commerce__payment-logos hse-commerce__payment-logos--secure">
					<a href="https://rs.visa.com/run-your-business/small-business-tools/payment-technology/visa-secure.html" target="_blank" rel="noreferrer" aria-label="Visa Secure">
						<img src="<?php echo esc_url( $payment_asset( 'visa-secure.png' ) ); ?>" width="198" height="198" alt="Visa Secure" />
					</a>
					<a href="https://www.mastercard.rs/sr-rs/korisnici/podrska/sigurnost-i-zastita/identity-check.html" target="_blank" rel="noreferrer" aria-label="Mastercard Identity Check">
						<img src="<?php echo esc_url( $payment_asset( 'mastercard-identity-check.png' ) ); ?>" width="320" height="239" alt="Mastercard Identity Check" />
					</a>
				</div>
			</div>
		</div>
	</section>

	<footer class="hse-commerce__footer">
		<div>
			<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> HSE Training DOO Beograd</p>
			<nav aria-label="<?php echo esc_attr( 'sr' === $locale ? 'Pravni dokumenti' : 'Legal' ); ?>">
				<a href="<?php echo esc_url( CommercePresentation::public_url( '/contact/' ) ); ?>"><?php echo esc_html( CommercePresentation::copy( 'contact' ) ); ?></a>
				<a href="<?php echo esc_url( CommercePresentation::public_url( '/privacy-policy/' ) ); ?>"><?php echo esc_html( CommercePresentation::copy( 'privacy' ) ); ?></a>
				<a href="<?php echo esc_url( CommercePresentation::public_url( '/terms-and-conditions/' ) ); ?>"><?php echo esc_html( CommercePresentation::copy( 'terms' ) ); ?></a>
			</nav>
		</div>
	</footer>
	<?php wp_footer(); ?>
</body>
</html>
