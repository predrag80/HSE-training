export const COOKIE_CONSENT_NAME = 'hse_cookie_consent';
export const COOKIE_CONSENT_MAX_AGE_SECONDS = 60 * 60 * 24 * 180;

export type CookieConsentLevel = 'necessary' | 'external-media';

const COOKIE_VALUES: Readonly<Record<CookieConsentLevel, string>> = {
	necessary: 'v1.necessary',
	'external-media': 'v1.external-media',
};

export function readCookieConsent(cookieHeader: string): CookieConsentLevel | null {
	const encodedValue = cookieHeader
		.split(';')
		.map((part) => part.trim())
		.find((part) => part.startsWith(`${COOKIE_CONSENT_NAME}=`))
		?.slice(COOKIE_CONSENT_NAME.length + 1);

	if (!encodedValue) return null;

	let value: string;
	try {
		value = decodeURIComponent(encodedValue);
	} catch {
		return null;
	}

	if (value === COOKIE_VALUES.necessary) return 'necessary';
	if (value === COOKIE_VALUES['external-media']) return 'external-media';
	return null;
}

export function createCookieConsentValue(level: CookieConsentLevel, secure: boolean): string {
	return [
		`${COOKIE_CONSENT_NAME}=${encodeURIComponent(COOKIE_VALUES[level])}`,
		'Path=/',
		`Max-Age=${COOKIE_CONSENT_MAX_AGE_SECONDS}`,
		'SameSite=Lax',
		...(secure ? ['Secure'] : []),
	].join('; ');
}

export function allowsExternalMedia(cookieHeader: string): boolean {
	return readCookieConsent(cookieHeader) === 'external-media';
}
