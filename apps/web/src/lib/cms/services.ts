import type { ServiceCollection } from '../../types/service';
import { defaultLocale, type Locale } from '../../i18n/config';
import { fetchCmsJson } from './client';
import { mapWordPressServiceCollection } from './service-mappers';

const SERVICES_ENDPOINT = '/wp-json/hse/v1/services';

/** Returns all published consulting Services in editorial order. */
export async function getServices(locale: Locale = defaultLocale): Promise<ServiceCollection> {
	return mapWordPressServiceCollection(await fetchCmsJson(SERVICES_ENDPOINT, { lang: locale }), locale);
}
