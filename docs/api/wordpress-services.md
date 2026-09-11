# WordPress Services API

Status: Implemented

## Purpose

The HSE Headless plugin owns one canonical, ordered Service collection. Astro
uses the same published records for the four-card Homepage Consulting services
section and the detailed Services section on `/browse-hse-talent/`. This prevents the
two pages from drifting while Astro continues to own their different layouts,
styles, and motion.

Editors manage records through **Services** in WordPress admin. A Service uses
its post title as the public name, its Featured image as the card image, and the
numeric Order field under Page Attributes for display sequence.

## Editorial fields

Every published Service requires:

- an English or Serbian content language, which locks after first publication;
- a `service_key` shared by translations and unique within that language, which
  locks after first publication;
- public title;
- Homepage card label;
- short card description;
- detailed Consulting Page description;
- CTA label and URL;
- Featured image;
- an explicit Order value.

The **Show this service on the Homepage** checkbox selects the Homepage subset.
At least one and at most four published Services per language may be selected.
Additional published Services remain available to `/browse-hse-talent/` without
changing the four-card Homepage layout.

CTA values accept a same-site absolute path, page anchor, or absolute HTTP(S)
URL. Other schemes are discarded. All editorial text is plain, sanitized, and
length-bounded.

## Endpoint

```text
GET /wp-json/hse/v1/services?lang=en
GET /wp-json/hse/v1/services?lang=sr
```

The route is public and read-only. It returns HTTP 503 until at least one
complete Service is published.

`lang` defaults to `en` and accepts only `en` or `sr`. The response contains
only records assigned to the requested language and includes the resolved
`locale`; it never falls back to the other language.

## Response contract

```json
{
  "schema_version": 1,
  "collection_key": "services",
  "locale": "en",
  "services": [
    {
      "service_key": "hse-leadership",
      "title": "HSE leadership",
      "card_label": "Leadership",
      "short_description": "Experienced HSE management",
      "detailed_description": "Experienced HSE managers and project safety leaders.",
      "cta": {
        "label": "Enquire",
        "url": "/contact/"
      },
      "image": {
        "url": "https://cms.hsetraining.rs/wp-content/uploads/service.webp",
        "alt": "HSE consultants discussing project leadership",
        "width": 600,
        "height": 815
      },
      "featured_on_homepage": true
    }
  ]
}
```

Published records are ordered by ascending WordPress Order and then publication
date. WordPress post and attachment IDs are deliberately absent. The plugin
resolves Media Library attachments into portable URL, alt text, and intrinsic
dimensions.

The Homepage endpoint contains the same representation under
`featured_services`, limited to the selected subset. `service_key` is the stable
cross-page content identifier; it is not a payment or business-state key.

## Local verification

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/service-integration.php
curl 'http://cms.hsetraining.test/wp-json/hse/v1/services?lang=en'
curl 'http://cms.hsetraining.test/wp-json/hse/v1/services?lang=sr'
```
