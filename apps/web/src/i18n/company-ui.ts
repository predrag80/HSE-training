import type { Locale } from './config';

const companyUi = {
	en: {
		metaTitle: 'About company | HSE Training',
		metaDescription: 'Discover the people, expertise and consulting approach behind HSE Training.',
		scrollToAbout: 'Scroll to about company',
		milestonesLabel: 'Company milestones',
		milestones: [
			{ year: 2010, title: 'Company founded', text: 'HSE Training was established' },
			{ year: 2014, title: 'Team expansion', text: 'Expert capacity expanded' },
			{ year: 2019, title: 'New programmes', text: 'Training portfolio developed' },
			{ year: 2021, title: 'International reach', text: 'Wider professional impact' },
		],
		timelineImageAlt: 'HSE Training team celebrating a milestone',
		agencyImageAlt: 'Business consultant working at a desk',
		agencyTitle: 'Professional HSE training and consulting.',
		agencyText: 'We help organisations improve health and safety knowledge, working practices and management systems through practical training and expert support.',
		agencyPoints: [
			'Practical knowledge for safer workplaces.',
			'Support for capable teams and responsible leaders.',
			'Advice adapted to real organisational needs.',
		],
		expertiseEyebrow: 'Work performance',
		expertiseTitle: 'Our expertise',
		expertiseDescription: 'Focused professional support for measurable workplace improvement.',
		expertise: [
			{ value: 96, title: 'HSE training' },
			{ value: 88, title: 'Management systems' },
			{ value: 90, title: 'Professional consulting' },
		],
		reasonsTitle: 'Why choose our company services?',
		reasonsText: 'We combine internationally recognised knowledge with practical experience and support tailored to each organisation.',
		reasonsCta: 'Get started now!',
		reasons: [
			{ title: 'Integrity', text: 'Professional and responsible cooperation.' },
			{ title: 'Analysis', text: 'Decisions grounded in real workplace needs.' },
			{ title: 'Leadership', text: 'Knowledge that strengthens teams and leaders.' },
			{ title: 'Delivery', text: 'Practical support focused on results.' },
		],
		trustedCompanies: 'Trusted companies',
	},
	sr: {
		metaTitle: 'O kompaniji | HSE Training',
		metaDescription: 'Upoznajte ljude, stručnost i konsultantski pristup kompanije HSE Training.',
		scrollToAbout: 'Pređite na sadržaj o kompaniji',
		milestonesLabel: 'Razvoj kompanije',
		milestones: [
			{ year: 2010, title: 'Osnivanje kompanije', text: 'Osnovan je HSE Training' },
			{ year: 2014, title: 'Proširenje tima', text: 'Prošireni stručni kapaciteti' },
			{ year: 2019, title: 'Novi programi', text: 'Razvijen portfolio obuka' },
			{ year: 2021, title: 'Međunarodni domet', text: 'Širi profesionalni uticaj' },
		],
		timelineImageAlt: 'HSE Training tim obeležava važan trenutak',
		agencyImageAlt: 'Poslovni konsultant radi za stolom',
		agencyTitle: 'Profesionalne HSE obuke i konsalting.',
		agencyText: 'Pomažemo organizacijama da unaprede znanje, radne prakse i sisteme upravljanja kroz praktične obuke i stručnu podršku.',
		agencyPoints: [
			'Praktično znanje za bezbednija radna mesta.',
			'Podrška sposobnim timovima i odgovornim liderima.',
			'Saveti prilagođeni stvarnim potrebama organizacije.',
		],
		expertiseEyebrow: 'Radni učinak',
		expertiseTitle: 'Naša stručnost',
		expertiseDescription: 'Usmerena stručna podrška za merljiva unapređenja na radnom mestu.',
		expertise: [
			{ value: 96, title: 'HSE obuke' },
			{ value: 88, title: 'Sistemi upravljanja' },
			{ value: 90, title: 'Stručni konsalting' },
		],
		reasonsTitle: 'Zašto izabrati usluge naše kompanije?',
		reasonsText: 'Spajamo međunarodno priznato znanje sa praktičnim iskustvom i podrškom prilagođenom svakoj organizaciji.',
		reasonsCta: 'Započnite saradnju!',
		reasons: [
			{ title: 'Integritet', text: 'Profesionalna i odgovorna saradnja.' },
			{ title: 'Analiza', text: 'Odluke zasnovane na stvarnim potrebama radnog mesta.' },
			{ title: 'Liderstvo', text: 'Znanje koje osnažuje timove i rukovodioce.' },
			{ title: 'Realizacija', text: 'Praktična podrška usmerena na rezultate.' },
		],
		trustedCompanies: 'Kompanije koje nam veruju',
	},
} as const;

export function getCompanyUiTranslations(locale: Locale) {
	return companyUi[locale];
}
