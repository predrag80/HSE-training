import { afterEach, describe, expect, it, vi } from 'vitest';

import { getLegalPage } from './legal-pages';

function jsonResponse(body: unknown): Response {
	return new Response(JSON.stringify(body), {
		status: 200,
		headers: { 'Content-Type': 'application/json' },
	});
}

afterEach(() => vi.unstubAllGlobals());

describe('Legal Page CMS client', () => {
	it('requests and maps the selected legal page and language', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse({
			schema_version: 1,
			page_key: 'privacy',
			locale: 'sr',
			content: {
				meta: { title: 'Politika privatnosti', description: 'Opis politike.' },
				eyebrow: 'Pravne informacije',
				title: 'Politika privatnosti',
				intro: 'Uvod u politiku.',
				last_updated: 'Ažurirano 14. septembra 2026.',
				sections: [{ title: 'Podaci', body_html: '<p>Sadržaj.</p>' }],
			},
		}));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getLegalPage('privacy', 'sr')).resolves.toMatchObject({
			pageKey: 'privacy',
			locale: 'sr',
			sections: [{ title: 'Podaci', bodyHtml: '<p>Sadržaj.</p>' }],
		});
		const url = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(url.pathname).toBe('/wp-json/hse/v1/legal-pages/privacy');
		expect(url.searchParams.get('lang')).toBe('sr');
	});

	it('rejects an empty legal section collection', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({
			schema_version: 1,
			page_key: 'terms',
			locale: 'en',
			content: {
				meta: { title: 'Terms', description: 'Terms description.' },
				eyebrow: 'Legal', title: 'Terms', intro: 'Intro', last_updated: 'Updated today', sections: [],
			},
		})));

		await expect(getLegalPage('terms', 'en')).rejects.toThrow(
			'content.sections must contain at least one section',
		);
	});
});
