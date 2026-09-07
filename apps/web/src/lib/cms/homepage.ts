import type { Homepage } from '../../types/homepage';
import { fetchCmsJson } from './client';
import { mapWordPressHomepage } from './homepage-mappers';

const HOMEPAGE_ENDPOINT = '/wp-json/hse/v1/homepage';

/** Returns the published Homepage content used during Astro's static build. */
export async function getHomepage(): Promise<Homepage> {
	return mapWordPressHomepage(await fetchCmsJson(HOMEPAGE_ENDPOINT));
}
