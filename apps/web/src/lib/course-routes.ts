import type { Course } from '../types/course';
import { defaultLocale, type Locale } from '../i18n/config';
import { getLocalizedPath } from '../i18n/routes';

export const NEBOSH_COURSE_KEYS = {
	igc: 'nebosh-igc',
	iogc: 'nebosh-iogc',
	eaw: 'nebosh-eaw',
} as const;

export const TRAINING_COURSE_KEYS = {
	custom: 'custom-training-design',
	banksman: 'banksman-slinger',
	trainer: 'train-the-trainer',
} as const;

const BESPOKE_COURSE_PATHS: Readonly<Record<string, string>> = {
	[NEBOSH_COURSE_KEYS.igc]:
		'/nebosh-international-general-certificate-in-occupational-health-and-safety/',
	[NEBOSH_COURSE_KEYS.iogc]: '/nebosh-international-oilgas-certificate/',
	[NEBOSH_COURSE_KEYS.eaw]: '/nebosh-award-environmental-awareness-at-work/',
	[TRAINING_COURSE_KEYS.custom]: '/training/custom-training-design/',
	[TRAINING_COURSE_KEYS.banksman]: '/training/banksman-slinger/',
	[TRAINING_COURSE_KEYS.trainer]: '/training/train-the-trainer/',
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

/** Route active IGC promotion to its details and enquiry-only NEBOSH cards to Contact. */
export function getNeboshCourseCardHref(
	course: Pick<Course, 'courseKey' | 'slug'>,
	locale: Locale = defaultLocale,
): string {
	return course.courseKey === NEBOSH_COURSE_KEYS.igc
		? getCoursePublicPath(course, locale)
		: getLocalizedPath('/contact/', locale);
}

export function hasBespokeCoursePage(courseKey: string): boolean {
	return getBespokeCoursePath(courseKey) !== null;
}
