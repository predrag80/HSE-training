import { defaultLocale, type Locale } from '../../i18n/config';
import type { LegalPageContent, LegalPageKey } from '../../types/legal';
import { fetchCmsJson } from './client';
import { mapWordPressLegalPage } from './legal-page-mappers';

const LEGAL_PAGES_ENDPOINT = '/wp-json/hse/v1/legal-pages';

/** Return one required, locale-specific legal page from WordPress. */
export async function getLegalPage(
	pageKey: LegalPageKey,
	locale: Locale = defaultLocale,
): Promise<LegalPageContent> {
	return mapWordPressLegalPage(
		await fetchCmsJson(`${LEGAL_PAGES_ENDPOINT}/${pageKey}`, { lang: locale }),
		pageKey,
		locale,
	);
}
