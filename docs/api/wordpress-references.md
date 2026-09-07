# WordPress References API

Status: Implemented

## Purpose

WordPress owns the client and participant feedback displayed in the Homepage
References section. Astro owns the existing Crafto-inspired card layout,
responsive behavior, hover state, and reveal animation.

Editors manage the collection through **References** in WordPress admin. The
WordPress post title is the public author or organisation name. The **Reference
details** box contains the stable key, quote, role or organisation, Homepage
selection, and optional accent-card selection. Page Attributes → Order controls
the public sequence.

At most four published References may be selected for the Homepage. Other
published References remain available in the API for a future dedicated page.

## Endpoint

```text
GET /wp-json/hse/v1/references
```

The endpoint is public and read-only. It returns HTTP 503 until at least one
complete Reference is published.

## Response contract

```json
{
  "schema_version": 1,
  "collection_key": "references",
  "references": [
    {
      "reference_key": "saule-kuza",
      "quote": "The trainer explained the qualification clearly.",
      "author_name": "Saule Kuza",
      "role": "Technical translator at KPO",
      "featured_on_homepage": true,
      "accent_on_homepage": false
    }
  ]
}
```

`reference_key` is unique and locks after first publication. WordPress post IDs
are deliberately absent. Quote, author, and role values are sanitized plain
text; editors cannot inject markup, layout, CSS, or animation through this
contract.

## Local verification

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/reference-integration.php
curl http://cms.hsetraining.test/wp-json/hse/v1/references
```
