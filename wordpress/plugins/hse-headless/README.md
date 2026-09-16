# HSE Training Headless

`hse-headless` is the Git-controlled WordPress plugin for HSE-specific headless
CMS integration.

## Current Status

The plugin provides separately managed Course and Training content, an ordered Homepage Hero
Slide collection, a canonical Company profile, and a Service collection shared
by the Homepage and Consulting Page. Course promotions and client References
also feed their Homepage sections from WordPress. WordPress theme rendering is
disabled so the installation remains a CMS rather than a second public site.
Homepage, Company, Hero Slide, Service, Course, Training, Reference, and Legal Page content supports
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
- Locale-specific Privacy Policy, Terms and Conditions, and Copyright pages
- Small REST API extensions needed by headless consumers
- Headless CMS integration
- Closed, non-indexable WordPress theme frontend

## Headless Access Boundary

Normal theme requests return HTTP 404 and a minimal non-indexable response.
WordPress administration, login processing, AJAX, cron, XML-RPC, and REST API
requests remain available because they do not render the public theme.
When the explicit staging-only commerce flag is enabled, WooCommerce checkout
and payment-return requests are the only additional public theme surface.

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

## Legal Pages API

Administrators edit legal copy through **Legal Pages**, then select the page
and English or Serbian tab. Header fields and up to seven rich-text sections
are editable; Astro owns routes, layout, typography, and automatic section
numbering. The public read-only documents are available at:

```text
/wp-json/hse/v1/legal-pages/privacy?lang=en
/wp-json/hse/v1/legal-pages/terms?lang=en
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
define( 'HSE_MAIL_TO', 'recipient@example.com' );
define( 'HSE_CONTACT_ALLOWED_ORIGINS', 'https://hsetraining.rs,https://www.hsetraining.rs,https://staging.hsetraining.rs' );
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
minutes. Delivery failures return generic public messages and server logs
contain only a generated request ID, never the submitted personal data. The
recipient is controlled by the server-only `HSE_MAIL_TO` value.

Run the endpoint checks without sending a real message:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/contact-rest-integration.php
```

## Staging WooCommerce evaluation

The temporary WooCommerce/RaiAccept bridge is disabled by default. It may be
enabled only on the staging CMS by defining the following untracked server
configuration before WordPress loads `wp-settings.php`:

```php
define( 'HSE_WOOCOMMERCE_STAGING_BRIDGE', true );
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
```

Provision the staging-only IGC product with an explicitly selected sandbox
price:

```sh
HSE_IGC_TEST_PRICE=1000.00 wp eval-file wp-content/plugins/hse-headless/scripts/provision-nebosh-igc-product.php
```

This bridge is for staging gateway validation only. It does not approve
WooCommerce as the production payment-state authority; see ADR-010.

## Not Responsible For

- Astro frontend
- Customer accounts
- Production payments and payment-provider integration
- Course delivery
- LMS functionality

The local WordPress runtime consumes this repository-owned source through a
symbolic link. WordPress core and runtime state remain outside this repository.
