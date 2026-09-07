export interface ServiceImage {
	readonly url: string;
	readonly alt: string;
	readonly width: number;
	readonly height: number;
}

export interface ServiceLink {
	readonly label: string;
	readonly url: string;
}

export interface Service {
	readonly serviceKey: string;
	readonly title: string;
	readonly cardLabel: string;
	readonly shortDescription: string;
	readonly detailedDescription: string;
	readonly cta: ServiceLink;
	readonly image: ServiceImage;
	readonly featuredOnHomepage: boolean;
}

export interface ServiceCollection {
	readonly schemaVersion: 1;
	readonly collectionKey: 'services';
	readonly services: readonly Service[];
}
