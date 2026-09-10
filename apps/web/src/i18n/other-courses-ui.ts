import type { Locale } from './config';

const otherCoursesUi = {
	en: {
		common: {
			heroScroll: 'Scroll to course content', courseInfoLabel: 'Course information', featureImageAlt: 'Professional practical training session',
			enquire: 'Enquire about this course', contentKicker: 'Course content', audienceKicker: 'Who should attend',
			datesText: 'Course dates and pricing are available upon enquiry.', readyTitle: 'Ready to train your team?', contactCta: 'Contact HSE Training',
		},
		overview: {
			metaTitle: 'Professional Training | HSE Training', metaDescription: 'Explore custom HSE training, Banksman and Slinger and Train the Trainer courses from HSE Training in Belgrade, Serbia.',
			heroEyebrow: 'Practical professional development', heroTitle: 'Training', selectorEyebrow: 'Choose your training', selectorTitle: 'Practical training for safer, stronger teams.', exploreCta: 'Explore the training',
			courses: [
				{ title: 'Custom Training Design', details: 'Designed for your organisation', text: 'HSE training aligned with your legislation, workforce, culture and operational reality.', href: '/training/custom-training-design/', image: '/images/consulting-service-01.webp' },
				{ title: 'Banksman / Slinger', details: '5 days · Serbian · Maximum 8 participants', text: 'Practical skills and knowledge for safely selecting, inspecting and using lifting equipment and directing crane operations.', href: '/training/banksman-slinger/', image: '/images/consulting-service-04.webp' },
				{ title: 'Train the trainer', details: '3 days · Serbian · Maximum 6 participants', text: 'Hands-on practice in the essential skills required to plan and deliver effective training sessions to groups.', href: '/training/train-the-trainer/', image: '/images/consulting-hero-02.webp' },
			],
		},
		banksman: {
			metaTitle: 'Banksman / Slinger Course | HSE Training', metaDescription: 'Five-day theory and practical Banksman and Slinger training for safe lifting operations, equipment inspection and crane communication.',
			title: 'Banksman / Slinger', eyebrow: 'Theory and practical training', introKicker: 'Safe lifting operations', introTitle: 'Build the competence to move loads safely and efficiently.',
			intro: ['This course gives participants the practical skills and knowledge required to undertake banksman and slinger duties safely in a range of working environments.', 'The programme combines essential theory with hands-on practice, helping participants understand lifting equipment, load control, communication and the risks involved in crane operations.'],
			facts: [{ label: 'Duration', value: '5 days' }, { label: 'Price', value: 'Upon enquiry' }, { label: 'Language', value: 'Serbian' }, { label: 'Group size', value: 'Maximum 8' }, { label: 'Format', value: 'Theory + practice' }],
			outcomesTitle: 'What participants will learn and demonstrate.', outcomesIntro: 'Training covers the complete working process, from selecting equipment and identifying hazards to directing the crane operator and placing the load.',
			outcomes: [
				{ title: 'Apply slinging techniques', text: 'Select and use appropriate slinging methods for a range of loads and working conditions.' },
				{ title: 'Inspect lifting gear', text: 'Select and inspect lifting gear and equipment before work begins.' },
				{ title: 'Direct crane operators', text: 'Use safe communication to guide load movement and placement, including out-of-view operations.' },
				{ title: 'Control lifting risks', text: 'Apply legislation, standards and risk-management procedures relevant to lifting operations.' },
				{ title: 'Understand crane operations', text: 'Recognise crane types, uses, characteristics, set-up requirements and operational limitations.' },
				{ title: 'Communicate clearly', text: 'Use hand signals, whistles and two-way radio communication during lifting work.' },
			],
			audienceTitle: 'Teams involved in lifting and load movement.',
			audience: ['Personnel responsible for attaching, securing or releasing loads.', 'Workers who direct crane operators during lifting operations.', 'Supervisors responsible for lifting safety and risk controls.', 'Employees who need practical knowledge of lifting gear inspection and selection.'],
		},
		trainer: {
			metaTitle: 'Train the Trainer Course | HSE Training', metaDescription: 'Three-day Train the Trainer programme with hands-on practice for new and experienced workplace trainers.',
			title: 'Train the trainer', eyebrow: 'Develop confident workplace trainers', introKicker: 'Process, practice and feedback', introTitle: 'Learn how to deliver training that people can use.',
			intro: ['The Train the Trainer Certificate gives both new and experienced trainers hands-on practice in the skills needed to deliver effective training sessions to groups.', 'The programme focuses on the training process rather than a single subject. Trainer input and participant practice run throughout the course, with much of the final day dedicated to assessment.'],
			facts: [{ label: 'Duration', value: '3 days' }, { label: 'Price', value: 'Upon enquiry' }, { label: 'Language', value: 'Serbian' }, { label: 'Group size', value: 'Maximum 6' }, { label: 'Format', value: 'Theory + practice' }],
			outcomesTitle: 'A practical foundation for effective training.', outcomesIntro: 'Participants work through the complete delivery process and practise techniques that can be adapted to different workplace topics.',
			outcomes: [
				{ title: 'Plan effective sessions', text: 'Set clear learning objectives and structure training around the needs of the group.' },
				{ title: 'Deliver with confidence', text: 'Practise the communication and presentation skills needed to engage participants.' },
				{ title: 'Use practical methods', text: 'Select delivery techniques and activities that help learners understand and retain information.' },
				{ title: 'Manage group learning', text: 'Facilitate participation, respond to different experience levels and maintain focus.' },
				{ title: 'Review performance', text: 'Use feedback and self-review to strengthen future training sessions.' },
				{ title: 'Demonstrate capability', text: 'Apply the complete training process during the final-day practical assessment.' },
			],
			audienceTitle: 'New and experienced workplace trainers.',
			audience: ['People preparing to deliver training sessions for the first time.', 'Experienced trainers who want to refresh and practise their delivery skills.', 'Subject-matter experts moving into a teaching or facilitation role.', 'Internal trainers who need methods they can adapt across different course topics.'],
		},
	},
	sr: {
		common: {
			heroScroll: 'Pređite na sadržaj kursa', courseInfoLabel: 'Informacije o kursu', featureImageAlt: 'Praktična stručna obuka',
			enquire: 'Raspitajte se o kursu', contentKicker: 'Sadržaj kursa', audienceKicker: 'Kome je kurs namenjen',
			datesText: 'Termini i cena kursa dostupni su na upit.', readyTitle: 'Spremni ste da obučite svoj tim?', contactCta: 'Kontaktirajte HSE Training',
		},
		overview: {
			metaTitle: 'Profesionalne obuke | HSE Training', metaDescription: 'Istražite HSE obuke po meri, Banksman / Slinger i Train the Trainer programe kompanije HSE Training.',
			heroEyebrow: 'Praktični profesionalni razvoj', heroTitle: 'Obuke', selectorEyebrow: 'Izaberite obuku', selectorTitle: 'Praktične obuke za bezbednije i snažnije timove.', exploreCta: 'Pogledajte obuku',
			courses: [
				{ title: 'Obuke po meri', details: 'Dizajnirano za vašu organizaciju', text: 'HSE obuka usklađena sa propisima, zaposlenima, kulturom i stvarnim načinom rada vaše organizacije.', href: '/training/custom-training-design/', image: '/images/consulting-service-01.webp' },
				{ title: 'Banksman / Slinger', details: '5 dana · srpski · najviše 8 polaznika', text: 'Praktične veštine i znanje za bezbedan izbor, pregled i korišćenje opreme za podizanje i usmeravanje rada dizalice.', href: '/training/banksman-slinger/', image: '/images/consulting-service-04.webp' },
				{ title: 'Train the Trainer', details: '3 dana · srpski · najviše 6 polaznika', text: 'Praktično vežbanje ključnih veština za planiranje i izvođenje delotvornih grupnih obuka.', href: '/training/train-the-trainer/', image: '/images/consulting-hero-02.webp' },
			],
		},
		banksman: {
			metaTitle: 'Banksman / Slinger kurs | HSE Training', metaDescription: 'Petodnevna teorijska i praktična Banksman / Slinger obuka za bezbedno podizanje, pregled opreme i komunikaciju sa rukovaocem dizalice.',
			title: 'Banksman / Slinger', eyebrow: 'Teorijska i praktična obuka', introKicker: 'Bezbedne operacije podizanja', introTitle: 'Razvijte kompetencije za bezbedno i efikasno pomeranje tereta.',
			intro: ['Kurs polaznicima pruža praktične veštine i znanje potrebno za bezbedno obavljanje poslova banksman-a i slingera u različitim radnim okruženjima.', 'Program spaja ključnu teoriju sa praktičnim vežbama i pomaže polaznicima da razumeju opremu za podizanje, kontrolu tereta, komunikaciju i rizike pri radu sa dizalicama.'],
			facts: [{ label: 'Trajanje', value: '5 dana' }, { label: 'Cena', value: 'Na upit' }, { label: 'Jezik', value: 'Srpski' }, { label: 'Veličina grupe', value: 'Najviše 8' }, { label: 'Format', value: 'Teorija + praksa' }],
			outcomesTitle: 'Šta će polaznici naučiti i pokazati.', outcomesIntro: 'Obuka obuhvata čitav proces rada, od izbora opreme i prepoznavanja opasnosti do usmeravanja rukovaoca dizalice i postavljanja tereta.',
			outcomes: [
				{ title: 'Primena tehnika vezivanja', text: 'Izbor i primena odgovarajućih metoda vezivanja za različite terete i uslove rada.' },
				{ title: 'Pregled opreme za podizanje', text: 'Izbor i pregled opreme i pribora za podizanje pre početka rada.' },
				{ title: 'Usmeravanje rukovaoca dizalice', text: 'Bezbedna komunikacija pri pomeranju i postavljanju tereta, uključujući rad van vidnog polja.' },
				{ title: 'Kontrola rizika pri podizanju', text: 'Primena propisa, standarda i postupaka upravljanja rizikom relevantnih za operacije podizanja.' },
				{ title: 'Razumevanje rada dizalice', text: 'Prepoznavanje tipova, namene, karakteristika, zahteva za postavljanje i ograničenja dizalica.' },
				{ title: 'Jasna komunikacija', text: 'Upotreba ručnih signala, zviždaljke i radio-veze tokom podizanja.' },
			],
			audienceTitle: 'Timovi uključeni u podizanje i pomeranje tereta.',
			audience: ['Osobe odgovorne za vezivanje, osiguravanje ili oslobađanje tereta.', 'Radnici koji usmeravaju rukovaoce dizalica tokom podizanja.', 'Nadzorna lica odgovorna za bezbednost podizanja i kontrolu rizika.', 'Zaposleni kojima je potrebno praktično znanje o izboru i pregledu opreme za podizanje.'],
		},
		trainer: {
			metaTitle: 'Train the Trainer kurs | HSE Training', metaDescription: 'Trodnevni Train the Trainer program sa praktičnim vežbama za nove i iskusne trenere na radnom mestu.',
			title: 'Train the Trainer', eyebrow: 'Razvoj sigurnih i uspešnih trenera', introKicker: 'Proces, vežba i povratna informacija', introTitle: 'Naučite da održite obuku čije znanje ljudi mogu da primene.',
			intro: ['Train the Trainer sertifikat pruža novim i iskusnim trenerima praktično vežbanje veština potrebnih za delotvorno izvođenje grupnih obuka.', 'Program je usmeren na proces obuke, a ne na jednu stručnu temu. Rad trenera i vežbe polaznika prisutni su tokom celog kursa, dok je veliki deo poslednjeg dana posvećen proceni.'],
			facts: [{ label: 'Trajanje', value: '3 dana' }, { label: 'Cena', value: 'Na upit' }, { label: 'Jezik', value: 'Srpski' }, { label: 'Veličina grupe', value: 'Najviše 6' }, { label: 'Format', value: 'Teorija + praksa' }],
			outcomesTitle: 'Praktična osnova za delotvornu obuku.', outcomesIntro: 'Polaznici prolaze kroz ceo proces izvođenja i vežbaju tehnike koje mogu prilagoditi različitim temama na radnom mestu.',
			outcomes: [
				{ title: 'Planiranje delotvornih sesija', text: 'Postavljanje jasnih ciljeva učenja i strukturiranje obuke prema potrebama grupe.' },
				{ title: 'Sigurno izvođenje obuke', text: 'Vežbanje komunikacionih i prezentacionih veština potrebnih za uključivanje polaznika.' },
				{ title: 'Primena praktičnih metoda', text: 'Izbor tehnika i aktivnosti koje pomažu polaznicima da razumeju i zapamte sadržaj.' },
				{ title: 'Upravljanje grupnim učenjem', text: 'Podsticanje učešća, prilagođavanje različitim nivoima iskustva i održavanje fokusa.' },
				{ title: 'Procena učinka', text: 'Korišćenje povratnih informacija i samoprocene za unapređenje narednih obuka.' },
				{ title: 'Pokazivanje sposobnosti', text: 'Primena kompletnog procesa obuke tokom praktične procene poslednjeg dana.' },
			],
			audienceTitle: 'Novi i iskusni treneri na radnom mestu.',
			audience: ['Osobe koje se pripremaju da prvi put održavaju obuke.', 'Iskusni treneri koji žele da osveže i uvežbaju svoje veštine.', 'Stručnjaci koji prelaze u ulogu predavača ili facilitatora.', 'Interni treneri kojima su potrebne metode prilagodljive različitim temama kurseva.'],
		},
	},
} as const;

export function getOtherCoursesUiTranslations(locale: Locale) {
	return otherCoursesUi[locale];
}
