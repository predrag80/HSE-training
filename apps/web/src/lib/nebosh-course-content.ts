import type { Course } from '../types/course';
import { defaultLocale, type Locale } from '../i18n/config';
import { getCourseByKey } from './cms';
import { CmsError } from './cms/errors';

/**
 * Loads required editor-owned Course content for a bespoke NEBOSH page.
 * A missing language variant is a build-time content error, never a fallback.
 */
export async function loadRequiredNeboshCourse(
	courseKey: string,
	locale: Locale = defaultLocale,
): Promise<Course> {
	const course = await getCourseByKey(courseKey, locale);
	if (!course) {
		throw new CmsError(
			'invalid-response',
			`CMS is missing the ${locale} Course translation for course_key ${courseKey}.`,
		);
	}

	return course;
}
