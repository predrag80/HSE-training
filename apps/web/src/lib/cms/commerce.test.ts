import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('../config', () => ({ serverConfig: { wordpressApiUrl: 'https://cms.example.test' } }));

import { formatCommercePrice, getOptionalCommerceProduct } from './commerce';

afterEach(() => vi.unstubAllGlobals());

describe('getOptionalCommerceProduct', () => {
	it('maps a valid product by stable course key', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({
			schema_version: 1,
			course_key: 'nebosh-igc',
			name: 'NEBOSH International General Certificate',
			price_minor: 100000,
			currency: 'RSD',
			currency_decimals: 2,
			purchasable: true,
			checkout_url: 'https://cms.example.test/?hse_course_checkout=nebosh-igc',
		}), { status: 200 })));

		await expect(getOptionalCommerceProduct('nebosh-igc')).resolves.toMatchObject({
			courseKey: 'nebosh-igc',
			priceMinor: 100000,
			purchasable: true,
		});
	});

	it('returns null when the staging bridge is disabled', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 404 })));
		await expect(getOptionalCommerceProduct('nebosh-igc')).resolves.toBeNull();
	});

	it('rejects a mismatched course key', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({
			schema_version: 1,
			course_key: 'nebosh-eaw',
			name: 'Wrong product',
			price_minor: 100000,
			currency: 'RSD',
			currency_decimals: 2,
			purchasable: true,
			checkout_url: 'https://cms.example.test/',
		}), { status: 200 })));

		await expect(getOptionalCommerceProduct('nebosh-igc')).rejects.toMatchObject({
			code: 'invalid-response',
		});
	});
});

describe('formatCommercePrice', () => {
	it('formats integer minor units for the requested locale', () => {
		const product = {
			schemaVersion: 1 as const,
			courseKey: 'nebosh-igc',
			name: 'NEBOSH IGC',
			priceMinor: 100000,
			currency: 'RSD',
			currencyDecimals: 2,
			purchasable: true,
			checkoutUrl: 'https://cms.example.test/',
		};
		expect(formatCommercePrice(product, 'en')).toContain('1,000.00');
		expect(formatCommercePrice(product, 'sr')).toContain('1.000,00');
	});
});
