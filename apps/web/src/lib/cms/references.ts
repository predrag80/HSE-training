import type { Reference, ReferenceCollection } from '../../types/reference';
import { defaultLocale, type Locale } from '../../i18n/config';
import { fetchCmsJson } from './client';
import { CmsError } from './errors';
import { mapWordPressReferenceCollection } from './reference-mappers';

const REFERENCES_ENDPOINT = '/wp-json/hse/v1/references';
const MAX_HOMEPAGE_REFERENCES = 4;

export interface HomepageReferenceSection {
	readonly featured: readonly Reference[];
	readonly additional: readonly Reference[];
}

/** Returns the complete published Reference collection. */
export async function getReferences(locale: Locale = defaultLocale): Promise<ReferenceCollection> {
	return mapWordPressReferenceCollection(
		await fetchCmsJson(REFERENCES_ENDPOINT, { lang: locale }),
		locale,
	);
}

/** Returns the ordered one-to-four References selected for the Homepage. */
export async function getHomepageReferences(locale: Locale = defaultLocale): Promise<readonly Reference[]> {
	return (await getHomepageReferenceSection(locale)).featured;
}

/** Returns featured cards and the remaining testimonials for the expandable Homepage list. */
export async function getHomepageReferenceSection(locale: Locale = defaultLocale): Promise<HomepageReferenceSection> {
	const collection = await getReferences(locale);
	const references = collection.references.filter((reference) => reference.featuredOnHomepage);
	if (references.length === 0 || references.length > MAX_HOMEPAGE_REFERENCES) {
		throw new CmsError(
			'invalid-response',
			`CMS must return between 1 and ${MAX_HOMEPAGE_REFERENCES} Homepage References.`,
		);
	}

	return {
		featured: references,
		additional: collection.references.filter((reference) => !reference.featuredOnHomepage),
	};
}
