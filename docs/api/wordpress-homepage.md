# WordPress Homepage API

Status: Locale-aware Homepage CMS implemented

## Purpose

The HSE Headless plugin exposes editor-authored Homepage content to Astro while
Astro continues to own section order, semantic markup, responsive layout,
styling, and animation. Hero content is an ordered collection of between one
and five published slides. The response also includes the canonical Company
profile used by the Homepage About/Value section and the selected Service cards.
Editors manage that shared content through the separate **Company Page** and
**Services** screens.

Editors manage this content from **Homepage → Hero Slides** in WordPress admin.
Each slide uses its Featured image and the numeric Order field under Page
Attributes. Lower Order values appear first. A save updates published CMS
content immediately; the static Astro site reflects the change after its next
build.

To add a slide:

1. Open **Homepage → Hero Slides → Add New**.
2. Enter an internal WordPress title used only to recognize the record.
3. Select English or Serbian before the first publication.
4. Complete every field in **Hero slide details**.
5. Set a `slide_key` shared by the English and Serbian translations; the key
   and language become immutable after first publication.
6. Choose a Featured image and set the Order value.
7. Publish the slide and rebuild Astro.

A sixth slide in the same language cannot be published until another published
slide in that language is changed to Draft. Draft slides never enter the public
response.

## Endpoint

```text
GET /wp-json/hse/v1/homepage?lang=en
GET /wp-json/hse/v1/homepage?lang=sr
```

The route is public and read-only. It requires no browser credential and returns
only presentation content. Missing or incomplete Hero, Company profile, or
featured Service content returns HTTP 503 instead of publishing a partial
document.

`lang` is optional and defaults to `en`. Only `en` and `sr` are accepted;
another value returns HTTP 400. A locale without a complete Hero collection,
Company profile, and featured Service collection returns HTTP 503 rather than
falling back to another language.

## Response contract

```json
{
  "schema_version": 1,
  "page_key": "home",
  "locale": "en",
  "hero": {
    "aria_label": "Professional consulting",
    "heading": "Professional consulting",
    "slides": [
      {
        "slide_key": "primary",
        "image": {
          "url": "https://cms.hsetraining.rs/wp-content/uploads/hero.webp",
          "alt": "HSE Training consulting",
          "width": 1920,
          "height": 1080
        },
        "leading_title": "Professional",
        "emphasized_title": "consulting",
        "primary_cta_label": "LET’S WORK TOGETHER",
        "primary_cta_url": "#contact",
        "message_prefix": "Request a free",
        "message_link_label": "business consulting!",
        "message_link_url": "#contact"
      }
    ]
  },
  "about": {
    "eyebrow": "Company’s vision",
    "headline": "Safety culture starts with people.",
    "description": "Company profile shared with the Company Page.",
    "primary_image": {
      "url": "https://cms.hsetraining.rs/wp-content/uploads/about-01.webp",
      "alt": "HSE consultant at work",
      "width": 430,
      "height": 553
    },
    "secondary_image": {
      "url": "https://cms.hsetraining.rs/wp-content/uploads/about-02.webp",
      "alt": "",
      "width": 352,
      "height": 452
    },
    "primary_cta": { "label": "About company", "url": "/company/" },
    "secondary_cta": { "label": "How we work", "url": "/consulting/" },
    "signature_label": "Training & consultancy",
    "values": [
      {
        "value_key": "professional-training",
        "icon_key": "training",
        "title": "Professional training",
        "description": "International programmes and practical instruction."
      },
      {
        "value_key": "hse-management",
        "icon_key": "management",
        "title": "HSE management",
        "description": "Clear support for health and safety systems."
      },
      {
        "value_key": "on-site-consultancy",
        "icon_key": "consultancy",
        "title": "On-site consultancy",
        "description": "Flexible delivery at the client location."
      }
    ]
  },
  "featured_services": [
    {
      "service_key": "hse-leadership",
      "title": "HSE leadership",
      "card_label": "Leadership",
      "short_description": "Experienced HSE management",
      "detailed_description": "Experienced HSE managers and project safety leaders.",
      "cta": { "label": "Enquire", "url": "/contact/" },
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

`schema_version` changes only for an incompatible API contract change.
`page_key` and `slide_key` are stable content identifiers. A translation pair
shares one `slide_key`; uniqueness is enforced for `(slide_key, locale)`.
WordPress attachment and post IDs are deliberately absent. The plugin resolves an internal Media
Library attachment into its public URL, alt text, and intrinsic dimensions.
The Service representation is identical to the dedicated Services endpoint and
is documented in [`wordpress-services.md`](wordpress-services.md).

CTA values accept a same-site absolute path, a page anchor, or an absolute
HTTP(S) URL. Other schemes are discarded when content is saved. All text is
plain, sanitized, and length-bounded. Published slides are returned by ascending
WordPress Order and then ascending publication date.

The original single-slide `hse_homepage` option is migrated idempotently into a
`primary` Hero Slide. The old option is intentionally retained as rollback data
and is no longer used by the public endpoint.

Existing Hero Slides and Services are migrated idempotently to English. The
existing Company settings option is copied to the English locale option and
retained as rollback data. Editors manage English and Serbian Company content
independently through the language tabs in WordPress admin.

## Astro consumption

`getHomepage(locale)` requests the selected locale explicitly during static
generation. Its mapper also rejects a response whose `locale` differs from the
requested language, preventing a Serbian build from silently accepting English
editorial content. It rejects unknown schema versions, malformed content
identifiers, missing text, unsafe links, non-HTTP image URLs, slide collections
outside the one-to-five limit, featured Service collections outside the
one-to-four limit, and invalid image dimensions. WordPress DTO field names
remain private to the CMS module; Astro components receive a camelCase domain
model.

The browser does not request WordPress directly. Production builds must be able
to reach `https://cms.hsetraining.rs`, and a later deployment task will define
the authenticated rebuild trigger used after an editor publishes content.

## Local verification

From the WordPress runtime:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/homepage-integration.php
curl 'http://cms.hsetraining.test/wp-json/hse/v1/homepage?lang=en'
curl 'http://cms.hsetraining.test/wp-json/hse/v1/homepage?lang=sr'
```
