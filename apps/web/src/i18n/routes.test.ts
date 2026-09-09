import { describe, expect, it } from 'vitest';
import {
	getDefaultLocalePath,
	getAvailablePublicPath,
	getLanguageAlternates,
	getLocaleFromPathname,
	getLocalizedPath,
	getLocalizedCmsHref,
} from './routes';

describe('locale routes', () => {
	it('keeps English on the existing unprefixed URLs', () => {
		expect(getLocalizedPath('/company/', 'en')).toBe('/company/');
		expect(getLocalizedPath('/sr/company/', 'en')).toBe('/company/');
		expect(getDefaultLocalePath('/sr')).toBe('/');
	});

	it('prefixes Serbian routes without duplicating the prefix', () => {
		expect(getLocalizedPath('/', 'sr')).toBe('/sr/');
		expect(getLocalizedPath('/company', 'sr')).toBe('/sr/company/');
		expect(getLocalizedPath('/sr/company/', 'sr')).toBe('/sr/company/');
	});

	it('detects only a complete Serbian path segment', () => {
		expect(getLocaleFromPathname('/sr/consulting/')).toBe('sr');
		expect(getLocaleFromPathname('/sr')).toBe('sr');
		expect(getLocaleFromPathname('/services/')).toBe('en');
	});

	it('builds both alternates for a route with reviewed localized content', () => {
		expect(getLanguageAlternates('/sr/')).toEqual({
			en: '/',
			sr: '/sr/',
		});
	});

	it('uses the Serbian Company route after its content slice is available', () => {
		expect(getAvailablePublicPath('/company/', 'sr')).toBe('/sr/company/');
		expect(getAvailablePublicPath('/', 'sr')).toBe('/sr/');
		expect(getLanguageAlternates('/company/')).toEqual({
			en: '/company/',
			sr: '/sr/company/',
		});
	});

	it('exposes the completed Serbian Course routes', () => {
		expect(getAvailablePublicPath('/courses/', 'sr')).toBe('/sr/courses/');
		expect(getAvailablePublicPath('/courses/iosh-kurs/', 'sr')).toBe('/sr/courses/iosh-kurs/');
		expect(getAvailablePublicPath('/nebosh/', 'sr')).toBe('/sr/nebosh/');
		expect(getLanguageAlternates('/nebosh-international-oilgas-certificate/')).toEqual({
			en: '/nebosh-international-oilgas-certificate/',
			sr: '/sr/nebosh-international-oilgas-certificate/',
		});
	});

	it('exposes all completed Serbian marketing routes', () => {
		for (const path of ['/company/', '/consulting/', '/contact/', '/gallery/', '/other-courses/', '/banksman-slinger/', '/train-the-trainer/']) {
			expect(getAvailablePublicPath(path, 'sr')).toBe(`/sr${path}`);
			expect(getLanguageAlternates(path)).toEqual({ en: path, sr: `/sr${path}` });
		}
	});

	it('localizes CMS-authored internal links without changing external links', () => {
		expect(getLocalizedCmsHref('/company/#about', 'sr')).toBe('/sr/company/#about');
		expect(getLocalizedCmsHref('/contact/?course=nebosh#form', 'sr')).toBe('/sr/contact/?course=nebosh#form');
		expect(getLocalizedCmsHref('https://example.com/', 'sr')).toBe('https://example.com/');
	});
});
