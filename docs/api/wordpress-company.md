# WordPress Company Page API

Status: Shared About/Value and Team slices implemented

## Content ownership

The **Company Page** screen in WordPress is the single editorial source for the
company profile in each supported language. Language tabs select the independent
English or Serbian settings document. Each profile is rendered in its matching
Homepage About/Value section and Company Page.

The editor currently owns:

- Company Page hero eyebrow and title;
- shared profile eyebrow, headline, description, images, actions, and signature;
- three fixed value highlights;
- five ordered team members, with locale-specific names and positions and an optional Media Library photo; and
- the Company introduction action.

The three value icon types, team order, page composition, CSS, responsive behavior,
parallax, and reveal animations remain controlled by Astro.

## Dedicated endpoint

```text
GET /wp-json/hse/v1/company?lang=en
GET /wp-json/hse/v1/company?lang=sr
```

The response contains a versioned `company` document with `hero`, `profile`,
and `intro_cta` values. The same `profile` object is composed into:

```text
GET /wp-json/hse/v1/homepage
```

WordPress Media Library IDs remain internal. Both responses contain only image
URL, alt text, width, and height. A team member without a CMS photo uses the
project-owned Astro fallback (or the neutral initials placeholder). Links are limited to same-site paths, page
anchors, and absolute HTTP(S) URLs. Missing required content returns HTTP 503
instead of a partial public document.

`lang` defaults to `en` and accepts only `en` or `sr`. Responses include the
resolved `locale`. Missing Serbian settings never fall back silently to English.

## Static rendering

Astro calls `getCompanyPage('en')` for `/company/`, `getCompanyPage('sr')` for
`/sr/company/`, and the matching `getHomepage(locale)` for each Homepage during
static generation. Both mappers validate the requested locale and shared
profile independently at the CMS boundary. Publishing in WordPress requires a
later Astro rebuild before the production HTML changes.

## Local verification

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/company-integration.php
curl 'http://cms.hsetraining.test/wp-json/hse/v1/company?lang=en'
curl 'http://cms.hsetraining.test/wp-json/hse/v1/company?lang=sr'
```
