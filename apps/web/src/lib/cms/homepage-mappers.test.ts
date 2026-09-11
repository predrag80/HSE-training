import { describe, expect, it } from 'vitest';

import { mapWordPressHomepage } from './homepage-mappers';

const companyProfile = {
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
};

const publishedHomepage = {
	schema_version: 1,
	page_key: 'home',
	locale: 'en',
	hero: {
		aria_label: 'Professional consulting',
		heading: 'Professional consulting',
		slides: [
			{
				slide_key: 'primary',
				image: {
					url: 'https://cms.example.test/hero.webp',
					alt: 'Consultant speaking with a client',
					width: 1920,
					height: 1080,
				},
				leading_title: 'Professional',
				emphasized_title: 'consulting',
				primary_cta_label: "LET'S WORK TOGETHER",
				primary_cta_url: '#contact',
				message_prefix: 'Request a free',
				message_link_label: 'business consultation!',
				message_link_url: '/browse-hse-talent/',
			},
		],
	},
	about: companyProfile,
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

describe('mapWordPressHomepage', () => {
	it('maps the versioned WordPress response into the internal Homepage shape', () => {
		expect(mapWordPressHomepage(publishedHomepage, 'en')).toEqual({
			schemaVersion: 1,
			pageKey: 'home',
			locale: 'en',
			hero: {
				ariaLabel: 'Professional consulting',
				heading: 'Professional consulting',
				slides: [
					{
						slideKey: 'primary',
						image: {
							url: 'https://cms.example.test/hero.webp',
							alt: 'Consultant speaking with a client',
							width: 1920,
							height: 1080,
						},
						leadingTitle: 'Professional',
						emphasizedTitle: 'consulting',
						primaryCtaLabel: "LET'S WORK TOGETHER",
						primaryCtaUrl: '#contact',
						messagePrefix: 'Request a free',
						messageLinkLabel: 'business consultation!',
						messageLinkUrl: '/browse-hse-talent/',
					},
				],
			},
			about: {
				eyebrow: "Company's vision",
				headline: 'Safety culture starts with people.',
				description: 'HSE Training helps organisations strengthen safety culture.',
				primaryImage: {
					url: 'https://cms.example.test/about-primary.webp',
					alt: 'HSE consultant at work',
					width: 430,
					height: 553,
				},
				secondaryImage: {
					url: 'https://cms.example.test/about-secondary.webp',
					alt: '',
					width: 352,
					height: 452,
				},
				primaryCta: { label: 'About company', url: '/company/' },
				secondaryCta: { label: 'How we work', url: '/company/#about' },
				signatureLabel: 'Training & consultancy',
				team: [
					{ memberKey: 'ana-springfield', name: 'Ana Springfield', role: 'Director', image: null },
					{ memberKey: 'john-springfield', name: 'John Springfield', role: 'Director of Operations', image: null },
					{ memberKey: 'biljana-stojanovic', name: 'Biljana Stojanovic', role: 'Financial Director', image: null },
					{ memberKey: 'kristina-atanaskovic', name: 'Kristina Atanaskovic', role: 'Consultant', image: null },
					{ memberKey: 'marija-blagojevic', name: 'Marija Blagojevic', role: 'Consultant', image: null },
				],
				values: [
					{
						valueKey: 'professional-training',
						iconKey: 'training',
						title: 'Professional training',
						description: 'International programmes and practical instruction.',
					},
					{
						valueKey: 'hse-management',
						iconKey: 'management',
						title: 'HSE management',
						description: 'Clear support for health and safety systems.',
					},
					{
						valueKey: 'on-site-consultancy',
						iconKey: 'consultancy',
						title: 'On-site consultancy',
						description: 'Flexible delivery at the client location.',
					},
				],
			},
			featuredServices: [
				{
					serviceKey: 'hse-leadership',
					title: 'HSE leadership',
					cardLabel: 'Leadership',
					shortDescription: 'Experienced HSE management',
					detailedDescription: 'Experienced HSE managers and project safety leaders.',
					cta: { label: 'Enquire', url: '/contact/' },
					image: {
						url: 'https://cms.example.test/service.webp',
						alt: '',
						width: 600,
						height: 815,
					},
					featuredOnHomepage: true,
				},
			],
		});
	});

	it('rejects an unknown contract version', () => {
		expect(() =>
			mapWordPressHomepage(
				{
					...publishedHomepage,
					schema_version: 2,
				},
				'en',
			),
		).toThrow('schema_version must be 1');
	});

	it('rejects an empty hero slide collection', () => {
		expect(() =>
			mapWordPressHomepage(
				{
					...publishedHomepage,
					hero: { ...publishedHomepage.hero, slides: [] },
				},
				'en',
			),
		).toThrow('hero.slides must contain between 1 and 5 items');
	});

	it('rejects more than five hero slides', () => {
		expect(() =>
			mapWordPressHomepage(
				{
					...publishedHomepage,
					hero: {
						...publishedHomepage.hero,
						slides: Array.from({ length: 6 }, (_, index) => ({
							...publishedHomepage.hero.slides[0],
							slide_key: `slide-${index + 1}`,
						})),
					},
				},
				'en',
			),
		).toThrow('hero.slides must contain between 1 and 5 items');
	});

	it('rejects unsafe links from the CMS', () => {
		expect(() =>
			mapWordPressHomepage(
				{
					...publishedHomepage,
					hero: {
						...publishedHomepage.hero,
						slides: [
							{
								...publishedHomepage.hero.slides[0],
								primary_cta_url: 'javascript:alert(1)',
							},
						],
					},
				},
				'en',
			),
		).toThrow('must be an internal path, anchor, or HTTP(S) URL');
	});

	it('rejects incomplete image metadata', () => {
		expect(() =>
			mapWordPressHomepage(
				{
					...publishedHomepage,
					hero: {
						...publishedHomepage.hero,
						slides: [
							{
								...publishedHomepage.hero.slides[0],
								image: { ...publishedHomepage.hero.slides[0].image, width: 0 },
							},
						],
					},
				},
				'en',
			),
		).toThrow('image.width must be a positive integer');
	});

	it('rejects a response for a different locale than requested', () => {
		expect(() => mapWordPressHomepage(publishedHomepage, 'sr')).toThrow(
			'locale must match the requested sr language',
		);
	});
});
