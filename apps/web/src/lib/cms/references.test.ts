import { afterEach, describe, expect, it, vi } from 'vitest';

import { getHomepageReferences, getReferences } from './references';

const reference = {
	reference_key: 'saule-kuza',
	quote: 'The trainer explained the qualification clearly.',
	author_name: 'Saule Kuza',
	role: 'Technical translator at KPO',
	featured_on_homepage: true,
	accent_on_homepage: false,
};

function response(references: readonly unknown[]): Response {
	return new Response(
		JSON.stringify({ schema_version: 1, collection_key: 'references', references }),
		{ status: 200, headers: { 'Content-Type': 'application/json' } },
	);
}

afterEach(() => vi.unstubAllGlobals());

describe('getReferences', () => {
	it('requests the public HSE References endpoint', async () => {
		const fetchMock = vi.fn().mockResolvedValue(response([reference]));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getReferences()).resolves.toMatchObject({
			collectionKey: 'references',
			references: [expect.objectContaining({ referenceKey: 'saule-kuza' })],
		});
		expect(new URL(String(fetchMock.mock.calls[0]?.[0])).pathname).toBe(
			'/wp-json/hse/v1/references',
		);
	});
});

describe('getHomepageReferences', () => {
	it('returns only References selected for the Homepage', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn().mockResolvedValue(
				response([reference, { ...reference, reference_key: 'not-featured', featured_on_homepage: false }]),
			),
		);

		await expect(getHomepageReferences()).resolves.toEqual([
			expect.objectContaining({ referenceKey: 'saule-kuza' }),
		]);
	});

	it('rejects more than four Homepage References', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn().mockResolvedValue(
				response(
					Array.from({ length: 5 }, (_, index) => ({
						...reference,
						reference_key: `reference-${index + 1}`,
					})),
				),
			),
		);

		await expect(getHomepageReferences()).rejects.toThrow('between 1 and 4');
	});
});
