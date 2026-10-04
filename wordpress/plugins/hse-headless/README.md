# HSE Training Headless

`hse-headless` is the Git-controlled WordPress plugin for HSE-specific headless
CMS integration.

## Current Status

The plugin provides separately managed Course and Training content, an ordered Homepage Hero
Slide collection, a canonical Company profile, a Service collection shared
by the Homepage and Consulting Page. Course promotions and client References
also feed their Homepage sections from WordPress, while Free Resources are managed
as a separate bilingual library. WordPress theme rendering is
disabled so the installation remains a CMS rather than a second public site.
Homepage, Company, Hero Slide, Service, Course, Training, Reference, Free Resource, and Legal Page content supports
explicit English and Serbian variants without a third-party translation plugin.

## Responsibilities

- HSE-specific WordPress content models
- Separate Course and Training post types with shared metadata validation
- Locale-specific Courses landing, NEBOSH overview, and Training overview page settings
- Homepage Hero Slide publishing, ordering, and featured images
- Company Page settings and shared About/Value content
- Ordered Services with a Homepage-featured subset
- Allowlisted `en`/`sr` content locales and locale-isolated Homepage responses
- Explicit Homepage Course promotion fields and ordering
- Ordered client References with a Homepage-featured subset
- Bilingual Free Resources with links, videos, documents, procedures, and standards
- Locale-specific Privacy Policy, Terms and Conditions, and Copyright pages
- Small REST API extensions needed by headless consumers
- Headless CMS integration
- Closed, non-indexable WordPress theme frontend
- Customer-facing WooCommerce order numbers with an environment-local `HSE-000001` sequence
- WooCommerce checkout, order-status, notification, RaiAccept compatibility,
  and BokaPOS compatibility for the production commerce architecture in ADR-011
- Production-only, privacy-bounded Sentry reporting for contact delivery,
  exceptional checkout failures, and RaiAccept request failures

Hero Slide featured images must be at least 1600 × 900 pixels. The editor
recommends 1920 × 1080 pixels and automatically keeps a slide in draft when a
smaller image is selected. This validation is scoped to Hero Slides and does
not block smaller team photos, logos, or document thumbnails elsewhere in the
Media Library.

## Headless Access Boundary

Normal theme requests return HTTP 404 and a minimal non-indexable response.
WordPress administration, login processing, AJAX, cron, and approved REST API
requests remain available because they do not render the public theme. XML-RPC
is disabled because no approved CMS, commerce, payment, or fiscal integration
uses it.
When the explicit commerce flag is enabled, WooCommerce checkout and
payment-return requests are the only additional public theme surface.

Run the focused local check with:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/headless-mode-integration.php
```

The custom login entry route is an operational security control documented in
[`docs/security/wordpress-cms-access.md`](../../../docs/security/wordpress-cms-access.md).

## Course API

Published Courses are available at:

```text
/wp-json/wp/v2/courses
/wp-json/wp/v2/courses?lang=sr
```

Published Trainings are managed under **Trainings** and available separately:

```text
/wp-json/wp/v2/trainings
/wp-json/wp/v2/trainings?lang=sr
```

The full contract and lookup behavior are documented in
[`docs/api/wordpress-courses.md`](../../../docs/api/wordpress-courses.md).

Run the focused local integration checks from the WordPress runtime:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/integration.php
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/training-integration.php
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/course-promotion-integration.php
```

Administrators edit Courses landing and NEBOSH overview through **Courses →
Course pages**. Training overview is edited separately through **Trainings →
Training page**. Both editors provide English and Serbian tabs. Astro continues
to own their layout, imagery, routes, and animation. The public page documents
are available at:

