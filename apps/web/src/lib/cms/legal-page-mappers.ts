import type { Locale } from '../../i18n/config';
import type { LegalPageContent, LegalPageKey } from '../../types/legal';
import { CmsError } from './errors';

function invalidLegalPage(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned invalid Legal Page content: ${reason}.`);
}

function requireRecord(value: unknown, field: string): Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value)
		? value as Record<string, unknown>
		: invalidLegalPage(`${field} must be an object`);
}

function requireString(value: unknown, field: string): string {
	return typeof value === 'string' && value.trim().length > 0
		? value
		: invalidLegalPage(`${field} is required`);
}

export function mapWordPressLegalPage(
	value: unknown,
	pageKey: LegalPageKey,
	locale: Locale,
): LegalPageContent {
	const document = requireRecord(value, 'document');
	if (document.schema_version !== 1) invalidLegalPage('schema_version must be 1');
	if (document.page_key !== pageKey) invalidLegalPage(`page_key must be ${pageKey}`);
	if (document.locale !== locale) invalidLegalPage(`locale must be ${locale}`);

	const content = requireRecord(document.content, 'content');
	const meta = requireRecord(content.meta, 'content.meta');
	if (!Array.isArray(content.sections) || content.sections.length === 0) {
		invalidLegalPage('content.sections must contain at least one section');
	}

	return {
		schemaVersion: 1,
		pageKey,
		locale,
		meta: {
			title: requireString(meta.title, 'content.meta.title'),
			description: requireString(meta.description, 'content.meta.description'),
		},
		eyebrow: requireString(content.eyebrow, 'content.eyebrow'),
		title: requireString(content.title, 'content.title'),
		intro: requireString(content.intro, 'content.intro'),
		lastUpdated: requireString(content.last_updated, 'content.last_updated'),
		sections: content.sections.map((item, index) => {
			const section = requireRecord(item, `content.sections[${index}]`);
			return {
				title: requireString(section.title, `content.sections[${index}].title`),
				bodyHtml: requireString(section.body_html, `content.sections[${index}].body_html`),
			};
		}),
	};
}
