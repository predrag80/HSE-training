export const locales = ['en', 'sr'] as const;

export type Locale = (typeof locales)[number];

export const defaultLocale: Locale = 'en';

export const localeMetadata = {
	en: {
		label: 'English',
		shortLabel: 'EN',
		htmlLang: 'en',
	},
	sr: {
		label: 'Srpski',
		shortLabel: 'SR',
		htmlLang: 'sr-Latn',
	},
} as const satisfies Record<Locale, {
	readonly label: string;
	readonly shortLabel: string;
	readonly htmlLang: string;
}>;

export function isLocale(value: string | undefined): value is Locale {
	return locales.some((locale) => locale === value);
}
