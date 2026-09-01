interface WordPressRenderedField {
	readonly rendered: string;
}

interface WordPressContentField extends WordPressRenderedField {
	readonly protected: false;
}

interface WordPressCourseMetaDto {
	readonly course_key: string;
	readonly short_description: string;
	readonly visible_price: string;
}

interface WordPressFeaturedMediaDto {
	readonly source_url: string;
}

export interface WordPressCourseDto {
	readonly slug: string;
	readonly status: 'publish';
	readonly title: WordPressRenderedField;
	readonly content: WordPressContentField;
	readonly featured_media: number;
	readonly meta: WordPressCourseMetaDto;
	readonly _embedded?: {
		readonly 'wp:featuredmedia'?: readonly WordPressFeaturedMediaDto[];
	};
}
