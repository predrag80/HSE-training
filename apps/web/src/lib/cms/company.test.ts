import { afterEach, describe, expect, it, vi } from 'vitest';

import { getCompanyPage } from './company';

const rawCompanyPage = {
	schema_version: 1,
	page_key: 'company',
	locale: 'en',
	hero: { eyebrow: 'Business profile', title: 'About company' },
	profile: {
		eyebrow: "Company's vision",
		headline: 'Safety culture starts with people.',
		description: 'HSE Training helps organisations strengthen safety culture.',
		primary_image: { url: 'https://cms.example.test/primary.webp', alt: 'Primary', width: 430, height: 553 },
		secondary_image: { url: 'https://cms.example.test/secondary.webp', alt: '', width: 352, height: 452 },
		primary_cta: { label: 'About company', url: '/company/' },
		secondary_cta: { label: 'How we work', url: '#about' },
		signature_label: 'Training & consultancy',
		values: [
			{ value_key: 'professional-training', icon_key: 'training', title: 'Professional training', description: 'Practical instruction.' },
			{ value_key: 'hse-management', icon_key: 'management', title: 'HSE management', description: 'Management support.' },
			{ value_key: 'on-site-consultancy', icon_key: 'consultancy', title: 'On-site consultancy', description: 'Flexible delivery.' },
		],
	},
	intro_cta: { label: 'Explore services', url: '/#services' },
};

function jsonResponse(body: unknown, status = 200): Response {
	return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
}

afterEach(() => vi.unstubAllGlobals());

describe('getCompanyPage', () => {
	it('requests and maps the dedicated Company endpoint', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse(rawCompanyPage));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getCompanyPage()).resolves.toMatchObject({
			pageKey: 'company',
			profile: { headline: 'Safety culture starts with people.' },
		});
		expect(new URL(String(fetchMock.mock.calls[0]?.[0])).pathname).toBe('/wp-json/hse/v1/company');
		expect(new URL(String(fetchMock.mock.calls[0]?.[0])).searchParams.get('lang')).toBe('en');
	});

	it('keeps an unconfigured Company response as a typed HTTP error', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({ code: 'hse_company_page_not_configured' }, 503)));

		await expect(getCompanyPage()).rejects.toMatchObject({ code: 'http', status: 503 });
	});
});
