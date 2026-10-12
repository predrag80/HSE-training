import { describe, expect, it } from 'vitest';

import type { Course } from '../../types/course';
import type { Resource } from '../../types/resource';
import type { Service } from '../../types/service';
import { createSearchEntries, htmlToSearchText, stripSiteNameSuffix } from './search-index';

const course: Course = {
	locale: 'sr',
	slug: 'nebosh-igc',
	courseKey: 'nebosh-igc',
	title: 'NEBOSH International General Certificate',
	shortDescription: 'Međunarodna kvalifikacija.',
	descriptionHtml: '<h2>Modul&nbsp;1</h2><p>Upravljanje &amp; kontrola</p><script>ignored()</script>',
	featuredImageUrl: null,
	visiblePrice: null,
	pageEyebrow: 'NEBOSH',
	homepageLabel: 'IGC',
	homepageCtaLabel: 'Saznajte više',
	featuredOnHomepage: true,
	status: 'publish',
};

const service: Service = {
	serviceKey: 'technical-safety',
	title: 'Tehnička bezbednost',
	cardLabel: 'Tehnika',
	shortDescription: 'Procena rizika.',
	detailedDescription: 'HAZOP i HAZID radionice.',
	cta: { label: 'Kontakt', url: '/contact/' },
	image: { url: 'https://cms.example/image.webp', alt: '', width: 10, height: 10 },
	featuredOnHomepage: true,
};

const resource: Resource = {
	resourceKey: 'iso-45001',
	title: 'ISO 45001',
	summary: 'Pregled standarda.',
	description: '<p>Standard za <strong>BZR</strong>.</p>',
	topic: 'Standardi',
	type: 'standard',
	featured: false,
	actionUrl: 'https://www.iso.org/standard/63787.html',
	isDownload: false,
	fileName: '',
	fileSize: 0,
	mimeType: '',
	image: null,
};

describe('site search index', () => {
	it('converts CMS HTML to bounded plain text without script content', () => {
		expect(htmlToSearchText(course.descriptionHtml)).toBe('Modul 1 Upravljanje & kontrola');
		expect(htmlToSearchText('<p>Dva&#8211;tri &#x2014; &unknown;</p>')).toBe('Dva–tri — &unknown;');
		expect(htmlToSearchText('one two three four', 9)).toBe('one two');
	});

	it('removes only the trailing site name from page titles', () => {
		expect(stripSiteNameSuffix('Kontakt | HSE Training')).toBe('Kontakt');
		expect(stripSiteNameSuffix('HSE Training akademija')).toBe('HSE Training akademija');
	});

	it('creates localized entries in course, training, page, service and resource order', () => {
		const entries = createSearchEntries(
			{
				pages: [{ path: '/contact/', title: 'Kontakt | HSE Training', description: 'Pišite nam.' }],
				courses: [course],
				trainings: [{ ...course, courseKey: 'banksman-slinger', slug: 'banksman-slinger', title: 'Banksman / Slinger' }],
				services: [service],
				resources: [resource],
			},
			'sr',
		);

		expect(entries).toEqual([
			{
				kind: 'course',
				title: 'NEBOSH International General Certificate',
				summary: 'Međunarodna kvalifikacija.',
				url: '/sr/nebosh-international-general-certificate-in-occupational-health-and-safety/',
				keywords: 'nebosh igc NEBOSH Modul 1 Upravljanje & kontrola',
				external: false,
			},
			{
				kind: 'training',
				title: 'Banksman / Slinger',
				summary: 'Međunarodna kvalifikacija.',
				url: '/sr/training/banksman-slinger/',
				keywords: 'banksman slinger NEBOSH Modul 1 Upravljanje & kontrola',
				external: false,
			},
			{ kind: 'page', title: 'Kontakt', summary: 'Pišite nam.', url: '/sr/contact/', keywords: '', external: false },
			{
				kind: 'service',
				title: 'Tehnička bezbednost',
				summary: 'Procena rizika.',
				url: '/sr/browse-hse-talent/#consulting-services-title',
				keywords: 'Tehnika HAZOP i HAZID radionice.',
				external: false,
			},
			{
				kind: 'resource',
				title: 'ISO 45001',
				summary: 'Pregled standarda.',
				url: 'https://www.iso.org/standard/63787.html',
				keywords: 'Standardi standard Standard za BZR .',
				external: true,
			},
		]);
	});
});
