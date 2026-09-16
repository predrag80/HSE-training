<?php
/**
 * Render the website contact enquiry in HTML and plain-text formats.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Contact;

defined( 'ABSPATH' ) || exit;

final class ContactEmailTemplate {
	/**
	 * Render the branded HTML email.
	 *
	 * @param array<string, mixed> $enquiry Contact enquiry data.
	 * @return string
	 */
	public static function render_html( $enquiry ) {
		$data          = self::normalize_enquiry( $enquiry );
		$template_path = dirname( __DIR__, 2 ) . '/templates/email/contact-enquiry.php';

		ob_start();
		require $template_path;

		return (string) ob_get_clean();
	}

	/**
	 * Render the multipart plain-text alternative.
	 *
	 * @param array<string, mixed> $enquiry Contact enquiry data.
	 * @return string
	 */
	public static function render_text( $enquiry ) {
		$data = self::normalize_enquiry( $enquiry );

		return implode(
			"\n",
			array(
				$data['labels']['title'],
				str_repeat( '=', strlen( $data['labels']['title'] ) ),
				'',
				$data['labels']['name'] . ': ' . $data['name'],
				$data['labels']['email'] . ': ' . $data['email'],
				$data['labels']['phone'] . ': ' . $data['phone'],
				$data['labels']['language'] . ': ' . $data['language'],
				$data['labels']['received'] . ': ' . $data['received_at'],
				'',
				$data['labels']['message'],
				str_repeat( '-', strlen( $data['labels']['message'] ) ),
				$data['message'],
				'',
				'HSE Training DOO',
				'Braće Radovanović 17/5, Lamela C',
				'11000 Belgrade, Serbia',
				'info@hsetraining.rs',
			)
		);
	}

	/**
	 * Normalize content once so both formats share identical safe values.
	 *
	 * @param array<string, mixed> $enquiry Contact enquiry data.
	 * @return array<string, mixed>
	 */
	private static function normalize_enquiry( $enquiry ) {
		$locale = isset( $enquiry['locale'] ) && 'sr' === $enquiry['locale'] ? 'sr' : 'en';
		$labels = self::get_labels( $locale );
		$email  = isset( $enquiry['email'] ) ? sanitize_email( (string) $enquiry['email'] ) : '';
		$phone  = isset( $enquiry['phone'] ) ? sanitize_text_field( (string) $enquiry['phone'] ) : '';

		return array(
			'locale'      => $locale,
			'language'    => 'sr' === $locale ? 'Srpski' : 'English',
			'labels'      => $labels,
			'name'        => isset( $enquiry['name'] ) ? sanitize_text_field( (string) $enquiry['name'] ) : '',
			'email'       => $email,
			'email_href'  => is_email( $email ) ? 'mailto:' . $email : '',
			'phone'       => '' !== $phone ? $phone : $labels['not_provided'],
			'message'     => isset( $enquiry['message'] ) ? sanitize_textarea_field( (string) $enquiry['message'] ) : '',
			'received_at' => isset( $enquiry['received_at'] )
				? sanitize_text_field( (string) $enquiry['received_at'] )
				: wp_date( 'd M Y, H:i T' ),
		);
	}

	/** Return internal email labels for the enquiry language. */
	private static function get_labels( $locale ) {
		if ( 'sr' === $locale ) {
			return array(
				'preheader'     => 'Novi upit je poslat putem HSE Training sajta.',
				'eyebrow'       => 'UPIT SA SAJTA',
				'title'         => 'Novi kontakt upit',
				'intro'         => 'Posetilac je poslao poruku putem kontakt forme na HSE Training sajtu.',
				'name'          => 'Ime',
				'email'         => 'E-mail',
				'phone'         => 'Telefon',
				'language'      => 'Jezik forme',
				'received'      => 'Primljeno',
				'message'       => 'Poruka',
				'reply'         => 'Odgovori pošiljaocu',
				'not_provided'  => 'Nije navedeno',
				'automated_note' => 'Ovo je automatska poruka sa HSE Training kontakt forme.',
			);
		}

		return array(
			'preheader'      => 'A new enquiry was submitted through the HSE Training website.',
			'eyebrow'        => 'WEBSITE ENQUIRY',
			'title'          => 'New contact request',
			'intro'          => 'A visitor submitted a message through the contact form on the HSE Training website.',
			'name'           => 'Name',
			'email'          => 'Email',
			'phone'          => 'Phone',
			'language'       => 'Form language',
			'received'       => 'Received',
			'message'        => 'Message',
			'reply'          => 'Reply to sender',
			'not_provided'   => 'Not provided',
			'automated_note' => 'This is an automated message from the HSE Training contact form.',
		);
	}
}
