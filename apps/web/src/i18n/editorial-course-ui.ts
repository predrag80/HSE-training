import type { Locale } from './config';

const editorialCourseUi = {
	en: {
		custom: {
			courseKey: 'custom-training-design',
			title: 'Custom HSE Training',
			eyebrow: 'Custom Training Design',
			metaDescription: 'Health and safety training designed around your organisation, workforce, legislation and culture.',
			shortDescription: 'Health and safety training designed specifically for how your organisation operates.',
			body: [
				'We design health and safety training specifically for your organisation, taking into account local legislation and regulatory requirements.',
				'Where gaps exist, we align your training with international standards and best practices, while incorporating your company’s design, values, ethics, ethos and culture.',
				'Training can be developed in the language your workforce understands best, with multilingual solutions available for both employees and management.',
				'The result is practical, relevant training that reflects how your organisation actually operates—not generic training designed for everyone.',
			],
			video: {
				eyebrow: 'Interactive training preview',
				title: 'See a custom digital course in action.',
				description: 'These short Serbian-language demonstrations show guided course navigation, visual explanations and an interactive knowledge check. Each programme can be adapted to the organisation, workforce and required language.',
				fallback: 'Your browser cannot play this video. Open the video:',
				items: [
					{
						src: '/videos/custom-training/demo-1.mp4',
						poster: '/images/custom-training/demo-1-poster.jpg',
						title: 'Visual course content',
						description: 'A guided module demonstrates how working-at-height and scaffolding topics can be presented clearly.',
						duration: '1:05',
					},
					{
						src: '/videos/custom-training/demo-2.mp4',
						poster: '/images/custom-training/demo-2-poster.jpg',
						title: 'Risk hierarchy explained',
						description: 'A visual presentation connects risk levels, likelihood and practical prevention priorities.',
						duration: '1:16',
					},
					{
						src: '/videos/custom-training/demo-3.mp4',
						poster: '/images/custom-training/demo-3-poster.jpg',
						title: 'Interactive knowledge check',
						description: 'A drag-and-drop activity reinforces the hierarchy of controls through active participation.',
						duration: '0:30',
					},
				],
			},
		},
		eaw: {
			courseKey: 'nebosh-eaw',
			title: 'NEBOSH Award in Environmental Awareness at Work',
			eyebrow: 'NEBOSH EAW',
			metaDescription: 'Introductory environmental awareness qualification for employees in any organisation and industry.',
			shortDescription: 'A practical introduction to environmental impacts, controls and good workplace practice.',
			body: [
				'This introductory qualification helps employees understand environmental impacts in the workplace and the practical controls that support better environmental performance.',
				'Full course details, delivery dates and language options will be published after the programme content is confirmed.',
			],
		},
		backToTraining: 'Back to Training',
		backToNebosh: 'Back to NEBOSH',
		contentLabel: 'Programme overview',
		contactCta: 'Discuss this training',
	},
	sr: {
		custom: {
			courseKey: 'custom-training-design',
			title: 'HSE obuke po meri',
			eyebrow: 'Dizajn obuke po meri',
			metaDescription: 'Obuke iz bezbednosti i zdravlja na radu prilagođene vašoj organizaciji, zaposlenima, propisima i kulturi.',
			shortDescription: 'Obuke iz bezbednosti i zdravlja na radu dizajnirane prema stvarnom načinu rada vaše organizacije.',
			body: [
				'Obuke iz bezbednosti i zdravlja na radu dizajniramo posebno za vašu organizaciju, uzimajući u obzir lokalno zakonodavstvo i regulatorne zahteve.',
				'Kada postoje nedostaci, obuku usklađujemo sa međunarodnim standardima i najboljom praksom, uz uključivanje identiteta, vrednosti, etike i kulture vaše kompanije.',
				'Obuka može biti razvijena na jeziku koji vaši zaposleni najbolje razumeju, uz višejezična rešenja za zaposlene i rukovodstvo.',
				'Rezultat je praktična i relevantna obuka koja odražava stvarni način rada vaše organizacije, a ne generički program namenjen svima.',
			],
			video: {
				eyebrow: 'Pregled interaktivne obuke',
				title: 'Pogledajte kako izgleda digitalni kurs po meri.',
				description: 'Ove kratke demonstracije prikazuju vođenu navigaciju kroz kurs, vizuelna objašnjenja i interaktivnu proveru znanja. Svaki program može biti prilagođen organizaciji, zaposlenima i potrebnom jeziku.',
				fallback: 'Vaš pregledač ne može da reprodukuje ovaj video. Otvorite video:',
				items: [
					{
						src: '/videos/custom-training/demo-1.mp4',
						poster: '/images/custom-training/demo-1-poster.jpg',
						title: 'Vizuelni sadržaj kursa',
						description: 'Vođeni modul pokazuje kako teme rada na visini i skela mogu biti predstavljene jasno i pregledno.',
						duration: '1:05',
					},
					{
						src: '/videos/custom-training/demo-2.mp4',
						poster: '/images/custom-training/demo-2-poster.jpg',
						title: 'Objašnjena hijerarhija rizika',
						description: 'Vizuelni prikaz povezuje nivo rizika, verovatnoću i praktične prioritete prevencije.',
						duration: '1:16',
					},
					{
						src: '/videos/custom-training/demo-3.mp4',
						poster: '/images/custom-training/demo-3-poster.jpg',
						title: 'Interaktivna provera znanja',
						description: 'Aktivnost prevlačenja pojmova učvršćuje razumevanje hijerarhije mera kontrole.',
						duration: '0:30',
					},
				],
			},
		},
		eaw: {
			courseKey: 'nebosh-eaw',
			title: 'NEBOSH Award in Environmental Awareness at Work',
			eyebrow: 'NEBOSH EAW',
			metaDescription: 'Uvodna kvalifikacija o zaštiti životne sredine namenjena zaposlenima u svim organizacijama i industrijama.',
			shortDescription: 'Praktičan uvod u uticaje na životnu sredinu, mere kontrole i dobru praksu na radnom mestu.',
			body: [
				'Ova uvodna kvalifikacija pomaže zaposlenima da razumeju uticaje radnog mesta na životnu sredinu i praktične mere koje doprinose boljim ekološkim rezultatima.',
				'Detaljan sadržaj, termini i jezičke opcije biće objavljeni nakon konačne potvrde programa.',
			],
		},
		backToTraining: 'Nazad na obuke',
		backToNebosh: 'Nazad na NEBOSH',
		contentLabel: 'Pregled programa',
		contactCta: 'Razgovarajte sa nama o obuci',
	},
} as const;

export function getEditorialCourseUi(locale: Locale) {
	return editorialCourseUi[locale];
}
