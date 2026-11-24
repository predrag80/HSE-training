/** Build-time assembly and validation of the localized site assistant content. */

import { getAssistantUiTranslations } from '../../i18n/assistant-ui';
import type { Locale } from '../../i18n/config';
import { getCompanyUiTranslations } from '../../i18n/company-ui';
import { getConsultingUiTranslations } from '../../i18n/consulting-ui';
import { getCourseUiTranslations } from '../../i18n/course-ui';
import { getOtherCoursesUiTranslations } from '../../i18n/other-courses-ui';
import { getAvailablePublicPath, getContactFormPath, hasLocalizedContentPath } from '../../i18n/routes';
import { getUiTranslations } from '../../i18n/ui';
import { contactDetails } from '../contact';
import { normalizeSearchText } from '../search/search-match';
import {
	ASSISTANT_FALLBACK_TOPIC_ID,
	ASSISTANT_START_TOPIC_ID,
	isAllowedAssistantLink,
	type AssistantContent,
	type AssistantLink,
	type AssistantTopic,
} from './assistant-match';

const MAIN_TOPIC_IDS = ['nebosh', 'training', 'access', 'consulting', 'company', 'contact'] as const;

/**
 * Word stems typed visitors may use, in English and Serbian, for each topic.
 * They are normalized like search text, so they are written without
 * diacritics. A typed word matches when it starts with a stem.
 */
const TOPIC_KEYWORDS: Readonly<Record<string, readonly string[]>> = {
	'igc-requirements': [
		'predznanj', 'prethodn', 'uslov', 'engles', 'znanj', 'iskustv',
		'requir', 'prior', 'previous', 'english', 'knowledge', 'experience', 'prerequisit',
	],
	'igc-audience': ['namenj', 'pohadj', 'polaznic', 'rukovodi', 'supervizor', 'audience', 'suitable', 'manager', 'supervisor'],
	iogc: ['iogc', 'oil', 'gas', 'naft', 'povuc'],
	access: [
		'pristup', 'nalog', 'platform', 'elearning', 'learning', 'lms', 'kupi', 'kupov', 'plat', 'plac', 'uplat', 'cen',
		'kost', 'produz', 'access', 'account', 'buy', 'purchas', 'pay', 'price', 'cost', 'fee', 'extension', 'refund',
		'povrac', 'odusta', 'withdrawal',
	],
	training: [
		'obuk', 'trening', 'banksman', 'slinger', 'dizalic', 'podizan', 'trener', 'predavac', 'meri',
		'training', 'crane', 'lifting', 'trainer', 'custom',
	],
	nebosh: ['nebosh', 'igc', 'eaw', 'kvalifikac', 'sertifik', 'ispit', 'ekolog', 'sredin', 'qualificat', 'certific', 'exam', 'assessment', 'environment'],
	consulting: ['konsalt', 'konsult', 'savet', 'strucnjak', 'talent', 'directory', 'angazov', 'consult', 'advice', 'expert', 'hire', 'recruit'],
	contact: [
		'kontakt', 'telefon', 'poziv', 'mail', 'poruk', 'posalj', 'pisem', 'pisite', 'whatsapp', 'upit', 'adres', 'nalaz',
		'lokacij', 'kancelarij', 'contact', 'phone', 'call', 'message', 'enquir', 'inquir', 'address', 'location', 'office',
	],
	company: ['kompanij', 'firm', 'galerij', 'resurs', 'referenc', 'klijent', 'istorij', 'company', 'team', 'gallery', 'resource', 'reference', 'client', 'history'],
};

function page(label: string, path: string, locale: Locale): AssistantLink {
	return { label, href: getAvailablePublicPath(path, locale), kind: 'page' };
}

function fill(template: string, values: Readonly<Record<string, string>>): string {
	return template.replace(/\{(\w+)\}/g, (placeholder, key: string) => values[key] ?? placeholder);
}

