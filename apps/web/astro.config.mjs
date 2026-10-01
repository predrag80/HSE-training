// @ts-check
import { defineConfig, envField } from 'astro/config';
import sentry from '@sentry/astro';

// https://astro.build/config
export default defineConfig({
	integrations: [
		sentry({
			enabled: { client: true, server: false },
			sourcemaps: { disable: true },
			telemetry: false,
			bundleSizeOptimizations: {
				excludeReplayIframe: true,
				excludeReplayShadowDom: true,
				excludeReplayWorker: true,
			},
		}),
	],
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
			HSE_CHECKOUT_SOURCE: envField.string({
				context: 'server',
				access: 'public',
				default: 'staging',
			}),
		},
	},
});
