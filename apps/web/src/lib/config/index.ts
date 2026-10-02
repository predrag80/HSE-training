import { HSE_CHECKOUT_SOURCE, WORDPRESS_API_URL } from 'astro:env/server';
import { normalizeCheckoutSource } from './checkout-source';

export const serverConfig = {
	wordpressApiUrl: WORDPRESS_API_URL,
	checkoutSource: normalizeCheckoutSource(HSE_CHECKOUT_SOURCE),
} as const;
