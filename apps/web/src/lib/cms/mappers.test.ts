import { describe, expect, it } from 'vitest';

import { mapWordPressCourse } from './mappers';

const publishedCourse = {
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
		expect(mapWordPressCourse(publishedCourse)).toEqual({
			slug: 'nebosh-international-general-certificate',
			courseKey: 'nebosh-igc',
			title: 'NEBOSH International General Certificate',
			shortDescription: 'Develop practical health and safety skills.',
			descriptionHtml: '<p>Trusted CMS marketing content.</p>',
			featuredImageUrl: 'https://cms.example.test/course.jpg',
			visiblePrice: '€499',
			status: 'publish',
		});
	});

	it('maps a Course without a featured image or visible price to null values', () => {
		expect(
			mapWordPressCourse({
				...publishedCourse,
				featured_media: 0,
				meta: {
					...publishedCourse.meta,
					visible_price: '',
				},
				_embedded: undefined,
			}),
		).toMatchObject({
			featuredImageUrl: null,
			visiblePrice: null,
		});
	});

	it('rejects a response missing a required course_key', () => {
		expect(() =>
			mapWordPressCourse({
				...publishedCourse,
				meta: {
					short_description: publishedCourse.meta.short_description,
					visible_price: publishedCourse.meta.visible_price,
				},
			}),
		).toThrow('invalid Course response');
	});

	it('rejects protected WordPress content from the public Course model', () => {
		expect(() =>
			mapWordPressCourse({
				...publishedCourse,
				content: {
					...publishedCourse.content,
					protected: true,
				},
			}),
		).toThrow('invalid Course response');
	});
});
