import { defaultLocale, type Locale } from '../../i18n/config';
import type { ResourceCollection } from '../../types/resource';
import { fetchCmsJson } from './client';
import { mapWordPressResourceCollection } from './resource-mappers';

const RESOURCES_ENDPOINT = '/wp-json/hse/v1/resources';

/** Returns all published Free Resources for one language. */
export async function getResources(locale: Locale = defaultLocale): Promise<ResourceCollection> {
	return mapWordPressResourceCollection(
		await fetchCmsJson(RESOURCES_ENDPOINT, { lang: locale }),
		locale,
	);
}
