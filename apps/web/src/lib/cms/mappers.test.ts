import { describe, expect, it } from 'vitest';

import { mapWordPressCourse } from './mappers';

const publishedCourse = {
	locale: 'en',
	slug: 'nebosh-international-general-certificate',
	status: 'publish',
	title: {
		rendered: 'NEBOSH International General Certificate',
	},
	content: {
		rendered: '<p>Trusted CMS marketing content.</p>',
		protected: false,
	},
	featured_media: 42,
	meta: {
		course_key: 'nebosh-igc',
		short_description: 'Develop practical health and safety skills.',
		visible_price: '€499',
		homepage_label: 'Popular course',
		homepage_cta_label: 'View course',
		featured_on_homepage: true,
		footnotes: '',
	},
	_embedded: {
		'wp:featuredmedia': [
			{
				source_url: 'https://cms.example.test/course.jpg',
			},
		],
	},
};

describe('mapWordPressCourse', () => {
	it('maps an approved WordPress response into the internal Course shape', () => {
		expect(mapWordPressCourse(publishedCourse, 'en')).toEqual({
			locale: 'en',
			slug: 'nebosh-international-general-certificate',
			courseKey: 'nebosh-igc',
			title: 'NEBOSH International General Certificate',
			shortDescription: 'Develop practical health and safety skills.',
			descriptionHtml: '<p>Trusted CMS marketing content.</p>',
			featuredImageUrl: 'https://cms.example.test/course.jpg',
			visiblePrice: '€499',
			homepageLabel: 'Popular course',
			homepageCtaLabel: 'View course',
			featuredOnHomepage: true,
			status: 'publish',
		});
	});

	it('maps a Course without a featured image or visible price to null values', () => {
		expect(
			mapWordPressCourse(
				{
					...publishedCourse,
					featured_media: 0,
					meta: {
						...publishedCourse.meta,
						visible_price: '',
					},
					_embedded: undefined,
				},
				'en',
			),
		).toMatchObject({
			featuredImageUrl: null,
			visiblePrice: null,
		});
	});

	it('decodes WordPress title entities into plain text', () => {
		expect(
			mapWordPressCourse(
				{ ...publishedCourse, title: { rendered: 'Oil &amp;#038; Gas &#8211; Safety' } },
				'en',
			).title,
		).toBe('Oil & Gas – Safety');
	});

	it('rejects a response missing a required course_key', () => {
		expect(() =>
			mapWordPressCourse(
				{
					...publishedCourse,
					meta: {
						short_description: publishedCourse.meta.short_description,
						visible_price: publishedCourse.meta.visible_price,
						homepage_label: publishedCourse.meta.homepage_label,
						homepage_cta_label: publishedCourse.meta.homepage_cta_label,
						featured_on_homepage: publishedCourse.meta.featured_on_homepage,
					},
				},
				'en',
			),
		).toThrow('invalid Course response');
	});

	it('rejects protected WordPress content from the public Course model', () => {
		expect(() =>
			mapWordPressCourse(
				{
					...publishedCourse,
					content: {
						...publishedCourse.content,
						protected: true,
					},
				},
				'en',
			),
		).toThrow('invalid Course response');
	});

	it('rejects content returned in a language other than the requested one', () => {
		expect(() => mapWordPressCourse({ ...publishedCourse, locale: 'sr' }, 'en')).toThrow(
			'locale must match the requested en language',
		);
	});
});
