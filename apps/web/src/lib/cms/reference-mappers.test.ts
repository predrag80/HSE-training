import { describe, expect, it } from 'vitest';

import { mapWordPressReferenceCollection } from './reference-mappers';

const reference = {
	reference_key: 'saule-kuza',
	quote: 'The trainer explained the qualification clearly.',
	author_name: 'Saule Kuza',
	role: 'Technical translator at KPO',
	featured_on_homepage: true,
	accent_on_homepage: false,
};

describe('mapWordPressReferenceCollection', () => {
	it('maps the versioned WordPress collection', () => {
		expect(
			mapWordPressReferenceCollection(
				{
					schema_version: 1,
					collection_key: 'references',
					locale: 'en',
					references: [reference],
				},
				'en',
			),
		).toEqual({
			schemaVersion: 1,
			collectionKey: 'references',
			locale: 'en',
			references: [
				{
					referenceKey: 'saule-kuza',
					quote: 'The trainer explained the qualification clearly.',
					authorName: 'Saule Kuza',
					role: 'Technical translator at KPO',
					featuredOnHomepage: true,
					accentOnHomepage: false,
				},
			],
		});
	});

	it('rejects a non-canonical Reference key', () => {
		expect(() =>
			mapWordPressReferenceCollection(
				{
					schema_version: 1,
					collection_key: 'references',
					locale: 'en',
					references: [{ ...reference, reference_key: 'Saule Kuza' }],
				},
				'en',
			),
		).toThrow('must be canonical');
	});

	it('rejects an invalid Homepage flag', () => {
		expect(() =>
			mapWordPressReferenceCollection(
				{
					schema_version: 1,
					collection_key: 'references',
					locale: 'en',
					references: [{ ...reference, featured_on_homepage: 'yes' }],
				},
				'en',
			),
		).toThrow('must be boolean');
	});

	it('rejects a collection returned in the wrong language', () => {
		expect(() =>
			mapWordPressReferenceCollection(
				{
					schema_version: 1,
					collection_key: 'references',
					locale: 'en',
					references: [reference],
				},
				'sr',
			),
		).toThrow('locale must match the requested sr language');
	});
});
