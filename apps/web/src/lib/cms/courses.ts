import type { Course } from '../../types/course';
import { defaultLocale, type Locale } from '../../i18n/config';
import { getCourseUiTranslations } from '../../i18n/course-ui';
import { getEditorialCourseUi } from '../../i18n/editorial-course-ui';
import { getBespokeCoursePath, NEBOSH_COURSE_KEYS } from '../course-routes';
import { fetchCmsJson } from './client';
import { isCanonicalCourseKey } from './course-key';
import { CmsError } from './errors';
import { mapWordPressCourse } from './mappers';

const COURSE_ENDPOINT = '/wp-json/wp/v2/courses';
const COURSE_FIELDS = 'locale,slug,status,title,content,featured_media,meta,_links,_embedded';
const HOMEPAGE_COURSE_KEYS = [
	NEBOSH_COURSE_KEYS.igc,
	NEBOSH_COURSE_KEYS.eaw,
	NEBOSH_COURSE_KEYS.iogc,
] as const;

const COLLECTION_QUERY = {
	_embed: 'wp:featuredmedia',
	_fields: COURSE_FIELDS,
	per_page: '100',
	orderby: 'date',
	order: 'desc',
} as const;

function createHomepageCourseFallback(
	courseKey: (typeof HOMEPAGE_COURSE_KEYS)[number],
	locale: Locale,
): Course {
	const courseCopy = getCourseUiTranslations(locale);
	const editorialCopy = getEditorialCourseUi(locale);
	const copy =
		courseKey === NEBOSH_COURSE_KEYS.igc
			? {
					title: courseCopy.nebosh.igc.fallbackTitle,
					shortDescription: courseCopy.nebosh.igc.fallbackDescription,
				}
			: courseKey === NEBOSH_COURSE_KEYS.iogc
				? {
						title: courseCopy.nebosh.iogc.fallbackTitle,
						shortDescription: courseCopy.nebosh.iogc.fallbackDescription,
					}
				: {
						title: editorialCopy.eaw.title,
						shortDescription: editorialCopy.eaw.shortDescription,
					};
	const path = getBespokeCoursePath(courseKey);

	return {
		locale,
		slug: path?.replace(/^\//, '').replace(/\/$/, '') ?? courseKey,
		courseKey,
		title: copy.title,
		shortDescription: copy.shortDescription,
		descriptionHtml: '',
		featuredImageUrl: null,
		visiblePrice: locale === 'sr' ? 'Cena na upit' : 'Price on request',
		homepageLabel: locale === 'sr' ? 'NEBOSH kvalifikacija' : 'NEBOSH qualification',
		homepageCtaLabel: courseCopy.card.view,
		featuredOnHomepage: false,
		status: 'publish',
	};
}

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

/** Returns the three NEBOSH Courses shown in the fixed Homepage course section. */
export async function getHomepageCourses(locale: Locale = defaultLocale): Promise<Course[]> {
	const courses = mapCourseCollection(
		await fetchCmsJson(COURSE_ENDPOINT, {
			...COLLECTION_QUERY,
			lang: locale,
		}),
		locale,
	);
	const coursesByKey = new Map(courses.map((course) => [course.courseKey, course]));

	return HOMEPAGE_COURSE_KEYS.map((courseKey) => {
		const fallback = createHomepageCourseFallback(courseKey, locale);
		const course = coursesByKey.get(courseKey);
		if (!course) return fallback;

		return {
			...course,
			shortDescription: course.shortDescription.trim() || fallback.shortDescription,
			visiblePrice: course.visiblePrice ?? fallback.visiblePrice,
			homepageLabel: course.homepageLabel.trim() || fallback.homepageLabel,
			homepageCtaLabel: course.homepageCtaLabel.trim() || fallback.homepageCtaLabel,
		};
	});
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
