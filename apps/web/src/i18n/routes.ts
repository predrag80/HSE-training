import { defaultLocale, type Locale } from './config';

const localizedContentPaths = new Set([
	'/',
	'/courses/',
	'/nebosh/',
	'/nebosh-international-general-certificate-in-occupational-health-and-safety/',
	'/nebosh-international-oilgas-certificate/',
	'/nebosh-award-environmental-awareness-at-work/',
	'/company/',
	'/browse-hse-talent/',
	'/contact/',
	'/gallery/',
	'/other-courses/',
	'/banksman-slinger/',
	'/train-the-trainer/',
	'/training/',
	'/training/custom-training-design/',
	'/training/banksman-slinger/',
	'/training/train-the-trainer/',
]);
const localizedContentPrefixes = ['/courses/'];

function normalizePathname(pathname: string): string {
	const path = pathname.startsWith('/') ? pathname : `/${pathname}`;
	if (path === '/') return path;

	return `${path.replace(/^\/sr(?:\/|$)/, '/').replace(/\/+$/, '')}/`;
}

export function getLocaleFromPathname(pathname: string): Locale {
	return pathname === '/sr' || pathname.startsWith('/sr/') ? 'sr' : defaultLocale;
}

export function getDefaultLocalePath(pathname: string): string {
	return normalizePathname(pathname);
}

export function getLocalizedPath(pathname: string, locale: Locale): string {
	const defaultPath = getDefaultLocalePath(pathname);
	if (locale === defaultLocale) return defaultPath;

	return defaultPath === '/' ? '/sr/' : `/sr${defaultPath}`;
}

export function hasLocalizedContentPath(pathname: string): boolean {
	const defaultPath = getDefaultLocalePath(pathname);

	return (
		localizedContentPaths.has(defaultPath) ||
		localizedContentPrefixes.some((prefix) => defaultPath.startsWith(prefix))
	);
}

/**
 * Returns only routes that exist in the current incremental implementation.
 * Remove the fallback route-by-route as reviewed Serbian content is added.
 */
export function getAvailablePublicPath(pathname: string, locale: Locale): string {
	if (locale === 'sr' && !hasLocalizedContentPath(pathname)) {
		return getDefaultLocalePath(pathname);
	}

	return getLocalizedPath(pathname, locale);
}

/** Localize a CMS-authored same-site path while preserving query and fragment data. */
export function getLocalizedCmsHref(href: string, locale: Locale): string {
	if (!href.startsWith('/') || href.startsWith('//')) return href;

	const url = new URL(href, 'https://hsetraining.local');
	return `${getAvailablePublicPath(url.pathname, locale)}${url.search}${url.hash}`;
}

export function getLanguageAlternates(pathname: string): Record<Locale, string> {
	const hasSerbianEquivalent = hasLocalizedContentPath(pathname);

	return {
		en: getLocalizedPath(pathname, 'en'),
		sr: hasSerbianEquivalent ? getLocalizedPath(pathname, 'sr') : '/sr/',
	};
}