```text
/wp-json/hse/v1/course-pages/courses?lang=en
/wp-json/hse/v1/course-pages/courses?lang=sr
/wp-json/hse/v1/course-pages/nebosh?lang=en
/wp-json/hse/v1/course-pages/nebosh?lang=sr
/wp-json/hse/v1/course-pages/training?lang=en
/wp-json/hse/v1/course-pages/training?lang=sr
```

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/course-pages-integration.php
```

## Homepage API

Editors manage slides through **Homepage → Hero Slides** in WordPress. Each
published slide has a stable key, content fields, featured image, and numeric
Order value. Between one and five slides may be published. Editors deliberately
cannot change Astro layout, section order, CSS, or animation behavior.

The public, read-only representation is available at:

```text
/wp-json/hse/v1/homepage?lang=en
/wp-json/hse/v1/homepage?lang=sr
```

The endpoint returns HTTP 503 until at least one complete Hero Slide is
published. The contract is documented in
[`docs/api/wordpress-homepage.md`](../../../docs/api/wordpress-homepage.md).

Run its focused local integration checks with:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/homepage-integration.php
```

## Company Page API

Administrators edit canonical company content through **Company Page**. The
shared About profile is included in both the Homepage response and the dedicated
Company response:

```text
/wp-json/hse/v1/company?lang=en
/wp-json/hse/v1/company?lang=sr
```

The contract is documented in
[`docs/api/wordpress-company.md`](../../../docs/api/wordpress-company.md).

The editor also owns the five ordered team profiles used by both Astro pages.
Names and positions are localized; each photo is optional so Astro can render a
controlled fallback while final photography is pending.

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/company-integration.php
```

## Services API

Editors manage the canonical consulting collection through **Services**. The
complete collection is available at:

```text
/wp-json/hse/v1/services?lang=en
/wp-json/hse/v1/services?lang=sr
```

The Homepage endpoint includes the selected one-to-four featured Services. The
contract is documented in
[`docs/api/wordpress-services.md`](../../../docs/api/wordpress-services.md).

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/service-integration.php
```

## References API

Editors manage testimonials through **References**. The public collection is
available at:

```text
/wp-json/hse/v1/references?lang=en
/wp-json/hse/v1/references?lang=sr
```

The contract is documented in
[`docs/api/wordpress-references.md`](../../../docs/api/wordpress-references.md).

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/reference-integration.php
```

## Free Resources API

Editors manage links, videos, documents, procedures, and standards through
**Free Resources**. A resource can point to an external URL or to a file selected
from the WordPress Media Library. English and Serbian entries use the same stable
resource key and remain separate localized records.

```text
/wp-json/hse/v1/resources?lang=en
/wp-json/hse/v1/resources?lang=sr
```

An empty collection is valid, allowing the public page to show its managed empty
state before the first resources are published.

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/resource-integration.php
```

## Legal Pages API

Administrators edit legal copy through **Legal Pages**, then select the page
and English or Serbian tab. Header fields and up to seven rich-text sections
are editable; Astro owns routes, layout, typography, and automatic section
numbering. The public read-only documents are available at:

```text
/wp-json/hse/v1/legal-pages/privacy?lang=en
/wp-json/hse/v1/legal-pages/terms?lang=en
/wp-json/hse/v1/legal-pages/withdrawal?lang=en
/wp-json/hse/v1/legal-pages/copyright?lang=en
```

