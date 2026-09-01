import { WORDPRESS_API_URL } from 'astro:env/server';

export const serverConfig = {
	wordpressApiUrl: WORDPRESS_API_URL,
} as const;
