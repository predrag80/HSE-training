import type { Locale } from '../../i18n/config';
import type { CoursesLandingPageContent, NeboshOverviewPageContent } from '../../types/course-page';
import { CmsError } from './errors';

function invalidCoursePage(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned invalid Course page content: ${reason}.`);
}

function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function requireRecord(value: unknown, field: string): Record<string, unknown> {
	return isRecord(value) ? value : invalidCoursePage(`${field} must be an object`);
}

function requireString(value: unknown, field: string): string {
	return typeof value === 'string' && value.trim().length > 0
		? value
		: invalidCoursePage(`${field} is required`);
}

function assertEnvelope(value: unknown, pageKey: 'courses' | 'nebosh', locale: Locale) {
	const page = requireRecord(value, 'document');
	if (page.schema_version !== 1) invalidCoursePage('schema_version must be 1');
	if (page.page_key !== pageKey) invalidCoursePage(`page_key must be ${pageKey}`);
	if (page.locale !== locale) invalidCoursePage(`locale must be ${locale}`);

	return { page, content: requireRecord(page.content, 'content') };
}

function mapMeta(value: unknown) {
	const meta = requireRecord(value, 'content.meta');
	return {
		title: requireString(meta.title, 'content.meta.title'),
		description: requireString(meta.description, 'content.meta.description'),
	};
}

export function mapWordPressCoursesLandingPage(value: unknown, locale: Locale): CoursesLandingPageContent {
	const { content } = assertEnvelope(value, 'courses', locale);
	const hero = requireRecord(content.hero, 'content.hero');
	const emptyState = requireRecord(content.empty_state, 'content.empty_state');

	return {
		schemaVersion: 1,
		pageKey: 'courses',
		locale,
		meta: mapMeta(content.meta),
		hero: {
			eyebrow: requireString(hero.eyebrow, 'content.hero.eyebrow'),
			title: requireString(hero.title, 'content.hero.title'),
			intro: requireString(hero.intro, 'content.hero.intro'),
		},
		emptyState: {
			title: requireString(emptyState.title, 'content.empty_state.title'),
			text: requireString(emptyState.text, 'content.empty_state.text'),
		},
	};
}

export function mapWordPressNeboshOverviewPage(value: unknown, locale: Locale): NeboshOverviewPageContent {
	const { content } = assertEnvelope(value, 'nebosh', locale);
	const hero = requireRecord(content.hero, 'content.hero');
	const intro = requireRecord(content.intro, 'content.intro');
	const courseSelection = requireRecord(content.course_selection, 'content.course_selection');
	const testimonial = requireRecord(content.testimonial, 'content.testimonial');
	if (!Array.isArray(content.strengths) || content.strengths.length !== 4) {
		return invalidCoursePage('content.strengths must contain exactly four items');
	}
	if (!Array.isArray(intro.paragraphs) || intro.paragraphs.length !== 2) {
		return invalidCoursePage('content.intro.paragraphs must contain exactly two items');
	}

	return {
		schemaVersion: 1,
		pageKey: 'nebosh',
		locale,
		meta: mapMeta(content.meta),
		hero: {
			eyebrow: requireString(hero.eyebrow, 'content.hero.eyebrow'),
			title: requireString(hero.title, 'content.hero.title'),
		},
		strengths: content.strengths.map((item, index) => {
			const strength = requireRecord(item, `content.strengths[${index}]`);
			return {
				title: requireString(strength.title, `content.strengths[${index}].title`),
				text: requireString(strength.text, `content.strengths[${index}].text`),
			};
		}),
		intro: {
			kicker: requireString(intro.kicker, 'content.intro.kicker'),
			title: requireString(intro.title, 'content.intro.title'),
			paragraphs: intro.paragraphs.map((item, index) => requireString(item, `content.intro.paragraphs[${index}]`)),
			ctaLabel: requireString(intro.cta_label, 'content.intro.cta_label'),
			imageAlt: requireString(intro.image_alt, 'content.intro.image_alt'),
			visualTitle: requireString(intro.visual_title, 'content.intro.visual_title'),
			visualText: requireString(intro.visual_text, 'content.intro.visual_text'),
		},
		courseSelection: {
			kicker: requireString(courseSelection.kicker, 'content.course_selection.kicker'),
			title: requireString(courseSelection.title, 'content.course_selection.title'),
			currentLabel: requireString(courseSelection.current_label, 'content.course_selection.current_label'),
			legacyLabel: requireString(courseSelection.legacy_label, 'content.course_selection.legacy_label'),
			igcFallback: requireString(courseSelection.igc_fallback, 'content.course_selection.igc_fallback'),
			iogcFallback: requireString(courseSelection.iogc_fallback, 'content.course_selection.iogc_fallback'),
			igcCtaLabel: requireString(courseSelection.igc_cta_label, 'content.course_selection.igc_cta_label'),
			iogcCtaLabel: requireString(courseSelection.iogc_cta_label, 'content.course_selection.iogc_cta_label'),
		},
		testimonial: {
			quote: requireString(testimonial.quote, 'content.testimonial.quote'),
			author: requireString(testimonial.author, 'content.testimonial.author'),
			role: requireString(testimonial.role, 'content.testimonial.role'),
		},
	};
}
