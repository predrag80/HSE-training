import type { ServiceCollection } from '../../types/service';
import { fetchCmsJson } from './client';
import { mapWordPressServiceCollection } from './service-mappers';

const SERVICES_ENDPOINT = '/wp-json/hse/v1/services';

/** Returns all published consulting Services in editorial order. */
export async function getServices(): Promise<ServiceCollection> {
	return mapWordPressServiceCollection(await fetchCmsJson(SERVICES_ENDPOINT));
}
