# WordPress Legal Pages API

Status: implemented by `hse-headless` 0.15.0

## Editor workflow

Administrators open **Legal Pages** in WordPress, select one of the three page
tabs and then select English or Serbian. Each localized page owns:

- SEO title and description;
- eyebrow, public page title, introduction, and last-updated text; and
- up to seven ordered sections, each with a title and rich-text body.

Astro owns the public routes, responsive layout, typography, and section
numbering. Empty trailing sections are ignored. A partially completed section
or a page without a complete header and at least one section returns HTTP 503,
so a static build cannot silently publish incomplete legal copy.

## Public endpoints

```text
GET /wp-json/hse/v1/legal-pages/privacy?lang=en
GET /wp-json/hse/v1/legal-pages/terms?lang=en
GET /wp-json/hse/v1/legal-pages/copyright?lang=en
```

`lang` accepts only `en` and `sr` and defaults to `en`.

## Response contract

```json
{
  "schema_version": 1,
  "page_key": "privacy",
  "locale": "en",
  "content": {
    "meta": {
      "title": "Privacy Policy",
      "description": "..."
    },
    "eyebrow": "Legal information",
    "title": "Privacy Policy",
    "intro": "...",
    "last_updated": "Last updated: ...",
    "sections": [
      {
        "title": "Who is responsible for your data",
        "body_html": "<p>...</p>"
      }
    ]
  }
}
```

WordPress IDs, option names, editor state, and unpublished values do not cross
the public boundary. Rich text is sanitized through the WordPress post-content
allowlist before storage and again before it is returned.

## Verification

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/legal-pages-integration.php
```
