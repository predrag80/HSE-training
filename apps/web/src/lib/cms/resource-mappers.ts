import type { Locale } from '../../i18n/config';
import { isLocale } from '../../i18n/config';
import type { Resource, ResourceCollection, ResourceImage, ResourceType } from '../../types/resource';
import { CmsError } from './errors';

const CONTENT_KEY_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
const RESOURCE_TYPES = new Set<ResourceType>(['link', 'video', 'document', 'procedure', 'standard']);

function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function invalidResources(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned invalid Free Resources content: ${reason}.`);
}

function requireString(value: unknown, field: string, allowEmpty = false): string {
	if (typeof value !== 'string' || (!allowEmpty && value.trim().length === 0)) {
		return invalidResources(`${field} must be ${allowEmpty ? 'a string' : 'a non-empty string'}`);
	}
	return value;
}

function mapImage(value: unknown, field: string): ResourceImage | null {
	if (value === null) return null;
	if (!isRecord(value)) return invalidResources(`${field} must be an object or null`);
	if (typeof value.width !== 'number' || value.width <= 0 || typeof value.height !== 'number' || value.height <= 0) {
		return invalidResources(`${field} dimensions must be positive numbers`);
	}
	return {
		url: requireString(value.url, `${field}.url`),
		alt: requireString(value.alt, `${field}.alt`, true),
		width: value.width,
		height: value.height,
	};
}

function mapResource(value: unknown, index: number): Resource {
	if (!isRecord(value)) return invalidResources(`resources[${index}] must be an object`);
	const prefix = `resources[${index}]`;
	const resourceKey = requireString(value.resource_key, `${prefix}.resource_key`);
	if (!CONTENT_KEY_PATTERN.test(resourceKey)) return invalidResources(`${prefix}.resource_key must be canonical`);
	if (typeof value.type !== 'string' || !RESOURCE_TYPES.has(value.type as ResourceType)) {
		return invalidResources(`${prefix}.type is unsupported`);
	}
	if (typeof value.featured !== 'boolean' || typeof value.is_download !== 'boolean') {
		return invalidResources(`${prefix} boolean flags are invalid`);
	}
	if (typeof value.file_size !== 'number' || value.file_size < 0) {
		return invalidResources(`${prefix}.file_size must be zero or greater`);
	}

	return {
		resourceKey,
		title: requireString(value.title, `${prefix}.title`),
		summary: requireString(value.summary, `${prefix}.summary`, true),
		description: requireString(value.description, `${prefix}.description`, true),
		topic: requireString(value.topic, `${prefix}.topic`, true),
		type: value.type as ResourceType,
		featured: value.featured,
		actionUrl: requireString(value.action_url, `${prefix}.action_url`),
		isDownload: value.is_download,
		fileName: requireString(value.file_name, `${prefix}.file_name`, true),
		fileSize: value.file_size,
		mimeType: requireString(value.mime_type, `${prefix}.mime_type`, true),
		image: mapImage(value.image, `${prefix}.image`),
	};
}

export function mapWordPressResourceCollection(value: unknown, expectedLocale: Locale): ResourceCollection {
	if (!isRecord(value) || !Array.isArray(value.resources)) {
		return invalidResources('expected a collection object with resources');
	}
	if (value.schema_version !== 1 || value.collection_key !== 'resources') {
		return invalidResources('collection identity is invalid');
	}
	if (typeof value.locale !== 'string' || !isLocale(value.locale) || value.locale !== expectedLocale) {
		return invalidResources(`locale must match the requested ${expectedLocale} language`);
	}

	return {
		schemaVersion: 1,
		collectionKey: 'resources',
		locale: value.locale,
		resources: value.resources.map(mapResource),
	};
}
