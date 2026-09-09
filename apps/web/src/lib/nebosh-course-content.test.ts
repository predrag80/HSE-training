import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { Course } from '../types/course';
import { getCourseByKey } from './cms';
import { loadRequiredNeboshCourse } from './nebosh-course-content';

vi.mock('./cms', () => ({ getCourseByKey: vi.fn() }));

const serbianCourse: Course = {
	locale: 'sr',
	slug: 'nebosh-medjunarodni-opsti-sertifikat',
	courseKey: 'nebosh-igc',
	title: 'NEBOSH International General Certificate',
	shortDescription: 'Srpski opis kursa.',
	descriptionHtml: '<p>Detalji kursa.</p>',
	featuredImageUrl: null,
	visiblePrice: 'Cena na upit',
	homepageLabel: 'NEBOSH kvalifikacija',
	homepageCtaLabel: 'Pogledajte kurs',
	featuredOnHomepage: true,
	status: 'publish',
};

beforeEach(() => vi.mocked(getCourseByKey).mockReset());

describe('loadRequiredNeboshCourse', () => {
	it('loads the requested Course translation by stable key', async () => {
		vi.mocked(getCourseByKey).mockResolvedValue(serbianCourse);

		await expect(loadRequiredNeboshCourse('nebosh-igc', 'sr')).resolves.toBe(serbianCourse);
		expect(getCourseByKey).toHaveBeenCalledWith('nebosh-igc', 'sr');
	});

	it('fails when the requested language variant is missing', async () => {
		vi.mocked(getCourseByKey).mockResolvedValue(null);

		await expect(loadRequiredNeboshCourse('nebosh-igc', 'sr')).rejects.toMatchObject({
			code: 'invalid-response',
			message: 'CMS is missing the sr Course translation for course_key nebosh-igc.',
		});
	});
});
