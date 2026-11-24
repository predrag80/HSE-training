import { describe, expect, it } from 'vitest';

import { contactDetails } from '../contact';
import type { SearchEntry } from '../search/search-match';
import {
	ASSISTANT_VISIBLE_MATCH_SCORE,
	findAssistantPages,
	findAssistantTopic,
	getAssistantContentPath,
	isAllowedAssistantLink,
	isSameSitePath,
	parseAssistantContent,
	tokenizeAssistantQuery,
	type AssistantTopic,
} from './assistant-match';

function topic(overrides: Partial<AssistantTopic>): AssistantTopic {
	return { id: 'topic', prompt: 'Prompt', answer: ['Answer'], links: [], next: [], keywords: [], ...overrides };
}

function entry(overrides: Partial<SearchEntry>): SearchEntry {
	return { kind: 'page', title: 'Title', summary: '', url: '/', keywords: '', external: false, ...overrides };
}

const start = topic({ id: 'start', next: ['access'] });
const fallback = topic({ id: 'fallback' });
const access = topic({ id: 'access', keywords: ['pristup', 'plat', 'cen', 'access', 'price'] });
const training = topic({ id: 'training', keywords: ['obuk', 'banksman', 'training'] });
const nebosh = topic({ id: 'nebosh', keywords: ['nebosh', 'igc'] });

describe('site assistant links', () => {
	it('points each locale at its static content file without a trailing slash', () => {
		expect(getAssistantContentPath('en')).toBe('/assistant.json');
		expect(getAssistantContentPath('sr')).toBe('/sr/assistant.json');
	});

	it('accepts only same-site paths', () => {
		expect(isSameSitePath('/')).toBe(true);
		expect(isSameSitePath('/sr/contact/#contact-form')).toBe(true);
		expect(isSameSitePath('/courses/?q=igc')).toBe(true);

		expect(isSameSitePath('https://hsetraining.rs/')).toBe(false);
		expect(isSameSitePath('//evil.example/')).toBe(false);
		expect(isSameSitePath('/\\evil.example/')).toBe(false);
		expect(isSameSitePath('/path with space/')).toBe(false);
		expect(isSameSitePath('javascript:alert(1)')).toBe(false);
		expect(isSameSitePath('courses/')).toBe(false);
		expect(isSameSitePath('')).toBe(false);
	});

	it('allows phone and e-mail links only to the company itself', () => {
		expect(isAllowedAssistantLink({ kind: 'phone', href: contactDetails.phoneHref })).toBe(true);
		expect(isAllowedAssistantLink({ kind: 'email', href: contactDetails.emailHref })).toBe(true);

		expect(isAllowedAssistantLink({ kind: 'phone', href: 'tel:+381000000' })).toBe(false);
		expect(isAllowedAssistantLink({ kind: 'email', href: 'mailto:someone@example.com' })).toBe(false);
		expect(isAllowedAssistantLink({ kind: 'page', href: contactDetails.phoneHref })).toBe(false);
		expect(isAllowedAssistantLink({ kind: 'page', href: 'https://wa.me/381615335010' })).toBe(false);
	});
});

describe('site assistant content parsing', () => {
	it('keeps valid topics and drops links that leave the website', () => {
		const parsed = parseAssistantContent({
			topics: [
				start,
				fallback,
				{
					...access,
					links: [
						{ label: 'All courses', href: '/courses/', kind: 'page' },
						{ label: 'Elsewhere', href: 'https://example.com/', kind: 'page' },
						{ label: 'Relative host', href: '//example.com/', kind: 'page' },
						{ label: 'Other phone', href: 'tel:+381000000', kind: 'phone' },
						{ label: 'Unknown kind', href: '/courses/', kind: 'video' },
						{ label: '', href: '/courses/', kind: 'page' },
					],
				},
			],
		});

		expect(parsed?.topics.map((item) => item.id)).toEqual(['start', 'fallback', 'access']);
		expect(parsed?.topics[2]?.links).toEqual([{ label: 'All courses', href: '/courses/', kind: 'page' }]);
	});

	it('removes follow-ups to unknown topics and malformed topics', () => {
		const parsed = parseAssistantContent({
			topics: [{ ...start, next: ['access', 'missing'] }, fallback, access, { id: 'broken', prompt: 'Broken' }],
		});

		expect(parsed?.topics.map((item) => item.id)).toEqual(['start', 'fallback', 'access']);
		expect(parsed?.topics[0]?.next).toEqual(['access']);
	});

	it('rejects content without start and fallback topics', () => {
		expect(parseAssistantContent({ topics: [start, access] })).toBeNull();
		expect(parseAssistantContent({ topics: [fallback] })).toBeNull();
		expect(parseAssistantContent([start, fallback])).toBeNull();
		expect(parseAssistantContent(null)).toBeNull();
	});
});