Use `lang=sr` for Serbian content. Run the focused integration check with:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/legal-pages-integration.php
```

## SMTP transport

The plugin configures WordPress mail through authenticated SMTP only when all
required server-owned settings are valid. It never writes SMTP credentials to
WordPress options or the database. Constants in `wp-config.php` take precedence
over environment variables with the same names.

Recommended staging and production configuration:

```php
define( 'HSE_SMTP_HOST', 'mail.hsetraining.rs' );
define( 'HSE_SMTP_PORT', 587 );
define( 'HSE_SMTP_SECURE', 'tls' );
define( 'HSE_SMTP_USERNAME', 'website@hsetraining.rs' );
define( 'HSE_SMTP_PASSWORD', getenv( 'HSE_SMTP_PASSWORD' ) );
define( 'HSE_MAIL_FROM', 'website@hsetraining.rs' );
define( 'HSE_COMMERCE_ADMIN_EMAIL', 'info@hsetraining.rs' );
define( 'HSE_CONTACT_ALLOWED_ORIGINS', 'https://hsetraining.rs,https://www.hsetraining.rs,https://staging.hsetraining.rs' );
define( 'HSE_TURNSTILE_SECRET_KEY', getenv( 'HSE_TURNSTILE_SECRET_KEY' ) );
define( 'HSE_TURNSTILE_ALLOWED_HOSTNAMES', 'hsetraining.rs,www.hsetraining.rs' );
define( 'HSE_TURNSTILE_ACTION', 'contact' );
define( 'HSE_TURNSTILE_REQUIRED', false );
```

Define these before WordPress loads `wp-settings.php`. Keep the password only
in the server environment or an untracked server `wp-config.php`; never add it
to this repository, a plugin ZIP, or a database export. Port `587` with `tls`
means SMTP with STARTTLS. Port `465` must use `ssl` instead.

An incomplete configuration is ignored so existing WordPress mail behavior is
not broken, and a validation warning without secret values is written to the
PHP error log. Run the mapping and hook checks locally with:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/smtp-mailer-integration.php
```

After the real server-owned values have been configured, verify SMTP
authentication without sending a message:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/smtp-connection-check.php
```

The command returns only a sanitized success or failure message. It does not
print credentials, expose the SMTP server response, or create an email.

## Contact email template

Website enquiries have a table-based HTML email template with inline styles,
a responsive single-column layout, Outlook-specific rendering metadata, and a
plain-text alternative. The design uses the public HSE Training palette while
keeping the company identity readable when remote images are blocked.

The renderer sanitizes every submitted value before output. Run its integration
check with:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/contact-email-template-integration.php
```

The public Astro contact form submits JSON to:

```text
POST /wp-json/hse/v1/contact
```

The endpoint validates and bounds every field, accepts only configured public
site origins (plus localhost during development), silently absorbs a honeypot,
and permits at most three accepted messages from one client address per ten
minutes. When `HSE_TURNSTILE_REQUIRED` is enabled, it also permits at most ten
verification attempts per client address per ten minutes and verifies the
single-use browser token directly with Cloudflare before delivery. A valid
response must match the configured production hostname allowlist and the
`contact` action. Delivery and verification failures return generic public
messages and server logs contain only a generated request ID, never the
submitted personal data. The recipient is fixed in the plugin to
`info@hsetraining.rs`; SMTP credentials control only the authenticated
transport and sender identity.

Deploy the server support with `HSE_TURNSTILE_REQUIRED` set to `false` first.
Set the secret only in private server configuration, deploy the Astro widget,
verify that requests contain a token, and then change the flag to `true`.
Turning the flag back to `false` is the immediate rollback and leaves the
origin, honeypot, request-size, input-validation, and delivery-rate controls in
place. Never place the secret in the plugin ZIP, Git repository, WordPress
database, frontend environment, or browser bundle.

Run the endpoint checks without sending a real message:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/contact-rest-integration.php
```

## WooCommerce commerce integration

The WooCommerce/RaiAccept commerce surface is disabled by default. Enable it on
an approved environment by defining the following untracked server
configuration before WordPress loads `wp-settings.php`. The constant retains
its historical staging name for backward compatibility; ADR-011 approves the
same boundary for production:

```php
define( 'HSE_WOOCOMMERCE_STAGING_BRIDGE', true );
define( 'HSE_PUBLIC_SITE_URL', 'https://hsetraining.rs' );
define( 'HSE_PUBLIC_SITE_DEV_URL', 'https://dev.hsetraining.rs' );
define( 'HSE_PUBLIC_SITE_STAGING_URL', 'https://staging.hsetraining.rs' );
```

Astro reads a public projection by stable `course_key`; WooCommerce API keys,
product IDs, and gateway credentials are never returned:

```text
GET /wp-json/hse/v1/commerce/products/nebosh-igc
```

The response supplies integer minor-unit price data and a same-host checkout
initiation URL. That URL resolves `course_key` to the product SKU on the server,
creates a one-item WooCommerce cart, and redirects to Woo checkout. Run the
contract check with:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/commerce-rest-integration.php
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/commerce-presentation-integration.php
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/commerce-product-sync-integration.php
```

