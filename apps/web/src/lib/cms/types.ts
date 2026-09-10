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
	readonly homepage_label: string;
	readonly homepage_cta_label: string;
	readonly featured_on_homepage: boolean;
}

interface WordPressFeaturedMediaDto {
	readonly source_url: string;
}

export interface WordPressCourseDto {
	readonly locale: 'en' | 'sr';
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

interface WordPressHomepageImageDto {
	readonly url: string;
	readonly alt: string;
	readonly width: number;
	readonly height: number;
}

interface WordPressHomepageHeroSlideDto {
	readonly slide_key: string;
	readonly image: WordPressHomepageImageDto;
	readonly leading_title: string;
	readonly emphasized_title: string;
	readonly primary_cta_label: string;
	readonly primary_cta_url: string;
	readonly message_prefix: string;
	readonly message_link_label: string;
	readonly message_link_url: string;
}

interface WordPressContentImageDto {
	readonly url: string;
	readonly alt: string;
	readonly width: number;
	readonly height: number;
}

interface WordPressContentLinkDto {
	readonly label: string;
	readonly url: string;
}

export interface WordPressServiceDto {
	readonly service_key: string;
	readonly title: string;
	readonly card_label: string;
	readonly short_description: string;
	readonly detailed_description: string;
	readonly cta: WordPressContentLinkDto;
	readonly image: WordPressContentImageDto;
	readonly featured_on_homepage: boolean;
}

export interface WordPressServiceCollectionDto {
	readonly schema_version: 1;
	readonly collection_key: 'services';
	readonly locale: 'en' | 'sr';
	readonly services: readonly WordPressServiceDto[];
}

export interface WordPressCompanyProfileDto {
	readonly eyebrow: string;
	readonly headline: string;
	readonly description: string;
	readonly primary_image: WordPressContentImageDto;
	readonly secondary_image: WordPressContentImageDto;
	readonly primary_cta: WordPressContentLinkDto;
	readonly secondary_cta: WordPressContentLinkDto;
	readonly signature_label: string;
	readonly values: readonly {
		readonly value_key: string;
		readonly icon_key: 'training' | 'management' | 'consultancy';
		readonly title: string;
		readonly description: string;
	}[];
	readonly team?: readonly {
		readonly member_key: string;
		readonly name: string;
		readonly role: string;
		readonly image: WordPressContentImageDto | null;
	}[];
}

export interface WordPressHomepageDto {
	readonly schema_version: 1;
	readonly page_key: 'home';
	readonly locale: 'en' | 'sr';
	readonly hero: {
		readonly aria_label: string;
		readonly heading: string;
		readonly slides: readonly WordPressHomepageHeroSlideDto[];
	};
	readonly about: WordPressCompanyProfileDto;
	readonly featured_services: readonly WordPressServiceDto[];
}

export interface WordPressCompanyPageDto {
	readonly schema_version: 1;
	readonly page_key: 'company';
	readonly locale: 'en' | 'sr';
	readonly hero: {
		readonly eyebrow: string;
		readonly title: string;
	};
	readonly profile: WordPressCompanyProfileDto;
	readonly intro_cta: WordPressContentLinkDto;
}
