import type { Locale } from '../i18n/config';

export interface Course {
	readonly locale: Locale;
	readonly slug: string;
	readonly courseKey: string;
	readonly title: string;
	readonly shortDescription: string;
	readonly descriptionHtml: string;
	readonly featuredImageUrl: string | null;
	readonly visiblePrice: string | null;
	readonly homepageLabel: string;
	readonly homepageCtaLabel: string;
	readonly featuredOnHomepage: boolean;
	readonly status: 'publish';
}
