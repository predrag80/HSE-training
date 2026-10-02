export type CheckoutSource = 'dev' | 'staging' | 'production';

/** Keep the shared WooCommerce checkout source on a small explicit allowlist. */
export function normalizeCheckoutSource(value: string | undefined): CheckoutSource {
	return value === 'dev' || value === 'production' ? value : 'staging';
}
