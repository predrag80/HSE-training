import type { CompanyPageContent } from '../../types/company';
import { defaultLocale, type Locale } from '../../i18n/config';
import { fetchCmsJson } from './client';
import { mapWordPressCompanyPage } from './company-mappers';

const COMPANY_ENDPOINT = '/wp-json/hse/v1/company';

/** Returns Company Page content and the canonical shared company profile. */
export async function getCompanyPage(locale: Locale = defaultLocale): Promise<CompanyPageContent> {
	return mapWordPressCompanyPage(await fetchCmsJson(COMPANY_ENDPOINT, { lang: locale }), locale);
}
