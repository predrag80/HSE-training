import { serverConfig } from '../config';
import { CmsError } from './errors';

const CMS_TIMEOUT_MS = 20_000;
const CMS_MAX_ATTEMPTS = 3;
const CMS_RETRY_DELAY_MS = 500;
const CMS_USER_AGENT = 'Mozilla/5.0 (compatible; HSETrainingBuild/1.0; +https://hsetraining.rs)';

function waitBeforeRetry(attempt: number): Promise<void> {
	return new Promise((resolve) => setTimeout(resolve, CMS_RETRY_DELAY_MS * attempt));
}

function isRetryableStatus(status: number): boolean {
	return status === 408 || status === 429 || status >= 500;
}

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
	for (let attempt = 1; attempt <= CMS_MAX_ATTEMPTS; attempt += 1) {
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
			if (attempt < CMS_MAX_ATTEMPTS) {
				await waitBeforeRetry(attempt);
				continue;
			}

			const isTimeout =
				typeof cause === 'object' && cause !== null && 'name' in cause && cause.name === 'TimeoutError';
			throw new CmsError('unavailable', isTimeout ? 'CMS request timed out.' : 'CMS is unavailable.', {
				cause,
			});
		}

		if (!response.ok) {
			if (attempt < CMS_MAX_ATTEMPTS && isRetryableStatus(response.status)) {
				await waitBeforeRetry(attempt);
				continue;
			}

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

	throw new CmsError('unavailable', 'CMS is unavailable.');
}
