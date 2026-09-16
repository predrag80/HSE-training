<?php
/**
 * One-time migration from brochure-site legal copy to e-commerce legal copy.
 *
 * @package HSETraining\Headless
 */

namespace HSETraining\Headless\Legal;

defined( 'ABSPATH' ) || exit;

/** Applies the approved bilingual e-commerce draft without deleting rollback data. */
final class EcommerceLegalMigration {
	private const SCHEMA_OPTION = 'hse_ecommerce_legal_schema_version';
	private const VERSION       = 2;
	private const BACKUP_SUFFIX = '_pre_ecommerce_20260916';

	/** Register the idempotent migration. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'maybe_migrate' ), 45 );
	}

	/** Replace the former brochure copy once and preserve each previous option. */
	public static function maybe_migrate(): void {
		if ( self::VERSION <= (int) get_option( self::SCHEMA_OPTION, 0 ) ) {
			return;
		}

		foreach ( self::documents() as $page_key => $translations ) {
			foreach ( $translations as $locale => $document ) {
				$option_name = LegalPageSettings::option_name( $page_key, $locale );
				$previous    = get_option( $option_name, false );
				if ( false !== $previous ) {
					add_option( $option_name . self::BACKUP_SUFFIX, $previous, '', false );
				}

				if ( false === update_option( $option_name, LegalPageSettings::sanitize_settings( $document ), false )
					&& false === get_option( $option_name, false ) ) {
					return;
				}
			}
		}

		update_option( self::SCHEMA_OPTION, self::VERSION, false );
	}

