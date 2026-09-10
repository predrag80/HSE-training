import type {
	CompanyImage,
	CompanyLink,
	CompanyPageContent,
	CompanyProfile,
	CompanyTeamMember,
	CompanyValue,
	CompanyValueIcon,
} from '../../types/company';
import type { Locale } from '../../i18n/config';
import { CmsError } from './errors';
import type { WordPressCompanyPageDto, WordPressCompanyProfileDto } from './types';

const CONTENT_KEY_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
const ANCHOR_PATTERN = /^#[A-Za-z][A-Za-z0-9_-]*$/;
const VALUE_ICONS = new Set<CompanyValueIcon>(['training', 'management', 'consultancy']);

function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function invalidCompany(reason: string): never {
	throw new CmsError('invalid-response', `CMS returned invalid Company content: ${reason}.`);
}

function requireString(value: unknown, field: string, allowEmpty = false): string {
	if (typeof value !== 'string' || (!allowEmpty && value.trim().length === 0)) {
		return invalidCompany(`${field} is required`);
	}

	return value;
}

function mapLink(value: unknown, field: string): CompanyLink {
	if (!isRecord(value)) return invalidCompany(`${field} must be an object`);

	const url = requireString(value.url, `${field}.url`);
	if (!ANCHOR_PATTERN.test(url) && !(url.startsWith('/') && !url.startsWith('//'))) {
		let parsed: URL;
		try {
			parsed = new URL(url);
		} catch {
			return invalidCompany(`${field}.url must be a valid URL, internal path, or anchor`);
		}
		if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
			return invalidCompany(`${field}.url must use HTTP or HTTPS`);
		}
	}

	return {
		label: requireString(value.label, `${field}.label`),
		url,
	};
}

function mapImage(value: unknown, field: string): CompanyImage {
	if (!isRecord(value)) return invalidCompany(`${field} must be an object`);

	const url = requireString(value.url, `${field}.url`);
	let parsed: URL;
	try {
		parsed = new URL(url);
	} catch {
		return invalidCompany(`${field}.url must be a valid URL`);
	}
	if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
		return invalidCompany(`${field}.url must use HTTP or HTTPS`);
	}

	if (!Number.isInteger(value.width) || (value.width as number) <= 0) {
		return invalidCompany(`${field}.width must be a positive integer`);
	}
	if (!Number.isInteger(value.height) || (value.height as number) <= 0) {
		return invalidCompany(`${field}.height must be a positive integer`);
	}

	return {
		url,
		alt: requireString(value.alt, `${field}.alt`, true),
		width: value.width as number,
		height: value.height as number,
	};
}

function mapValue(value: unknown, index: number): CompanyValue {
	if (!isRecord(value)) return invalidCompany(`values[${index}] must be an object`);

	const valueKey = requireString(value.value_key, `values[${index}].value_key`);
	if (!CONTENT_KEY_PATTERN.test(valueKey)) {
		return invalidCompany(`values[${index}].value_key must be canonical`);
	}
	if (typeof value.icon_key !== 'string' || !VALUE_ICONS.has(value.icon_key as CompanyValueIcon)) {
		return invalidCompany(`values[${index}].icon_key is unsupported`);
	}

	return {
		valueKey,
		iconKey: value.icon_key as CompanyValueIcon,
		title: requireString(value.title, `values[${index}].title`),
		description: requireString(value.description, `values[${index}].description`),
	};
}

function mapTeamMember(value: unknown, index: number): CompanyTeamMember {
	if (!isRecord(value)) return invalidCompany(`team[${index}] must be an object`);

	const memberKey = requireString(value.member_key, `team[${index}].member_key`);
	if (!CONTENT_KEY_PATTERN.test(memberKey)) {
		return invalidCompany(`team[${index}].member_key must be canonical`);
	}

	return {
		memberKey,
		name: requireString(value.name, `team[${index}].name`),
		role: requireString(value.role, `team[${index}].role`),
		image: value.image === null || value.image === undefined
			? null
			: mapImage(value.image, `team[${index}].image`),
	};
}

function defaultTeam(locale: Locale): readonly CompanyTeamMember[] {
	return [
		{ memberKey: 'ana-springfield', name: 'Ana Springfield', role: locale === 'sr' ? 'Direktorka' : 'Director', image: null },
		{ memberKey: 'john-springfield', name: 'John Springfield', role: locale === 'sr' ? 'Direktor operacija' : 'Director of Operations', image: null },
		{ memberKey: 'biljana-stojanovic', name: 'Biljana Stojanovic', role: locale === 'sr' ? 'Finansijska direktorka' : 'Financial Director', image: null },
		{ memberKey: 'kristina-atanaskovic', name: 'Kristina Atanaskovic', role: locale === 'sr' ? 'Konsultantkinja' : 'Consultant', image: null },
	];
}

export function mapWordPressCompanyProfile(value: unknown, locale: Locale = 'en'): CompanyProfile {
	if (
		!isRecord(value) ||
		!Array.isArray(value.values) ||
		value.values.length !== 3
	) {
		return invalidCompany('profile must contain exactly three value highlights');
	}
	if (value.team !== undefined && (!Array.isArray(value.team) || value.team.length !== 4)) {
		return invalidCompany('profile team must contain exactly four members');
	}

	const profile = value as unknown as WordPressCompanyProfileDto;

	return {
		eyebrow: requireString(profile.eyebrow, 'profile.eyebrow'),
		headline: requireString(profile.headline, 'profile.headline'),
		description: requireString(profile.description, 'profile.description'),
		primaryImage: mapImage(profile.primary_image, 'profile.primary_image'),
		secondaryImage: mapImage(profile.secondary_image, 'profile.secondary_image'),
		primaryCta: mapLink(profile.primary_cta, 'profile.primary_cta'),
		secondaryCta: mapLink(profile.secondary_cta, 'profile.secondary_cta'),
		signatureLabel: requireString(profile.signature_label, 'profile.signature_label'),
		values: profile.values.map(mapValue),
		team: Array.isArray(profile.team) ? profile.team.map(mapTeamMember) : defaultTeam(locale),
	};
}

export function mapWordPressCompanyPage(value: unknown, locale: Locale = 'en'): CompanyPageContent {
	if (!isRecord(value) || !isRecord(value.hero) || !isRecord(value.profile)) {
		return invalidCompany('expected a page object with hero and profile');
	}
	if (value.schema_version !== 1) return invalidCompany('schema_version must be 1');
	if (value.page_key !== 'company') return invalidCompany('page_key must be company');
	if (value.locale !== locale) return invalidCompany(`locale must be ${locale}`);

	const page = value as unknown as WordPressCompanyPageDto;

	return {
		schemaVersion: page.schema_version,
		pageKey: page.page_key,
		locale,
		hero: {
			eyebrow: requireString(page.hero.eyebrow, 'hero.eyebrow'),
			title: requireString(page.hero.title, 'hero.title'),
		},
		profile: mapWordPressCompanyProfile(page.profile, locale),
		introCta: mapLink(page.intro_cta, 'intro_cta'),
	};
}
