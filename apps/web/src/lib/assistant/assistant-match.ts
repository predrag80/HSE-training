/** Client-safe content contract and rule-based matching for the site assistant. */

import { contactDetails } from '../contact';
import { normalizeSearchText, type SearchEntry } from '../search/search-match';

export type AssistantLinkKind = 'page' | 'phone' | 'email';

export interface AssistantLink {
	readonly label: string;
	readonly href: string;
	readonly kind: AssistantLinkKind;
}

export interface AssistantTopic {
	readonly id: string;
	/** Button label; also echoed as the visitor's message when chosen. */
	readonly prompt: string;
	readonly answer: readonly string[];
	readonly links: readonly AssistantLink[];
	/** Topic ids offered as follow-up choices after this answer. */
	readonly next: readonly string[];
	/** Normalized word stems; a typed word matches when it starts with one. */
	readonly keywords: readonly string[];
}

export interface AssistantContent {
	readonly topics: readonly AssistantTopic[];
}

export const ASSISTANT_START_TOPIC_ID = 'start';
export const ASSISTANT_FALLBACK_TOPIC_ID = 'fallback';
export const ASSISTANT_QUERY_MAX_LENGTH = 160;
export const ASSISTANT_PAGE_RESULT_LIMIT = 3;
const MIN_TOKEN_LENGTH = 3;

/** Static content URL per locale; like the search index it is a file without a trailing slash. */
export function getAssistantContentPath(locale: 'en' | 'sr'): string {
	return locale === 'sr' ? '/sr/assistant.json' : '/assistant.json';
}

/**
 * Same-site path only: one leading slash, no protocol-relative `//` or `/\`
 * form (browsers treat both as another host), no whitespace or backslashes.
 */
export function isSameSitePath(href: string): boolean {
	return /^\/(?![/\\])[^\s\\]*$/.test(href);
}

/** The assistant may link only to this website and to the company's own phone and e-mail. */
export function isAllowedAssistantLink(link: Pick<AssistantLink, 'href' | 'kind'>): boolean {
	switch (link.kind) {
		case 'page':
			return isSameSitePath(link.href);
		case 'phone':
			return link.href === contactDetails.phoneHref;
		case 'email':
			return link.href === contactDetails.emailHref;
		default:
			return false;
	}
}

const LINK_KINDS: ReadonlySet<string> = new Set(['page', 'phone', 'email']);

function isNonEmptyString(value: unknown): value is string {
	return typeof value === 'string' && value.trim().length > 0;
}

function isStringArray(value: unknown): value is string[] {
	return Array.isArray(value) && value.every((item) => typeof item === 'string');
}

function parseLink(value: unknown): AssistantLink | null {
	if (typeof value !== 'object' || value === null) return null;

	const link = value as Record<string, unknown>;
	if (!isNonEmptyString(link.label) || typeof link.href !== 'string' || typeof link.kind !== 'string') return null;
	if (!LINK_KINDS.has(link.kind)) return null;

	const parsed: AssistantLink = { label: link.label, href: link.href, kind: link.kind as AssistantLinkKind };
	return isAllowedAssistantLink(parsed) ? parsed : null;
}

function parseTopic(value: unknown): AssistantTopic | null {
	if (typeof value !== 'object' || value === null) return null;

	const topic = value as Record<string, unknown>;
	if (!isNonEmptyString(topic.id) || !isNonEmptyString(topic.prompt)) return null;
	if (!isStringArray(topic.answer) || !Array.isArray(topic.links)) return null;
	if (!isStringArray(topic.next) || !isStringArray(topic.keywords)) return null;

	return {
		id: topic.id,
		prompt: topic.prompt,
		answer: topic.answer.filter(isNonEmptyString),
		links: topic.links.map(parseLink).filter((link): link is AssistantLink => link !== null),
		next: topic.next,
		keywords: topic.keywords.filter((keyword) => keyword.length >= MIN_TOKEN_LENGTH),
	};
}

/**
 * Validate downloaded assistant content. Links that are not same-site pages or
 * the company's own phone/e-mail are dropped, follow-ups to unknown topics are
 * removed, and content without start and fallback topics is rejected.
 */
