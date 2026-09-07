import type { Reference, ReferenceCollection } from '../../types/reference';
import { CmsError } from './errors';

const CONTENT_KEY_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function invalidReferences(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned invalid Reference content: ${reason}.`);
}

function requireString(value: unknown, field: string): string {
	if (typeof value !== 'string' || value.trim().length === 0) {
		return invalidReferences(`${field} is required`);
	}

	return value;
}

function mapReference(value: unknown, index: number): Reference {
	if (!isRecord(value)) return invalidReferences(`references[${index}] must be an object`);
	const referenceKey = requireString(value.reference_key, `references[${index}].reference_key`);
	if (!CONTENT_KEY_PATTERN.test(referenceKey)) {
		return invalidReferences(`references[${index}].reference_key must be canonical`);
	}
	if (typeof value.featured_on_homepage !== 'boolean') {
		return invalidReferences(`references[${index}].featured_on_homepage must be boolean`);
	}
	if (typeof value.accent_on_homepage !== 'boolean') {
		return invalidReferences(`references[${index}].accent_on_homepage must be boolean`);
	}

	return {
		referenceKey,
		quote: requireString(value.quote, `references[${index}].quote`),
		authorName: requireString(value.author_name, `references[${index}].author_name`),
		role: requireString(value.role, `references[${index}].role`),
		featuredOnHomepage: value.featured_on_homepage,
		accentOnHomepage: value.accent_on_homepage,
	};
}

export function mapWordPressReferenceCollection(value: unknown): ReferenceCollection {
	if (!isRecord(value) || !Array.isArray(value.references)) {
		return invalidReferences('expected a collection object with references');
	}
	if (value.schema_version !== 1) return invalidReferences('schema_version must be 1');
	if (value.collection_key !== 'references') {
		return invalidReferences('collection_key must be references');
	}
	if (value.references.length === 0) return invalidReferences('references must not be empty');

	return {
		schemaVersion: 1,
		collectionKey: 'references',
		references: value.references.map(mapReference),
	};
}
