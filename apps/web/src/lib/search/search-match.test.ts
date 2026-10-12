import { describe, expect, it } from 'vitest';

import { findSearchResults, normalizeSearchText, parseSearchIndex, type SearchEntry } from './search-match';

function entry(overrides: Partial<SearchEntry>): SearchEntry {
	return {
		kind: 'page',
		title: 'Title',
		summary: '',
		url: '/',
		keywords: '',
		external: false,
		...overrides,
	};
}

const igc = entry({
	kind: 'course',
	title: 'NEBOSH International General Certificate',
	summary: 'Globally recognised health and safety qualification.',
	url: '/nebosh-international-general-certificate-in-occupational-health-and-safety/',
	keywords: 'nebosh igc',
});
const banksman = entry({ kind: 'training', title: 'Banksman / Slinger', summary: 'Lifting operations.', url: '/training/banksman-slinger/' });
const resource = entry({
	kind: 'resource',
	title: 'Risk assessment guidance',
	summary: 'Practical NEBOSH revision material.',
	url: 'https://www.hse.gov.uk/risk/',
	external: true,
});
const serbian = entry({ title: 'Bezbednost i zdravlje na radu', summary: 'Obuke za građevinarstvo i đake.', url: '/sr/' });

describe('site search matching', () => {
	it('folds case, diacritics and đ so Serbian queries match without accents', () => {
		expect(normalizeSearchText('  Građevinarstvo ČŠŽĆ  Đak ')).toBe('gradjevinarstvo cszc djak');
		expect(findSearchResults([serbian], 'gradjevinarstvo')).toEqual([serbian]);
		expect(findSearchResults([serbian], 'BEZBEDNOST zdravlje')).toEqual([serbian]);
	});

	it('requires every query word and ignores queries shorter than two characters', () => {
		expect(findSearchResults([igc, banksman], 'nebosh lifting')).toEqual([]);
		expect(findSearchResults([igc, banksman], 'n')).toEqual([]);
		expect(findSearchResults([igc, banksman], '   ')).toEqual([]);
	});

	it('ranks title matches above summary and keyword matches, keeping index order for ties', () => {
		expect(findSearchResults([resource, igc], 'nebosh')).toEqual([igc, resource]);
		expect(findSearchResults([igc], 'igc')).toEqual([igc]);
		expect(findSearchResults([igc, banksman, resource], 'a', 8)).toEqual([]);
	});

	it('limits the number of results', () => {
		const entries = Array.from({ length: 12 }, (_, index) => entry({ title: `Safety ${index}`, url: `/${index}/` }));

		expect(findSearchResults(entries, 'safety')).toHaveLength(8);
		expect(findSearchResults(entries, 'safety', 3).map((result) => result.url)).toEqual(['/0/', '/1/', '/2/']);
	});

	it('keeps only well-formed index entries with safe URLs', () => {
		expect(parseSearchIndex({ entries: [] })).toEqual([]);
		expect(parseSearchIndex([
			igc,
			resource,
			{ ...igc, kind: 'lesson' },
			{ ...igc, title: '' },
			{ ...igc, url: 'javascript:alert(1)' },
			{ ...igc, url: '//evil.example/' },
			{ ...igc, external: 'yes' },
			null,
		])).toEqual([igc, resource]);
	});
});
