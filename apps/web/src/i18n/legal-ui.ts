import type { Locale } from './config';

export type LegalPageKey = 'privacy' | 'terms' | 'copyright';

const placeholderParagraphs = [
	'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
	'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
	'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
] as const;

const legalUi = {
	en: {
		placeholderLabel: 'Draft placeholder content',
		lastUpdated: 'Temporary content — to be replaced with approved legal text.',
		sectionTitles: ['Overview', 'Information', 'Use of this website'] as const,
		pages: {
			privacy: {
				title: 'Privacy Policy',
				metaDescription: 'Privacy Policy for the HSE Training website.',
			},
			terms: {
				title: 'Terms and Conditions',
				metaDescription: 'Terms and Conditions for the HSE Training website.',
			},
			copyright: {
				title: 'Copyright',
				metaDescription: 'Copyright information for the HSE Training website.',
			},
		},
	},
	sr: {
		placeholderLabel: 'Privremeni radni sadržaj',
		lastUpdated: 'Privremeni sadržaj — biće zamenjen odobrenim pravnim tekstom.',
		sectionTitles: ['Pregled', 'Informacije', 'Korišćenje ovog sajta'] as const,
		pages: {
			privacy: {
				title: 'Politika privatnosti',
				metaDescription: 'Politika privatnosti internet stranice HSE Training.',
			},
			terms: {
				title: 'Uslovi korišćenja',
				metaDescription: 'Uslovi korišćenja internet stranice HSE Training.',
			},
			copyright: {
				title: 'Autorska prava',
				metaDescription: 'Informacije o autorskim pravima za internet stranicu HSE Training.',
			},
		},
	},
} as const;

export function getLegalPageContent(locale: Locale, page: LegalPageKey) {
	const copy = legalUi[locale];

	return {
		...copy.pages[page],
		placeholderLabel: copy.placeholderLabel,
		lastUpdated: copy.lastUpdated,
		sections: copy.sectionTitles.map((title, index) => ({
			title,
			paragraph: placeholderParagraphs[index],
		})),
	};
}
