/** Build-time construction of the per-locale static site search index. */

import type { Locale } from '../../i18n/config';
import { getCompanyUiTranslations } from '../../i18n/company-ui';
import { getConsultingUiTranslations } from '../../i18n/consulting-ui';
import { getContactUiTranslations } from '../../i18n/contact-ui';
import { getGalleryUiTranslations } from '../../i18n/gallery-ui';
import { getResourcesUiTranslations } from '../../i18n/resources-ui';
import { getLocalizedPath } from '../../i18n/routes';
import type { Course } from '../../types/course';
import type { LegalPageKey } from '../../types/legal';
import type { Resource } from '../../types/resource';
import type { Service } from '../../types/service';
import {
	getCoursesLandingPage,
	getHomepageCourses,
	getLegalPage,
	getNeboshOverviewPage,
	getResources,
	getServices,
	getTrainingCourses,
	getTrainingOverviewPage,
} from '../cms';
import { getCoursePublicPath } from '../course-routes';
import type { SearchEntry } from './search-match';

const KEYWORD_TEXT_LIMIT = 1500;
const SERVICES_SECTION_PATH = '/browse-hse-talent/';
const SERVICES_SECTION_ANCHOR = '#consulting-services-title';
const LEGAL_PAGE_PATHS: Readonly<Record<LegalPageKey, string>> = {
	privacy: '/privacy-policy/',
	terms: '/terms-and-conditions/',
	withdrawal: '/withdrawal-form/',
	copyright: '/copyright/',
};

export interface SearchPageSource {
	/** Default-locale public path; the builder localizes it. */
	readonly path: string;
	readonly title: string;
	readonly description: string;
}

export interface SearchSources {
	readonly pages: readonly SearchPageSource[];
	readonly courses: readonly Course[];
	readonly trainings: readonly Course[];
	readonly services: readonly Service[];
	readonly resources: readonly Resource[];
}

const HTML_ENTITIES: Readonly<Record<string, string>> = {
	amp: '&',
	lt: '<',
	gt: '>',
	quot: '"',
	apos: "'",
	nbsp: ' ',
};

/** Convert CMS HTML into bounded plain text for matching only; it is never rendered. */
export function htmlToSearchText(html: string, limit = KEYWORD_TEXT_LIMIT): string {
	const text = html
		.replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, ' ')
		.replace(/<[^>]*>/g, ' ')
		.replace(/&(#x[0-9a-f]+|#\d+|[a-z]+);/gi, (entity, name: string) => {
			if (name.startsWith('#x') || name.startsWith('#X')) return String.fromCodePoint(Number.parseInt(name.slice(2), 16));
			if (name.startsWith('#')) return String.fromCodePoint(Number.parseInt(name.slice(1), 10));
			return HTML_ENTITIES[name.toLowerCase()] ?? entity;
		})
		.replace(/\s+/g, ' ')
		.trim();

	if (text.length <= limit) return text;
	const cut = text.slice(0, limit);
	const lastSpace = cut.lastIndexOf(' ');
	return lastSpace > 0 ? cut.slice(0, lastSpace) : cut;
}

/** Page titles carry the site-name suffix for browser tabs; results show the page name only. */
export function stripSiteNameSuffix(title: string): string {
	return title.replace(/\s*\|\s*HSE Training\s*$/, '').trim();
}

function joinKeywords(...values: readonly (string | null | undefined)[]): string {
	return values.filter((value): value is string => Boolean(value?.trim())).join(' ');
}

function createCourseEntry(course: Course, kind: 'course' | 'training', locale: Locale): SearchEntry {
	return {
		kind,
		title: course.title,
		summary: course.shortDescription,
		url: getCoursePublicPath(course, locale),
		keywords: joinKeywords(
			course.courseKey.replace(/-/g, ' '),
			course.pageEyebrow,
			htmlToSearchText(course.descriptionHtml),
		),
		external: false,
	};
}

/** Map already validated CMS and dictionary content to search entries in result-priority order. */
export function createSearchEntries(sources: SearchSources, locale: Locale): SearchEntry[] {
	return [
		...sources.courses.map((course) => createCourseEntry(course, 'course', locale)),
		...sources.trainings.map((course) => createCourseEntry(course, 'training', locale)),
		...sources.pages.map((page): SearchEntry => ({
			kind: 'page',
			title: stripSiteNameSuffix(page.title),
			summary: page.description,
			url: getLocalizedPath(page.path, locale),
			keywords: '',
			external: false,
		})),
		...sources.services.map((service): SearchEntry => ({
			kind: 'service',
			title: service.title,
			summary: service.shortDescription,
			url: `${getLocalizedPath(SERVICES_SECTION_PATH, locale)}${SERVICES_SECTION_ANCHOR}`,
			keywords: joinKeywords(service.cardLabel, service.detailedDescription),
			external: false,
		})),
		...sources.resources.map((resource): SearchEntry => ({
			kind: 'resource',
			title: resource.title,
			summary: resource.summary,
			url: resource.actionUrl,
			keywords: joinKeywords(resource.topic, resource.type, htmlToSearchText(resource.description)),
			external: true,
		})),
	];
}

/** Load every searchable public document for one locale; any CMS failure fails the build. */
export async function loadSearchEntries(locale: Locale): Promise<SearchEntry[]> {
	const legalPageKeys = Object.keys(LEGAL_PAGE_PATHS) as LegalPageKey[];
	const [courses, trainings, coursesLanding, neboshOverview, trainingOverview, services, resources, legalPages] = await Promise.all([
		getHomepageCourses(locale),
		getTrainingCourses(locale),
		getCoursesLandingPage(locale),
		getNeboshOverviewPage(locale),
		getTrainingOverviewPage(locale),
		getServices(locale),
		getResources(locale),
		Promise.all(legalPageKeys.map((pageKey) => getLegalPage(pageKey, locale))),
	]);
	const dictionaryPages = [
		{ path: '/company/', copy: getCompanyUiTranslations(locale) },
		{ path: '/browse-hse-talent/', copy: getConsultingUiTranslations(locale) },
		{ path: '/resources/', copy: getResourcesUiTranslations(locale) },
		{ path: '/gallery/', copy: getGalleryUiTranslations(locale) },
		{ path: '/contact/', copy: getContactUiTranslations(locale) },
	];

	return createSearchEntries(
		{
			pages: [
				{ path: '/courses/', ...coursesLanding.meta },
				{ path: '/nebosh/', ...neboshOverview.meta },
				{ path: '/training/', ...trainingOverview.meta },
				...dictionaryPages.map(({ path, copy }) => ({
					path,
					title: copy.metaTitle,
					description: copy.metaDescription,
				})),
				...legalPages.map((page) => ({
					path: LEGAL_PAGE_PATHS[page.pageKey],
					...page.meta,
				})),
			],
			courses,
			trainings,
			services: services.services,
			resources: resources.resources,
		},
		locale,
	);
}
