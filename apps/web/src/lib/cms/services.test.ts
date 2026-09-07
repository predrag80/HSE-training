import { afterEach, describe, expect, it, vi } from 'vitest';

import { getServices } from './services';

const rawServices = {
	schema_version: 1,
	collection_key: 'services',
	services: [
		{
			service_key: 'hse-leadership',
			title: 'HSE leadership',
			card_label: 'Leadership',
			short_description: 'Experienced HSE management',
			detailed_description: 'Experienced HSE managers and project safety leaders.',
			cta: { label: 'Enquire', url: '/contact/' },
			image: {
				url: 'https://cms.example.test/service.webp',
				alt: '',
				width: 600,
				height: 815,
			},
			featured_on_homepage: true,
		},
	],
};

function jsonResponse(body: unknown, status = 200): Response {
	return new Response(JSON.stringify(body), {
		status,
		headers: { 'Content-Type': 'application/json' },
	});
}

afterEach(() => {
	vi.unstubAllGlobals();
});

describe('getServices', () => {
	it('requests and maps the public HSE Services endpoint', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse(rawServices));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getServices()).resolves.toMatchObject({
			schemaVersion: 1,
			collectionKey: 'services',
			services: [expect.objectContaining({ serviceKey: 'hse-leadership' })],
		});

		const requestedUrl = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(requestedUrl.pathname).toBe('/wp-json/hse/v1/services');
		expect(requestedUrl.search).toBe('');
	});

	it('throws a typed HTTP error when Services are not configured', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn().mockResolvedValue(jsonResponse({ code: 'hse_services_not_configured' }, 503)),
		);

		await expect(getServices()).rejects.toMatchObject({ code: 'http', status: 503 });
	});
});
