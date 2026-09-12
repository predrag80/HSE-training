import type { Locale } from './config';

const companyUi = {
	en: {
		metaTitle: 'About company | HSE Training',
		metaDescription: 'Discover the people, expertise and consulting approach behind HSE Training.',
		scrollToAbout: 'Scroll to about company',
		milestonesLabel: 'Company milestones',
		milestones: [
			{ year: 2013, title: 'The Beginning', text: 'HSE Training d.o.o. was founded.' },
			{ year: 2014, title: 'A Regional Milestone', text: 'Became the first NEBOSH Accredited Centre in the Balkans.' },
			{ year: 2019, title: 'Growing Stronger', text: 'Expanded into new office premises.' },
			{ year: 2024, title: 'Recognised for Excellence', text: 'Achieved NEBOSH Silver Learning Partner Accreditation.' },
			{ year: 2025, title: 'Going Digital', text: 'Successfully launched our online training platform.' },
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
		expertise: [
			{
				title: 'HSE training',
				description: ['Focused professional support for measurable workplace improvement.'],
			},
			{
				title: 'HSE Directory',
				description: [
					'For Employers & Recruiters: Find experienced HSE professionals when you need them.',
					'For HSE Professionals: Get discovered by employers looking for your skills and experience.',
				],
			},
			{
				title: 'HSE Coaching (new)',
				description: ['1-1 HSE Coaching on how you can become a more effective leader.'],
			},
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
		clientsEyebrow: 'Organisations we support',
		clientsTitle: 'Clients who have trusted HSE Training.',
	},
	sr: {
		metaTitle: 'O kompaniji | HSE Training',
		metaDescription: 'Upoznajte ljude, stručnost i konsultantski pristup kompanije HSE Training.',
		scrollToAbout: 'Pređite na sadržaj o kompaniji',
		milestonesLabel: 'Razvoj kompanije',
		milestones: [
			{ year: 2013, title: 'Početak', text: 'Osnovan je HSE Training d.o.o.' },
			{ year: 2014, title: 'Regionalna prekretnica', text: 'Postali smo prvi NEBOSH akreditovani centar na Balkanu.' },
			{ year: 2019, title: 'Dalji rast', text: 'Preselili smo se u veće poslovne prostorije.' },
			{ year: 2024, title: 'Priznanje za izvrsnost', text: 'Stekli smo NEBOSH Silver Learning Partner akreditaciju.' },
			{ year: 2025, title: 'Digitalni razvoj', text: 'Uspešno smo pokrenuli platformu za onlajn obuke.' },
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
		expertise: [
			{
				title: 'HSE obuke',
				description: ['Usmerena stručna podrška za merljiva unapređenja na radnom mestu.'],
			},
			{
				title: 'HSE Directory',
				description: [
					'Za poslodavce i regrutere: Pronađite iskusne HSE stručnjake onda kada su vam potrebni.',
					'Za HSE stručnjake: Budite dostupni poslodavcima koji traže vaše veštine i iskustvo.',
				],
			},
			{
				title: 'HSE koučing (novo)',
				description: ['Individualni HSE koučing koji vam pomaže da postanete efikasniji lider.'],
			},
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
		clientsEyebrow: 'Organizacije koje podržavamo',
		clientsTitle: 'Klijenti koji su ukazali poverenje kompaniji HSE Training.',
	},
} as const;

export function getCompanyUiTranslations(locale: Locale) {
	return companyUi[locale];
}
