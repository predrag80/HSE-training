import type { Locale } from './config';

const consultingUi = {
	en: {
		metaTitle: 'HSE Consulting Services | HSE Training',
		metaDescription: 'HSEQ personnel and project HSE management support for renewable energy, mining, oil and gas, and construction operations.',
		heroEyebrow: 'Professional HSEQ expertise', heroTitle: 'Browse Consultants', heroScroll: 'Scroll to consulting services',
		introKicker: 'Local capability. International experience.',
		introTitle: 'HSEQ personnel and project support where you need it.',
		introParagraphs: [
			'HSE Training can provide local HSEQ personnel and project HSE management services for operations and projects across Eastern Europe and international locations.',
			'Our personnel database combines international experience, tertiary qualifications and professionals who bring practical knowledge from operational and technical roles.',
		],
		introCta: 'Discuss your requirements', directoryCta: 'Browse the HSE Directory', capabilitiesLabel: 'Consulting capabilities',
		facts: [
			{ value: 'HSEQ', label: 'Specialist personnel' }, { value: '13', label: 'Working languages' },
			{ value: '4', label: 'Core industries' }, { value: 'On-site', label: 'Project support' },
		],
		servicesEyebrow: 'Consulting services', servicesTitle: 'The right expertise for demanding environments.',
		peopleImageAlt: 'HSE professionals planning operational support', peopleKicker: 'People with practical perspective',
		peopleTitle: 'Experience beyond the classroom.',
		peopleText: 'Our network includes professionals with international project experience and people who developed their HSE expertise after working directly on the shop floor or with the tools.',
		peoplePoints: ['International and regional project experience', 'Tertiary and professional HSE qualifications', 'Operational and technical backgrounds', 'Multilingual communication'],
		industriesEyebrow: 'Industry experience', industriesTitle: 'Support shaped around your operation.',
		industriesIntro: 'Our consultants bring experience from field operations, complex projects and high-risk working environments.',
		industries: [
			{ title: 'Renewable energy', text: 'HSE support for renewable-energy development, construction and operations.' },
			{ title: 'Mining', text: 'Practical personnel and project support for mineral exploration and mining activities.' },
			{ title: 'Oil and gas', text: 'Operational safety experience across seismic, drilling, pipeline and plant environments.' },
			{ title: 'Construction', text: 'Site-focused HSE guidance for construction teams, contractors and project leadership.' },
		],
		experienceKicker: 'Operational knowledge', experienceTitle: 'Ready for every stage of the project lifecycle.',
		experienceText: 'Personnel in our network have supported specialist operations from exploration and drilling through construction, commissioning, ongoing operations and maintenance.',
		experienceAreas: ['Seismic operations', 'Drilling', 'Pipeline construction', 'Plant construction', 'Plant operations', 'Maintenance'],
		languagesKicker: 'International communication', languagesTitle: 'Multilingual teams for regional and global work.',
		languages: ['Serbian', 'Croatian', 'English', 'Albanian', 'Romanian', 'Polish', 'Spanish', 'Slovenian', 'Bosnian', 'Italian', 'Turkish', 'Russian', 'Arabic'],
		qualificationsEyebrow: 'Qualified leadership', qualificationsTitle: 'Professional knowledge backed by recognised HSEQ education.', qualificationsLabel: 'Lead consultant qualification profile',
		qualifications: ['Master’s — HSEQ', 'Bachelor — Occupational Health and Safety', 'Diploma — Occupational Health and Safety'],
		ctaEyebrow: 'Planning a project or strengthening your HSE team?', ctaTitle: 'Let’s discuss the expertise you need.', ctaLabel: 'Contact HSE Training',
	},
	sr: {
		metaTitle: 'HSE konsalting usluge | HSE Training',
		metaDescription: 'HSEQ stručnjaci i podrška upravljanju HSE projektima u obnovljivoj energiji, rudarstvu, nafti i gasu i građevinarstvu.',
		heroEyebrow: 'Profesionalna HSEQ stručnost', heroTitle: 'Izaberite konsultanta', heroScroll: 'Pređite na konsultantske usluge',
		introKicker: 'Lokalni kapaciteti. Međunarodno iskustvo.',
		introTitle: 'HSEQ stručnjaci i projektna podrška tamo gde su vam potrebni.',
		introParagraphs: [
			'HSE Training obezbeđuje lokalne HSEQ stručnjake i usluge upravljanja HSE projektima za aktivnosti i projekte u istočnoj Evropi i na međunarodnim lokacijama.',
			'Naša baza stručnjaka objedinjuje međunarodno iskustvo, visoko obrazovanje i profesionalce sa praktičnim znanjem iz operativnih i tehničkih poslova.',
		],
		introCta: 'Razgovarajte o svojim potrebama', directoryCta: 'Otvorite HSE Directory', capabilitiesLabel: 'Konsultantski kapaciteti',
		facts: [
			{ value: 'HSEQ', label: 'Specijalizovani stručnjaci' }, { value: '13', label: 'Radnih jezika' },
			{ value: '4', label: 'Ključne industrije' }, { value: 'Na lokaciji', label: 'Projektna podrška' },
		],
		servicesEyebrow: 'Konsultantske usluge', servicesTitle: 'Odgovarajuća stručnost za zahtevna okruženja.',
		peopleImageAlt: 'HSE stručnjaci planiraju operativnu podršku', peopleKicker: 'Ljudi sa praktičnim iskustvom',
		peopleTitle: 'Iskustvo koje prevazilazi učionicu.',
		peopleText: 'Našu mrežu čine stručnjaci sa međunarodnim projektnim iskustvom i ljudi koji su HSE stručnost razvili kroz neposredan operativni i tehnički rad.',
		peoplePoints: ['Međunarodno i regionalno projektno iskustvo', 'Visoko obrazovanje i profesionalne HSE kvalifikacije', 'Operativno i tehničko iskustvo', 'Višejezična komunikacija'],
		industriesEyebrow: 'Iskustvo u industriji', industriesTitle: 'Podrška prilagođena vašem poslovanju.',
		industriesIntro: 'Naši konsultanti donose iskustvo iz terenskih operacija, složenih projekata i radnih okruženja visokog rizika.',
		industries: [
			{ title: 'Obnovljiva energija', text: 'HSE podrška razvoju, izgradnji i radu postrojenja obnovljive energije.' },
			{ title: 'Rudarstvo', text: 'Praktična kadrovska i projektna podrška istraživanju minerala i rudarskim aktivnostima.' },
			{ title: 'Nafta i gas', text: 'Iskustvo u operativnoj bezbednosti seizmičkih, bušačkih, cevovodnih i postrojenjskih aktivnosti.' },
			{ title: 'Građevinarstvo', text: 'HSE podrška na gradilištu za izvođače, timove i rukovodstvo projekta.' },
		],
		experienceKicker: 'Operativno znanje', experienceTitle: 'Spremni za svaku fazu životnog ciklusa projekta.',
		experienceText: 'Stručnjaci iz naše mreže podržavali su specijalizovane aktivnosti od istraživanja i bušenja do izgradnje, puštanja u rad, redovnog rada i održavanja.',
		experienceAreas: ['Seizmičke operacije', 'Bušenje', 'Izgradnja cevovoda', 'Izgradnja postrojenja', 'Rad postrojenja', 'Održavanje'],
		languagesKicker: 'Međunarodna komunikacija', languagesTitle: 'Višejezični timovi za regionalne i globalne poslove.',
		languages: ['Srpski', 'Hrvatski', 'Engleski', 'Albanski', 'Rumunski', 'Poljski', 'Španski', 'Slovenački', 'Bosanski', 'Italijanski', 'Turski', 'Ruski', 'Arapski'],
		qualificationsEyebrow: 'Kvalifikovano rukovođenje', qualificationsTitle: 'Stručno znanje zasnovano na priznatom HSEQ obrazovanju.', qualificationsLabel: 'Profil kvalifikacija vodećeg konsultanta',
		qualifications: ['Master — HSEQ', 'Osnovne studije — bezbednost i zdravlje na radu', 'Diploma — bezbednost i zdravlje na radu'],
		ctaEyebrow: 'Planirate projekat ili jačate svoj HSE tim?', ctaTitle: 'Razgovarajmo o stručnosti koja vam je potrebna.', ctaLabel: 'Kontaktirajte HSE Training',
	},
} as const;

export function getConsultingUiTranslations(locale: Locale) {
	return consultingUi[locale];
}
