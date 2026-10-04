# HSE Training platforma

Produkcioni repozitorijum za javni HSE Training sajt, headless WordPress CMS,
WooCommerce prodaju kurseva i automatizovani deployment.

## Arhitektura

- **Astro 7 + TypeScript** generiše statički, dvojezični javni sajt.
- **WordPress + `hse-headless`** upravlja sadržajem i izlaže kontrolisani REST API.
- **WooCommerce** je izvor istine za porudžbine, statuse, refundacije i poslovni tok.
- **RaiAccept** obrađuje kartična plaćanja i povraćaj sredstava na strani banke.
- **BokaPOS** izdaje konačne fiskalne račune i fiskalne refundacije.
- **HSE e-learning platforma** je odvojeni sistem za naloge polaznika i pristup kursu.

Javni frontend ne obrađuje kartične podatke i ne menja statuse porudžbina. Sve
transakcione odluke ostaju na serveru, unutar WooCommerce/RaiAccept/BokaPOS toka.

## Okruženja

| Okruženje | Branch | Astro | CMS | Namena |
|---|---|---|---|---|
| Staging | `main` | `staging.hsetraining.rs` | `staging-cms.hsetraining.rs` | izolovano testiranje sa sandbox kredencijalima |
| Production | `production` | `hsetraining.rs` | `cms.hsetraining.rs` | javni sajt i realne transakcije |

Staging i produkcija imaju odvojene WordPress fajlove, uploads direktorijume,
baze podataka i payment/fiscal kredencijale. CMS sadržaj se ne sinhronizuje
automatski između okruženja.

## Struktura repozitorijuma

```text
apps/web/                         Astro aplikacija
wordpress/plugins/hse-headless/  HSE WordPress/CMS i commerce sloj
docs/                             ADR, API, deployment i operativna dokumentacija
infra/                            Apache, Nginx i robots konfiguracija
scripts/                          deployment i server konfiguracioni alati
.github/workflows/                CI/CD workflow-i
```

## Lokalna provera Astro aplikacije

```sh
cd apps/web
npm ci
npm run test
npm run check
npm run lint
npm run build
```

Za build mora biti dostupan odgovarajući WordPress REST API. Nevalidan ili
nepotpun obavezan CMS odgovor prekida build umesto objavljivanja nepotpune
stranice.

## Deployment

- Push Astro promena na `main` pokreće staging build i atomsku aktivaciju na
  Hetzneru.
- Push odobrenih Astro promena na `production` pokreće produkcioni build,
  backup, aktivaciju na Unlimited.rs i smoke testove.
- Produkcioni CMS može posle odobrene izmene sadržaja da debouncovano pokrene
  isti produkcioni workflow; porudžbine nikada ne pokreću frontend build.
- `hse-headless` plugin ima poseban ručni produkcioni workflow sa proverom ZIP
  arhive, PHP sintakse, integracionim testom i automatskim rollback-om.

## Dokumentacija

Kompletan tehnički pregled, tokovi kupovine, fiskalizacije, kontakt forme,
bezbednosti i deployment-a nalaze se u
[Tehničkoj dokumentaciji sistema](docs/technical-system-overview.sr.md).

Dodatni detalji:

- [Produkcioni i staging deployment](docs/deployment/unlimited.md)
- [CMS editabilnost](docs/architecture/cms-editability.md)
- [Kontakt forma i isporuka](docs/architecture/contact-delivery.md)
- [WordPress bezbednosna granica](docs/security/wordpress-cms-access.md)
- [Arhitektonske odluke](docs/adr/)

## Pravila razvoja

- Ne menjati direktno zvanične RaiAccept i BokaPOS plugin fajlove; integracione
  korekcije pripadaju repozitorijumskom `hse-headless` sloju.
- Ne čuvati lozinke, privatne ključeve, payment kredencijale ili WordPress
  konfiguraciju u Git-u.
- `course_key`/SKU je stabilni identitet kursa između CMS-a, Astro stranice i
  WooCommerce proizvoda; WordPress post ID nije javni poslovni identitet.
- Callback, email, fiskalizacija i refundacija moraju ostati idempotentni.
- Baza, uploads i WordPress runtime nisu deo običnog Astro deployment-a.
