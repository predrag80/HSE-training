// @ts-check
import { defineConfig, envField } from 'astro/config';

// https://astro.build/config
export default defineConfig({
	i18n: {
		locales: ['en', 'sr'],
		defaultLocale: 'en',
		routing: {
			prefixDefaultLocale: false,
		},
	},
	devToolbar: {
		enabled: false,
	},
	env: {
		schema: {
			WORDPRESS_API_URL: envField.string({
				context: 'server',
				access: 'public',
				url: true,
			}),
		},
	},
});