Astro appends `lang=en` or `lang=sr` and the allowlisted `hse_source` environment
to checkout initiation. The plugin keeps
that allowlisted locale in the Woo session, an HTTP-only cookie, and the order
so checkout and the gateway return use the same reviewed English or
Serbian-Latin copy. It also stores the initiating `dev`, `staging`, or
`production` source on
the order so shared WooCommerce checkout notifications can use separate
merchant recipients. Checkout and the order-received endpoint render inside a
plugin-owned, non-indexable HSE shell and use the classic Woo checkout renderer;
WooCommerce and RaiAccept core files remain untouched. Confirmation copy comes
from the Woo order status and does not treat a browser return as proof of
payment. Checkout separately records the purchaser's express request for
immediate digital delivery, including the UTC timestamp and wording version,
and the completed-order email confirms that acknowledgement and the external
platform account-creation step.

`HSE_PUBLIC_SITE_URL` owns production links back to Astro. The optional
`HSE_PUBLIC_SITE_DEV_URL` and `HSE_PUBLIC_SITE_STAGING_URL` constants keep
return links attached to the initiating frontend while all three environments
share one WooCommerce runtime. Local WordPress defaults to
`http://localhost:4321`; unconfigured deployed sources use their known public
origin.

The CMS exposes only the WooCommerce checkout surfaces required by the
headless purchase flow. Unused storefront routes (`cart`, `shop`, product and
product-taxonomy pages, `my-account` and order tracking) return a temporary,
non-cacheable redirect to the configured Astro homepage. Checkout,
`order-pay`, `order-received`, WooCommerce API/callback, REST, AJAX and cron
requests remain available and are never covered by this redirect.

Published **Courses** are the editorial source for a derived, hidden
WooCommerce catalogue. A Course save automatically synchronizes the product
whose SKU equals `course_key`; English content populates the standard product
fields and both English and Serbian content are retained in private product
metadata. Run the idempotent bulk synchronization after first deployment:

```sh
wp eval-file wp-content/plugins/hse-headless/scripts/sync-course-products.php
```

Each Course has **Available for online purchase** and **Online price** fields.
When enabled with a valid positive price, the price is synchronized to the
hidden WooCommerce product and Astro renders a Buy now CTA that proceeds
directly to checkout. When
disabled, the derived product has no checkout price and Astro renders Contact
us. The setting is shared between the English and Serbian records for the same
`course_key`. The separate display-only Course price is never promoted to a
checkout amount. For a purchasable Course, Astro may show that display-only
value beside the authoritative checkout amount as a clearly labelled reference
price. This supports a dual RSD/foreign-currency presentation without allowing
the foreign-currency value to reach WooCommerce or the payment gateway.

The current evaluation uses RSD with two decimal places, in line with the
bank-supplied Internet-sales-site instructions:

```sh
wp option update woocommerce_currency RSD
wp option update woocommerce_price_num_decimals 2
```

Checkout asks whether the customer is an individual or a legal entity. Company
name and tax identification number (PIB) become required only for a legal
entity; the company registration number remains optional and is validated when
provided. The HSE fields replace the duplicate optional BokaPOS company toggle
and PIB controls. BokaPOS reads the authoritative PIB from the
`_hse_company_tax_id` order meta key. The selection is validated server-side,
stored on the WooCommerce order, and included in the administrator order view,
merchant notification, and localized completed-order receipt. Serbian company
identifiers require a nine-digit PIB and, when supplied, an eight-digit
registration number. The Serbian PIB is checked with its ISO 7064 MOD 11,10
check digit before payment. Foreign legal entities receive a localized Tax/VAT
identifier field; accepted bounded alphanumeric identifiers are stored for
BokaPOS as buyer identification `40:TIN`, while Serbian companies continue to
use `10:PIB` through the configured `_hse_company_tax_id` mapping.

