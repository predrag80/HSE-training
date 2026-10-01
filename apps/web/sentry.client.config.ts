import * as Sentry from '@sentry/astro';
import { sanitizeSentryEvent } from './src/lib/sentry-privacy';

const dsn = import.meta.env.PUBLIC_SENTRY_DSN?.trim();
const environment = import.meta.env.PUBLIC_SENTRY_ENVIRONMENT?.trim() || 'local';

Sentry.init({
	dsn,
	enabled: Boolean(dsn),
	environment,
	dataCollection: {
		userInfo: false,
		cookies: false,
		httpHeaders: false,
		httpBodies: [],
		urlQueryParams: false,
		stackFrameVariables: false,
	},
	integrations: [Sentry.browserTracingIntegration()],
	tracesSampler: ({ name }) => name.includes('/contact') ? 1 : 0.1,
	beforeSend: sanitizeSentryEvent,
});
