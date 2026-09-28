import { HSE_CHECKOUT_SOURCE, WORDPRESS_API_URL } from 'astro:env/server';

export const serverConfig = {
	wordpressApiUrl: WORDPRESS_API_URL,
	checkoutSource: HSE_CHECKOUT_SOURCE === 'dev' ? 'dev' : 'staging',
} as const;
