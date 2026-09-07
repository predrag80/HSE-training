import type { Course } from '../types/course';
import { hasBespokeCoursePage } from './course-routes';

export interface CourseStaticPath {
	readonly params: {
		readonly slug: string;
	};
	readonly props: {
		readonly course: Course;
	};
}

export interface CoursePageMetadata {
	readonly title: string;
	readonly description: string;
}

export function createCourseStaticPaths(courses: readonly Course[]): CourseStaticPath[] {
	const slugs = new Set<string>();

	return courses.map((course) => {
		if (slugs.has(course.slug)) {
			throw new Error(`Duplicate Course slug received from the CMS: ${course.slug}`);
		}

		slugs.add(course.slug);

		return {
			params: { slug: course.slug },
			props: { course },
		};
	});
}

export function createGenericCourseStaticPaths(courses: readonly Course[]): CourseStaticPath[] {
	return createCourseStaticPaths(courses.filter((course) => !hasBespokeCoursePage(course.courseKey)));
}

export function getCoursePageMetadata(course: Course): CoursePageMetadata {
	return {
		title: course.title,
		description: course.shortDescription,
	};
}
