import { describe, expect, it } from 'vitest';

import type { Course } from '../types/course';
import {
	getBespokeCoursePath,
	getCoursePublicPath,
	hasBespokeCoursePage,
	NEBOSH_COURSE_KEYS,
} from './course-routes';

const course = (courseKey: string, slug: string): Pick<Course, 'courseKey' | 'slug'> => ({
	courseKey,
	slug,
});

describe('Course public routes', () => {
	it('maps the NEBOSH IGC stable key to its designed route', () => {
		expect(getBespokeCoursePath(NEBOSH_COURSE_KEYS.igc)).toBe(
			'/nebosh-international-general-certificate-in-occupational-health-and-safety/',
		);
	});

	it('maps the NEBOSH IOGC stable key to its designed route', () => {
		expect(getCoursePublicPath(course(NEBOSH_COURSE_KEYS.iogc, 'editable-wp-slug'))).toBe(
			'/nebosh-international-oilgas-certificate/',
		);
	});

	it('uses the generic dynamic route for courses without a bespoke page', () => {
		expect(getCoursePublicPath(course('iosh-managing-safely', 'iosh-course'))).toBe(
			'/courses/iosh-course',
		);
		expect(hasBespokeCoursePage('iosh-managing-safely')).toBe(false);
	});
});
