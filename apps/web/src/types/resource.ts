import type { Locale } from '../i18n/config';

export type ResourceType = 'link' | 'video' | 'document' | 'procedure' | 'standard';

export interface ResourceImage {
	readonly url: string;
	readonly alt: string;
	readonly width: number;
	readonly height: number;
}

export interface Resource {
	readonly resourceKey: string;
	readonly title: string;
	readonly summary: string;
	readonly description: string;
	readonly topic: string;
	readonly type: ResourceType;
	readonly featured: boolean;
	readonly actionUrl: string;
	readonly isDownload: boolean;
	readonly fileName: string;
	readonly fileSize: number;
	readonly mimeType: string;
	readonly image: ResourceImage | null;
}

export interface ResourceCollection {
	readonly schemaVersion: 1;
	readonly collectionKey: 'resources';
	readonly locale: Locale;
	readonly resources: readonly Resource[];
}
