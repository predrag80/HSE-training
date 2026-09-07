import { describe, expect, it } from 'vitest';

import { mapWordPressFeaturedServices, mapWordPressServiceCollection } from './service-mappers';

const service = {
	service_key: 'hse-leadership',
	title: 'HSE leadership',
	card_label: 'Leadership',
	short_description: 'Experienced HSE management',
	detailed_description: 'Experienced HSE managers and project safety leaders.',
	cta: { label: 'Enquire', url: '/contact/' },
	image: {
		url: 'https://cms.example.test/service.webp',
		alt: 'HSE leaders reviewing a project',
		width: 600,
		height: 815,
	},
	featured_on_homepage: true,
};

describe('mapWordPressServiceCollection', () => {
	it('maps the versioned WordPress collection into the application shape', () => {
		expect(
			mapWordPressServiceCollection({
				schema_version: 1,
				collection_key: 'services',
				services: [service],
			}),
		).toEqual({
			schemaVersion: 1,
			collectionKey: 'services',
			services: [
				{
					serviceKey: 'hse-leadership',
					title: 'HSE leadership',
					cardLabel: 'Leadership',
					shortDescription: 'Experienced HSE management',
					detailedDescription: 'Experienced HSE managers and project safety leaders.',
					cta: { label: 'Enquire', url: '/contact/' },
					image: {
						url: 'https://cms.example.test/service.webp',
						alt: 'HSE leaders reviewing a project',
						width: 600,
						height: 815,
					},
					featuredOnHomepage: true,
				},
			],
		});
	});

	it('rejects unsafe CTA links', () => {
		expect(() =>
			mapWordPressServiceCollection({
				schema_version: 1,
				collection_key: 'services',
				services: [{ ...service, cta: { label: 'Bad', url: 'javascript:alert(1)' } }],
			}),
		).toThrow('must use HTTP or HTTPS');
	});

	it('rejects malformed image dimensions', () => {
		expect(() =>
			mapWordPressServiceCollection({
				schema_version: 1,
				collection_key: 'services',
				services: [{ ...service, image: { ...service.image, width: 0 } }],
			}),
		).toThrow('image.width must be a positive integer');
	});
});

describe('mapWordPressFeaturedServices', () => {
	it('accepts up to four Homepage-featured Services', () => {
		expect(
			mapWordPressFeaturedServices(
				Array.from({ length: 4 }, (_, index) => ({
					...service,
					service_key: `service-${index + 1}`,
				})),
			),
		).toHaveLength(4);
	});

	it('rejects a Service that is not selected for the Homepage', () => {
		expect(() =>
			mapWordPressFeaturedServices([{ ...service, featured_on_homepage: false }]),
		).toThrow('not selected for the Homepage');
	});

	it('rejects more than four featured Services', () => {
		expect(() =>
			mapWordPressFeaturedServices(
				Array.from({ length: 5 }, (_, index) => ({
					...service,
					service_key: `service-${index + 1}`,
				})),
			),
		).toThrow('between 1 and 4 items');
	});
});
