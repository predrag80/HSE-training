import type { Locale } from './config';

const galleryUi = {
	en: {
		metaTitle: 'Training Gallery | HSE Training', metaDescription: 'Photographs from HSE Training professional health and safety courses and practical training sessions.',
		heroEyebrow: 'Learning in practice', heroTitle: 'Gallery', heroScroll: 'Scroll to training gallery',
		headingEyebrow: 'HSE Training in action', headingTitle: 'People, knowledge and practical experience.',
		headingText: 'A selection of moments from our professional training sessions and participant groups.',
		photoAlt: 'HSE Training course session and participants — photograph', openPhoto: 'Open photograph', of: 'of',
		lightboxLabel: 'Expanded gallery photograph', close: 'Close gallery', previous: 'Previous photograph', next: 'Next photograph',
	},
	sr: {
		metaTitle: 'Galerija obuka | HSE Training', metaDescription: 'Fotografije sa stručnih kurseva bezbednosti i zdravlja na radu i praktičnih obuka kompanije HSE Training.',
		heroEyebrow: 'Učenje kroz praksu', heroTitle: 'Galerija', heroScroll: 'Pređite na galeriju obuka',
		headingEyebrow: 'HSE Training u praksi', headingTitle: 'Ljudi, znanje i praktično iskustvo.',
		headingText: 'Izbor trenutaka sa naših stručnih obuka i fotografija grupa polaznika.',
		photoAlt: 'HSE Training obuka i polaznici — fotografija', openPhoto: 'Otvorite fotografiju', of: 'od',
		lightboxLabel: 'Uvećana fotografija iz galerije', close: 'Zatvorite galeriju', previous: 'Prethodna fotografija', next: 'Sledeća fotografija',
	},
} as const;

export function getGalleryUiTranslations(locale: Locale) {
	return galleryUi[locale];
}
