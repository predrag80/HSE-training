import { afterEach, describe, expect, it, vi } from 'vitest';

import { getHomepage } from './homepage';

const rawHomepage = {
	schema_version: 1,
	page_key: 'home',
	hero: {
		aria_label: 'Professional consulting',
		heading: 'Professional consulting',
		slides: [
			{
				slide_key: 'primary',
				image: {
					url: 'https://cms.example.test/hero.webp',
					alt: '',
					width: 1920,
					height: 1080,
				},
				leading_title: 'Professional',
				emphasized_title: 'consulting',
				primary_cta_label: "LET'S WORK TOGETHER",
				primary_cta_url: '#contact',
				message_prefix: 'Request a free',
				message_link_label: 'business consultation!',
				message_link_url: '/consulting/',
			},
		],
	},
	about: {
		eyebrow: "Company's vision",
		headline: 'Safety culture starts with people.',
		description: 'HSE Training helps organisations strengthen safety culture.',
		primary_image: {
			url: 'https://cms.example.test/about-primary.webp',
			alt: 'HSE consultant at work',
			width: 430,
			height: 553,
		},
		secondary_image: {
			url: 'https://cms.example.test/about-secondary.webp',
			alt: '',
			width: 352,
			height: 452,
		},
		primary_cta: { label: 'About company', url: '/company/' },
		secondary_cta: { label: 'How we work', url: '/company/#about' },
		signature_label: 'Training & consultancy',
		values: [
			{
				value_key: 'professional-training',
				icon_key: 'training',
				title: 'Professional training',
				description: 'International programmes and practical instruction.',
			},
			{
				value_key: 'hse-management',
				icon_key: 'management',
				title: 'HSE management',
				description: 'Clear support for health and safety systems.',
			},
			{
				value_key: 'on-site-consultancy',
				icon_key: 'consultancy',
				title: 'On-site consultancy',
				description: 'Flexible delivery at the client location.',
			},
		],
	},
	featured_services: [
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

describe('getHomepage', () => {
	it('requests and maps the public HSE Homepage endpoint', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse(rawHomepage));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getHomepage()).resolves.toMatchObject({
			schemaVersion: 1,
			pageKey: 'home',
			hero: {
				slides: [expect.objectContaining({ slideKey: 'primary' })],
			},
		});

		const requestedUrl = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(requestedUrl.pathname).toBe('/wp-json/hse/v1/homepage');
		expect(requestedUrl.search).toBe('');
	});

	it('throws a typed HTTP error when Homepage content is not configured', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn().mockResolvedValue(jsonResponse({ code: 'hse_homepage_not_configured' }, 503)),
		);

		await expect(getHomepage()).rejects.toMatchObject({
			code: 'http',
			status: 503,
		});
	});

	it('rejects a malformed Homepage response', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({ page_key: 'home' })));

		await expect(getHomepage()).rejects.toMatchObject({ code: 'invalid-response' });
	});
});
