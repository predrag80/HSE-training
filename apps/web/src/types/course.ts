export interface Course {
	readonly slug: string;
	readonly courseKey: string;
	readonly title: string;
	readonly shortDescription: string;
	readonly descriptionHtml: string;
	readonly featuredImageUrl: string | null;
	readonly visiblePrice: string | null;
	readonly status: 'publish';
}
