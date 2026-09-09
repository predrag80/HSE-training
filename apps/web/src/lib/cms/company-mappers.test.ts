import { describe, expect, it } from 'vitest';

import { mapWordPressCompanyPage, mapWordPressCompanyProfile } from './company-mappers';

const profile = {
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
};

const companyPage = {
	schema_version: 1,
	page_key: 'company',
	locale: 'en',
	hero: { eyebrow: 'Business profile', title: 'About company' },
	profile,
	intro_cta: { label: 'Explore services', url: '/#services' },
};

describe('Company CMS mappers', () => {
	it('maps the canonical shared profile', () => {
		expect(mapWordPressCompanyProfile(profile)).toMatchObject({
			headline: 'Safety culture starts with people.',
			primaryImage: { width: 430, height: 553 },
			values: [
				expect.objectContaining({ valueKey: 'professional-training', iconKey: 'training' }),
				expect.objectContaining({ valueKey: 'hse-management', iconKey: 'management' }),
				expect.objectContaining({ valueKey: 'on-site-consultancy', iconKey: 'consultancy' }),
			],
		});
	});

	it('maps the dedicated Company Page document', () => {
			expect(mapWordPressCompanyPage(companyPage)).toMatchObject({
			schemaVersion: 1,
			pageKey: 'company',
			locale: 'en',
			hero: { eyebrow: 'Business profile', title: 'About company' },
			profile: { headline: 'Safety culture starts with people.' },
			introCta: { label: 'Explore services', url: '/#services' },
		});
	});

	it('rejects an unsupported value icon', () => {
		expect(() =>
			mapWordPressCompanyProfile({
				...profile,
				values: [{ ...profile.values[0], icon_key: 'unknown' }, ...profile.values.slice(1)],
			}),
		).toThrow('icon_key is unsupported');
	});

	it('rejects unsafe shared links', () => {
		expect(() =>
			mapWordPressCompanyProfile({
				...profile,
				primary_cta: { ...profile.primary_cta, url: 'javascript:alert(1)' },
			}),
		).toThrow('must use HTTP or HTTPS');
	});
});
