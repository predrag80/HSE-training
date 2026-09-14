import type { Locale } from '../i18n/config';

export type LegalPageKey = 'privacy' | 'terms' | 'copyright';

export interface LegalPageSection {
	readonly title: string;
	readonly bodyHtml: string;
}

export interface LegalPageContent {
	readonly schemaVersion: 1;
	readonly pageKey: LegalPageKey;
	readonly locale: Locale;
	readonly meta: {
		readonly title: string;
		readonly description: string;
	};
	readonly eyebrow: string;
	readonly title: string;
	readonly intro: string;
	readonly lastUpdated: string;
	readonly sections: readonly LegalPageSection[];
}
