import { defaultLocale, type Locale } from '../../i18n/config';
import type { CoursesLandingPageContent, NeboshOverviewPageContent, TrainingOverviewPageContent } from '../../types/course-page';
import { fetchCmsJson } from './client';
import { mapWordPressCoursesLandingPage, mapWordPressNeboshOverviewPage, mapWordPressTrainingOverviewPage } from './course-page-mappers';

const COURSE_PAGES_ENDPOINT = '/wp-json/hse/v1/course-pages';

export async function getCoursesLandingPage(locale: Locale = defaultLocale): Promise<CoursesLandingPageContent> {
	return mapWordPressCoursesLandingPage(
		await fetchCmsJson(`${COURSE_PAGES_ENDPOINT}/courses`, { lang: locale }),
		locale,
	);
}

export async function getNeboshOverviewPage(locale: Locale = defaultLocale): Promise<NeboshOverviewPageContent> {
	return mapWordPressNeboshOverviewPage(
		await fetchCmsJson(`${COURSE_PAGES_ENDPOINT}/nebosh`, { lang: locale }),
		locale,
	);
}

export async function getTrainingOverviewPage(locale: Locale = defaultLocale): Promise<TrainingOverviewPageContent> {
	return mapWordPressTrainingOverviewPage(
		await fetchCmsJson(`${COURSE_PAGES_ENDPOINT}/training`, { lang: locale }),
		locale,
	);
}
