import type { Locale } from './config';

const translations = {
	en: {
		metaTitle: 'Free HSE Resources | HSE Training',
		metaDescription: 'Explore free HSE training videos, practical documents, procedures, standards and useful industry links.',
		eyebrow: 'Knowledge you can use',
		title: 'Free resources',
		description: 'Practical HSE materials selected to support safer work, stronger teams and continuous professional development.',
		filtersLabel: 'Filter resources by type',
		all: 'All resources',
		types: {
			link: 'Web links',
			video: 'Training videos',
			document: 'Documents',
			procedure: 'Procedures',
			standard: 'Standards',
		},
		open: 'Open resource',
		watch: 'Watch video',
		download: 'View or download',
		featured: 'Featured',
		empty: 'Free resources are being prepared. Please check this page again soon.',
		noResults: 'No resources match this filter yet.',
	},
	sr: {
		metaTitle: 'Besplatni HSE resursi | HSE Training',
		metaDescription: 'Istražite besplatne HSE video-obuke, praktične dokumente, procedure, standarde i korisne stručne linkove.',
		eyebrow: 'Znanje koje možete primeniti',
		title: 'Besplatni resursi',
		description: 'Praktični HSE materijali odabrani za bezbedniji rad, snažnije timove i kontinuirani profesionalni razvoj.',
		filtersLabel: 'Filtrirajte resurse prema vrsti',
		all: 'Svi resursi',
		types: {
			link: 'Veb-linkovi',
			video: 'Video-obuke',
			document: 'Dokumenti',
			procedure: 'Procedure',
			standard: 'Standardi',
		},
		open: 'Otvorite resurs',
		watch: 'Pogledajte video',
		download: 'Pogledajte ili preuzmite',
		featured: 'Izdvojeno',
		empty: 'Besplatni resursi su u pripremi. Posetite ovu stranicu ponovo uskoro.',
		noResults: 'Za izabrani filter još nema resursa.',
	},
} as const;

export function getResourcesUiTranslations(locale: Locale) {
	return translations[locale];
}
