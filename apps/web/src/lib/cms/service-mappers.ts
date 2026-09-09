import type { Service, ServiceCollection, ServiceImage, ServiceLink } from '../../types/service';
import type { Locale } from '../../i18n/config';
import { CmsError } from './errors';
import type { WordPressServiceCollectionDto, WordPressServiceDto } from './types';

const CONTENT_KEY_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
const ANCHOR_PATTERN = /^#[A-Za-z][A-Za-z0-9_-]*$/;
export const MAX_HOMEPAGE_SERVICES = 4;

function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function invalidServices(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned invalid Service content: ${reason}.`);
}

function requireString(value: unknown, field: string, allowEmpty = false): string {
	if (typeof value !== 'string' || (!allowEmpty && value.trim().length === 0)) {
		return invalidServices(`${field} is required`);
	}

	return value;
}

function mapLink(value: unknown, field: string): ServiceLink {
	if (!isRecord(value)) return invalidServices(`${field} must be an object`);

	const url = requireString(value.url, `${field}.url`);
	if (!ANCHOR_PATTERN.test(url) && !(url.startsWith('/') && !url.startsWith('//'))) {
		let parsed: URL;
		try {
			parsed = new URL(url);
		} catch {
			return invalidServices(`${field}.url must be a valid URL, internal path, or anchor`);
		}
		if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
			return invalidServices(`${field}.url must use HTTP or HTTPS`);
		}
	}

	return {
		label: requireString(value.label, `${field}.label`),
		url,
	};
}

function mapImage(value: unknown, field: string): ServiceImage {
	if (!isRecord(value)) return invalidServices(`${field} must be an object`);

	const url = requireString(value.url, `${field}.url`);
	let parsed: URL;
	try {
		parsed = new URL(url);
	} catch {
		return invalidServices(`${field}.url must be a valid URL`);
	}
	if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
		return invalidServices(`${field}.url must use HTTP or HTTPS`);
	}
	if (!Number.isInteger(value.width) || (value.width as number) <= 0) {
		return invalidServices(`${field}.width must be a positive integer`);
	}
	if (!Number.isInteger(value.height) || (value.height as number) <= 0) {
		return invalidServices(`${field}.height must be a positive integer`);
	}

	return {
		url,
		alt: requireString(value.alt, `${field}.alt`, true),
		width: value.width as number,
		height: value.height as number,
	};
}

export function mapWordPressService(value: unknown, index: number): Service {
	if (!isRecord(value)) return invalidServices(`services[${index}] must be an object`);

	const serviceKey = requireString(value.service_key, `services[${index}].service_key`);
	if (!CONTENT_KEY_PATTERN.test(serviceKey)) {
		return invalidServices(`services[${index}].service_key must be canonical`);
	}
	if (typeof value.featured_on_homepage !== 'boolean') {
		return invalidServices(`services[${index}].featured_on_homepage must be boolean`);
	}

	const service = value as unknown as WordPressServiceDto;
	return {
		serviceKey,
		title: requireString(service.title, `services[${index}].title`),
		cardLabel: requireString(service.card_label, `services[${index}].card_label`),
		shortDescription: requireString(
			service.short_description,
			`services[${index}].short_description`,
		),
		detailedDescription: requireString(
			service.detailed_description,
			`services[${index}].detailed_description`,
		),
		cta: mapLink(service.cta, `services[${index}].cta`),
		image: mapImage(service.image, `services[${index}].image`),
		featuredOnHomepage: service.featured_on_homepage,
	};
}

export function mapWordPressServiceCollection(value: unknown, locale: Locale = 'en'): ServiceCollection {
	if (!isRecord(value) || !Array.isArray(value.services)) {
		return invalidServices('expected a collection object with services');
	}
	if (value.schema_version !== 1) return invalidServices('schema_version must be 1');
	if (value.collection_key !== 'services') {
		return invalidServices('collection_key must be services');
	}
	if (value.locale !== locale) return invalidServices(`locale must be ${locale}`);
	if (value.services.length === 0) return invalidServices('services must not be empty');

	const collection = value as unknown as WordPressServiceCollectionDto;
	return {
		schemaVersion: collection.schema_version,
		collectionKey: collection.collection_key,
		locale,
		services: collection.services.map(mapWordPressService),
	};
}

export function mapWordPressFeaturedServices(value: unknown): readonly Service[] {
	if (!Array.isArray(value) || value.length === 0 || value.length > MAX_HOMEPAGE_SERVICES) {
		return invalidServices(
			`featured_services must contain between 1 and ${MAX_HOMEPAGE_SERVICES} items`,
		);
	}

	const services = value.map(mapWordPressService);
	if (services.some((service) => !service.featuredOnHomepage)) {
		return invalidServices('featured_services contains a service not selected for the Homepage');
	}

	return services;
}
