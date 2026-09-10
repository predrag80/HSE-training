import type { Locale } from './config';

const contactUi = {
	en: {
		metaTitle: 'Contact us | HSE Training', metaDescription: 'Contact HSE Training DOO in Belgrade for professional HSE training and consultancy support.',
		title: 'Contact us', scroll: 'Scroll to contact details', detailsLabel: 'Contact details',
		callTitle: 'WhatsApp or call us', phonePrefix: 'WhatsApp', emailTitle: 'E-mail us', vatLabel: 'VAT number', registrationLabel: 'Registration number',
		mapLabel: 'Map showing the HSE Training office in Belgrade', mapTitle: 'HSE Training office in Belgrade', mapLink: 'View larger map',
		formEyebrow: 'Contact us', formTitle: 'How can we help you?',
		form: {
			nameLabel: 'Your name', namePlaceholder: 'Your name*', emailLabel: 'Your email address', emailPlaceholder: 'Your email address*',
			phoneLabel: 'Your phone', phonePlaceholder: 'Your phone', subjectLabel: 'Your subject', subjectPlaceholder: 'Your subject',
			messageLabel: 'Your message', messagePlaceholder: 'Your message', submit: 'Send message',
			privacy: 'We are committed to protecting your privacy. We will never collect information about you without your explicit consent.',
			success: 'Thank you. Your message is ready to be sent.',
		},
	},
	sr: {
		metaTitle: 'Kontakt | HSE Training', metaDescription: 'Kontaktirajte HSE Training DOO u Beogradu za profesionalne HSE obuke i konsultantsku podršku.',
		title: 'Kontakt', scroll: 'Pređite na kontakt podatke', detailsLabel: 'Kontakt podaci',
		callTitle: 'WhatsApp ili poziv', phonePrefix: 'WhatsApp', emailTitle: 'Pošaljite nam e-mail', vatLabel: 'PIB', registrationLabel: 'Matični broj',
		mapLabel: 'Mapa lokacije kancelarije HSE Training u Beogradu', mapTitle: 'Kancelarija HSE Training u Beogradu', mapLink: 'Prikažite veću mapu',
		formEyebrow: 'Kontaktirajte nas', formTitle: 'Kako možemo da vam pomognemo?',
		form: {
			nameLabel: 'Vaše ime', namePlaceholder: 'Vaše ime*', emailLabel: 'Vaša e-mail adresa', emailPlaceholder: 'Vaša e-mail adresa*',
			phoneLabel: 'Vaš telefon', phonePlaceholder: 'Vaš telefon', subjectLabel: 'Naslov poruke', subjectPlaceholder: 'Naslov poruke',
			messageLabel: 'Vaša poruka', messagePlaceholder: 'Vaša poruka', submit: 'Pošaljite poruku',
			privacy: 'Posvećeni smo zaštiti vaše privatnosti. Nikada nećemo prikupljati podatke o vama bez vaše izričite saglasnosti.',
			success: 'Hvala. Vaša poruka je spremna za slanje.',
		},
	},
} as const;

export function getContactUiTranslations(locale: Locale) {
	return contactUi[locale];
}
