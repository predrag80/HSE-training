import type { Course } from '../types/course';
import type { Locale } from '../i18n/config';
import { getCoursePublicPath, hasBespokeCoursePage } from './course-routes';

export type CourseLanguageAlternates = Readonly<Record<Locale, string>>;

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

export interface LocalizedCourseStaticPath extends CourseStaticPath {
	readonly props: {
		readonly course: Course;
		readonly languageAlternates: CourseLanguageAlternates;
	};
}

/** Build generic Course routes and pair translated slugs through course_key. */
export function createLocalizedGenericCourseStaticPaths(
	courses: readonly Course[],
	translations: readonly Course[],
): LocalizedCourseStaticPath[] {
	const translationsByKey = new Map<string, Course>();
	for (const translation of translations) {
		if (translationsByKey.has(translation.courseKey)) {
			throw new Error(`Duplicate translated Course key received from the CMS: ${translation.courseKey}`);
		}
		translationsByKey.set(translation.courseKey, translation);
	}

	return createGenericCourseStaticPaths(courses).map(({ params, props }) => {
		const course = props.course;
		const translation = translationsByKey.get(course.courseKey);
		if (!translation) {
			throw new Error(`Missing Course translation for course_key: ${course.courseKey}`);
		}
		if (translation.locale === course.locale) {
			throw new Error(`Course translation has duplicate locale for course_key: ${course.courseKey}`);
		}

		const languageAlternates = {
			[course.locale]: getCoursePublicPath(course, course.locale),
			[translation.locale]: getCoursePublicPath(translation, translation.locale),
		} as Partial<Record<Locale, string>>;
		if (!languageAlternates.en || !languageAlternates.sr) {
			throw new Error(`Course translation pair must include en and sr: ${course.courseKey}`);
		}

		return {
			params,
			props: {
				course,
				languageAlternates: languageAlternates as Record<Locale, string>,
			},
		};
	});
}

export function getCoursePageMetadata(course: Course): CoursePageMetadata {
	return {
		title: course.title,
		description: course.shortDescription,
	};
}
