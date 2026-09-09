import { describe, expect, it } from 'vitest';

import { mapWordPressCoursesLandingPage, mapWordPressNeboshOverviewPage } from './course-page-mappers';

const coursesDocument = {
	schema_version: 1,
	page_key: 'courses',
	locale: 'en',
	content: {
		meta: { title: 'Courses | HSE Training', description: 'Available courses.' },
		hero: { eyebrow: 'Training', title: 'Courses', intro: 'Choose a course.' },
		empty_state: { title: 'No courses.', text: 'Please check again.' },
	},
};

const neboshDocument = {
	schema_version: 1,
	page_key: 'nebosh',
	locale: 'sr',
	content: {
		meta: { title: 'NEBOSH', description: 'NEBOSH kvalifikacije.' },
		hero: { eyebrow: 'Bezbednost', title: 'NEBOSH kvalifikacije' },
		strengths: Array.from({ length: 4 }, (_, index) => ({ title: `Prednost ${index + 1}`, text: 'Opis.' })),
		intro: {
			kicker: 'Kvalifikacije', title: 'Bezbedniji rad', paragraphs: ['Prvi pasus.', 'Drugi pasus.'],
			cta_label: 'Kontakt', image_alt: 'Savetovanje', visual_title: 'Znanje', visual_text: 'Praktična primena.',
		},
		course_selection: {
			kicker: 'Izbor', title: 'Kursevi', current_label: 'Aktuelno', legacy_label: 'Ranije',
			igc_fallback: 'IGC opis.', iogc_fallback: 'IOGC opis.', igc_cta_label: 'IGC', iogc_cta_label: 'IOGC',
		},
		testimonial: { quote: 'Preporuka.', author: 'Autor', role: 'Uloga' },
	},
};

describe('Course page CMS mappers', () => {
	it('maps a locale-specific Courses landing document', () => {
		expect(mapWordPressCoursesLandingPage(coursesDocument, 'en')).toMatchObject({
			pageKey: 'courses', locale: 'en', hero: { title: 'Courses' }, emptyState: { title: 'No courses.' },
		});
	});

	it('maps a locale-specific NEBOSH overview document', () => {
		expect(mapWordPressNeboshOverviewPage(neboshDocument, 'sr')).toMatchObject({
			pageKey: 'nebosh', locale: 'sr', intro: { title: 'Bezbedniji rad' },
			courseSelection: { currentLabel: 'Aktuelno' }, testimonial: { author: 'Autor' },
		});
	});

	it('rejects a locale mismatch', () => {
		expect(() => mapWordPressCoursesLandingPage(coursesDocument, 'sr')).toThrow('locale must be sr');
	});

	it('requires exactly four NEBOSH strengths', () => {
		expect(() => mapWordPressNeboshOverviewPage({
			...neboshDocument,
			content: { ...neboshDocument.content, strengths: neboshDocument.content.strengths.slice(0, 3) },
		}, 'sr')).toThrow('exactly four');
	});
});
