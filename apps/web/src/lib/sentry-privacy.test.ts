import { describe, expect, it } from 'vitest';
import type { Event } from '@sentry/astro';
import { sanitizeSentryEvent, sanitizeSentryUrl } from './sentry-privacy';

describe('Sentry privacy sanitization', () => {
	it('removes query strings and fragments from absolute and relative URLs', () => {
		expect(sanitizeSentryUrl('https://dev.hsetraining.rs/contact/?email=test@example.com#form')).toBe(
			'https://dev.hsetraining.rs/contact/',
		);
		expect(sanitizeSentryUrl('/contact/?course=nebosh-igc#form')).toBe('/contact/');
	});

	it('removes identity, request payloads, cookies and headers', () => {
		const event = sanitizeSentryEvent({
			user: { email: 'customer@example.com' },
			request: {
				url: 'https://dev.hsetraining.rs/contact/?email=customer@example.com',
				cookies: { session: 'private' },
				data: { message: 'private enquiry' },
				headers: { authorization: 'private' },
				query_string: 'email=customer@example.com',
			},
			breadcrumbs: [{ data: { url: '/contact/?email=customer@example.com', method: 'POST' } }],
		} as Event);

		expect(event.user).toBeUndefined();
		expect(event.request).toEqual({ url: 'https://dev.hsetraining.rs/contact/' });
		expect(event.breadcrumbs?.[0]?.data).toEqual({ url: '/contact/', method: 'POST' });
	});
});
