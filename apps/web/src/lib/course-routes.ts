import type { Course } from '../types/course';
import { defaultLocale, type Locale } from '../i18n/config';
import { getLocalizedPath } from '../i18n/routes';

export const NEBOSH_COURSE_KEYS = {
	igc: 'nebosh-igc',
	iogc: 'nebosh-iogc',
	eaw: 'nebosh-eaw',
} as const;

const BESPOKE_COURSE_PATHS: Readonly<Record<string, string>> = {
	[NEBOSH_COURSE_KEYS.igc]:
		'/nebosh-international-general-certificate-in-occupational-health-and-safety/',
	[NEBOSH_COURSE_KEYS.iogc]: '/nebosh-international-oilgas-certificate/',
	[NEBOSH_COURSE_KEYS.eaw]: '/nebosh-award-environmental-awareness-at-work/',
};

export function getBespokeCoursePath(courseKey: string): string | null {
	return BESPOKE_COURSE_PATHS[courseKey] ?? null;
}

export function getCoursePublicPath(
	course: Pick<Course, 'courseKey' | 'slug'>,
	locale: Locale = defaultLocale,
): string {
	const path = getBespokeCoursePath(course.courseKey) ?? `/courses/${course.slug}/`;

	return getLocalizedPath(path, locale);
}

export function hasBespokeCoursePage(courseKey: string): boolean {
	return getBespokeCoursePath(courseKey) !== null;
}
