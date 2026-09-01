const COURSE_KEY_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export function isCanonicalCourseKey(value: string): boolean {
	return value.length <= 80 && COURSE_KEY_PATTERN.test(value);
}
