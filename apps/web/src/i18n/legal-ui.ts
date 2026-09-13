import type { Locale } from './config';

export type LegalPageKey = 'privacy' | 'terms' | 'copyright';

interface LegalLink {
	readonly label: string;
	readonly href: string;
	readonly external?: boolean;
}

interface LegalSection {
	readonly title: string;
	readonly paragraphs: readonly string[];
	readonly items?: readonly string[];
	readonly links?: readonly LegalLink[];
}

interface LegalPageContent {
	readonly title: string;
	readonly metaDescription: string;
	readonly eyebrow: string;
	readonly intro: string;
	readonly lastUpdated: string;
	readonly sections: readonly LegalSection[];
}

const details = {
	en: 'HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Belgrade, Serbia (company registration number 20952288; VAT number 108205616)',
	sr: 'HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Beograd, Srbija (matični broj 20952288; PIB 108205616)',
} as const;

const links = {
	emailEn: { label: 'Contact HSE Training', href: 'mailto:info@hsetraining.rs' },
	emailSr: { label: 'Kontaktirajte HSE Training', href: 'mailto:info@hsetraining.rs' },
	commissionerEn: {
		label: 'Serbian Data Protection Commissioner',
		href: 'https://poverenik.rs/en/',
		external: true,
	},
	commissionerSr: {
		label: 'Poverenik za zaštitu podataka o ličnosti',
		href: 'https://poverenik.rs/',
		external: true,
	},
} as const;

