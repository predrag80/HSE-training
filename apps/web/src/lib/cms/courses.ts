import type { Course } from '../../types/course';
import { defaultLocale, type Locale } from '../../i18n/config';
import { fetchCmsJson } from './client';
import { isCanonicalCourseKey } from './course-key';
import { CmsError } from './errors';
import { mapWordPressCourse } from './mappers';

const COURSE_ENDPOINT = '/wp-json/wp/v2/courses';
const COURSE_FIELDS = 'locale,slug,status,title,content,featured_media,meta,_links,_embedded';
const MAX_HOMEPAGE_COURSES = 3;

const COLLECTION_QUERY = {
	_embed: 'wp:featuredmedia',
	_fields: COURSE_FIELDS,
	per_page: '100',
	orderby: 'date',
	order: 'desc',
} as const;

function mapCourseCollection(value: unknown, locale: Locale): Course[] {
	if (!Array.isArray(value)) {
		throw new CmsError('invalid-response', 'CMS returned an invalid Course collection.');
	}

	return value.map((course) => mapWordPressCourse(course, locale));
}

function mapUniqueCourse(value: unknown, lookupName: string, locale: Locale): Course | null {
	const courses = mapCourseCollection(value, locale);
	if (courses.length > 1) {
		throw new CmsError(
			'invalid-response',
			`CMS returned multiple Courses for the unique ${lookupName} lookup.`,
		);
	}

	return courses[0] ?? null;
}

function validateLookupValue(value: string, name: 'slug' | 'course_key', maxLength: number): void {
	if (value.trim().length === 0 || value.length > maxLength) {
		throw new CmsError('invalid-query', `Course ${name} is invalid.`);
	}
}

/** Returns up to 100 published Courses in newest-first WordPress date order. */
export async function getCourses(locale: Locale = defaultLocale): Promise<Course[]> {
	return mapCourseCollection(
		await fetchCmsJson(COURSE_ENDPOINT, { ...COLLECTION_QUERY, lang: locale }),
		locale,
	);
}

/** Returns the ordered one-to-three Courses explicitly promoted on the Homepage. */
export async function getHomepageCourses(locale: Locale = defaultLocale): Promise<Course[]> {
	const courses = mapCourseCollection(
		await fetchCmsJson(COURSE_ENDPOINT, {
			...COLLECTION_QUERY,
			per_page: String(MAX_HOMEPAGE_COURSES + 1),
			orderby: 'menu_order',
			order: 'asc',
			featured_on_homepage: 'true',
			lang: locale,
		}),
		locale,
	);

	if (courses.length === 0 || courses.length > MAX_HOMEPAGE_COURSES) {
		throw new CmsError(
			'invalid-response',
			`CMS must return between 1 and ${MAX_HOMEPAGE_COURSES} Homepage Courses.`,
		);
	}
	for (const course of courses) {
		if (
			!course.featuredOnHomepage ||
			course.homepageLabel.trim().length === 0 ||
			course.homepageCtaLabel.trim().length === 0 ||
			course.shortDescription.trim().length === 0 ||
			course.visiblePrice === null ||
			course.featuredImageUrl === null
		) {
			throw new CmsError('invalid-response', 'CMS returned an incomplete Homepage Course.');
		}
	}

	return courses;
}

/** Returns a published Course by its mutable WordPress route slug, or null when absent. */
export async function getCourseBySlug(slug: string, locale: Locale = defaultLocale): Promise<Course | null> {
	validateLookupValue(slug, 'slug', 200);

	return mapUniqueCourse(
		await fetchCmsJson(COURSE_ENDPOINT, {
			...COLLECTION_QUERY,
			slug,
			lang: locale,
		}),
		'slug',
		locale,
	);
}

/** Returns a published Course by its stable cross-system course_key, or null when absent. */
export async function getCourseByKey(courseKey: string, locale: Locale = defaultLocale): Promise<Course | null> {
	validateLookupValue(courseKey, 'course_key', 80);
	if (!isCanonicalCourseKey(courseKey)) {
		throw new CmsError('invalid-query', 'Course course_key is invalid.');
	}

	return mapUniqueCourse(
		await fetchCmsJson(COURSE_ENDPOINT, {
			...COLLECTION_QUERY,
			course_key: courseKey,
			lang: locale,
		}),
		'course_key',
		locale,
	);
}
