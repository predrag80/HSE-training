import type { Course } from '../types/course';

export const NEBOSH_COURSE_KEYS = {
	igc: 'nebosh-igc',
	iogc: 'nebosh-iogc',
} as const;

const BESPOKE_COURSE_PATHS: Readonly<Record<string, string>> = {
	[NEBOSH_COURSE_KEYS.igc]:
		'/nebosh-international-general-certificate-in-occupational-health-and-safety/',
	[NEBOSH_COURSE_KEYS.iogc]: '/nebosh-international-oilgas-certificate/',
};

export function getBespokeCoursePath(courseKey: string): string | null {
	return BESPOKE_COURSE_PATHS[courseKey] ?? null;
}

export function getCoursePublicPath(course: Pick<Course, 'courseKey' | 'slug'>): string {
	return getBespokeCoursePath(course.courseKey) ?? `/courses/${course.slug}`;
}

export function hasBespokeCoursePage(courseKey: string): boolean {
	return getBespokeCoursePath(courseKey) !== null;
}
