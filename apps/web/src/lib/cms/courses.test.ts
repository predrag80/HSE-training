import { afterEach, describe, expect, it, vi } from 'vitest';

import { getCourseByKey, getCourseBySlug, getCourses, getHomepageCourses } from './courses';

const rawCourse = {
	locale: 'en',
	slug: 'nebosh-international-general-certificate',
	status: 'publish',
	title: { rendered: 'NEBOSH International General Certificate' },
	content: { rendered: '<p>Course description.</p>', protected: false },
	featured_media: 0,
	meta: {
		course_key: 'nebosh-igc',
		short_description: 'Course summary.',
		visible_price: '€499',
		homepage_label: 'Popular course',
		homepage_cta_label: 'View course',
		featured_on_homepage: true,
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
		expect(requestedUrl.searchParams.get('lang')).toBe('en');
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

describe('getHomepageCourses', () => {
	const homepageCourses = [
		rawCourse,
		{
			...rawCourse,
			slug: 'nebosh-award-environmental-awareness-at-work',
			meta: { ...rawCourse.meta, course_key: 'nebosh-eaw', featured_on_homepage: false },
		},
		{
			...rawCourse,
			slug: 'nebosh-international-oilgas-certificate',
			meta: { ...rawCourse.meta, course_key: 'nebosh-iogc', featured_on_homepage: false },
		},
	];

	it('returns IGC, EAW, and IOGC in the fixed Homepage order', async () => {
		const fetchMock = vi.fn().mockResolvedValue(jsonResponse([...homepageCourses].reverse()));
		vi.stubGlobal('fetch', fetchMock);

		await expect(getHomepageCourses()).resolves.toEqual(
			['nebosh-igc', 'nebosh-eaw', 'nebosh-iogc'].map((courseKey) =>
				expect.objectContaining({ courseKey }),
			),
		);

		const requestedUrl = new URL(String(fetchMock.mock.calls[0]?.[0]));
		expect(requestedUrl.searchParams.has('featured_on_homepage')).toBe(false);
		expect(requestedUrl.searchParams.get('orderby')).toBe('date');
		expect(requestedUrl.searchParams.get('order')).toBe('desc');
		expect(requestedUrl.searchParams.get('per_page')).toBe('100');
		expect(requestedUrl.searchParams.get('lang')).toBe('en');
	});

	it('requests and validates all three Serbian Homepage Courses', async () => {
		const fetchMock = vi.fn().mockResolvedValue(
			jsonResponse(homepageCourses.map((course) => ({ ...course, locale: 'sr' }))),
		);
		vi.stubGlobal('fetch', fetchMock);

		const courses = await getHomepageCourses('sr');
		expect(courses).toHaveLength(3);
		expect(courses).toEqual(
			expect.arrayContaining([expect.objectContaining({ locale: 'sr', courseKey: 'nebosh-eaw' })]),
		);
		expect(new URL(String(fetchMock.mock.calls[0]?.[0])).searchParams.get('lang')).toBe('sr');
	});

	it('rejects a Course returned in the wrong language', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse([rawCourse])));

		await expect(getCourses('sr')).rejects.toMatchObject({ code: 'invalid-response' });
	});

	it('fills incomplete Homepage card fields with safe localized copy', async () => {
		const incompleteCourses = homepageCourses.map((course) =>
			course.meta.course_key === 'nebosh-eaw'
				? {
						...course,
						meta: {
							...course.meta,
							homepage_cta_label: '',
							homepage_label: '',
							short_description: '',
							visible_price: '',
						},
					}
				: course,
		);
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse(incompleteCourses)));

		await expect(getHomepageCourses()).resolves.toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					courseKey: 'nebosh-eaw',
					homepageCtaLabel: 'View course',
					homepageLabel: 'NEBOSH qualification',
					visiblePrice: 'Price on request',
				}),
			]),
		);
	});

	it('provides the approved fallback when a Homepage Course is missing from CMS', async () => {
		vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse(homepageCourses.slice(0, 2))));

		await expect(getHomepageCourses()).resolves.toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					courseKey: 'nebosh-iogc',
					title: 'NEBOSH International Oil & Gas Certificate',
				}),
			]),
		);
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
		expect(requestedUrl.searchParams.get('lang')).toBe('en');
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
		expect(requestedUrl.searchParams.get('lang')).toBe('en');
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