	/** @return array<string, array<string, array<string, string>>> */
	private static function documents(): array {
		return array(
			'terms' => array(
				'en' => array(
					'meta_title'       => 'Online Purchase Terms | HSE Training',
					'meta_description' => 'Terms for ordering, paying for, receiving, cancelling and making a complaint about HSE Training courses purchased online.',
					'eyebrow'          => 'E-commerce information',
					'title'            => 'Online Purchase Terms',
					'intro'            => 'These terms explain how consumers order and pay for HSE Training courses online, how course access is delivered, and how cancellations, refunds and complaints are handled.',
					'last_updated'     => 'Last updated: 16 September 2026',
					'section_1_title'  => 'Seller information and scope',
					'section_1_body'   => '<p>The seller is HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Belgrade, Serbia. Company registration number: 20952288. VAT number: 108205616. Registered activity: 7022 — Management consultancy activities. Website: <a href="https://hsetraining.rs/">hsetraining.rs</a>. Customer support and complaints: <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>, +381 61 5335 010.</p><p>These terms apply to courses that are available for online purchase. Course-specific information displayed before checkout forms part of the pre-contract information.</p>',
					'section_2_title'  => 'Course description, availability and delivery',
					'section_2_body'   => '<p>Each course page states the course title, scope, delivery format, language, current availability and other material information. Customers should review that information before ordering. Online purchases concern training services and electronically delivered enrolment or access information; no physical shipping charge applies unless a course page expressly states otherwise.</p><p>After verified payment, HSE Training sends the purchaser the confirmed enrolment and the next steps for accessing the externally delivered learning service. Any applicable start date or access deadline is shown on the course page or confirmed by email.</p>',
					'section_3_title'  => 'Prices and payment',
					'section_3_body'   => '<p>Online checkout prices are displayed and charged in Serbian dinars (RSD). The checkout shows the course, quantity, unit price, applicable tax and total amount before payment. Any additional cost must be shown before the order is submitted.</p><p>Card payments are processed through the Raiffeisen Bank RaiAccept hosted payment service. Accepted card types are displayed on the website and checkout. HSE Training does not receive or store the full card number, security code or authentication credentials entered on the hosted payment page.</p>',
					'section_4_title'  => 'Ordering and conclusion of the contract',
					'section_4_body'   => '<ol><li>Select an available course and choose Buy now to proceed directly to checkout.</li><li>Review the course, quantity, price, purchaser details and total amount.</li><li>Read and accept these Purchase Terms and the Privacy Policy.</li><li>Continue to the hosted card-payment page and complete the requested authentication.</li></ol><p>The website assigns a unique order number and shows the current transaction result. An automated receipt confirms that the order was received; enrolment is confirmed after the payment provider verifies the payment and HSE Training confirms that the purchased service can be delivered.</p>',
					'section_5_title'  => 'Payment confirmation and proof of delivery',
					'section_5_body'   => '<p>After a payment attempt, the website shows an appropriate successful, pending, cancelled or failed status and an electronic confirmation is sent to the purchaser email address. A browser return page alone is not treated as proof of payment; the order is marked paid only after provider confirmation.</p><p>For electronically delivered services, HSE Training may retain the order record, provider reference, status history, access or enrolment email and relevant server logs as proof of delivery. Delivery evidence is retained for at least 120 days and longer where accounting, consumer-protection or other legal duties require it.</p>',
					'section_6_title'  => 'Withdrawal, cancellation and refunds',
					'section_6_body'   => '<p>A consumer who concludes a distance contract may exercise the statutory right of withdrawal within 14 days, subject to the conditions and exceptions of applicable Serbian consumer law. If the consumer expressly requests that a service begin during the withdrawal period, the legal consequences of that request, including any proportionate charge or loss of the right after full performance, apply only where the required notice and explicit consent have been provided.</p><p>Send a cancellation or refund request to <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> with the order number and purchaser details. Approved card-payment refunds are returned through the original payment method within the applicable legal and payment-network deadlines. If HSE Training cancels a paid course and no acceptable alternative is agreed, the paid course amount is refunded.</p>',
					'section_7_title'  => 'Complaints and dispute resolution',
					'section_7_body'   => '<p>Complaints may be submitted to <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> or to the postal address above. Include the purchaser name, order number, course, description of the issue and the requested remedy. HSE Training records the complaint, acknowledges receipt and responds within the statutory period, including the eight-day response period applicable to consumer complaints.</p><p>Consumers may also use the competent Serbian consumer-protection authority or the official platform for out-of-court consumer dispute resolution at <a href="https://vansudsko.must.gov.rs/" target="_blank" rel="noreferrer">vansudsko.must.gov.rs</a>. Mandatory consumer rights are not limited by these terms.</p>',
				),
				'sr' => array(
					'meta_title'       => 'Uslovi online kupovine | HSE Training',
					'meta_description' => 'Uslovi poručivanja, plaćanja, isporuke, otkazivanja i reklamacije za HSE Training kurseve kupljene preko interneta.',
					'eyebrow'          => 'Informacije o e-trgovini',
					'title'            => 'Uslovi online kupovine',
					'intro'            => 'Ovi uslovi objašnjavaju kako potrošač poručuje i plaća HSE Training kurs preko interneta, kako se isporučuje pristup kursu i kako se rešavaju otkazivanje, povraćaj sredstava i reklamacije.',
					'last_updated'     => 'Poslednje ažuriranje: 16. septembra 2026.',
					'section_1_title'  => 'Podaci o prodavcu i primena uslova',
					'section_1_body'   => '<p>Prodavac je HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Beograd, Srbija. Matični broj: 20952288. PIB: 108205616. Pretežna delatnost: 7022 — Konsultantske aktivnosti u vezi s poslovanjem i ostalim upravljanjem. Internet adresa: <a href="https://hsetraining.rs/">hsetraining.rs</a>. Podrška kupcima i reklamacije: <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>, +381 61 5335 010.</p><p>Ovi uslovi primenjuju se na kurseve koji su dostupni za onlajn kupovinu. Informacije o konkretnom kursu prikazane pre plaćanja čine deo predugovornog obaveštenja.</p>',
					'section_2_title'  => 'Opis kursa, dostupnost i isporuka',
					'section_2_body'   => '<p>Na stranici kursa prikazani su naziv, sadržaj, način realizacije, jezik, aktuelna dostupnost i druge bitne informacije. Kupac treba da ih proveri pre poručivanja. Online kupovina odnosi se na uslugu obuke i elektronsku dostavu potvrde prijave ili pristupnih informacija; nema troška fizičke dostave osim ako je na stranici kursa izričito navedeno drugačije.</p><p>Nakon potvrđenog plaćanja, HSE Training šalje potvrdu prijave i sledeće korake za pristup eksterno pruženoj usluzi učenja. Datum početka ili rok za pristup prikazuje se na stranici kursa ili potvrđuje emailom.</p>',
					'section_3_title'  => 'Cene i način plaćanja',
					'section_3_body'   => '<p>Cene u online naplati prikazane su i naplaćuju se u dinarima (RSD). Pre plaćanja checkout prikazuje kurs, količinu, pojedinačnu cenu, primenljivi porez i ukupan iznos. Svaki dodatni trošak mora biti prikazan pre slanja porudžbine.</p><p>Kartično plaćanje obrađuje se preko hostovanog servisa Raiffeisen Bank RaiAccept. Prihvaćene kartice prikazane su na sajtu i checkout stranici. HSE Training ne prima niti čuva puni broj kartice, sigurnosni kod ili podatke za autentifikaciju koje kupac unosi na hostovanoj stranici za plaćanje.</p>',
					'section_4_title'  => 'Koraci kupovine i zaključenje ugovora',
					'section_4_body'   => '<ol><li>Izaberite dostupan kurs i kliknite na „Kupi sada“ da biste prešli direktno na checkout.</li><li>Proverite kurs, količinu, cenu, podatke o kupcu i ukupan iznos.</li><li>Pročitajte i prihvatite ove Uslove kupovine i Politiku privatnosti.</li><li>Nastavite na hostovanu stranicu za kartično plaćanje i završite zahtevanu autentifikaciju.</li></ol><p>Sajt dodeljuje jedinstveni broj porudžbine i prikazuje aktuelni rezultat transakcije. Automatska potvrda potvrđuje prijem porudžbine; prijava na kurs potvrđena je nakon što procesor plaćanja potvrdi uplatu i HSE Training potvrdi da kupljena usluga može biti isporučena.</p>',
					'section_5_title'  => 'Potvrda plaćanja i dokaz isporuke',
					'section_5_body'   => '<p>Nakon pokušaja plaćanja sajt prikazuje odgovarajući status uspešnog, neuspešnog, otkazanog ili plaćanja na čekanju, a elektronska potvrda šalje se na email kupca. Povratna stranica u pregledaču sama po sebi nije dokaz plaćanja; porudžbina se označava plaćenom tek nakon potvrde procesora.</p><p>Kod elektronski isporučenih usluga HSE Training može čuvati porudžbinu, referencu procesora, istoriju statusa, email o pristupu ili prijavi i relevantne serverske logove kao dokaz isporuke. Dokaz se čuva najmanje 120 dana, odnosno duže kada to zahtevaju računovodstvene, potrošačke ili druge zakonske obaveze.</p>',
					'section_6_title'  => 'Odustanak, otkazivanje i povraćaj sredstava',
					'section_6_body'   => '<p>Potrošač koji zaključi ugovor na daljinu može ostvariti zakonsko pravo na odustanak u roku od 14 dana, uz uslove i izuzetke propisane važećim pravom Republike Srbije. Ako potrošač izričito zahteva da pružanje usluge počne tokom roka za odustanak, pravne posledice takvog zahteva, uključujući moguću srazmernu naknadu ili gubitak prava nakon potpunog izvršenja, primenjuju se samo kada su dato propisano obaveštenje i izričita saglasnost.</p><p>Zahtev za otkazivanje ili povraćaj pošaljite na <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> uz broj porudžbine i podatke kupca. Odobreni povraćaj kartične uplate vrši se istim načinom plaćanja u primenljivom zakonskom roku i roku platne mreže. Ako HSE Training otkaže plaćeni kurs, a kupac ne prihvati odgovarajuću zamenu, vraća se plaćeni iznos kursa.</p>',
					'section_7_title'  => 'Reklamacije i rešavanje sporova',
					'section_7_body'   => '<p>Reklamacija se može poslati na <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> ili na poštansku adresu prodavca. Navedite ime kupca, broj porudžbine, kurs, opis problema i zahtevani način rešavanja. HSE Training evidentira reklamaciju, potvrđuje njen prijem i odgovara u zakonskom roku, uključujući rok od osam dana za odgovor na potrošačku reklamaciju.</p><p>Potrošač može koristiti i nadležni organ za zaštitu potrošača ili zvaničnu platformu za vansudsko rešavanje potrošačkih sporova na <a href="https://vansudsko.must.gov.rs/" target="_blank" rel="noreferrer">vansudsko.must.gov.rs</a>. Ovi uslovi ne ograničavaju obavezna prava potrošača.</p>',
				),
			),
			'privacy' => array(
				'en' => array(
					'meta_title'       => 'Privacy Policy | HSE Training',
					'meta_description' => 'How HSE Training processes website, enquiry, checkout, order, payment-status and course-delivery data.',
					'eyebrow'          => 'Legal information',
					'title'            => 'Privacy Policy',
					'intro'            => 'This policy explains how HSE Training processes personal data when you browse the website, contact us, place an order, attempt payment or receive course access.',
					'last_updated'     => 'Last updated: 16 September 2026',
					'section_1_title'  => 'Controller and contact details',
					'section_1_body'   => '<p>HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Belgrade, Serbia (registration number 20952288; VAT number 108205616) is the controller. Privacy questions and requests may be sent to <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> or made by telephone on +381 61 5335 010.</p>',
					'section_2_title'  => 'Data we process',
					'section_2_body'   => '<p>Depending on your interaction, we process contact and enquiry data; billing and order details; selected course, quantity, price and currency; order and payment status, provider references and timestamps; course-enrolment and delivery records; and proportionate technical and security logs such as IP address, browser, requested URL and time.</p><p>Card numbers, card security codes and 3-D Secure authentication credentials are entered on the hosted payment-provider page and are not received or stored by HSE Training.</p>',
					'section_3_title'  => 'Purposes and legal bases',
					'section_3_body'   => '<p>We use personal data to answer enquiries, take pre-contract steps, create and administer orders, reconcile and refund payments, confirm course enrolment, deliver access information, handle complaints, prevent fraud, secure the service, keep required accounting and transaction records and establish or defend legal claims.</p><p>Processing is based, as applicable, on steps requested before a contract, performance of a contract, compliance with legal obligations, proportionate legitimate interests or consent where the law specifically requires it.</p>',
					'section_4_title'  => 'Payment, hosting and delivery providers',
					'section_4_body'   => '<p>Necessary data may be processed by the website and hosting providers, authenticated email provider, WooCommerce order system, Raiffeisen Bank/RaiAccept payment service, professional advisers and the external learning service used to fulfil a paid enrolment. Public authorities may receive data where disclosure is legally required.</p><p>Payment-status data is accepted only through the configured provider flow. We do not sell personal data.</p>',
					'section_5_title'  => 'Retention and delivery evidence',
					'section_5_body'   => '<p>Transaction and electronic-delivery evidence is kept for at least 120 days so that payment disputes can be investigated. Order, invoice, tax, complaint and contractual records may be retained longer where accounting, consumer-protection, limitation or other legal rules require it. Records are then deleted, anonymised or securely archived according to the applicable retention requirement.</p>',
					'section_6_title'  => 'Security, cookies and external content',
					'section_6_body'   => '<p>We apply proportionate access, transport-security, update, backup and logging controls. Essential cookies or similar storage may be used for checkout, session state, language, security and fraud prevention. Embedded maps and links to external services are governed by the relevant provider privacy terms.</p><p>No internet service can guarantee absolute security. Please contact us promptly if you suspect misuse of your data or order.</p>',
					'section_7_title'  => 'Your rights and policy changes',
					'section_7_body'   => '<p>Subject to applicable law, you may request access, correction, deletion, restriction or portability, object to processing, or withdraw consent without affecting earlier lawful processing. We may verify identity before acting on a request. You may also complain to the Serbian Commissioner for Information of Public Importance and Personal Data Protection at <a href="https://poverenik.rs/en/" target="_blank" rel="noreferrer">poverenik.rs</a>.</p><p>We update this policy when the website, providers or legal requirements change and publish the effective date here.</p>',
				),
				'sr' => array(
					'meta_title'       => 'Politika privatnosti | HSE Training',
					'meta_description' => 'Kako HSE Training obrađuje podatke sa sajta, upita, checkouta, porudžbine, statusa plaćanja i isporuke kursa.',
					'eyebrow'          => 'Pravne informacije',
					'title'            => 'Politika privatnosti',
					'intro'            => 'Ova politika objašnjava kako HSE Training obrađuje podatke o ličnosti kada pregledate sajt, kontaktirate nas, poručite kurs, pokušate plaćanje ili dobijete pristup kursu.',
					'last_updated'     => 'Poslednje ažuriranje: 16. septembra 2026.',
					'section_1_title'  => 'Rukovalac i kontakt podaci',
					'section_1_body'   => '<p>HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Beograd, Srbija (matični broj 20952288; PIB 108205616) je rukovalac podacima. Pitanja i zahteve u vezi sa privatnošću možete poslati na <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> ili uputiti telefonom na +381 61 5335 010.</p>',
					'section_2_title'  => 'Podaci koje obrađujemo',
					'section_2_body'   => '<p>U zavisnosti od vaše aktivnosti obrađujemo podatke iz kontakta i upita; podatke za obračun i porudžbinu; izabrani kurs, količinu, cenu i valutu; status porudžbine i plaćanja, reference procesora i vreme događaja; podatke o prijavi i isporuci kursa; i srazmerne tehničke i bezbednosne logove kao što su IP adresa, pregledač, traženi URL i vreme.</p><p>Broj kartice, sigurnosni kod kartice i podaci za 3-D Secure autentifikaciju unose se na hostovanoj stranici procesora plaćanja i HSE Training ih ne prima niti čuva.</p>',
					'section_3_title'  => 'Svrhe i pravni osnovi',
					'section_3_body'   => '<p>Podatke koristimo da odgovorimo na upit, preduzmemo radnje pre ugovora, kreiramo i administriramo porudžbinu, uskladimo i vratimo uplatu, potvrdimo prijavu, isporučimo pristupne informacije, rešimo reklamaciju, sprečimo prevaru, zaštitimo servis, vodimo potrebnu računovodstvenu i transakcionu evidenciju i ostvarimo ili odbranimo pravni zahtev.</p><p>Obrada se, prema okolnostima, zasniva na radnjama zatraženim pre ugovora, izvršenju ugovora, pravnoj obavezi, srazmernom legitimnom interesu ili pristanku kada ga zakon posebno zahteva.</p>',
					'section_4_title'  => 'Pružaoci plaćanja, hostinga i isporuke',
					'section_4_body'   => '<p>Neophodne podatke mogu obrađivati pružaoci sajta i hostinga, autentifikovanog emaila, WooCommerce sistema porudžbina, Raiffeisen Bank/RaiAccept platne usluge, stručni savetnici i spoljna platforma za učenje korišćena za realizaciju plaćene prijave. Nadležni organi mogu dobiti podatke kada je otkrivanje zakonski obavezno.</p><p>Status plaćanja prihvata se samo kroz podešeni tok procesora. Podatke o ličnosti ne prodajemo.</p>',
					'section_5_title'  => 'Rok čuvanja i dokaz isporuke',
					'section_5_body'   => '<p>Evidencija transakcije i elektronske isporuke čuva se najmanje 120 dana radi provere eventualnog spora u vezi sa plaćanjem. Porudžbine, računi, poreska, reklamaciona i ugovorna evidencija mogu se čuvati duže kada to zahtevaju računovodstveni, potrošački, rokovi zastarelosti ili druge zakonske obaveze. Po isteku odgovarajućeg roka podaci se brišu, anonimizuju ili bezbedno arhiviraju.</p>',
					'section_6_title'  => 'Bezbednost, kolačići i spoljni sadržaj',
					'section_6_body'   => '<p>Primenjujemo srazmerne kontrole pristupa, zaštitu prenosa, ažuriranje, bekap i bezbednosno evidentiranje. Neophodni kolačići ili slično skladištenje mogu se koristiti za checkout, stanje sesije, jezik, bezbednost i sprečavanje prevare. Ugrađene mape i linkovi ka spoljnim servisima podležu pravilima privatnosti tih pružalaca.</p><p>Nijedan internet servis ne može garantovati apsolutnu bezbednost. Kontaktirajte nas bez odlaganja ako sumnjate na zloupotrebu podataka ili porudžbine.</p>',
					'section_7_title'  => 'Vaša prava i izmene politike',
					'section_7_body'   => '<p>Pod uslovima važećeg prava možete tražiti pristup, ispravku, brisanje, ograničenje ili prenosivost, podneti prigovor na obradu ili opozvati pristanak bez uticaja na raniju zakonitu obradu. Pre postupanja možemo proveriti identitet. Možete podneti pritužbu Povereniku za informacije od javnog značaja i zaštitu podataka o ličnosti na <a href="https://poverenik.rs/" target="_blank" rel="noreferrer">poverenik.rs</a>.</p><p>Politiku ažuriramo kada se promene sajt, pružaoci ili pravni zahtevi i ovde objavljujemo datum početka primene.</p>',
				),
			),
		);
	}
}
