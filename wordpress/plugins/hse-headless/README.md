# HSE Training Headless

`hse-headless` is the Git-controlled WordPress plugin for HSE-specific headless
CMS integration.

## Current Status

The plugin provides editor-managed Course content, an ordered Homepage Hero
Slide collection, a canonical Company profile, and a Service collection shared
by the Homepage and Consulting Page. Course promotions and client References
also feed their Homepage sections from WordPress. WordPress theme rendering is
disabled so the installation remains a CMS rather than a second public site.
Homepage, Company, Hero Slide, Service, Course, and Reference content supports
explicit English and Serbian variants without a third-party translation plugin.

## Responsibilities

- HSE-specific WordPress content models
- Course metadata validation and admin editing
- Locale-specific Courses landing and NEBOSH overview page settings
- Homepage Hero Slide publishing, ordering, and featured images
- Company Page settings and shared About/Value content
- Ordered Services with a Homepage-featured subset
- Allowlisted `en`/`sr` content locales and locale-isolated Homepage responses
- Explicit Homepage Course promotion fields and ordering
- Ordered client References with a Homepage-featured subset
- Small REST API extensions needed by headless consumers
- Headless CMS integration
- Closed, non-indexable WordPress theme frontend

## Headless Access Boundary

Normal theme requests return HTTP 404 and a minimal non-indexable response.
WordPress administration, login processing, AJAX, cron, XML-RPC, and REST API
requests remain available because they do not render the public theme.

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

The full contract and lookup behavior are documented in
[`docs/api/wordpress-courses.md`](../../../docs/api/wordpress-courses.md).

Run the focused local integration checks from the WordPress runtime:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/integration.php
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/course-promotion-integration.php
```

Administrators edit the two Course index pages through **Courses → Course
pages**, then select **Courses landing** or **NEBOSH overview** and an English
or Serbian language tab. Astro continues to own their layout, imagery, routes,
and animation. The public page documents are available at:

```text
/wp-json/hse/v1/course-pages/courses?lang=en
/wp-json/hse/v1/course-pages/courses?lang=sr
/wp-json/hse/v1/course-pages/nebosh?lang=en
/wp-json/hse/v1/course-pages/nebosh?lang=sr
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

## Not Responsible For

- Astro frontend
- Customer accounts
- Payments
- Payment-provider integration
- Course delivery
- LMS functionality

The local WordPress runtime consumes this repository-owned source through a
symbolic link. WordPress core and runtime state remain outside this repository.
