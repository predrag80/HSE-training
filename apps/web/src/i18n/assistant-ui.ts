import type { Locale } from './config';

/**
 * Site assistant wording. Course, training, consulting and company facts are
 * reused from their own dictionaries by `lib/assistant/assistant-content.ts`;
 * this file holds only the assistant's navigation and conversation copy.
 */
const assistantUi = {
	en: {
		panel: {
			open: 'Open site assistant',
			close: 'Close site assistant',
			title: 'Site assistant',
			subtitle: 'Automated guide to this website',
			choicesLabel: 'Suggested topics',
			inputLabel: 'Type your question',
			placeholder: 'Type your question…',
			send: 'Send question',
			note: 'Answers link only to pages on this website. The conversation stays in this browser tab and is deleted when you close it.',
			assistantName: 'Assistant',
			visitorName: 'You',
			loading: 'Loading the assistant…',
			unavailable: 'The assistant is temporarily unavailable. Please use the contact form.',
			contactForm: 'Contact form',
			foundOnSite: 'These pages on this website may help:',
			alsoOnSite: 'You may also find these pages useful:',
		},
		topics: {
			startPrompt: 'Start over',
			greeting: [
				'Hello! I am an automated guide to the HSE Training website, not a person and not an AI.',
				'Choose a topic or type a short question, and I will point you to the right page.',
			],
			neboshPrompt: 'NEBOSH courses',
			iogcPrompt: 'NEBOSH IOGC status',
			trainingPrompt: 'Training courses',
			accessPrompt: 'Buying a course and access',
			accessPriceText: '{allCourses} lists every course with its price or “{priceOnRequest}”. Courses payable online have a “{buyNow}” button; for the others, contact us.',
			consultingPrompt: 'Consulting and HSE talent',
			companyPrompt: 'About HSE Training',
			contactPrompt: 'Contact',
			contactText: 'Send us a message through the contact form, call us or write to us:',
			fallbackPrompt: 'No matching answer',
			fallback: [
				'I could not find an answer to that on this website.',
				'Our team can help directly. Send us a message through the contact form, call us or write to us:',
			],
		},
	},
	sr: {
		panel: {
			open: 'Otvori asistenta sajta',
			close: 'Zatvori asistenta sajta',
			title: 'Asistent sajta',
			subtitle: 'Automatski vodič kroz ovaj sajt',
			choicesLabel: 'Predložene teme',
			inputLabel: 'Ukucajte pitanje',
			placeholder: 'Ukucajte pitanje…',
			send: 'Pošaljite pitanje',
			note: 'Odgovori vode samo na stranice ovog sajta. Razgovor ostaje samo u ovom tabu pregledača i briše se kada ga zatvorite.',
			assistantName: 'Asistent',
			visitorName: 'Vi',
			loading: 'Učitavanje asistenta…',
			unavailable: 'Asistent trenutno nije dostupan. Molimo vas da koristite kontakt formu.',
			contactForm: 'Kontakt forma',
			foundOnSite: 'Ove stranice na sajtu mogu da vam pomognu:',
			alsoOnSite: 'Mogu vam koristiti i ove stranice:',
		},
		topics: {
			startPrompt: 'Na početak',
			greeting: [
				'Zdravo! Ja sam automatski vodič kroz sajt HSE Training. Nisam osoba niti veštačka inteligencija.',
				'Izaberite temu ili ukucajte kratko pitanje, a ja ću vas uputiti na pravu stranicu.',
			],
			neboshPrompt: 'NEBOSH kursevi',
			iogcPrompt: 'Status kursa NEBOSH IOGC',
			trainingPrompt: 'Obuke',
			accessPrompt: 'Kupovina kursa i pristup',
			accessPriceText: 'Na stranici {allCourses} uz svaki kurs piše cena ili „{priceOnRequest}”. Kursevi koji se plaćaju onlajn imaju dugme „{buyNow}”, a za ostale nas kontaktirajte.',
			consultingPrompt: 'Konsalting i HSE stručnjaci',
			companyPrompt: 'O kompaniji HSE Training',
			contactPrompt: 'Kontakt',
			contactText: 'Pošaljite nam poruku preko kontakt forme, pozovite nas ili nam pišite:',
			fallbackPrompt: 'Nema odgovarajućeg odgovora',
			fallback: [
				'Na ovom sajtu nisam pronašao odgovor na to pitanje.',
				'Naš tim može direktno da vam pomogne. Pošaljite nam poruku preko kontakt forme, pozovite nas ili nam pišite:',
			],
		},
	},
} as const;

export type AssistantUiTranslations = (typeof assistantUi)[Locale];

export function getAssistantUiTranslations(locale: Locale): AssistantUiTranslations {
	return assistantUi[locale];
}
