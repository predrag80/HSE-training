import type { Homepage, HomepageHeroSlide } from '../../types/homepage';
import { mapWordPressCompanyProfile } from './company-mappers';
import { CmsError } from './errors';
import { mapWordPressFeaturedServices } from './service-mappers';
import type { WordPressHomepageDto } from './types';

const CONTENT_KEY_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
const ANCHOR_PATTERN = /^#[A-Za-z][A-Za-z0-9_-]*$/;
const MAX_SLIDES = 5;

function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function invalidHomepage(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned an invalid Homepage response: ${reason}.`);
}

function requireString(value: unknown, field: string, allowEmpty = false): string {
	if (typeof value !== 'string' || (!allowEmpty && value.trim().length === 0)) {
		return invalidHomepage(`${field} is required`);
	}

	return value;
}

function requireLinkUrl(value: unknown, field: string): string {
	const url = requireString(value, field);

	if (ANCHOR_PATTERN.test(url) || (url.startsWith('/') && !url.startsWith('//'))) return url;

	let parsed: URL | null = null;
	try {
		parsed = new URL(url);
	} catch {
		// The common error below intentionally avoids reflecting untrusted CMS data.
	}
	if (parsed && (parsed.protocol === 'http:' || parsed.protocol === 'https:')) return url;

	return invalidHomepage(`${field} must be an internal path, anchor, or HTTP(S) URL`);
}

function parseSlide(value: unknown, index: number): HomepageHeroSlide {
	if (!isRecord(value) || !isRecord(value.image)) {
		return invalidHomepage(`hero.slides[${index}] must contain an image`);
	}

	const slideKey = requireString(value.slide_key, `hero.slides[${index}].slide_key`);
	if (!CONTENT_KEY_PATTERN.test(slideKey)) {
		return invalidHomepage(`hero.slides[${index}].slide_key must be canonical`);
	}

	const imageUrl = requireString(value.image.url, `hero.slides[${index}].image.url`);
	let parsedImageUrl: URL;
	try {
		parsedImageUrl = new URL(imageUrl);
	} catch {
		return invalidHomepage(`hero.slides[${index}].image.url must be a valid URL`);
	}
	if (parsedImageUrl.protocol !== 'http:' && parsedImageUrl.protocol !== 'https:') {
		return invalidHomepage(`hero.slides[${index}].image.url must use HTTP or HTTPS`);
	}

	const { width, height } = value.image;
	if (!Number.isInteger(width) || (width as number) <= 0) {
		return invalidHomepage(`hero.slides[${index}].image.width must be a positive integer`);
	}
	if (!Number.isInteger(height) || (height as number) <= 0) {
		return invalidHomepage(`hero.slides[${index}].image.height must be a positive integer`);
	}

	return {
		slideKey,
		image: {
			url: imageUrl,
			alt: requireString(value.image.alt, `hero.slides[${index}].image.alt`, true),
			width: width as number,
			height: height as number,
		},
		leadingTitle: requireString(value.leading_title, `hero.slides[${index}].leading_title`),
		emphasizedTitle: requireString(
			value.emphasized_title,
			`hero.slides[${index}].emphasized_title`,
		),
		primaryCtaLabel: requireString(
			value.primary_cta_label,
			`hero.slides[${index}].primary_cta_label`,
		),
		primaryCtaUrl: requireLinkUrl(
			value.primary_cta_url,
			`hero.slides[${index}].primary_cta_url`,
		),
		messagePrefix: requireString(
			value.message_prefix,
			`hero.slides[${index}].message_prefix`,
		),
		messageLinkLabel: requireString(
			value.message_link_label,
			`hero.slides[${index}].message_link_label`,
		),
		messageLinkUrl: requireLinkUrl(
			value.message_link_url,
			`hero.slides[${index}].message_link_url`,
		),
	};
}

export function mapWordPressHomepage(value: unknown): Homepage {
	if (
		!isRecord(value) ||
		!isRecord(value.hero) ||
		!Array.isArray(value.hero.slides) ||
		!isRecord(value.about) ||
		!Array.isArray(value.featured_services)
	) {
		return invalidHomepage('expected an object with hero slides, about content, and services');
	}
	if (value.schema_version !== 1) return invalidHomepage('schema_version must be 1');
	if (value.page_key !== 'home') return invalidHomepage('page_key must be home');
	if (value.hero.slides.length === 0 || value.hero.slides.length > MAX_SLIDES) {
		return invalidHomepage(`hero.slides must contain between 1 and ${MAX_SLIDES} items`);
	}

	const homepage = value as unknown as WordPressHomepageDto;

	return {
		schemaVersion: homepage.schema_version,
		pageKey: homepage.page_key,
		hero: {
			ariaLabel: requireString(homepage.hero.aria_label, 'hero.aria_label'),
			heading: requireString(homepage.hero.heading, 'hero.heading'),
			slides: homepage.hero.slides.map(parseSlide),
		},
		about: mapWordPressCompanyProfile(homepage.about),
		featuredServices: mapWordPressFeaturedServices(homepage.featured_services),
	};
}
