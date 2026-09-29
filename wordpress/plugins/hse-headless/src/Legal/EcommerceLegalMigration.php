<?php
/** Versioned migration for bilingual e-commerce legal copy. */

namespace HSETraining\Headless\Legal;

defined( 'ABSPATH' ) || exit;

/** Applies reviewed legal drafts while preserving previous CMS options. */
final class EcommerceLegalMigration {
	private const SCHEMA_OPTION = 'hse_ecommerce_legal_schema_version';
	private const VERSION       = 4;
	private const BACKUP_SUFFIX = '_pre_digital_delivery_20260929';
	private const ACCESS_TERMS_BACKUP_SUFFIX = '_pre_access_term_20260929';

	/** Register the idempotent migration. */
	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'maybe_migrate' ), 45 );
	}

	/** Replace the prior draft once and preserve each previous option. */
	public static function maybe_migrate(): void {
		$current_version = (int) get_option( self::SCHEMA_OPTION, 0 );
		if ( self::VERSION <= $current_version ) {
			return;
		}

		if ( 3 > $current_version ) {
			if ( ! self::migrate_all_documents() ) {
				return;
			}
		} elseif ( ! self::migrate_course_access_terms() ) {
			return;
		}

		update_option( self::SCHEMA_OPTION, self::VERSION, false );
	}

	/** Install the full reviewed document set for sites that predate schema version 3. */
	private static function migrate_all_documents(): bool {
		foreach ( self::documents() as $page_key => $translations ) {
			foreach ( $translations as $locale => $document ) {
				$option_name = LegalPageSettings::option_name( $page_key, $locale );
				$previous    = get_option( $option_name, false );
				if ( false !== $previous ) {
					add_option( $option_name . self::BACKUP_SUFFIX, $previous, '', false );
				}

				if ( false === update_option( $option_name, LegalPageSettings::sanitize_settings( $document ), false )
					&& false === get_option( $option_name, false ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/** Update only the access terms so unrelated editor changes remain intact. */
	private static function migrate_course_access_terms(): bool {
		$documents = self::documents();
		foreach ( array( 'en', 'sr' ) as $locale ) {
			$option_name = LegalPageSettings::option_name( 'terms', $locale );
			$previous    = get_option( $option_name, false );
			if ( ! is_array( $previous ) ) {
				return false;
			}

			add_option( $option_name . self::ACCESS_TERMS_BACKUP_SUFFIX, $previous, '', false );
			$previous['section_2_body'] = $documents['terms'][ $locale ]['section_2_body'];

			if ( false === update_option( $option_name, LegalPageSettings::sanitize_settings( $previous ), false )
				&& false === get_option( $option_name, false ) ) {
				return false;
			}
		}

		return true;
	}

	/** @return array<string, array<string, array<string, string>>> */
	private static function documents(): array {
		return array(
			'terms' => array(
				'en' => array(
					'meta_title'       => 'Online Purchase Terms | HSE Training',
					'meta_description' => 'Terms for ordering, paying for and receiving immediate digital access to HSE Training courses.',
					'eyebrow'          => 'E-commerce information',
					'title'            => 'Online Purchase Terms',
					'intro'            => 'These terms explain online ordering, payment, immediate digital course delivery, withdrawal, refunds and complaints.',
					'last_updated'     => 'Last updated: 29 September 2026',
					'section_1_title'  => 'Seller information and scope',
					'section_1_body'   => '<p>The seller is HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Belgrade, Serbia. Company registration number: 20952288. Tax identification number (PIB): 108205616. Registered activity: 7022 — Management consultancy activities. Website: <a href="https://hsetraining.rs/">hsetraining.rs</a>. Customer support and complaints: <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>, +381 61 5335 010.</p><p>These terms apply to courses offered for online purchase. The course description, language, price, access period and technical requirements shown before checkout form part of the pre-contract information.</p>',
					'section_2_title'  => 'Digital course delivery and access',
					'section_2_body'   => '<p>The purchased course is delivered digitally through an external learning platform and no physical shipping applies. After confirmed payment, the purchaser receives instructions or a link to create an account on that platform. The purchaser can create the account and begin the course immediately. Standard access lasts 12 months from the purchase date.</p><p>If the purchaser has not completed the course within that period, further access to the external learning portal may be arranged subject to an additional extension fee charged separately by HSE Training. HSE Training will provide the applicable fee and payment instructions before an extension is activated. The extension fee is not calculated or collected through this website.</p><p>Card delivery begins after the payment provider verifies the transaction. For a direct bank transfer, delivery begins only after HSE Training confirms that the funds have been credited. If account-creation instructions do not arrive or access does not work, contact <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> and quote the order number.</p>',
					'section_3_title'  => 'Prices, card payment and bank transfer',
					'section_3_body'   => '<p>Checkout prices are displayed and charged in Serbian dinars (RSD). Before submission, checkout shows the course, quantity, unit price, applicable tax and total amount. Any additional cost must be shown before the order is placed.</p><p>Card payments are processed on the Raiffeisen Bank RaiAccept hosted service. HSE Training does not receive or store the full card number, security code or 3-D Secure credentials. If direct bank transfer is selected, the order confirmation provides the bank details and order reference. Creating a bank-transfer order does not constitute payment or activate course access; access begins after the funds are received and confirmed.</p>',
					'section_4_title'  => 'Ordering and conclusion of the contract',
					'section_4_body'   => '<ol><li>Select an available course and choose Buy now.</li><li>Review the course, price, customer data and total amount.</li><li>Choose a payment method.</li><li>Accept the Purchase Terms and Privacy Policy.</li><li>If immediate digital delivery is requested, provide the separate express consent and acknowledgement shown at checkout.</li><li>Submit the order using the button that clearly indicates the payment obligation.</li></ol><p>The website assigns a unique order number. A card order becomes paid only after verified provider confirmation. A direct-bank-transfer order remains unpaid until the funds are credited and confirmed. A browser return page alone is not proof of payment.</p>',
					'section_5_title'  => 'Confirmation, fiscal receipt and delivery evidence',
					'section_5_body'   => '<p>Electronic order and payment confirmations are sent to the email address supplied by the purchaser. Where required, BokaPOS processes the data needed for fiscalisation and delivers the electronic fiscal receipt or its verification information. HSE Training may retain the order record, payment reference, fiscalisation status, consent version and time, access email and relevant security logs as evidence of the transaction and digital delivery.</p><p>The purchaser is responsible for providing a working email address and checking spam or junk folders. Contact support promptly if an expected confirmation, receipt or access instruction is missing.</p>',
					'section_6_title'  => 'Withdrawal, cancellation and refunds',
					'section_6_body'   => '<p>A consumer normally has 14 days to withdraw from a distance contract, subject to statutory exceptions. Where the consumer separately and expressly requests immediate supply of digital content and acknowledges the legal consequence, the right to withdraw is lost when digital delivery begins to the extent provided by applicable law. This does not limit rights concerning non-conforming, inaccessible or incorrectly supplied content.</p><p>Where a right of withdrawal applies, the purchaser may use the <a href="/withdrawal-form/">withdrawal form</a> or send an unambiguous request to <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>. A required refund is made without undue delay and no later than 14 days after a valid withdrawal notice, using the original payment method unless expressly agreed otherwise. Payment and fiscal records are corrected through the applicable provider procedure.</p>',
					'section_7_title'  => 'Complaints and dispute resolution',
					'section_7_body'   => '<p>Complaints may be submitted to <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> or to the seller postal address. Include the purchaser name, order number, course, description of the issue and requested remedy. HSE Training acknowledges and records the complaint, provides a complaint reference and responds within eight days. The complaint is resolved within the applicable statutory period, normally no later than 15 days unless a lawful extension is agreed.</p><p>Consumers may use the competent consumer-protection authority or the official out-of-court dispute platform at <a href="https://vansudsko.must.gov.rs/" target="_blank" rel="noreferrer">vansudsko.must.gov.rs</a>. Mandatory consumer and conformity rights are not limited by these terms.</p>',
				),
				'sr' => array(
					'meta_title'       => 'Uslovi online kupovine | HSE Training',
					'meta_description' => 'Uslovi poručivanja, plaćanja i trenutne digitalne isporuke HSE Training kurseva.',
					'eyebrow'          => 'Informacije o e-trgovini',
					'title'            => 'Uslovi online kupovine',
					'intro'            => 'Ovi uslovi objašnjavaju online poručivanje, plaćanje, trenutnu digitalnu isporuku kursa, odustanak, povraćaj sredstava i reklamacije.',
					'last_updated'     => 'Poslednje ažuriranje: 29. septembra 2026.',
					'section_1_title'  => 'Podaci o prodavcu i primena uslova',
					'section_1_body'   => '<p>Prodavac je HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Beograd, Srbija. Matični broj: 20952288. PIB: 108205616. Pretežna delatnost: 7022 — Konsultantske aktivnosti u vezi s poslovanjem i ostalim upravljanjem. Internet adresa: <a href="https://hsetraining.rs/">hsetraining.rs</a>. Podrška kupcima i reklamacije: <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>, +381 61 5335 010.</p><p>Ovi uslovi primenjuju se na kurseve dostupne za online kupovinu. Opis kursa, jezik, cena, period pristupa i tehnički zahtevi prikazani pre checkouta čine deo predugovornog obaveštenja.</p>',
					'section_2_title'  => 'Digitalna isporuka kursa i pristup',
					'section_2_body'   => '<p>Kupljeni kurs isporučuje se digitalno preko spoljne platforme za učenje i nema fizičke dostave. Nakon potvrđenog plaćanja kupac dobija uputstvo ili link za kreiranje naloga na toj platformi. Kupac može odmah da kreira nalog i započne kurs. Standardni pristup traje 12 meseci od datuma kupovine.</p><p>Ako kupac ne završi kurs u tom periodu, dodatni pristup spoljnom portalu za učenje može se dogovoriti uz naknadu za produženje koju HSE Training naplaćuje odvojeno. HSE Training će kupcu saopštiti važeći iznos i instrukcije za plaćanje pre aktiviranja produženja. Naknada za produženje ne obračunava se niti naplaćuje preko ovog sajta.</p><p>Kod kartičnog plaćanja isporuka počinje nakon što procesor potvrdi transakciju. Kod direktne uplate na račun isporuka počinje tek nakon što HSE Training potvrdi priliv sredstava. Ako uputstvo za kreiranje naloga ne stigne ili pristup ne radi, kupac treba da kontaktira <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> i navede broj porudžbine.</p>',
					'section_3_title'  => 'Cene, kartično plaćanje i direktna uplata',
					'section_3_body'   => '<p>Cene na checkoutu prikazane su i naplaćuju se u dinarima (RSD). Pre slanja porudžbine prikazuju se kurs, količina, pojedinačna cena, primenljivi porez i ukupan iznos. Svaki dodatni trošak mora biti prikazan pre poručivanja.</p><p>Kartično plaćanje obrađuje se na hostovanom servisu Raiffeisen Bank RaiAccept. HSE Training ne prima niti čuva puni broj kartice, sigurnosni kod ili 3-D Secure podatke. Ako kupac izabere direktnu uplatu, u potvrdi porudžbine dobija podatke računa i poziv na broj. Kreiranje porudžbine za direktnu uplatu ne znači da je kurs plaćen niti aktivira pristup; pristup počinje nakon prijema i potvrde sredstava.</p>',
					'section_4_title'  => 'Koraci kupovine i zaključenje ugovora',
					'section_4_body'   => '<ol><li>Izaberite dostupan kurs i kliknite na „Kupi sada“.</li><li>Proverite kurs, cenu, podatke kupca i ukupan iznos.</li><li>Izaberite način plaćanja.</li><li>Prihvatite Uslove kupovine i Politiku privatnosti.</li><li>Ako zahtevate trenutnu digitalnu isporuku, dajte posebnu izričitu saglasnost i potvrdu prikazanu na checkoutu.</li><li>Pošaljite porudžbinu dugmetom koje jasno označava obavezu plaćanja.</li></ol><p>Sajt dodeljuje jedinstveni broj porudžbine. Kartična porudžbina označava se plaćenom tek nakon proverene potvrde procesora. Porudžbina sa direktnom uplatom ostaje neplaćena do evidentiranja i potvrde priliva. Povratna stranica pregledača sama po sebi nije dokaz plaćanja.</p>',
					'section_5_title'  => 'Potvrda, fiskalni račun i dokaz isporuke',
					'section_5_body'   => '<p>Elektronska potvrda porudžbine i plaćanja šalje se na email kupca. Kada je primenljivo, BokaPOS obrađuje podatke potrebne za fiskalizaciju i dostavlja elektronski fiskalni račun ili podatke za njegovu proveru. HSE Training može čuvati porudžbinu, referencu plaćanja, status fiskalizacije, verziju i vreme saglasnosti, email o pristupu i relevantne bezbednosne logove kao dokaz transakcije i digitalne isporuke.</p><p>Kupac je odgovoran za unos ispravne email adrese i proveru spam foldera. Ako očekivana potvrda, račun ili uputstvo za pristup nedostaje, potrebno je bez odlaganja kontaktirati podršku.</p>',
					'section_6_title'  => 'Odustanak, otkazivanje i povraćaj sredstava',
					'section_6_body'   => '<p>Potrošač po pravilu ima 14 dana da odustane od ugovora na daljinu, uz zakonske izuzetke. Kada potrošač odvojeno i izričito zahteva trenutnu isporuku digitalnog sadržaja i potvrdi da razume pravnu posledicu, pravo na odustanak gubi se početkom digitalne isporuke u meri propisanoj važećim zakonom. To ne ograničava prava u slučaju nesaobraznog, nedostupnog ili pogrešno isporučenog sadržaja.</p><p>Kada pravo na odustanak postoji, kupac može koristiti <a href="/sr/withdrawal-form/">obrazac za odustanak</a> ili poslati nedvosmislen zahtev na <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>. Obavezan povraćaj vrši se bez odlaganja, a najkasnije u roku od 14 dana od prijema važeće izjave, istim sredstvom plaćanja osim ako je izričito dogovoreno drugačije. Platna i fiskalna evidencija koriguju se kroz postupak odgovarajućeg pružaoca.</p>',
					'section_7_title'  => 'Reklamacije i rešavanje sporova',
					'section_7_body'   => '<p>Reklamacija se može poslati na <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> ili na poštansku adresu prodavca. Potrebno je navesti ime kupca, broj porudžbine, kurs, opis problema i zahtevani način rešavanja. HSE Training potvrđuje i evidentira reklamaciju, dostavlja broj pod kojim je zavedena i odgovara u roku od osam dana. Reklamacija se rešava u primenljivom zakonskom roku, po pravilu najkasnije u roku od 15 dana, osim ako je zakonito ugovoreno produženje.</p><p>Potrošač može koristiti nadležni organ za zaštitu potrošača ili zvaničnu platformu za vansudsko rešavanje sporova na <a href="https://vansudsko.must.gov.rs/" target="_blank" rel="noreferrer">vansudsko.must.gov.rs</a>. Ovi uslovi ne ograničavaju obavezna prava potrošača i prava zbog nesaobraznosti.</p>',
				),
			),
			'privacy' => array(
				'en' => array(
					'meta_title'       => 'Privacy and Cookie Policy | HSE Training',
					'meta_description' => 'How HSE Training processes website, enquiry, checkout, payment, fiscalisation and digital course access data.',
					'eyebrow'          => 'Legal information',
					'title'            => 'Privacy and Cookie Policy',
					'intro'            => 'This policy explains how personal data and cookies are used when you browse, contact us, place an order, pay and receive digital course access.',
					'last_updated'     => 'Last updated: 29 September 2026',
					'section_1_title'  => 'Controller and contact details',
					'section_1_body'   => '<p>HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Belgrade, Serbia (company registration number 20952288; tax identification number (PIB) 108205616) is the controller. Privacy questions and requests may be sent to <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> or made by telephone on +381 61 5335 010.</p>',
					'section_2_title'  => 'Data we process and whether it is required',
					'section_2_body'   => '<p>Depending on the interaction, we process contact and enquiry data; billing, company and tax-identification data; selected course, price and currency; order, payment, refund and fiscalisation status; provider references and timestamps; the checkout language; the version and time of legal consents; account-creation and course-delivery evidence; and proportionate technical and security logs such as IP address, browser, requested URL and time.</p><p>Required checkout data is necessary to conclude and perform the contract, process payment, issue required records and deliver course access. Without it we cannot complete the order. Card numbers, security codes and 3-D Secure credentials are entered on the hosted bank page and are not received or stored by HSE Training.</p>',
					'section_3_title'  => 'Purposes and legal bases',
					'section_3_body'   => '<p>Enquiries and checkout preparation are processed to take requested pre-contract steps. Orders, payment reconciliation, refunds, account-creation instructions and course access are processed to perform the contract. Fiscal, accounting and complaint records are processed to comply with legal duties. Service security, fraud prevention, delivery evidence and legal claims are processed for the legitimate interests of operating and protecting the service and demonstrating compliance, subject to the required balancing assessment. Optional external media is loaded only with consent.</p><p>We do not use checkout data for automated decision-making or profiling that produces legal or similarly significant effects.</p>',
					'section_4_title'  => 'Providers, recipients and international transfers',
					'section_4_body'   => '<p>Necessary data may be processed by our hosting and authenticated email providers; WooCommerce for order administration; Raiffeisen Bank/RaiAccept for hosted card payment; BokaPOS for fiscalisation and electronic fiscal receipts; professional advisers; public authorities where legally required; and the external learning platform on which the purchaser creates an account and accesses the paid course.</p><p>Only the data required for each purpose is shared. If a provider processes data outside Serbia, HSE Training will use an applicable legal transfer mechanism and make information about the relevant safeguards available. We do not sell personal data.</p>',
					'section_5_title'  => 'Retention and delivery evidence',
					'section_5_body'   => '<p>Transaction and electronic-delivery evidence is retained for at least 120 days and longer while a payment dispute, complaint or legal claim may be pursued. Complaint records are kept for at least two years. Order, payment, fiscal, tax, accounting and contractual records are retained for the periods required by applicable law. Enquiries, security logs and consent records are kept only for as long as necessary for their stated purpose, security investigation or proof of compliance, after which they are deleted, anonymised or securely archived.</p>',
					'section_6_title'  => 'Cookies and external content',
					'section_6_body'   => '<p>Necessary storage includes <code>hse_cookie_consent</code> (your choice for 180 days), <code>hse_checkout_locale</code> (checkout language for up to one month) and WooCommerce session/cart cookies such as <code>woocommerce_cart_hash</code>, <code>woocommerce_items_in_cart</code> and <code>wp_woocommerce_session_*</code>. They support language, consent choice, cart, checkout, security and fraud prevention and cannot be disabled where required for the requested service.</p><p>Google Maps is optional and loads only after consent; loading it may disclose IP address and browser data to Google. Cookie Settings in the website footer lets you change or withdraw that choice at any time. RaiAccept or another external service may set its own necessary cookies on its domain under its own notice.</p>',
					'section_7_title'  => 'Your rights and policy changes',
					'section_7_body'   => '<p>Subject to applicable law, you may request access, correction, deletion, restriction or portability, object to processing, or withdraw consent without affecting earlier lawful processing. We may verify identity before acting and normally respond within 30 days. You may complain to the Serbian Commissioner for Information of Public Importance and Personal Data Protection at <a href="https://poverenik.rs/en/" target="_blank" rel="noreferrer">poverenik.rs</a>.</p><p>We update this policy when the website, providers or legal requirements change and publish the effective date here.</p>',
				),
				'sr' => array(
					'meta_title'       => 'Politika privatnosti i kolačića | HSE Training',
					'meta_description' => 'Kako HSE Training obrađuje podatke sa sajta, checkouta, plaćanja, fiskalizacije i digitalne isporuke kursa.',
					'eyebrow'          => 'Pravne informacije',
					'title'            => 'Politika privatnosti i kolačića',
					'intro'            => 'Ova politika objašnjava kako koristimo podatke o ličnosti i kolačiće kada pregledate sajt, kontaktirate nas, poručite, platite i dobijete digitalni pristup kursu.',
					'last_updated'     => 'Poslednje ažuriranje: 29. septembra 2026.',
					'section_1_title'  => 'Rukovalac i kontakt podaci',
					'section_1_body'   => '<p>HSE Training DOO, Braće Radovanović 17/5, Lamela C, 11000 Beograd, Srbija (matični broj 20952288; PIB 108205616) je rukovalac podacima. Pitanja i zahteve u vezi sa privatnošću možete poslati na <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a> ili uputiti telefonom na +381 61 5335 010.</p>',
					'section_2_title'  => 'Podaci koje obrađujemo i obaveznost davanja',
					'section_2_body'   => '<p>U zavisnosti od aktivnosti obrađujemo kontakt podatke i upite; podatke za obračun, kompaniju i poresku identifikaciju; izabrani kurs, cenu i valutu; status porudžbine, plaćanja, povraćaja i fiskalizacije; reference pružalaca i vreme događaja; jezik checkouta; verziju i vreme pravnih saglasnosti; dokaz kreiranja naloga i isporuke kursa; i srazmerne tehničke i bezbednosne logove kao što su IP adresa, pregledač, traženi URL i vreme.</p><p>Obavezni checkout podaci potrebni su za zaključenje i izvršenje ugovora, obradu plaćanja, izdavanje propisanih evidencija i dostavu pristupa kursu. Bez njih ne možemo završiti porudžbinu. Broj kartice, sigurnosni kod i 3-D Secure podaci unose se na hostovanoj stranici banke i HSE Training ih ne prima niti čuva.</p>',
					'section_3_title'  => 'Svrhe i pravni osnovi',
					'section_3_body'   => '<p>Upite i pripremu checkouta obrađujemo radi preduzimanja radnji na zahtev lica pre zaključenja ugovora. Porudžbine, usklađivanje i povraćaj uplata, uputstvo za kreiranje naloga i pristup kursu obrađujemo radi izvršenja ugovora. Fiskalne, računovodstvene i reklamacione podatke obrađujemo radi poštovanja pravnih obaveza. Bezbednost servisa, sprečavanje prevare, dokaz isporuke i pravne zahteve obrađujemo zbog legitimnog interesa da servis radi bezbedno i da možemo dokazati usklađenost, uz potrebnu procenu interesa. Opcioni spoljni sadržaj učitava se samo na osnovu pristanka.</p><p>Checkout podatke ne koristimo za automatizovano odlučivanje ili profilisanje koje proizvodi pravne ili slično značajne posledice.</p>',
					'section_4_title'  => 'Pružaoci, primaoci i međunarodni prenos',
					'section_4_body'   => '<p>Neophodne podatke mogu obrađivati pružaoci hostinga i autentifikovanog emaila; WooCommerce za administraciju porudžbina; Raiffeisen Bank/RaiAccept za hostovano kartično plaćanje; BokaPOS za fiskalizaciju i elektronske fiskalne račune; stručni savetnici; nadležni organi kada je to zakonski obavezno; i spoljna platforma za učenje na kojoj kupac kreira nalog i pristupa plaćenom kursu.</p><p>Deli se samo obim podataka potreban za konkretnu svrhu. Ako pružalac obrađuje podatke van Srbije, HSE Training će primeniti odgovarajući zakonski mehanizam prenosa i učiniti dostupnim informacije o merama zaštite. Podatke o ličnosti ne prodajemo.</p>',
					'section_5_title'  => 'Rok čuvanja i dokaz isporuke',
					'section_5_body'   => '<p>Evidencija transakcije i elektronske isporuke čuva se najmanje 120 dana, odnosno duže dok je moguć spor u vezi sa plaćanjem, reklamacija ili pravni zahtev. Evidencija reklamacija čuva se najmanje dve godine. Porudžbine, platna, fiskalna, poreska, računovodstvena i ugovorna dokumentacija čuva se u rokovima propisanim važećim pravom. Upiti, bezbednosni logovi i evidencije saglasnosti čuvaju se samo koliko je potrebno za navedenu svrhu, bezbednosnu istragu ili dokaz usklađenosti, nakon čega se brišu, anonimizuju ili bezbedno arhiviraju.</p>',
					'section_6_title'  => 'Kolačići i spoljni sadržaj',
					'section_6_body'   => '<p>Neophodno skladištenje obuhvata <code>hse_cookie_consent</code> (izbor korisnika tokom 180 dana), <code>hse_checkout_locale</code> (jezik checkouta do mesec dana) i WooCommerce kolačiće sesije i korpe, kao što su <code>woocommerce_cart_hash</code>, <code>woocommerce_items_in_cart</code> i <code>wp_woocommerce_session_*</code>. Oni podržavaju jezik, izbor kolačića, korpu, checkout, bezbednost i sprečavanje prevare i ne mogu se isključiti kada su potrebni za traženu uslugu.</p><p>Google Maps je opcioni sadržaj i učitava se samo nakon pristanka; njegovo učitavanje može proslediti IP adresu i podatke pregledača kompaniji Google. Dugme „Podešavanja kolačića“ u footeru omogućava izmenu ili povlačenje izbora u svakom trenutku. RaiAccept ili drugi spoljni servis može na svom domenu postaviti sopstvene neophodne kolačiće uz svoje obaveštenje.</p>',
					'section_7_title'  => 'Vaša prava i izmene politike',
					'section_7_body'   => '<p>Pod uslovima važećeg prava možete tražiti pristup, ispravku, brisanje, ograničenje ili prenosivost, podneti prigovor na obradu ili opozvati pristanak bez uticaja na raniju zakonitu obradu. Pre postupanja možemo proveriti identitet, a na zahtev po pravilu odgovaramo u roku od 30 dana. Možete podneti pritužbu Povereniku za informacije od javnog značaja i zaštitu podataka o ličnosti na <a href="https://poverenik.rs/" target="_blank" rel="noreferrer">poverenik.rs</a>.</p><p>Politiku ažuriramo kada se promene sajt, pružaoci ili pravni zahtevi i ovde objavljujemo datum početka primene.</p>',
				),
			),
			'withdrawal' => array(
				'en' => array(
					'meta_title'       => 'Withdrawal Form | HSE Training',
					'meta_description' => 'Model form for notifying HSE Training of withdrawal from an eligible distance contract.',
					'eyebrow'          => 'Consumer information',
					'title'            => 'Withdrawal form',
					'intro'            => 'Use this model form only where a statutory right of withdrawal applies to your purchase.',
					'last_updated'     => 'Last updated: 29 September 2026',
					'section_1_title'  => 'Before using this form',
					'section_1_body'   => '<p>For digital content supplied immediately after your separate express request and acknowledgement, the right to withdraw may be lost when digital delivery begins. This does not limit rights concerning non-conforming or inaccessible content. If you are unsure, contact <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>.</p>',
					'section_2_title'  => 'Recipient',
					'section_2_body'   => '<p>HSE Training DOO<br>Braće Radovanović 17/5, Lamela C<br>11000 Belgrade, Serbia<br><a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a></p>',
					'section_3_title'  => 'Model statement',
					'section_3_body'   => '<p>I hereby notify you that I withdraw from the distance contract for the following course/service: ____________________.</p><p>Order number: ____________________<br>Order date: ____________________<br>Consumer name: ____________________<br>Consumer address: ____________________<br>Email used for the order: ____________________<br>Date: ____________________</p><p>Signature (only if submitted on paper): ____________________</p>',
					'section_4_title'  => 'How to submit',
					'section_4_body'   => '<p>Copy the completed statement into an email or attach a signed copy and send it to <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>. You may also send it to the postal address above. Keep proof that the notice was sent. An unambiguous withdrawal statement containing the same information may also be used.</p>',
				),
				'sr' => array(
					'meta_title'       => 'Obrazac za odustanak | HSE Training',
					'meta_description' => 'Model obrasca za obaveštavanje HSE Traininga o odustanku od ugovora na daljinu kada pravo postoji.',
					'eyebrow'          => 'Informacije za potrošače',
					'title'            => 'Obrazac za odustanak',
					'intro'            => 'Koristite ovaj model obrasca samo kada za konkretnu kupovinu postoji zakonsko pravo na odustanak.',
					'last_updated'     => 'Poslednje ažuriranje: 29. septembra 2026.',
					'section_1_title'  => 'Pre korišćenja obrasca',
					'section_1_body'   => '<p>Kod digitalnog sadržaja isporučenog odmah nakon posebnog izričitog zahteva i potvrde kupca, pravo na odustanak može prestati početkom digitalne isporuke. To ne ograničava prava u slučaju nesaobraznog ili nedostupnog sadržaja. Ako niste sigurni, kontaktirajte <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>.</p>',
					'section_2_title'  => 'Primalac',
					'section_2_body'   => '<p>HSE Training DOO<br>Braće Radovanović 17/5, Lamela C<br>11000 Beograd, Srbija<br><a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a></p>',
					'section_3_title'  => 'Model izjave',
					'section_3_body'   => '<p>Ovim vas obaveštavam da odustajem od ugovora na daljinu za sledeći kurs/uslugu: ____________________.</p><p>Broj porudžbine: ____________________<br>Datum porudžbine: ____________________<br>Ime i prezime potrošača: ____________________<br>Adresa potrošača: ____________________<br>Email korišćen za porudžbinu: ____________________<br>Datum: ____________________</p><p>Potpis potrošača (samo ako se obrazac dostavlja na papiru): ____________________</p>',
					'section_4_title'  => 'Način dostavljanja',
					'section_4_body'   => '<p>Popunjenu izjavu kopirajte u email ili priložite potpisanu kopiju i pošaljite na <a href="mailto:info@hsetraining.rs">info@hsetraining.rs</a>. Možete je poslati i na navedenu poštansku adresu. Sačuvajte dokaz o slanju. Možete koristiti i drugu nedvosmislenu izjavu o odustanku koja sadrži iste podatke.</p>',
				),
			),
		);
	}
}