/** Assemble the assistant from the site's existing approved copy and public routes. */
export function createAssistantContent(locale: Locale): AssistantContent {
	const copy = getAssistantUiTranslations(locale);
	const ui = getUiTranslations(locale);
	const courseUi = getCourseUiTranslations(locale);
	const igc = courseUi.nebosh.igc;
	const training = getOtherCoursesUiTranslations(locale);
	const consulting = getConsultingUiTranslations(locale);
	const company = getCompanyUiTranslations(locale);
	const [audienceFaq, requirementsFaq, accessFaq] = igc.faqs;

	const contactForm: AssistantLink = { label: copy.panel.contactForm, href: getContactFormPath(locale), kind: 'page' };
	const directContactLinks: AssistantLink[] = [
		contactForm,
		{ label: contactDetails.phone, href: contactDetails.phoneHref, kind: 'phone' },
		{ label: contactDetails.email, href: contactDetails.emailHref, kind: 'email' },
	];
	const igcPage = page(igc.fallbackTitle, '/nebosh-international-general-certificate-in-occupational-health-and-safety/', locale);
	const keywords = (id: string) => (TOPIC_KEYWORDS[id] ?? []).map((stem) => normalizeSearchText(stem));

	const topics: AssistantTopic[] = [
		{
			id: ASSISTANT_START_TOPIC_ID,
			prompt: copy.topics.startPrompt,
			answer: copy.topics.greeting,
			links: [],
			next: MAIN_TOPIC_IDS,
			keywords: [],
		},
		{
			id: 'igc-requirements',
			prompt: requirementsFaq.question,
			answer: [requirementsFaq.answer],
			links: [igcPage],
			next: ['igc-audience', 'access', 'contact'],
			keywords: keywords('igc-requirements'),
		},
		{
			id: 'igc-audience',
			prompt: audienceFaq.question,
			answer: [audienceFaq.answer],
			links: [igcPage],
			next: ['igc-requirements', 'access', 'contact'],
			keywords: keywords('igc-audience'),
		},
		{
			id: 'iogc',
			prompt: copy.topics.iogcPrompt,
			answer: [...courseUi.nebosh.iogc.statusParagraphs],
			links: [page(ui.navigation.neboshIogc, '/nebosh-international-oilgas-certificate/', locale), contactForm],
			next: ['nebosh', 'contact'],
			keywords: keywords('iogc'),
		},
		{
			id: 'access',
			prompt: copy.topics.accessPrompt,
			answer: [
				fill(copy.topics.accessPriceText, {
					allCourses: ui.homepage.pricing.allCourses,
					priceOnRequest: ui.homepage.pricing.priceOnRequest,
					buyNow: courseUi.card.buyNow,
				}),
				accessFaq.answer,
			],
			links: [
				page(ui.homepage.pricing.allCourses, '/courses/', locale),
				page(ui.footer.terms, '/terms-and-conditions/', locale),
				page(ui.footer.withdrawalForm, '/withdrawal-form/', locale),
			],
			next: ['nebosh', 'training', 'contact'],
			keywords: keywords('access'),
		},
		{
			id: 'training',
			prompt: copy.topics.trainingPrompt,
			answer: [
				training.overview.selectorTitle,
				...training.overview.courses.map((course) => `${course.title}: ${course.details}.`),
				training.common.datesText,
			],
			links: [
				page(ui.navigation.otherCourses, '/training/', locale),
				...training.overview.courses.map((course) => page(course.title, course.href, locale)),
			],
			next: ['nebosh', 'access', 'contact'],
			keywords: keywords('training'),
		},
		{
			id: 'nebosh',
			prompt: copy.topics.neboshPrompt,
			answer: [ui.homepage.pricing.description],
			links: [
				page(courseUi.index.neboshTitle, '/nebosh/', locale),
				igcPage,
				page(ui.navigation.neboshEaw, '/nebosh-award-environmental-awareness-at-work/', locale),
			],
			next: ['igc-audience', 'igc-requirements', 'access', 'iogc'],
			keywords: keywords('nebosh'),
		},
		{
			id: 'consulting',
			prompt: copy.topics.consultingPrompt,
			answer: [consulting.introParagraphs[0]],
			links: [page(ui.navigation.consulting, '/browse-hse-talent/', locale), contactForm],
			next: ['company', 'contact'],
			keywords: keywords('consulting'),
		},
		{
			id: 'contact',
			prompt: copy.topics.contactPrompt,
			answer: [ui.footer.enquiryDescription, copy.topics.contactText],
			links: directContactLinks,
			next: ['nebosh', 'training', 'consulting'],
			keywords: keywords('contact'),
		},
		{
			id: 'company',
			prompt: copy.topics.companyPrompt,
			answer: [company.agencyText],
			links: [
				page(ui.footer.company, '/company/', locale),
				page(ui.footer.gallery, '/gallery/', locale),
				page(ui.footer.resources, '/resources/', locale),
			],
			next: ['nebosh', 'training', 'consulting', 'contact'],
			keywords: keywords('company'),
		},
		{
			id: ASSISTANT_FALLBACK_TOPIC_ID,
			prompt: copy.topics.fallbackPrompt,
			answer: copy.topics.fallback,
			links: directContactLinks,
			next: MAIN_TOPIC_IDS,
			keywords: [],
		},
	];

	return { topics };
}

