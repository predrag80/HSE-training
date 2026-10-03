import * as Sentry from '@sentry/astro';
import { sanitizeSentryEvent } from './src/lib/sentry-privacy';
import { shouldEnableContactSentry } from './src/lib/sentry-scope';

const dsn = import.meta.env.PUBLIC_SENTRY_DSN?.trim();
const environment = import.meta.env.PUBLIC_SENTRY_ENVIRONMENT?.trim() || 'local';
const pathname = typeof window === 'undefined' ? '' : window.location.pathname;
const enabled = shouldEnableContactSentry(dsn, environment, pathname);

Sentry.init({
	dsn,
	enabled,
	environment,
	dataCollection: {
		userInfo: false,
		cookies: false,
		httpHeaders: false,
		httpBodies: [],
		urlQueryParams: false,
		stackFrameVariables: false,
	},
	integrations: enabled ? [Sentry.browserTracingIntegration()] : [],
	tracesSampleRate: enabled ? 1 : 0,
	traceLifecycle: 'static',
	beforeSend: sanitizeSentryEvent,
	beforeSendTransaction: sanitizeSentryEvent,
});
