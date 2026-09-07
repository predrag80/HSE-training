# WordPress Course API

Status: Implemented local contract

## Purpose

WordPress owns editable course marketing content. Astro will consume published
Course records through this boundary and map the response into its own typed
model. WordPress post IDs may be retained for CMS diagnostics, but they must
never be used as cross-system business identifiers.

## Content model

The custom post type is `course`. WordPress-native fields own the following
content:

| Domain field | WordPress REST field | Notes |
|---|---|---|
| title | `title.rendered` | Editorial title |
| slug | `slug` | Public Astro route input; mutable |
| description | `content.rendered` | Marketing description, not course lessons |
| featured image | `featured_media` | Media ID; request `_embed=wp:featuredmedia` when image data is needed |
| status | `status` | Public responses contain published records by default |

HSE-specific fields are registered as single strings and appear as predictable
scalar values under `meta`:

| REST field | Type / limit | Meaning |
|---|---|---|
| `meta.course_key` | string, 80 characters | Stable cross-system business identifier |
| `meta.short_description` | string, 500 characters | Concise editorial summary |
| `meta.visible_price` | string, 100 characters | Display-only price text |
| `meta.homepage_label` | string, 120 characters | Short label above the Homepage card title |
| `meta.homepage_cta_label` | string, 120 characters | Homepage card action text |
| `meta.featured_on_homepage` | boolean | Explicit Homepage selection |

`visible_price` is a UTF-8 display string such as `€499`. Consumers must not
parse it into authoritative currency or amount values. Lemon Squeezy will own
checkout pricing; any future machine-readable price mapping belongs to the
payment integration, not this CMS field.

## CPT configuration

The Course post type uses these headless settings:

| Setting | Value | Reason |
|---|---:|---|
| `public` | `false` | WordPress is not the public application |
| `publicly_queryable` | `false` | No theme-based single Course pages |
| `show_ui` | `true` | Editors manage Courses in wp-admin |
| `show_in_rest` | `true` | Astro can consume the core REST controller |
| `rest_base` | `courses` | Stable, plural endpoint |
| `has_archive` | `false` | No WordPress Course archive |
| `rewrite` | `false` | No Course rewrite rules or duplicate public URLs |

The post type supports title, editor, featured image, page attributes, revisions, and the
`custom-fields` feature required by WordPress for registered REST metadata. The
generic custom-fields meta box is hidden; editors use the plugin-owned Course
details and Homepage promotion boxes. Page Attributes → Order controls promoted
Course order.

## Endpoint and lookups

The canonical collection endpoint is:

```text
GET /wp-json/wp/v2/courses
```

Use the core `slug` filter for an Astro `getCourseBySlug(slug)` operation:

```text
GET /wp-json/wp/v2/courses?slug=nebosh-international-general-certificate
```

The plugin adds one allowlisted collection parameter for stable identity:

```text
GET /wp-json/wp/v2/courses?course_key=nebosh-igc
```

It also provides an explicit Homepage promotion filter and supports
`menu_order` ordering:

```text
GET /wp-json/wp/v2/courses?featured_on_homepage=true&orderby=menu_order&order=asc
```

Between one and three complete published Courses may be selected for the
Homepage. A promoted Course requires its card label, CTA label, short
description, visible price, and Featured image. The CTA destination is derived
by Astro from `course_key` and route configuration rather than stored as an
editable payment or business-state URL.

`course_key` lookup is exact after validation against the canonical key format.
Both lookups return a collection because the endpoint uses the core WordPress
posts controller. A successful unique lookup therefore contains zero or one
items; Astro must handle zero items explicitly.

Consumers can reduce the WordPress response surface with `_fields` and request
featured-media data only when needed. For example:

```text
GET /wp-json/wp/v2/courses?course_key=nebosh-igc&_fields=id,slug,status,title,content,featured_media,meta
```

## `course_key` rules

- A key is required before a Course can be published.
- Input is trimmed, transliterated to ASCII where WordPress supports it,
  lowercased, and each run of non-alphanumeric characters becomes one hyphen.
- Leading and trailing hyphens are removed.
- The maximum stored length is 80 characters.
- The resulting value must contain at least one ASCII letter or number.
- A key must be unique across Courses in every WordPress status, including the
  trash. The current Course is excluded during its own validation.
- The first publication stores a private lock value. The key remains immutable
  even if the Course later returns to draft or trash.

Representative normalization:

| Input | Stored key |
|---|---|
| `NEBOSH IGC` | `nebosh-igc` |
| ` Nebosh IGC ` | `nebosh-igc` |
| `nebosh/igc` | `nebosh-igc` |
| `NEBOSH@IGC` | `nebosh-igc` |

Uniqueness is enforced server-side before Course metadata writes and by the
admin and REST validation paths. WordPress's post metadata table has no
cross-post unique-value constraint, so integrations must write through
WordPress/plugin APIs rather than directly to the database.

## Visibility and permissions

Unauthenticated requests can read published Course records. WordPress's core
REST controller excludes drafts and private Courses from public collection and
single-item responses. Authenticated users only gain access according to normal
WordPress post capabilities.

Metadata writes require the capability to edit the owning Course. The wp-admin
form also requires its plugin nonce. The private publication lock is neither
registered nor exposed through REST.

## Example response

The local test Course returns the following relevant subset. The numeric `id`
is CMS-local and must not be propagated as the business identifier.

```json
[
  {
    "id": 9,
    "slug": "nebosh-international-general-certificate",
    "status": "publish",
    "title": {
      "rendered": "NEBOSH International General Certificate"
    },
    "content": {
      "rendered": "<p>Placeholder course description for local development.</p>\n",
      "protected": false
    },
    "featured_media": 0,
    "meta": {
      "course_key": "nebosh-igc",
      "short_description": "Local development test course.",
      "visible_price": "€499",
      "homepage_label": "NEBOSH qualification",
      "homepage_cta_label": "View course",
      "featured_on_homepage": true
    }
  }
]
```

Astro should validate this external response and map it at one CMS adapter
boundary. Within Astro and every other business integration, the stable
identity is `course_key`; the slug is only routing content and the WordPress ID
is only a CMS implementation detail.
