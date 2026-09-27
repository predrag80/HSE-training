import { describe, expect, it } from 'vitest';

import { mapWordPressResourceCollection } from './resource-mappers';

const resource = {
	resource_key: 'risk-assessment-template',
	title: 'Risk assessment template',
	summary: 'A practical editable template.',
	description: '<p>Use this template as a starting point.</p>',
	topic: 'Risk management',
	type: 'document',
	featured: true,
	action_url: 'https://cms.hsetraining.rs/wp-content/uploads/template.docx',
	is_download: true,
	file_name: 'template.docx',
	file_size: 2048,
	mime_type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	image: null,
};

describe('mapWordPressResourceCollection', () => {
	it('maps a versioned resource collection', () => {
		const result = mapWordPressResourceCollection(
			{ schema_version: 1, collection_key: 'resources', locale: 'en', resources: [resource] },
			'en',
		);
		expect(result.resources[0]).toMatchObject({
			resourceKey: 'risk-assessment-template',
			type: 'document',
			isDownload: true,
			fileSize: 2048,
		});
	});

	it('accepts an empty collection', () => {
		expect(
			mapWordPressResourceCollection(
				{ schema_version: 1, collection_key: 'resources', locale: 'sr', resources: [] },
				'sr',
			).resources,
		).toEqual([]);
	});

	it('rejects unsupported resource types', () => {
		expect(() =>
			mapWordPressResourceCollection(
				{ schema_version: 1, collection_key: 'resources', locale: 'en', resources: [{ ...resource, type: 'audio' }] },
				'en',
			),
		).toThrow('type is unsupported');
	});

	it('rejects a response in the wrong language', () => {
		expect(() =>
			mapWordPressResourceCollection(
				{ schema_version: 1, collection_key: 'resources', locale: 'en', resources: [resource] },
				'sr',
			),
		).toThrow('locale must match the requested sr language');
	});
});
