import { describe, expect, it } from 'vitest';

import { locales } from '../../i18n/config';
import { contactDetails } from '../contact';
import { assertAssistantContent, createAssistantContent, loadAssistantContent } from './assistant-content';
import { findAssistantTopic, parseAssistantContent, tokenizeAssistantQuery, type AssistantContent } from './assistant-match';

function withTopic(content: AssistantContent, id: string, change: Record<string, unknown>): AssistantContent {
	return { topics: content.topics.map((topic) => (topic.id === id ? { ...topic, ...change } : topic)) };
}

describe('site assistant content', () => {
	it.each(locales)('builds valid %s content that survives client parsing unchanged', (locale) => {
		const content = loadAssistantContent(locale);

		expect(parseAssistantContent(JSON.parse(JSON.stringify(content)))).toEqual(content);
	});

	it('offers the same topics and follow-ups in both languages', () => {
		const shape = (content: AssistantContent) => content.topics.map(({ id, next, links }) => ({ id, next, linkCount: links.length }));

		expect(shape(createAssistantContent('sr'))).toEqual(shape(createAssistantContent('en')));
	});

	it.each(locales)('links %s visitors only to this website and the company phone and e-mail', (locale) => {
		const links = createAssistantContent(locale).topics.flatMap((topic) => topic.links);
		const pageHrefs = links.filter((link) => link.kind === 'page').map((link) => link.href);

		expect(links.length).toBeGreaterThan(0);
		for (const href of pageHrefs) {
			expect(href).toMatch(locale === 'sr' ? /^\/sr\// : /^\/(?!sr\/)/);
		}
		expect(new Set(links.filter((link) => link.kind !== 'page').map((link) => link.href))).toEqual(
			new Set([contactDetails.phoneHref, contactDetails.emailHref]),
		);
	});

	it('reuses approved course copy instead of inventing answers', () => {
		const access = createAssistantContent('sr').topics.find((topic) => topic.id === 'access');

		expect(access?.answer.join(' ')).toContain('Nakon potvrđenog plaćanja dobijate uputstvo');
		expect(access?.answer[0]).toContain('„Kupi sada”');
		expect(access?.answer[0]).toContain('„Cena na upit”');
	});

	it.each(locales)('has no %s topic for questions unrelated to the website', (locale) => {
		const { topics } = createAssistantContent(locale);

		expect(findAssistantTopic(topics, tokenizeAssistantQuery("What's the weather in Belgrade tomorrow?"))).toBeUndefined();
		expect(findAssistantTopic(topics, tokenizeAssistantQuery('Kakvo je vreme sutra u Beogradu?'))).toBeUndefined();
	});

	it.each([
		['sr', 'Kako da platim kurs?', 'access'],
		['sr', 'Da li mi treba predznanje engleskog?', 'igc-requirements'],
		['sr', 'Koliko traje Banksman obuka?', 'training'],
		['sr', 'Broj telefona?', 'contact'],
		['sr', 'Gde se nalazite?', 'contact'],
		['en', 'Do I need previous experience?', 'igc-requirements'],
		['en', 'Is the oil and gas course still available?', 'iogc'],
		['en', 'Can you recommend HSE consultants?', 'consulting'],
	] as const)('answers the %s question "%s" with the %s topic', (locale, question, topicId) => {
		const { topics } = createAssistantContent(locale);

		expect(findAssistantTopic(topics, tokenizeAssistantQuery(question))?.id).toBe(topicId);
	});
});

describe('site assistant content validation', () => {
	const content = createAssistantContent('en');

	it.each([
		['an external website', 'https://example.com/'],
		['a protocol-relative host', '//example.com/'],
		['a backslash host', '/\\example.com/'],
		['an unpublished page', '/not-a-page/'],
	])('fails the build for a link to %s', (_label, href) => {
		const broken = withTopic(content, 'contact', { links: [{ label: 'Link', href, kind: 'page' }] });

		expect(() => assertAssistantContent(broken)).toThrow(/links/);
	});

	it('fails the build for a phone number other than the company number', () => {
		const broken = withTopic(content, 'contact', { links: [{ label: 'Phone', href: 'tel:+381000000', kind: 'phone' }] });

		expect(() => assertAssistantContent(broken)).toThrow(/links outside/);
	});

	it('fails the build for unknown follow-ups, duplicate or missing topics, and empty answers', () => {
		expect(() => assertAssistantContent(withTopic(content, 'contact', { next: ['missing'] }))).toThrow(/unknown follow-up/);
		expect(() => assertAssistantContent(withTopic(content, 'contact', { next: ['start'] }))).toThrow(/unknown follow-up/);
		expect(() => assertAssistantContent({ topics: [...content.topics, content.topics[1]!] })).toThrow(/more than once/);
		expect(() => assertAssistantContent({ topics: content.topics.filter((topic) => topic.id !== 'fallback') })).toThrow(/missing/);
		expect(() => assertAssistantContent(withTopic(content, 'contact', { answer: [' '] }))).toThrow(/empty answer/);
	});

	it('fails the build for missing or malformed keywords', () => {
		expect(() => assertAssistantContent(withTopic(content, 'contact', { keywords: [] }))).toThrow(/no keywords/);
		expect(() => assertAssistantContent(withTopic(content, 'contact', { keywords: ['e mail'] }))).toThrow(/invalid keyword/);
	});
});