Checkout validation summaries and field errors follow the selected checkout
language. Serbian sessions localize required-field, email, phone, postcode,
country and region validation messages instead of falling back to the
WooCommerce English strings.

The global Astro footer and the plugin-owned checkout shell use the official
bank-provided Raiffeisen, card-scheme and 3-D Secure assets. Purchase Terms and
Privacy Policy are migrated once in English and Serbian; the previous option
values are retained with the `_pre_ecommerce_20260916` suffix. The legal copy is
an implementation draft and must be approved before production launch.

The checkout and confirmation shell packages the same transparent HSE Training
wordmark used by the Astro header, so it does not depend on an Astro public
asset URL or retain the earlier plugin-only logo treatment.

The checkout payment methods use equal, selectable cards with persistent helper
copy. Purchase Terms and immediate digital delivery are presented as two
separate required confirmation cards. The digital-delivery checkbox is never
preselected and its accepted text version and UTC timestamp remain stored on
the order.

The successful customer flow suppresses WooCommerce's intermediate processing
email and sends the final completed-order payment receipt only. Pending,
failed, cancelled and refunded outcomes keep their own notifications. The
verified RaiAccept payment-complete event moves an order containing only
plugin-synchronized virtual Course products directly to `completed`. A late
fallback also promotes a verified paid RaiAccept order if that gateway version
persists `processing` directly instead of using WooCommerce's standard
payment-complete status filter. This starts immediate fulfilment and lets
BokaPOS issue the final fiscal receipt.
The rule never auto-completes `bacs` orders. Direct bank transfer orders remain
`on-hold` until the merchant verifies the incoming credit on the bank account
and manually changes the order to `completed`.

The customer `on-hold` notification uses a plugin-owned responsive HTML and
plain-text template: Serbian individuals receive an **Uplatnica** presentation,
while Serbian legal entities receive a **Nalog za prenos** presentation. Both
include payment code `221`, purpose, amount and the verified domestic beneficiary
account `265-6040310000144-40`. Model and payment-reference fields are
intentionally omitted until HSE Training supplies an approved reference convention.
International customers receive the official Raiffeisen beneficiary name,
address, IBAN `RS35265100000016397028` and SWIFT/BIC `RZBSRSBG`, plus the bank's
official EUR incoming-payment instructions as a PDF attachment. WooCommerce
sends this existing `customer_on_hold_order` email once when the BACS order
enters `on-hold`; the plugin does not trigger a second message. The
order-received page repeats the applicable payment details.

BokaPOS must therefore auto-fiscalize only on `completed`, have no advance or
proforma gateways, and map `raiaccept` to `CARD` and `bacs` to
`WIRE_TRANSFER`. Use one fiscal-email sender: the production direction enables
the BokaPOS portal e-mail for the official sale and refund fiscal documents and
disables the separate WooCommerce BokaPOS fiscal-receipt e-mail. WooCommerce
continues to send the distinct order confirmation/status or refund message.

On the WooCommerce order editor, an admin-only compatibility script removes
native browser `required` validation from BokaPOS refund-buyer controls only
while those controls are hidden. This prevents a hidden
`bokapos_refund_buyer_value` field from blocking an ordinary **Update** action;
the requirement is restored automatically whenever the refund control becomes
visible.

