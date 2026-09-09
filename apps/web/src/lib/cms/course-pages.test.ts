import { afterEach, describe, expect, it, vi } from 'vitest';

import { getCoursesLandingPage, getNeboshOverviewPage } from './course-pages';

function jsonResponse(body: unknown): Response {
	return new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } });
}

afterEach(() => vi.unstubAllGlobals());

describe('Course page CMS client', () => {
	it('requests the Courses page in the selected language', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse({
			schema_version: 1, page_key: 'courses', locale: 'sr',
			content: {
				meta: { title: 'Kursevi', description: 'Opis' },
				hero: { eyebrow: 'Obuke', title: 'Kursevi', intro: 'Izaberite kurs.' },
				empty_state: { title: 'Nema kurseva.', text: 'Pokušajte ponovo.' },
			},
		}));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getCoursesLandingPage('sr')).resolves.toMatchObject({ locale: 'sr', pageKey: 'courses' });
		const url = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(url.pathname).toBe('/wp-json/hse/v1/course-pages/courses');
		expect(url.searchParams.get('lang')).toBe('sr');
	});

	it('requests the NEBOSH overview in the selected language', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse({
			schema_version: 1, page_key: 'nebosh', locale: 'en',
			content: {
				meta: { title: 'NEBOSH', description: 'Description' }, hero: { eyebrow: 'Safety', title: 'NEBOSH' },
				strengths: Array.from({ length: 4 }, (_, index) => ({ title: `Benefit ${index}`, text: 'Text' })),
				intro: { kicker: 'Intro', title: 'Title', paragraphs: ['One', 'Two'], cta_label: 'Contact', image_alt: 'Team', visual_title: 'Safer', visual_text: 'Practice' },
				course_selection: { kicker: 'Choose', title: 'Courses', current_label: 'Current', legacy_label: 'Legacy', igc_fallback: 'IGC', iogc_fallback: 'IOGC', igc_cta_label: 'Explore', iogc_cta_label: 'Status' },
				testimonial: { quote: 'Quote', author: 'Author', role: 'Role' },
			},
		}));
		vi.stubGlobal('fetch', fetchMock);

		await getNeboshOverviewPage('en');
		const url = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(url.pathname).toBe('/wp-json/hse/v1/course-pages/nebosh');
		expect(url.searchParams.get('lang')).toBe('en');
	});
});
