import { afterEach, describe, expect, it, vi } from 'vitest';

import { fetchCmsJson } from './client';

afterEach(() => vi.unstubAllGlobals());

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
});
