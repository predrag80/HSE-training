import type { Locale } from '../i18n/config';

export type CompanyValueIcon = 'training' | 'management' | 'consultancy';

export interface CompanyImage {
	readonly url: string;
	readonly alt: string;
	readonly width: number;
	readonly height: number;
}

export interface CompanyLink {
	readonly label: string;
	readonly url: string;
}

export interface CompanyValue {
	readonly valueKey: string;
	readonly iconKey: CompanyValueIcon;
	readonly title: string;
	readonly description: string;
}

export interface CompanyProfile {
	readonly eyebrow: string;
	readonly headline: string;
	readonly description: string;
	readonly primaryImage: CompanyImage;
	readonly secondaryImage: CompanyImage;
	readonly primaryCta: CompanyLink;
	readonly secondaryCta: CompanyLink;
	readonly signatureLabel: string;
	readonly values: readonly CompanyValue[];
}

export interface CompanyPageContent {
	readonly schemaVersion: 1;
	readonly pageKey: 'company';
	readonly locale: Locale;
	readonly hero: {
		readonly eyebrow: string;
		readonly title: string;
	};
	readonly profile: CompanyProfile;
	readonly introCta: CompanyLink;
}
