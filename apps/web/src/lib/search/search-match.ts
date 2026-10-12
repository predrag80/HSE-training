/** Client-safe site search matching over the static per-locale search index. */

export type SearchEntryKind = 'page' | 'course' | 'training' | 'service' | 'resource';

export interface SearchEntry {
	readonly kind: SearchEntryKind;
	readonly title: string;
	readonly summary: string;
	readonly url: string;
	/** Additional searchable text that is never displayed. */
	readonly keywords: string;
	/** Off-site links and downloads open in a new tab, as on the Resources page. */
	readonly external: boolean;
}

export const SEARCH_MIN_QUERY_LENGTH = 2;
export const SEARCH_RESULT_LIMIT = 8;

const SEARCH_ENTRY_KINDS: ReadonlySet<string> = new Set(['page', 'course', 'training', 'service', 'resource']);

/** Lowercase, fold diacritics, and spell đ as dj so Serbian queries match without accents. */
export function normalizeSearchText(value: string): string {
	return value
		.toLocaleLowerCase('sr-Latn')
		.replace(/đ/g, 'dj')
		.normalize('NFD')
		.replace(/[̀-ͯ]/g, '')
		.replace(/[^\p{L}\p{N}]+/gu, ' ')
		.trim();
}

function isSearchEntry(value: unknown): value is SearchEntry {
	if (typeof value !== 'object' || value === null) return false;

	const entry = value as Record<string, unknown>;
	return typeof entry.kind === 'string'
		&& SEARCH_ENTRY_KINDS.has(entry.kind)
		&& typeof entry.title === 'string'
		&& entry.title.length > 0
		&& typeof entry.summary === 'string'
		&& typeof entry.url === 'string'
		&& (entry.url.startsWith('/') || /^https?:\/\//.test(entry.url))
		&& !entry.url.startsWith('//')
		&& typeof entry.keywords === 'string'
		&& typeof entry.external === 'boolean';
}

/** Accept only well-formed entries from the downloaded index. */
export function parseSearchIndex(value: unknown): SearchEntry[] {
	return Array.isArray(value) ? value.filter(isSearchEntry) : [];
}

interface PreparedEntry {
	readonly entry: SearchEntry;
	readonly title: string;
	readonly summary: string;
	readonly keywords: string;
}

function scoreToken(prepared: PreparedEntry, token: string): number {
	if (` ${prepared.title}`.includes(` ${token}`)) return 6;
	if (prepared.title.includes(token)) return 4;
	if (prepared.summary.includes(token)) return 2;
	if (prepared.keywords.includes(token)) return 1;

	return 0;
}

/**
 * Return entries that contain every query word, best title matches first and
 * otherwise in index order.
 */
export function findSearchResults(
	entries: readonly SearchEntry[],
	query: string,
	limit = SEARCH_RESULT_LIMIT,
): SearchEntry[] {
	const normalizedQuery = normalizeSearchText(query);
	if (normalizedQuery.length < SEARCH_MIN_QUERY_LENGTH) return [];

	const tokens = normalizedQuery.split(' ');
	const matches: { entry: SearchEntry; score: number; index: number }[] = [];

	entries.forEach((entry, index) => {
		const prepared: PreparedEntry = {
			entry,
			title: normalizeSearchText(entry.title),
			summary: normalizeSearchText(entry.summary),
			keywords: normalizeSearchText(entry.keywords),
		};
		let score = 0;

		for (const token of tokens) {
			const tokenScore = scoreToken(prepared, token);
			if (tokenScore === 0) return;
			score += tokenScore;
		}

		matches.push({ entry, score, index });
	});

	return matches
		.sort((left, right) => right.score - left.score || left.index - right.index)
		.slice(0, limit)
		.map((match) => match.entry);
}