The completed receipt is rendered by plugin-owned HTML and plain-text templates;
its subject, body, Course title and labels use the `en` or `sr` locale stored on
the order. It includes the customer billing details, payment method, order
number and date, line items, subtotal, total paid, payment status and HSE
contact details. Failed and cancelled card-payment notifications use the same
responsive HSE presentation and stored checkout language. The failed-payment
message states that access and fiscalization did not start, summarizes the
attempt and provides WooCommerce's signed retry-payment URL for the same order;
the cancelled variant directs the customer to start a new purchase instead.
The signed link intentionally opens a localized `order-pay` review step before
RaiAccept. It uses the same responsive card, typography, payment-method and CTA
design as the main checkout, with separate order-review and payment steps. This
lets WooCommerce create a fresh gateway transaction for the existing order and
avoids initiating payment from an email-prefetched GET request.
The official RaiAccept plugin retains its provider order ID after a declined
attempt, but the provider refuses a later checkout session for that inactive
attempt. An update-safe compatibility hook therefore runs only after the
customer submits the signed `order-pay` form with RaiAccept selected and only
while the WooCommerce order is `failed`. It archives the previous provider
order ID, prepares a unique reference such as
`1553-retry-20260930211530-a1b2c3d4` and clears the inactive iframe/session
metadata. An update-safe child of the official gateway changes only its
merchant-reference mapping, so the inherited provider-order and payment-session
requests use that exact same retry reference. The official provider plugin is
not edited. Opening or prefetching the e-mail link does not mutate the order,
bank-transfer attempts are untouched, and both the provider-order and
retry-reference histories remain on the WooCommerce order for operational
review.
Merchant new-order notifications use their own branded HTML
and plain-text templates with customer identity, payment and transaction data,
line items, total, checkout language and current order status. The plugin
forces both branded message types to render
with the HTML template and matching `text/html` MIME header, while retaining
the plain-text template as a fallback. They use `HSE_COMMERCE_ADMIN_EMAIL` when it contains a
valid address and otherwise fall back to `info@hsetraining.rs`, so an imported
WordPress administrator address cannot become the recipient. Orders initiated
from the dev Astro build use `HSE_COMMERCE_DEV_ADMIN_EMAIL` when configured and
otherwise fall back to `predo.vuckovic@gmail.com`; staging orders retain the
standard merchant recipient. Customer order-status and BokaPOS receipt emails
always retain the actual billing email address entered at checkout. After WordPress
reports a successful send, the plugin stores only the customer notification
identifier and UTC timestamp on the order as operational evidence.
RaiAccept can deliver overlapping status callbacks, so the completed-order
notification also takes an atomic, non-autoloaded database claim before sending.
Only the first callback can acquire it; a failed send releases the claim, while
a successful send keeps a durable marker and prevents a duplicate `Thank you`
message even when another callback is already running with stale order data.

Full and partial customer refund notifications use the same responsive HSE
HTML and plain-text design as the other order messages. They state the amount
returned to the original payment method, explain that bank posting time can
vary, and distinguish the WooCommerce refund confirmation from the separate
BokaPOS fiscal-refund document.

A BokaPOS fiscal refund must contain at least one returned line quantity that
maps back to the original fiscal receipt. An amount-only WooCommerce refund can
return money through the payment gateway but cannot be fiscalized. The HSE
compatibility layer therefore validates this before WooCommerce invokes the
gateway whenever `Fiscalize this refund` is selected. Both a browser-side guard
and an authoritative AJAX guard require an administrator to enter a positive
quantity beside the refunded course. Provider plugin files remain unmodified.
If an older amount-only refund already succeeded financially and failed with
`NO_REFUND_LINES`, never invoke the gateway refund a second time; reconcile its
fiscal document with BokaPOS support or use a controlled manual-only correction.

The separate BokaPOS fiscal-receipt notification also uses plugin-owned HTML
and plain-text templates. Its subject, heading, explanatory copy, course title
and actions follow the immutable `en` or `sr` checkout locale stored on the
order, while a non-Serbian billing country always selects the English wrapper.
The official PFR PDF, QR data and Tax Administration verification link
remain unchanged. An English checkout translates only the surrounding message
and explains that the attached fiscal document retains its legally prescribed
original language and format.

The provider plugin remains unmodified. Before its `bokapos_order_fiscalized`
email handler runs, an HSE compatibility hook loads WordPress's file helpers so
the provider can create its temporary PDF attachment during WP-Cron as well as
an async request. A delayed watchdog checks the existing WooCommerce delivery
evidence and makes one idempotent retry only when the fiscal receipt exists but
the `bokapos_receipt` email was not recorded as sent. Remove the workaround
after a BokaPOS release officially loads `wp-admin/includes/file.php` in its
background delivery path and the same sandbox scenarios pass without it.

