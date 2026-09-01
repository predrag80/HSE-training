import { afterEach, describe, expect, it, vi } from 'vitest';

import { getCourseByKey, getCourseBySlug, getCourses } from './courses';

const rawCourse = {
	slug: 'nebosh-international-general-certificate',
	status: 'publish',
	title: { rendered: 'NEBOSH International General Certificate' },
	content: { rendered: '<p>Course description.</p>', protected: false },
	featured_media: 0,
	meta: {
		course_key: 'nebosh-igc',
		short_description: 'Course summary.',
		visible_price: '€499',
	},
};

function jsonResponse(body: unknown, status = 200): Response {
	return new Response(JSON.stringify(body), {
		status,
		headers: { 'Content-Type': 'application/json' },
	});
}

afterEach(() => {
	vi.unstubAllGlobals();
});

describe('getCourses', () => {
	it('returns mapped published Courses in the CMS response order', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse([rawCourse]));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getCourses()).resolves.toEqual([
			expect.objectContaining({
				courseKey: 'nebosh-igc',
				slug: 'nebosh-international-general-certificate',
				status: 'publish',
			}),
		]);

		const requestedUrl = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(requestedUrl.pathname).toBe('/wp-json/wp/v2/courses');
		expect(requestedUrl.searchParams.get('_embed')).toBe('wp:featuredmedia');
		expect(requestedUrl.searchParams.get('per_page')).toBe('100');
		expect(requestedUrl.searchParams.get('orderby')).toBe('date');
		expect(requestedUrl.searchParams.get('order')).toBe('desc');
	});

	it('returns an empty array when WordPress has no published Courses', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse([])));

		await expect(getCourses()).resolves.toEqual([]);
	});

	it('throws a typed HTTP error for a non-successful CMS response', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({ code: 'rest_error' }, 503)));

		await expect(getCourses()).rejects.toMatchObject({
			code: 'http',
			status: 503,
		});
	});

	it('throws an invalid-response error when the collection is not an array', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({ courses: [] })));

		await expect(getCourses()).rejects.toMatchObject({
			code: 'invalid-response',
		});
	});

	it('throws an invalid-response error when CMS JSON cannot be parsed', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{', { status: 200 })));

		await expect(getCourses()).rejects.toMatchObject({
			code: 'invalid-response',
		});
	});

	it('throws an unavailable error when the CMS request fails', async () => {
		vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('connection refused')));

		await expect(getCourses()).rejects.toMatchObject({
			code: 'unavailable',
			status: null,
		});
	});
});

describe('getCourseBySlug', () => {
	it('returns the published Course for an existing slug', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse([rawCourse]));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getCourseBySlug('nebosh-international-general-certificate')).resolves.toMatchObject({
			courseKey: 'nebosh-igc',
			slug: 'nebosh-international-general-certificate',
		});

		const requestedUrl = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(requestedUrl.searchParams.get('slug')).toBe(
			'nebosh-international-general-certificate',
		);
	});

	it('returns null when no published Course has the slug', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse([])));

		await expect(getCourseBySlug('missing-course')).resolves.toBeNull();
	});

	it('keeps slug query syntax inside the encoded slug parameter', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse([]));
		vi.stubGlobal('fetch', fetchMock);

		await getCourseBySlug('nebosh&status=draft');

		const requestedUrl = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(requestedUrl.searchParams.get('slug')).toBe('nebosh&status=draft');
		expect(requestedUrl.searchParams.has('status')).toBe(false);
	});

	it('throws an invalid-response error for a malformed matching Course', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse([{ slug: 'broken' }])));

		await expect(getCourseBySlug('broken')).rejects.toMatchObject({
			code: 'invalid-response',
		});
	});

	it('throws a typed HTTP error instead of returning null when the CMS fails', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({}, 500)));

		await expect(getCourseBySlug('nebosh-igc')).rejects.toMatchObject({
			code: 'http',
			status: 500,
		});
	});
});

describe('getCourseByKey', () => {
	it('uses the server-side course_key lookup and returns the matching Course', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse([rawCourse]));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getCourseByKey('nebosh-igc')).resolves.toMatchObject({
			courseKey: 'nebosh-igc',
		});

		const requestedUrl = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(requestedUrl.searchParams.get('course_key')).toBe('nebosh-igc');
	});

	it('returns null when the course_key is unknown', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse([])));

		await expect(getCourseByKey('unknown-course')).resolves.toBeNull();
	});

	it('rejects an unexpected duplicate course_key response', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse([rawCourse, rawCourse])));

		await expect(getCourseByKey('nebosh-igc')).rejects.toMatchObject({
			code: 'invalid-response',
		});
	});

	it('rejects a non-canonical course_key without requesting WordPress', async () => {
		const fetchMock = vi.fn();
		vi.stubGlobal('fetch', fetchMock);

		await expect(getCourseByKey('NEBOSH IGC')).rejects.toMatchObject({
			code: 'invalid-query',
		});
		expect(fetchMock).not.toHaveBeenCalled();
	});
});
