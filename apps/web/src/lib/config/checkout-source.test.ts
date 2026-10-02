import { describe, expect, it } from 'vitest';
import { normalizeCheckoutSource } from './checkout-source';

describe('normalizeCheckoutSource', () => {
	it('accepts each deployed public environment', () => {
		expect(normalizeCheckoutSource('dev')).toBe('dev');
		expect(normalizeCheckoutSource('staging')).toBe('staging');
		expect(normalizeCheckoutSource('production')).toBe('production');
	});

	it('falls back safely for missing or unknown values', () => {
		expect(normalizeCheckoutSource(undefined)).toBe('staging');
		expect(normalizeCheckoutSource('preview')).toBe('staging');
	});
});
