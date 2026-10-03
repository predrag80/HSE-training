// @ts-check
import { defineConfig, envField } from 'astro/config';
import sentry from '@sentry/astro';

const runtime = /** @type {{ process?: { env?: Record<string, string | undefined> } }} */ (globalThis);
const processEnvironment = runtime.process?.env ?? {};
const isProductionSentryBuild = processEnvironment.PUBLIC_SENTRY_ENVIRONMENT === 'production'
	&& Boolean(processEnvironment.PUBLIC_SENTRY_DSN?.trim());

// https://astro.build/config
export default defineConfig({
	integrations: isProductionSentryBuild ? [
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
	] : [],
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