const legalUi: Record<Locale, Record<LegalPageKey, LegalPageContent>> = {
	en: {
		privacy: {
			title: 'Privacy Policy',
			metaDescription:
				'How HSE Training DOO collects, uses, retains and protects personal data through this website and direct enquiries.',
			eyebrow: 'Legal information',
			intro:
				'This policy explains what personal data we may process when you visit hsetraining.rs or contact us, why we use it and which choices and rights are available to you.',
			lastUpdated: 'Last updated: 13 September 2026',
			sections: [
				{
					title: 'Who is responsible for your data',
					paragraphs: [
						`${details.en} is the controller of personal data described in this policy. Privacy questions and requests may be sent to info@hsetraining.rs or made by telephone on +381 61 5335 010.`,
					],
					links: [links.emailEn],
				},
				{
					title: 'Data we may collect',
					paragraphs: [
						'We collect only data relevant to operating the website, responding to enquiries and delivering agreed services. The exact data depends on how you interact with us.',
					],
					items: [
						'Contact and enquiry data, such as your name, email address, telephone number, organisation and message when you contact us by email, telephone, WhatsApp or an available website form.',
						'Technical data that may be recorded in server and security logs, such as IP address, browser and device information, requested URL, date, time and diagnostic data.',
						'Business correspondence and records needed to prepare an offer, arrange training or consultancy, perform an agreement and meet accounting or other legal obligations.',
					],
				},
				{
					title: 'Purposes and legal bases',
					paragraphs: [
						'We process personal data only where there is an appropriate legal basis. Depending on the interaction, processing may be necessary to take steps at your request before a contract, to perform a contract, to comply with a legal obligation, for a carefully assessed legitimate interest, or on the basis of consent where specifically requested.',
					],
					items: [
						'Responding to enquiries, preparing proposals and administering training or consultancy services.',
						'Operating, maintaining, securing and troubleshooting the website and related systems.',
						'Maintaining necessary business and financial records and establishing, exercising or defending legal claims.',
					],
				},
				{
					title: 'Cookies and external services',
					paragraphs: [
						'HSE Training does not currently use advertising or behavioural analytics cookies on this website. Essential technical storage may be used where required for security or core functionality.',
						'The contact page includes an embedded Google Map, and the website links to services such as WhatsApp, LinkedIn, Facebook, Instagram, YouTube, NEBOSH and HSE Directory. When you load embedded content or follow an external link, that provider may receive technical data and use cookies under its own privacy terms. HSE Training does not control those third-party services.',
					],
				},
				{
					title: 'Sharing, transfers and retention',
					paragraphs: [
						'We do not sell personal data. Data may be disclosed only where necessary to providers supporting hosting, email, communications, IT or professional services; to a partner involved in fulfilling your request; or to public authorities where disclosure is legally required. Some providers may process data outside Serbia. Where required, we use an applicable transfer mechanism and reasonable safeguards.',
						'We retain data only as long as reasonably needed for its purpose, necessary business records, or legal, accounting and dispute-resolution requirements. The period depends on the record, relationship and applicable law. We then delete, anonymise or securely archive it as appropriate and apply proportionate organisational and technical security measures.',
					],
				},
				{
					title: 'Your rights',
					paragraphs: [
						'Subject to applicable data-protection law, you may request access, correction or completion, deletion, restriction, data portability, or object to processing. Where processing is based on consent, you may withdraw it at any time without affecting earlier processing.',
						'To exercise a right, contact us and provide enough information to identify the relevant record. We may need to verify your identity. You may also complain to the Serbian Commissioner for Information of Public Importance and Personal Data Protection or seek another available legal remedy.',
					],
					links: [links.emailEn, links.commissionerEn],
				},
				{
					title: 'Policy changes',
					paragraphs: [
						'We may update this policy when the website, our services or legal requirements change. The current version and update date will be published here. Material changes affecting an active relationship may also be communicated by an appropriate additional method.',
					],
				},
			],
		},
		terms: {
			title: 'Terms and Conditions',
			metaDescription:
				'Terms governing access to and use of the HSE Training website, enquiries and website content.',
			eyebrow: 'Legal information',
			intro:
				'These terms govern your use of hsetraining.rs. Separate written terms apply to any training, consultancy or other service that HSE Training agrees to provide.',
			lastUpdated: 'Last updated: 13 September 2026',
			sections: [
				{
					title: 'Website operator and acceptance',
					paragraphs: [
						`${details.en} operates this website. By using it, you agree to these terms and applicable law. If you do not agree, please stop using the website.`,
					],
					links: [links.emailEn],
				},
				{
					title: 'Information, enquiries and services',
					paragraphs: [
						'Website content is general information and an invitation to enquire. Course dates, availability, fees, specifications, accreditation status and consultancy scope may change and must be confirmed directly with HSE Training before you rely on them.',
						'Sending an enquiry does not create a contract, reserve a place or oblige either party to proceed. A service relationship begins only when the parties confirm it in writing or otherwise conclude a valid agreement. Any quotation, booking form, engagement letter or course-specific terms agreed with you take priority over these website terms if there is a conflict.',
					],
				},
				{
					title: 'Permitted use',
					paragraphs: [
						'You may browse the website and use its public information for lawful personal or internal business purposes.',
					],
					items: [
						'Do not attempt unauthorised access, disrupt the website, introduce malicious code or bypass technical protections.',
						'Do not use automated extraction, scraping or systematic copying that burdens the service or infringes rights.',
						'Do not impersonate another person, submit unlawful or misleading material, or use the website to violate another person’s rights.',
					],
				},
				{
					title: 'Professional information and outcomes',
					paragraphs: [
						'General HSE, training and qualification information on the website is not legal, medical, engineering or site-specific professional advice. Decisions about workplace risk and legal compliance must be based on current rules, the circumstances and appropriately qualified advice.',
						'Testimonials and examples describe individual experiences and do not guarantee examination results, employment, promotion, commercial outcomes or any other result.',
					],
				},
				{
					title: 'Third-party services and intellectual property',
					paragraphs: [
						'The website links to or embeds independent services, including Google Maps, WhatsApp, social networks, NEBOSH and HSE Directory. Links are provided for convenience or identification. HSE Training does not control external content, availability, security or privacy practices. Your use of an external service is governed by that provider’s terms.',
						'Website content is protected by copyright, trademark and other applicable rights. Access does not transfer ownership or grant a licence beyond the limited use expressly allowed here and on the Copyright page. Third-party names, marks and materials remain the property of their owners.',
					],
				},
				{
					title: 'Availability and liability',
					paragraphs: [
						'We aim to keep the website useful, accurate and available, but do not promise uninterrupted or error-free operation. We may correct, update, suspend or withdraw content or functionality when reasonably necessary.',
						'To the fullest extent permitted by law, HSE Training is not liable for loss arising solely from reliance on general website content, temporary unavailability, or an external service outside our control. Nothing here excludes liability that cannot lawfully be excluded or any mandatory consumer right.',
					],
				},
				{
					title: 'Governing law and changes',
					paragraphs: [
						'These website terms are governed by the law of the Republic of Serbia. Courts with jurisdiction in Serbia will resolve disputes, subject to any mandatory consumer jurisdiction or other protection that applies to you.',
						'We may update these terms when the website, services or law changes. The version published here applies from its stated update date. Questions may be sent to info@hsetraining.rs.',
					],
					links: [links.emailEn],
				},
			],
		},
		copyright: {
			title: 'Copyright',
			metaDescription:
				'Copyright, trademark and permitted-use information for HSE Training website content and training materials.',
			eyebrow: 'Legal information',
			intro:
				'This notice explains how content on hsetraining.rs may be used and how to report a suspected infringement.',
			lastUpdated: 'Last updated: 13 September 2026',
			sections: [
				{
					title: 'Ownership',
					paragraphs: [
						'Unless a credit states otherwise, website text, original graphics, page design and other original content are owned by or licensed to HSE Training DOO and protected by applicable copyright and other intellectual-property laws. Copyright protection arises independently of this notice.',
						'Third-party photographs, logos, names, qualifications, testimonials and trademarks remain the property of their owners and are used under permission, licence or another applicable legal basis. Their appearance does not transfer rights to website users.',
					],
				},
				{
					title: 'Permitted use',
					paragraphs: [
						'You may view the website and make a reasonable number of copies or printouts for personal, non-commercial use or internal evaluation of HSE Training services, provided content is not altered and all rights notices remain visible.',
						'Uses expressly permitted by mandatory law remain unaffected. Broader use requires prior written permission from HSE Training and, where relevant, the third-party rights holder.',
					],
				},
				{
					title: 'Uses requiring permission',
					paragraphs: ['Unless applicable law or a written licence allows it, you must not:'],
					items: [
						'Republish, distribute, sell, sublicense or commercially exploit website content.',
						'Modify, adapt, translate or create derivative materials from protected content.',
						'Remove credits or notices, frame the website, or systematically scrape images, text, testimonials or data.',
						'Use an HSE Training or third-party mark in a way that suggests endorsement, affiliation or authorisation that has not been granted.',
					],
				},
				{
					title: 'Course and training materials',
					paragraphs: [
						'Course handouts, presentations, recordings, assessment materials and resources supplied to learners or clients are not licensed merely because information about them appears here. Their use is governed by applicable enrolment, licence and awarding-body terms. They may not be shared, uploaded or reproduced without permission.',
					],
				},
				{
					title: 'Third-party marks',
					paragraphs: [
						'NEBOSH and other third-party names, logos and course marks belong to their respective owners. Client and partner logos are displayed for identification in the context stated on the website. No licence to use any such mark is granted to visitors.',
					],
				},
				{
					title: 'Permissions and infringement notices',
					paragraphs: [
						'If you believe material here infringes your rights, email info@hsetraining.rs with your contact details, identification of the protected work or mark, the exact page or URL, a description of the issue and the basis on which you may act. We will review a sufficiently detailed notice and take appropriate action where required.',
						'For reuse permission, describe the material, proposed use, territory, format and duration. Permission is valid only when confirmed in writing by an authorised representative. © 2026 HSE Training DOO. All rights reserved, except where otherwise stated.',
					],
					links: [links.emailEn],
				},
			],
		},
	},
	sr: {
		privacy: {
			title: 'Politika privatnosti',
			metaDescription:
				'Kako HSE Training DOO prikuplja, koristi, čuva i štiti podatke o ličnosti putem ovog sajta i direktnih upita.',
			eyebrow: 'Pravne informacije',
			intro:
				'Ova politika objašnjava koje podatke o ličnosti možemo obrađivati kada posetite hsetraining.rs ili nas kontaktirate, zašto ih koristimo i koja prava i mogućnosti imate.',
			lastUpdated: 'Poslednje ažuriranje: 13. septembra 2026.',
			sections: [
				{
					title: 'Ko je odgovoran za vaše podatke',
					paragraphs: [
						`${details.sr} je rukovalac podacima opisanim u ovoj politici. Pitanja i zahteve u vezi sa privatnošću možete poslati na info@hsetraining.rs ili uputiti telefonom na +381 61 5335 010.`,
					],
					links: [links.emailSr],
				},
				{
					title: 'Podaci koje možemo prikupljati',
					paragraphs: [
						'Prikupljamo samo podatke relevantne za rad sajta, odgovaranje na upite i pružanje ugovorenih usluga. Tačan obim zavisi od načina na koji komunicirate sa nama.',
					],
					items: [
						'Kontakt podatke i podatke iz upita, kao što su ime, imejl, telefon, organizacija i poruka kada nas kontaktirate putem imejla, telefona, WhatsApp-a ili dostupnog formulara.',
						'Tehničke podatke u serverskim i bezbednosnim evidencijama, kao što su IP adresa, pregledač i uređaj, traženi URL, datum, vreme i dijagnostički podaci.',
						'Poslovnu prepisku i evidencije potrebne za ponudu, organizovanje obuke ili konsultantske usluge, izvršenje ugovora i zakonske obaveze.',
					],
				},
				{
					title: 'Svrhe i pravni osnovi',
					paragraphs: [
						'Podatke obrađujemo samo uz odgovarajući pravni osnov. Obrada može biti potrebna radi radnji na vaš zahtev pre ugovora, izvršenja ugovora, poštovanja pravne obaveze, pažljivo procenjenog legitimnog interesa ili na osnovu pristanka kada je posebno zatražen.',
					],
					items: [
						'Odgovaranje na upite, priprema ponuda i administriranje obuka ili konsultantskih usluga.',
						'Rad, održavanje, zaštita i otklanjanje tehničkih problema na sajtu i povezanim sistemima.',
						'Vođenje potrebne poslovne i finansijske evidencije i ostvarivanje ili odbrana pravnih zahteva.',
					],
				},
				{
					title: 'Kolačići i spoljne usluge',
					paragraphs: [
						'HSE Training trenutno ne koristi kolačiće za oglašavanje ili bihevioralnu analitiku. Neophodno tehničko skladištenje može se koristiti radi bezbednosti ili osnovnih funkcija.',
						'Kontakt stranica sadrži Google mapu, a sajt vodi ka uslugama kao što su WhatsApp, LinkedIn, Facebook, Instagram, YouTube, NEBOSH i HSE Directory. Kada učitate ugrađeni sadržaj ili pratite spoljni link, pružalac može primiti tehničke podatke i koristiti kolačiće prema svojim pravilima. HSE Training ne kontroliše te usluge.',
					],
				},
				{
					title: 'Primaoci, prenos i rok čuvanja',
					paragraphs: [
						'Ne prodajemo podatke o ličnosti. Podaci se mogu otkriti samo kada je potrebno pružaocima hostinga, imejla, komunikacija, IT ili stručnih usluga; partneru uključenom u vaš zahtev; ili nadležnom organu kada je to propisano. Neki pružaoci mogu obrađivati podatke van Srbije; kada je potrebno, primenjujemo odgovarajući mehanizam prenosa i razumne mere zaštite.',
						'Podatke čuvamo samo koliko je razumno potrebno za svrhu, poslovnu evidenciju ili zakonske, računovodstvene i potrebe rešavanja sporova. Rok zavisi od vrste evidencije, odnosa i propisa. Zatim ih brišemo, anonimizujemo ili bezbedno arhiviramo i primenjujemo srazmerne organizacione i tehničke mere zaštite.',
					],
				},
				{
					title: 'Vaša prava',
					paragraphs: [
						'Pod uslovima važećeg prava možete tražiti pristup, ispravku ili dopunu, brisanje, ograničenje obrade ili prenosivost podataka, odnosno podneti prigovor. Kada se obrada zasniva na pristanku, možete ga opozvati bez uticaja na raniju obradu.',
						'Radi ostvarivanja prava kontaktirajte nas i navedite dovoljno podataka da pronađemo evidenciju. Možemo zatražiti potvrdu identiteta. Možete podneti i pritužbu Povereniku za informacije od javnog značaja i zaštitu podataka o ličnosti ili koristiti drugo pravno sredstvo.',
					],
					links: [links.emailSr, links.commissionerSr],
				},
				{
					title: 'Izmene politike',
					paragraphs: [
						'Ovu politiku možemo izmeniti kada se promene sajt, usluge ili pravni zahtevi. Važeća verzija i datum ažuriranja biće objavljeni ovde. O važnim izmenama koje utiču na postojeći odnos možemo vas obavestiti i na drugi odgovarajući način.',
					],
				},
			],
		},
		terms: {
			title: 'Uslovi korišćenja',
			metaDescription:
				'Uslovi pristupa i korišćenja sajta HSE Training, slanja upita i korišćenja sadržaja sajta.',
			eyebrow: 'Pravne informacije',
			intro:
				'Ovi uslovi uređuju korišćenje sajta hsetraining.rs. Na obuku, konsultantsku ili drugu uslugu koju HSE Training prihvati da pruži primenjuju se posebni pisani uslovi.',
			lastUpdated: 'Poslednje ažuriranje: 13. septembra 2026.',
			sections: [
				{
					title: 'Upravljač sajta i prihvatanje uslova',
					paragraphs: [
						`${details.sr} upravlja ovim sajtom. Korišćenjem sajta prihvatate ove uslove i važeće propise. Ako se ne slažete, prestanite da koristite sajt.`,
					],
					links: [links.emailSr],
				},
				{
					title: 'Informacije, upiti i usluge',
					paragraphs: [
						'Sadržaj sajta služi opštem informisanju i pozivu za upit. Termini, dostupnost, cene, specifikacije, status akreditacije i obim konsultantskih usluga mogu se menjati i moraju se potvrditi sa HSE Training pre oslanjanja na njih.',
						'Slanje upita ne zaključuje ugovor, ne rezerviše mesto i ne obavezuje strane. Poslovni odnos nastaje tek kada ga strane potvrde pisanim putem ili drugačije zaključe važeći ugovor. Dogovorena ponuda, prijava, angažovanje ili posebni uslovi kursa imaju prednost nad ovim uslovima ako postoji nesaglasnost.',
					],
				},
				{
					title: 'Dozvoljeno korišćenje',
					paragraphs: [
						'Sajt i javne informacije možete koristiti u zakonite lične svrhe ili za interne poslovne potrebe.',
					],
					items: [
						'Ne pokušavajte neovlašćen pristup, ometanje sajta, unošenje zlonamernog koda ili zaobilaženje zaštite.',
						'Ne koristite automatizovano preuzimanje, scraping ili sistematsko kopiranje koje opterećuje uslugu ili povređuje prava.',
						'Ne predstavljajte se kao drugo lice, ne šaljite nezakonit ili obmanjujući sadržaj i ne povređujte tuđa prava.',
					],
				},
				{
					title: 'Stručne informacije i rezultati',
					paragraphs: [
						'Opšte HSE informacije i informacije o obukama nisu pravni, medicinski, inženjerski niti stručni savet za konkretno radno mesto. Odluke o rizicima i usklađenosti moraju se zasnivati na važećim propisima, okolnostima i savetu odgovarajućeg stručnjaka.',
						'Iskustva polaznika i primeri ne garantuju rezultat ispita, zaposlenje, napredovanje, poslovni ishod niti drugi rezultat.',
					],
				},
				{
					title: 'Spoljne usluge i intelektualna svojina',
					paragraphs: [
						'Sajt vodi ka nezavisnim uslugama ili ih ugrađuje, uključujući Google Maps, WhatsApp, društvene mreže, NEBOSH i HSE Directory. HSE Training ne kontroliše njihov sadržaj, dostupnost, bezbednost ili privatnost. Na njihovo korišćenje primenjuju se uslovi pružaoca.',
						'Sadržaj sajta zaštićen je autorskim pravom, žigovima i drugim pravima. Pristup ne prenosi vlasništvo niti daje licencu izvan ograničene upotrebe dozvoljene ovde i na stranici Autorska prava. Oznake i materijali trećih strana ostaju svojina svojih nosilaca.',
					],
				},
				{
					title: 'Dostupnost i odgovornost',
					paragraphs: [
						'Nastojimo da sajt bude koristan, tačan i dostupan, ali ne garantujemo neprekidan rad bez grešaka. Sadržaj ili funkcije možemo ispraviti, ažurirati, obustaviti ili povući kada je razumno potrebno.',
						'U najvećem obimu dozvoljenom pravom, HSE Training ne odgovara za štetu nastalu isključivo oslanjanjem na opšti sadržaj, privremenu nedostupnost ili spoljnu uslugu van naše kontrole. Ne isključuje se odgovornost koja se zakonom ne može isključiti niti obavezno pravo potrošača.',
					],
				},
				{
					title: 'Merodavno pravo i izmene',
					paragraphs: [
						'Na ove uslove primenjuje se pravo Republike Srbije. Sporove rešavaju nadležni sudovi u Srbiji, uz poštovanje obavezne potrošačke nadležnosti ili druge zaštite koja se na vas primenjuje.',
						'Uslove možemo menjati sa promenama sajta, usluga ili propisa. Objavljena verzija primenjuje se od navedenog datuma. Pitanja pošaljite na info@hsetraining.rs.',
					],
					links: [links.emailSr],
				},
			],
		},
		copyright: {
			title: 'Autorska prava',
			metaDescription:
				'Informacije o autorskim pravima, žigovima i dozvoljenom korišćenju sadržaja i materijala HSE Training.',
			eyebrow: 'Pravne informacije',
			intro:
				'Ovo obaveštenje objašnjava kako se sadržaj sajta hsetraining.rs može koristiti i kako prijaviti moguću povredu prava.',
			lastUpdated: 'Poslednje ažuriranje: 13. septembra 2026.',
			sections: [
				{
					title: 'Nosilac prava',
					paragraphs: [
						'Osim kada je drugačije navedeno, tekst sajta, originalna grafika, dizajn stranica i drugi originalni sadržaj vlasništvo su ili su licencirani društvu HSE Training DOO i zaštićeni važećim pravima intelektualne svojine. Autorskopravna zaštita nastaje nezavisno od ovog obaveštenja.',
						'Fotografije, logotipi, nazivi, kvalifikacije, preporuke i žigovi trećih strana ostaju svojina svojih nosilaca i koriste se na osnovu dozvole, licence ili drugog pravnog osnova. Njihov prikaz ne prenosi prava posetiocima.',
					],
				},
				{
					title: 'Dozvoljena upotreba',
					paragraphs: [
						'Sajt možete pregledati i napraviti razuman broj kopija ili štampanih primeraka za ličnu, nekomercijalnu upotrebu ili internu procenu usluga HSE Training, bez izmene sadržaja i uz zadržavanje oznaka o pravima.',
						'Načini korišćenja koje dozvoljavaju prinudni propisi ostaju nepromenjeni. Za šire korišćenje potrebna je prethodna pisana dozvola HSE Training i, kada je relevantno, nosioca prava treće strane.',
					],
				},
				{
					title: 'Upotreba za koju je potrebna dozvola',
					paragraphs: ['Osim ako propis ili pisana licenca to dozvoljava, nije dozvoljeno:'],
					items: [
						'Ponovno objavljivanje, distribucija, prodaja, podlicenciranje ili komercijalno iskorišćavanje sadržaja.',
						'Izmena, prilagođavanje, prevođenje ili izrada izvedenih materijala od zaštićenog sadržaja.',
						'Uklanjanje oznaka o pravima, prikaz sajta unutar okvira ili sistematsko preuzimanje fotografija, teksta, preporuka ili podataka.',
						'Korišćenje žiga HSE Training ili treće strane na način koji sugeriše nedobijenu podršku, povezanost ili odobrenje.',
					],
				},
				{
					title: 'Materijali za kurseve i obuke',
					paragraphs: [
						'Skripte, prezentacije, snimci, materijali za ocenjivanje i resursi dostavljeni polaznicima ili klijentima nisu licencirani time što su informacije o njima na sajtu. Njihovo korišćenje uređuju uslovi upisa, licence i tela koje dodeljuje kvalifikaciju. Ne smeju se deliti, postavljati ili umnožavati bez dozvole.',
					],
				},
				{
					title: 'Oznake trećih strana',
					paragraphs: [
						'NEBOSH i drugi nazivi, logotipi i oznake kurseva pripadaju svojim nosiocima. Logotipi klijenata i partnera prikazuju se radi identifikacije u kontekstu navedenom na sajtu. Posetiocima se ne daje licenca za korišćenje tih oznaka.',
					],
				},
				{
					title: 'Dozvole i prijava povrede',
					paragraphs: [
						'Ako smatrate da materijal povređuje vaša prava, pošaljite na info@hsetraining.rs kontakt podatke, identifikaciju zaštićenog dela ili oznake, tačnu stranicu ili URL, opis problema i osnov po kojem postupate. Razmotrićemo dovoljno detaljno obaveštenje i preduzeti potrebne mere.',
						'Za dozvolu za ponovnu upotrebu opišite materijal, planiranu upotrebu, teritoriju, format i trajanje. Dozvola važi samo kada je pisano potvrdi ovlašćeni predstavnik. © 2026 HSE Training DOO. Sva prava zadržana, osim kada je drugačije navedeno.',
					],
					links: [links.emailSr],
				},
			],
		},
	},
};

export function getLegalPageContent(locale: Locale, page: LegalPageKey): LegalPageContent {
	return legalUi[locale][page];
}
