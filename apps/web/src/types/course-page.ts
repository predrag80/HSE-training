import type { Locale } from '../i18n/config';

export interface CoursePageMeta {
	readonly title: string;
	readonly description: string;
}

export interface CoursesLandingPageContent {
	readonly schemaVersion: 1;
	readonly pageKey: 'courses';
	readonly locale: Locale;
	readonly meta: CoursePageMeta;
	readonly hero: {
		readonly eyebrow: string;
		readonly title: string;
		readonly intro: string;
	};
	readonly emptyState: {
		readonly title: string;
		readonly text: string;
	};
}

export interface NeboshOverviewPageContent {
	readonly schemaVersion: 1;
	readonly pageKey: 'nebosh';
	readonly locale: Locale;
	readonly meta: CoursePageMeta;
	readonly hero: {
		readonly eyebrow: string;
		readonly title: string;
	};
	readonly strengths: readonly {
		readonly title: string;
		readonly text: string;
	}[];
	readonly intro: {
		readonly kicker: string;
		readonly title: string;
		readonly paragraphs: readonly string[];
		readonly ctaLabel: string;
		readonly imageAlt: string;
		readonly visualTitle: string;
		readonly visualText: string;
	};
	readonly courseSelection: {
		readonly kicker: string;
		readonly title: string;
		readonly currentLabel: string;
		readonly legacyLabel: string;
		readonly igcFallback: string;
		readonly iogcFallback: string;
		readonly igcCtaLabel: string;
		readonly iogcCtaLabel: string;
	};
	readonly testimonial: {
		readonly quote: string;
		readonly author: string;
		readonly role: string;
	};
}
