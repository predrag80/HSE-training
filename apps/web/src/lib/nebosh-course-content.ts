import type { Course } from '../types/course';
import { getCourseByKey, isCmsError } from './cms';

/**
 * Loads editor-owned Course content for a bespoke NEBOSH page.
 *
 * These pages keep curated fallback copy so the public design remains available
 * during a temporary CMS outage. Contract and configuration errors still fail
 * loudly instead of hiding an invalid integration.
 */
export async function loadOptionalNeboshCourse(courseKey: string): Promise<Course | null> {
	try {
		return await getCourseByKey(courseKey);
	} catch (error) {
		if (isCmsError(error) && (error.code === 'unavailable' || error.code === 'http')) {
			return null;
		}

		throw error;
	}
}
