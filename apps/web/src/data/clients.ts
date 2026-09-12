export interface Client {
	readonly name: string;
	readonly logo?: string;
}

export const clients: readonly Client[] = [
	{ name: 'Elektromontaža d.o.o. Kraljevo', logo: '/images/clients/elektromontaza.png' },
	{ name: 'NIS a.d. Novi Sad', logo: '/images/clients/nis.png' },
	{ name: 'INA', logo: '/images/clients/ina.jpg' },
	{ name: 'Coca-Cola Hellenic', logo: '/images/clients/coca-cola-hbc.svg' },
	{ name: 'Nestlé Adriatic', logo: '/images/clients/nestle.svg' },
	{ name: 'Tara Resources', logo: '/images/clients/tara-resources.png' },
	{ name: 'HSEES', logo: '/images/clients/hsees.jpg' },
	{ name: 'Moravacem', logo: '/images/clients/moravacem.png' },
	{ name: 'Freyssinet Romania', logo: '/images/clients/freyssinet-romania.webp' },
	{ name: 'Feromont Inženjering', logo: '/images/clients/feromont.png' },
	{ name: 'Freyssinet Serbia', logo: '/images/clients/freyssinet-serbia.svg' },
	{ name: 'ARUP Beograd', logo: '/images/clients/arup.png' },
	{ name: 'Ball Global Business Services EMEA', logo: '/images/clients/ball.svg' },
	{ name: 'JCHX Kinsey Mining Construction', logo: '/images/clients/jchx.png' },
	{ name: 'Kreativa Unlimited', logo: '/images/clients/kreativa.jpg' },
	{ name: 'GOPA International Energy Consultants', logo: '/images/clients/gopa.svg' },
	{ name: 'MOL Serbia', logo: '/images/clients/mol.svg' },
	{ name: 'Vinstrol', logo: '/images/clients/vinstrol.webp' },
] as const;
