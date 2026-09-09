import type { Locale } from '../i18n/config';
import type { CompanyProfile } from './company';
import type { Service } from './service';

export interface HomepageHeroImage {
	readonly url: string;
	readonly alt: string;
	readonly width: number;
	readonly height: number;
}

export interface HomepageHeroSlide {
	readonly slideKey: string;
	readonly image: HomepageHeroImage;
	readonly leadingTitle: string;
	readonly emphasizedTitle: string;
	readonly primaryCtaLabel: string;
	readonly primaryCtaUrl: string;
	readonly messagePrefix: string;
	readonly messageLinkLabel: string;
	readonly messageLinkUrl: string;
}

export interface HomepageHero {
	readonly ariaLabel: string;
	readonly heading: string;
	readonly slides: readonly HomepageHeroSlide[];
}

export interface Homepage {
	readonly schemaVersion: 1;
	readonly pageKey: 'home';
	readonly locale: Locale;
	readonly hero: HomepageHero;
	readonly about: CompanyProfile;
	readonly featuredServices: readonly Service[];
}