export function parseAssistantContent(value: unknown): AssistantContent | null {
	if (typeof value !== 'object' || value === null) return null;

	const rawTopics = (value as Record<string, unknown>).topics;
	if (!Array.isArray(rawTopics)) return null;

	const topics = rawTopics.map(parseTopic).filter((topic): topic is AssistantTopic => topic !== null);
	const ids = new Set(topics.map((topic) => topic.id));
	if (!ids.has(ASSISTANT_START_TOPIC_ID) || !ids.has(ASSISTANT_FALLBACK_TOPIC_ID)) return null;

	return {
		topics: topics.map((topic) => ({ ...topic, next: topic.next.filter((id) => ids.has(id)) })),
	};
}

/**
 * Question words and fillers that carry no topic meaning in either language,
 * plus words that describe the whole site (company, city, country) and would
 * otherwise turn an unrelated question into page suggestions.
 */
const STOPWORDS: ReadonlySet<string> = new Set([
	// The whole website
	'hse', 'doo', 'beograd', 'beogradu', 'belgrade', 'srbija', 'srbiji', 'serbia',
	// Serbian (normalized: no diacritics, đ as dj)
	'ako', 'ali', 'biti', 'bih', 'cemu', 'gde', 'hocu', 'ili', 'ima', 'imam', 'imate', 'jos', 'kada',
	'kako', 'koja', 'koje', 'koji', 'koju', 'koliko', 'kod', 'mene', 'meni', 'mogu', 'moze', 'mozete', 'nas', 'nije',
	'ovo', 'ove', 'ovaj', 'pod', 'sta', 'sto', 'tome', 'treba', 'vam', 'vas', 'vase', 'zasto', 'zelim', 'zanima',
	'molim', 'hvala', 'dobar', 'dan', 'zdravo', 'pozdrav',
	// English
	'about', 'and', 'any', 'are', 'can', 'could', 'does', 'for', 'get', 'have', 'hello', 'how', 'into', 'like', 'need',
	'please', 'tell', 'thanks', 'that', 'the', 'there', 'this', 'what', 'when', 'where', 'which', 'why', 'will', 'with',
	'would', 'you', 'your', 'want',
]);

/** Normalized, de-duplicated query words worth matching (three or more letters, no fillers). */
export function tokenizeAssistantQuery(query: string): string[] {
	const words = normalizeSearchText(query.slice(0, ASSISTANT_QUERY_MAX_LENGTH)).split(' ');

	return [...new Set(words.filter((word) => word.length >= MIN_TOKEN_LENGTH && !STOPWORDS.has(word)))];
}

function matchesStem(token: string, stem: string): boolean {
	return token.startsWith(stem) || (token.length >= 4 && stem.startsWith(token));
}

/**
 * Pick the topic whose keyword stems match the most query words. Ties keep
 * topic order, so specific topics listed first win over broad ones.
 */
export function findAssistantTopic(
	topics: readonly AssistantTopic[],
	tokens: readonly string[],
): AssistantTopic | undefined {
	let best: AssistantTopic | undefined;
	let bestScore = 0;

	for (const topic of topics) {
		const score = tokens.filter((token) => topic.keywords.some((stem) => matchesStem(token, stem))).length;
		if (score > bestScore) {
			best = topic;
			bestScore = score;
		}
	}

	return best;
}

/** A title or summary match; keyword-only matches score lower. */
export const ASSISTANT_VISIBLE_MATCH_SCORE = 2;

/**
 * Rank same-site search-index entries by how many query words they contain.
 * Unlike header search, a page needs only one matching word, because visitors
 * type questions rather than keywords. `minScore` raises the bar for extra
 * suggestions shown beside a matched topic. Off-site entries are never returned.
 */
export function findAssistantPages(
	entries: readonly SearchEntry[],
	tokens: readonly string[],
	minScore = 1,
	limit = ASSISTANT_PAGE_RESULT_LIMIT,
): SearchEntry[] {
	if (tokens.length === 0) return [];

	const matches: { entry: SearchEntry; score: number; index: number }[] = [];
	entries.forEach((entry, index) => {
		if (entry.external || !isSameSitePath(entry.url)) return;

		const title = normalizeSearchText(entry.title);
		const summary = normalizeSearchText(entry.summary);
		const keywords = normalizeSearchText(entry.keywords);
		let score = 0;
		for (const token of tokens) {
			if (title.includes(token)) score += 3;
			else if (summary.includes(token)) score += 2;
			else if (keywords.includes(token)) score += 1;
		}

		if (score > 0 && score >= minScore) matches.push({ entry, score, index });
	});

	return matches
		.sort((left, right) => right.score - left.score || left.index - right.index)
		.slice(0, limit)
		.map((match) => match.entry);
}
