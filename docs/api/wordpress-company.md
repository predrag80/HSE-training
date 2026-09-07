# WordPress Company Page API

Status: Shared About/Value slice implemented

## Content ownership

The **Company Page** screen in WordPress is the single editorial source for the
company profile. That profile is rendered in the Homepage About/Value section
and in the introduction of `/company/`, avoiding two independently maintained
copies of company facts.

The editor currently owns:

- Company Page hero eyebrow and title;
- shared profile eyebrow, headline, description, images, actions, and signature;
- three fixed value highlights; and
- the Company introduction action.

The three value icon types, page composition, CSS, responsive behavior,
parallax, and reveal animations remain controlled by Astro.

## Dedicated endpoint

```text
GET /wp-json/hse/v1/company
```

The response contains a versioned `company` document with `hero`, `profile`,
and `intro_cta` values. The same `profile` object is composed into:

```text
GET /wp-json/hse/v1/homepage
```

WordPress Media Library IDs remain internal. Both responses contain only image
URL, alt text, width, and height. Links are limited to same-site paths, page
anchors, and absolute HTTP(S) URLs. Missing required content returns HTTP 503
instead of a partial public document.

## Static rendering

Astro calls `getCompanyPage()` for `/company/` and `getHomepage()` for `/` during
static generation. Both mappers validate the shared profile independently at
the CMS boundary. Publishing in WordPress requires a later Astro rebuild before
the production HTML changes.

## Local verification

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/company-integration.php
curl http://cms.hsetraining.test/wp-json/hse/v1/company
```
