import { describe, expect, it } from 'vitest';
import {
	allowsExternalMedia,
	COOKIE_CONSENT_MAX_AGE_SECONDS,
	createCookieConsentValue,
	readCookieConsent,
} from './cookie-consent';

describe('cookie consent', () => {
	it('reads only supported versioned consent values', () => {
		expect(readCookieConsent('foo=bar; hse_cookie_consent=v1.necessary')).toBe('necessary');
		expect(readCookieConsent('hse_cookie_consent=v1.external-media')).toBe('external-media');
		expect(readCookieConsent('hse_cookie_consent=v0.external-media')).toBeNull();
		expect(readCookieConsent('')).toBeNull();
	});

	it('allows external media only after explicit consent', () => {
		expect(allowsExternalMedia('hse_cookie_consent=v1.external-media')).toBe(true);
		expect(allowsExternalMedia('hse_cookie_consent=v1.necessary')).toBe(false);
	});

	it('creates a six-month, first-party consent cookie', () => {
		const cookie = createCookieConsentValue('external-media', true);

		expect(cookie).toContain('hse_cookie_consent=v1.external-media');
		expect(cookie).toContain(`Max-Age=${COOKIE_CONSENT_MAX_AGE_SECONDS}`);
		expect(cookie).toContain('SameSite=Lax');
		expect(cookie).toContain('Secure');
	});
});
