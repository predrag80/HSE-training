import { serverConfig } from '../config';
import { CmsError } from './errors';

const CMS_TIMEOUT_MS = 10_000;
const CMS_USER_AGENT = 'Mozilla/5.0 (compatible; HSETrainingBuild/1.0; +https://hsetraining.rs)';

function createCmsUrl(path: string, query: Readonly<Record<string, string>>): URL {
	let cmsBaseUrl: URL;

	try {
		cmsBaseUrl = new URL(serverConfig.wordpressApiUrl);
	} catch (cause) {
		throw new CmsError('configuration', 'WORDPRESS_API_URL must be a valid URL.', { cause });
	}

	if (cmsBaseUrl.protocol !== 'http:' && cmsBaseUrl.protocol !== 'https:') {
		throw new CmsError('configuration', 'WORDPRESS_API_URL must use HTTP or HTTPS.');
	}

	const url = new URL(path, cmsBaseUrl);
	for (const [name, value] of Object.entries(query)) url.searchParams.set(name, value);

	return url;
}

export async function fetchCmsJson(
	path: string,
	query: Readonly<Record<string, string>> = {},
): Promise<unknown> {
	const url = createCmsUrl(path, query);
	let response: Response;

	try {
		response = await fetch(url, {
			headers: {
				Accept: 'application/json',
				'User-Agent': CMS_USER_AGENT,
			},
			signal: AbortSignal.timeout(CMS_TIMEOUT_MS),
		});
	} catch (cause) {
		const isTimeout =
			typeof cause === 'object' && cause !== null && 'name' in cause && cause.name === 'TimeoutError';
		throw new CmsError('unavailable', isTimeout ? 'CMS request timed out.' : 'CMS is unavailable.', {
			cause,
		});
	}

	if (!response.ok) {
		throw new CmsError('http', `CMS request failed with HTTP ${response.status}.`, {
			status: response.status,
		});
	}

	try {
		return await response.json();
	} catch (cause) {
		throw new CmsError('invalid-response', 'CMS returned invalid JSON.', { cause });
	}
}
