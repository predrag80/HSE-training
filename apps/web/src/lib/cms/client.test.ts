import { afterEach, describe, expect, it, vi } from 'vitest';

import { fetchCmsJson } from './client';

afterEach(() => {
	vi.unstubAllGlobals();
	vi.unstubAllEnvs();
});

function jsonResponse(body: unknown, status = 200): Response {
	return new Response(JSON.stringify(body), { status });
}

describe('CMS HTTP client', () => {
	it('retries a transient server response', async () => {
		const fetchMock = vi.fn()
			.mockResolvedValueOnce(new Response('', { status: 503 }))
			.mockResolvedValueOnce(new Response(JSON.stringify({ available: true }), { status: 200 }));
		vi.stubGlobal('fetch', fetchMock);

		await expect(fetchCmsJson('/wp-json/hse/v1/test')).resolves.toEqual({ available: true });
		expect(fetchMock).toHaveBeenCalledTimes(2);
	});

	it('does not retry a permanent client error', async () => {
		const fetchMock = vi.fn().mockResolvedValue(new Response('', { status: 404 }));
		vi.stubGlobal('fetch', fetchMock);

		await expect(fetchCmsJson('/wp-json/hse/v1/missing')).rejects.toThrow(
			'CMS request failed with HTTP 404.',
		);
		expect(fetchMock).toHaveBeenCalledOnce();
	});

	it('shares one request per URL in a production build and returns independent copies', async () => {
		vi.stubEnv('PROD', true);
		const fetchMock = vi.fn().mockImplementation(async () => jsonResponse({ items: ['course'] }));
		vi.stubGlobal('fetch', fetchMock);

		const [first, second] = await Promise.all([
			fetchCmsJson('/wp-json/hse/v1/shared', { lang: 'en' }),
			fetchCmsJson('/wp-json/hse/v1/shared', { lang: 'en' }),
		]);
		(first as { items: string[] }).items.push('changed by one page');

		await expect(fetchCmsJson('/wp-json/hse/v1/shared', { lang: 'en' })).resolves.toEqual({ items: ['course'] });
		expect(second).toEqual({ items: ['course'] });
		expect(fetchMock).toHaveBeenCalledOnce();

		await fetchCmsJson('/wp-json/hse/v1/shared', { lang: 'sr' });
		expect(fetchMock).toHaveBeenCalledTimes(2);
	});

	it('requests a failed URL again in a production build', async () => {
		vi.stubEnv('PROD', true);
		const fetchMock = vi.fn()
			.mockResolvedValueOnce(new Response('', { status: 404 }))
			.mockResolvedValueOnce(jsonResponse({ available: true }));
		vi.stubGlobal('fetch', fetchMock);

		await expect(fetchCmsJson('/wp-json/hse/v1/flaky')).rejects.toThrow('CMS request failed with HTTP 404.');
		await expect(fetchCmsJson('/wp-json/hse/v1/flaky')).resolves.toEqual({ available: true });
		expect(fetchMock).toHaveBeenCalledTimes(2);
	});

	it('always requests fresh content outside a production build', async () => {
		const fetchMock = vi.fn().mockImplementation(async () => jsonResponse({ available: true }));
		vi.stubGlobal('fetch', fetchMock);

		await fetchCmsJson('/wp-json/hse/v1/dev');
		await fetchCmsJson('/wp-json/hse/v1/dev');
		expect(fetchMock).toHaveBeenCalledTimes(2);
	});
});