describe('site assistant matching', () => {
	it('keeps meaningful words, folds diacritics and drops fillers', () => {
		expect(tokenizeAssistantQuery('Kako da platim kurs?')).toEqual(['platim', 'kurs']);
		expect(tokenizeAssistantQuery('Koliko košta obuka? Obuka!')).toEqual(['kosta', 'obuka']);
		expect(tokenizeAssistantQuery('How can I get access to the course?')).toEqual(['access', 'course']);
		expect(tokenizeAssistantQuery('?? a i')).toEqual([]);
		expect(tokenizeAssistantQuery("What's the weather in Belgrade, Serbia? HSE DOO")).toEqual(['weather']);
	});

	it('bounds the query length', () => {
		expect(tokenizeAssistantQuery(`${'x'.repeat(200)} nebosh`)).toEqual(['x'.repeat(160)]);
	});

	it('matches inflected Serbian and English words to topic stems', () => {
		const topics = [start, access, training, nebosh, fallback];

		expect(findAssistantTopic(topics, tokenizeAssistantQuery('Kako da platim kurs?'))?.id).toBe('access');
		expect(findAssistantTopic(topics, tokenizeAssistantQuery('Kada dobijam pristup?'))?.id).toBe('access');
		expect(findAssistantTopic(topics, tokenizeAssistantQuery('What is the price?'))?.id).toBe('access');
		expect(findAssistantTopic(topics, tokenizeAssistantQuery('Obuke za banksman-a'))?.id).toBe('training');
		expect(findAssistantTopic(topics, tokenizeAssistantQuery('NEBOSH IGC'))?.id).toBe('nebosh');
	});

	it('prefers the topic with more matching words and keeps topic order for ties', () => {
		const topics = [access, training, nebosh];

		expect(findAssistantTopic(topics, ['cena', 'obuke', 'banksman'])?.id).toBe('training');
		expect(findAssistantTopic(topics, ['nebosh', 'cena'])?.id).toBe('access');
	});

	it('accepts a partly typed word of at least four letters', () => {
		expect(findAssistantTopic([training], ['banks'])?.id).toBe('training');
		expect(findAssistantTopic([training], ['ban'])).toBeUndefined();
	});

	it('returns no topic when nothing matches', () => {
		expect(findAssistantTopic([start, access, fallback], ['vreme', 'prognoza'])).toBeUndefined();
		expect(findAssistantTopic([access], [])).toBeUndefined();
	});
});

describe('site assistant page suggestions', () => {
	const igc = entry({
		kind: 'course',
		title: 'NEBOSH International General Certificate',
		summary: 'Health and safety qualification.',
		url: '/nebosh-international-general-certificate-in-occupational-health-and-safety/',
		keywords: 'nebosh igc risk assessment',
	});
	const banksman = entry({ kind: 'training', title: 'Banksman / Slinger', summary: 'Safe lifting operations.', url: '/training/banksman-slinger/' });
	const externalResource = entry({ kind: 'resource', title: 'NEBOSH risk guide', url: 'https://example.com/guide.pdf', external: true });
	const unsafeUrl = entry({ title: 'NEBOSH elsewhere', url: '//example.com/', external: false });

	it('never suggests off-site entries', () => {
		expect(findAssistantPages([externalResource, unsafeUrl, igc], ['nebosh'])).toEqual([igc]);
	});

	it('needs only one matching word and ranks title matches first', () => {
		expect(findAssistantPages([igc, banksman], ['lifting', 'unknown'])).toEqual([banksman]);
		expect(findAssistantPages([igc, banksman], ['risk', 'banksman'])).toEqual([banksman, igc]);
	});

	it('can require a title or summary match for extra suggestions', () => {
		expect(findAssistantPages([igc, banksman], ['risk'], ASSISTANT_VISIBLE_MATCH_SCORE)).toEqual([]);
		expect(findAssistantPages([igc, banksman], ['lifting'], ASSISTANT_VISIBLE_MATCH_SCORE)).toEqual([banksman]);
	});

	it('limits suggestions and returns nothing for an empty query', () => {
		const entries = Array.from({ length: 6 }, (_, index) => entry({ title: `Safety ${index}`, url: `/${index}/` }));

		expect(findAssistantPages(entries, ['safety']).map((item) => item.url)).toEqual(['/0/', '/1/', '/2/']);
		expect(findAssistantPages(entries, ['safety'], 1, 2).map((item) => item.url)).toEqual(['/0/', '/1/']);
		expect(findAssistantPages(entries, [])).toEqual([]);
	});
});
