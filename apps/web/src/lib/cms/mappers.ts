import type { Course } from '../../types/course';
import { isCanonicalCourseKey } from './course-key';
import { CmsError } from './errors';
import type { WordPressCourseDto } from './types';

function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function invalidCourse(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned an invalid Course response: ${reason}.`);
}

function parseCourse(value: unknown): WordPressCourseDto {
	if (!isRecord(value) || !isRecord(value.title) || !isRecord(value.content) || !isRecord(value.meta)) {
		return invalidCourse('expected an object with title, content, and meta fields');
	}

	const { slug, status, title, content, featured_media: featuredMedia, meta } = value;
	if (typeof slug !== 'string' || slug.length === 0) return invalidCourse('slug is required');
	if (status !== 'publish') return invalidCourse('status must be publish');
	if (typeof title.rendered !== 'string' || title.rendered.length === 0) {
		return invalidCourse('title.rendered is required');
	}
	if (typeof content.rendered !== 'string') return invalidCourse('content.rendered is required');
	if (content.protected !== false) return invalidCourse('protected content is not public');
	if (!Number.isInteger(featuredMedia) || (featuredMedia as number) < 0) {
		return invalidCourse('featured_media must be a non-negative integer');
	}
	if (typeof meta.course_key !== 'string' || !isCanonicalCourseKey(meta.course_key)) {
		return invalidCourse('meta.course_key is required and must be canonical');
	}
	if (typeof meta.short_description !== 'string') {
		return invalidCourse('meta.short_description is required');
	}
	if (typeof meta.visible_price !== 'string') {
		return invalidCourse('meta.visible_price is required');
	}
	if (typeof meta.homepage_label !== 'string') {
		return invalidCourse('meta.homepage_label is required');
	}
	if (typeof meta.homepage_cta_label !== 'string') {
		return invalidCourse('meta.homepage_cta_label is required');
	}
	if (typeof meta.featured_on_homepage !== 'boolean') {
		return invalidCourse('meta.featured_on_homepage must be boolean');
	}

	return value as unknown as WordPressCourseDto;
}

function getFeaturedImageUrl(course: WordPressCourseDto): string | null {
	if (course.featured_media === 0) return null;

	const sourceUrl = course._embedded?.['wp:featuredmedia']?.[0]?.source_url;
	if (typeof sourceUrl !== 'string') {
		return invalidCourse('featured media data is missing');
	}

	try {
		const url = new URL(sourceUrl);
		if (url.protocol !== 'http:' && url.protocol !== 'https:') {
			throw new TypeError('unsupported protocol');
		}
	} catch {
		return invalidCourse('featured media URL is invalid');
	}

	return sourceUrl;
}

export function mapWordPressCourse(value: unknown): Course {
	const course = parseCourse(value);

	return {
		slug: course.slug,
		courseKey: course.meta.course_key,
		title: course.title.rendered,
		shortDescription: course.meta.short_description,
		descriptionHtml: course.content.rendered,
		featuredImageUrl: getFeaturedImageUrl(course),
		visiblePrice: course.meta.visible_price || null,
		homepageLabel: course.meta.homepage_label,
		homepageCtaLabel: course.meta.homepage_cta_label,
		featuredOnHomepage: course.meta.featured_on_homepage,
		status: course.status,
	};
}
