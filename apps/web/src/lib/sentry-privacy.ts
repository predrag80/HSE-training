import type { Event } from '@sentry/astro';

const URL_FIELDS = ['url', 'from', 'to'] as const;

export function sanitizeSentryUrl(value: unknown): unknown {
	if (typeof value !== 'string' || value === '') return value;

	try {
		const url = new URL(value, 'https://hsetraining.invalid');
		return url.origin === 'https://hsetraining.invalid' ? url.pathname : `${url.origin}${url.pathname}`;
	} catch {
		return value.split(/[?#]/, 1)[0];
	}
}

export function sanitizeSentryEvent<T extends Event>(event: T): T {
	delete event.user;

	if (event.request) {
		event.request.url = sanitizeSentryUrl(event.request.url) as string | undefined;
		delete event.request.cookies;
		delete event.request.data;
		delete event.request.headers;
		delete event.request.query_string;
	}

	event.breadcrumbs = event.breadcrumbs?.map((breadcrumb) => {
		if (!breadcrumb.data) return breadcrumb;
		const data = { ...breadcrumb.data };
		for (const field of URL_FIELDS) {
			if (field in data) data[field] = sanitizeSentryUrl(data[field]);
		}
		return { ...breadcrumb, data };
	});

	return event;
}