function stripQueryAndHash(href: string): string {
	return href.replace(/[?#].*$/, '');
}

/**
 * Fail the build unless every topic is complete, every follow-up exists, and
 * every link is a published page of this website or the company's own phone or
 * e-mail. This is what keeps the assistant from pointing visitors elsewhere.
 */
export function assertAssistantContent(content: AssistantContent): void {
	const ids = new Set<string>();
	for (const topic of content.topics) {
		if (ids.has(topic.id)) throw new Error(`Assistant topic "${topic.id}" is defined more than once.`);
		ids.add(topic.id);
	}

	for (const required of [ASSISTANT_START_TOPIC_ID, ASSISTANT_FALLBACK_TOPIC_ID]) {
		if (!ids.has(required)) throw new Error(`Assistant topic "${required}" is missing.`);
	}

	for (const topic of content.topics) {
		if (!topic.prompt.trim()) throw new Error(`Assistant topic "${topic.id}" has no prompt.`);
		if (topic.answer.length === 0 || topic.answer.some((paragraph) => !paragraph.trim())) {
			throw new Error(`Assistant topic "${topic.id}" has an empty answer.`);
		}

		for (const nextId of topic.next) {
			if (!ids.has(nextId) || nextId === ASSISTANT_START_TOPIC_ID || nextId === ASSISTANT_FALLBACK_TOPIC_ID) {
				throw new Error(`Assistant topic "${topic.id}" offers unknown follow-up "${nextId}".`);
			}
		}

		const isMatchable = topic.id !== ASSISTANT_START_TOPIC_ID && topic.id !== ASSISTANT_FALLBACK_TOPIC_ID;
		if (isMatchable && topic.keywords.length === 0) throw new Error(`Assistant topic "${topic.id}" has no keywords.`);
		for (const keyword of topic.keywords) {
			if (!/^[\p{L}\p{N}]{3,}$/u.test(keyword)) {
				throw new Error(`Assistant topic "${topic.id}" has an invalid keyword "${keyword}".`);
			}
		}

		for (const link of topic.links) {
			if (!link.label.trim() || !isAllowedAssistantLink(link)) {
				throw new Error(`Assistant topic "${topic.id}" links outside this website: "${link.href}".`);
			}
			if (link.kind === 'page' && !hasLocalizedContentPath(stripQueryAndHash(link.href))) {
				throw new Error(`Assistant topic "${topic.id}" links to an unpublished page: "${link.href}".`);
			}
		}
	}
}

/** Content for one locale's static `assistant.json`; invalid content fails the build. */
export function loadAssistantContent(locale: Locale): AssistantContent {
	const content = createAssistantContent(locale);
	assertAssistantContent(content);
	return content;
}