### Production checkout and contact monitoring

Sentry is limited to exceptional production order creation, RaiAccept hosted
payment requests and contact-form delivery failures. Every event must carry an
explicit production checkout source or production contact origin; dev, staging
and unclassified activity is discarded before transport. Normal card declines,
validation responses, successful submissions, BokaPOS fiscal operations and
general CMS activity are not Sentry events.

Configure the production-only Sentry channel and its fallback recipient through
server-owned values before WordPress loads:

```php
define( 'HSE_MONITORING_SENTRY_DSN', getenv( 'HSE_MONITORING_SENTRY_DSN' ) );
define( 'HSE_MONITORING_ENVIRONMENT', 'production' );
define( 'HSE_MONITORING_ALERT_EMAIL', 'info@hsetraining.rs' );
```

When Sentry is unavailable, the same bounded production checkout/contact
incident is sent through WordPress mail. The official BokaPOS portal,
administrator warning and provider notifications remain enabled and unchanged;
there is no custom Sentry fiscalization or refund watchdog.

Production must use a real server cron. First add and manually test the cron
command, then set `DISABLE_WP_CRON` to `true`; never reverse that order. The
server command must run all due WordPress events and a bounded Action Scheduler
batch so WooCommerce and BokaPOS retry/poll dependencies preserve their order.
Check the queue under **WooCommerce → Status → Scheduled Actions** and run the
focused Sentry-scope integration check with:

```sh
wp eval-file wp-content/plugins/hse-headless/tests/sentry-reporting-integration.php
```

The email palette can be aligned to the HSE checkout with:

```sh
wp option update woocommerce_email_base_color '#292d36'
wp option update woocommerce_email_background_color '#f7f7f7'
wp option update woocommerce_email_body_background_color '#ffffff'
wp option update woocommerce_email_text_color '#292d36'
```

The initial IGC product can be provisioned idempotently in a non-production
environment; production changes should be made through the Course editor, then
synchronized in bulk after first deployment:

```sh
HSE_IGC_TEST_PRICE=117000.00 wp eval-file wp-content/plugins/hse-headless/scripts/provision-nebosh-igc-product.php
wp eval-file wp-content/plugins/hse-headless/scripts/sync-course-products.php
```

WooCommerce is the production order and business-workflow authority under
ADR-011. RaiAccept remains authoritative for card-payment/refund outcomes, and
BokaPOS remains authoritative for fiscal documents.

### Automatic production content deploys

Astro reads CMS content during a static build. On production, enable the
repository-owned content trigger so approved editorial saves queue the existing
production GitHub Actions workflow through the real once-per-minute server
cron:

```php
define( 'HSE_CONTENT_DEPLOY_ENABLED', true );
define( 'HSE_CONTENT_DEPLOY_GITHUB_TOKEN', getenv( 'HSE_CONTENT_DEPLOY_GITHUB_TOKEN' ) );
```

`HSE_CONTENT_DEPLOY_GITHUB_TOKEN` must be a dedicated fine-grained GitHub token
restricted to the `predrag80/HSE-training` repository with **Actions: Read and
write** permission. Keep it in private server configuration; never place it in
the database, plugin ZIP, Astro environment, repository, or browser code. Do
not define the enable flag on dev or staging CMS instances.

The trigger covers Course, Training, Hero Slide, Service, Reference, Resource,
Media Library, Company Page, Course Page, and Legal Page content. It never runs
for WooCommerce orders. Rapid edits are collapsed into one build, temporary
dispatch failures receive bounded retries, and configuration failures appear as
an administrator notice. Verify without contacting GitHub:

```sh
wp eval-file wp-content/plugins/hse-headless/tests/content-deploy-trigger-integration.php
```

## Not Responsible For

- Astro frontend
- Customer accounts
- Card processing and provider settlement performed by RaiAccept
- Fiscal document issuance performed by BokaPOS
- Course delivery
- LMS functionality

The local WordPress runtime consumes this repository-owned source through a
symbolic link. WordPress core and runtime state remain outside this repository.
