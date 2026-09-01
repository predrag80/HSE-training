import { describe, expect, it } from 'vitest';

import type { Course } from '../types/course';
import { createCourseStaticPaths, getCoursePageMetadata } from './course-pages';

const firstCourse: Course = {
	slug: 'nebosh-international-general-certificate',
	courseKey: 'nebosh-igc',
	title: 'NEBOSH International General Certificate',
	shortDescription: 'Build practical health and safety knowledge.',
	descriptionHtml: '<p>Trusted editor content.</p>',
	featuredImageUrl: null,
	visiblePrice: '€499',
	status: 'publish',
};

const secondCourse: Course = {
	...firstCourse,
	slug: 'iosh-managing-safely',
	courseKey: 'iosh-managing-safely',
	title: 'IOSH Managing Safely',
};

describe('createCourseStaticPaths', () => {
	it('returns no paths when no courses are published', () => {
		expect(createCourseStaticPaths([])).toEqual([]);
	});

	it('creates one domain-oriented path for each published course', () => {
		expect(createCourseStaticPaths([firstCourse, secondCourse])).toEqual([
			{
				params: { slug: firstCourse.slug },
				props: { course: firstCourse },
			},
			{
				params: { slug: secondCourse.slug },
				props: { course: secondCourse },
			},
		]);
	});

	it('throws a clear error instead of generating duplicate Course paths', () => {
		const duplicateSlugCourse = {
			...secondCourse,
			slug: firstCourse.slug,
		};

		expect(() => createCourseStaticPaths([firstCourse, duplicateSlugCourse])).toThrow(
			`Duplicate Course slug received from the CMS: ${firstCourse.slug}`,
		);
	});
});

describe('getCoursePageMetadata', () => {
	it('uses the Course title and short description', () => {
		expect(getCoursePageMetadata(firstCourse)).toEqual({
			title: firstCourse.title,
			description: firstCourse.shortDescription,
		});
	});
});
