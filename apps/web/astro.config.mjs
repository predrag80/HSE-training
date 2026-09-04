// @ts-check
import { defineConfig, envField } from 'astro/config';

// https://astro.build/config
export default defineConfig({
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
