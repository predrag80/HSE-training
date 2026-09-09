# ADR-009: English and Serbian Content Architecture

## Status

Proposed

## Date

2026-09-08

## Context

The public Astro site requires English and Serbian content. The previous public
site replaces page text at runtime with Google Translate while retaining the
same URL. That approach does not provide stable localized routes, reviewed HSE
terminology, or a reliable editorial source for each language.

WordPress remains the editorial CMS, and the project already owns the
`hse-headless` integration plugin. Stable content keys, rather than WordPress
post IDs or localized slugs, identify related content across system boundaries.

## Proposed Decision

Use Astro's built-in internationalized routing with English as the unprefixed
default locale and Serbian under `/sr/`. Serbian content uses Latin script and
the `sr-Latn` HTML language tag.

Store application-interface translations in typed Astro dictionaries. Extend
the repository-owned `hse-headless` plugin so editorial records are explicitly
assigned `en` or `sr` and its public endpoints accept an allowlisted `lang`
parameter. Translation pairs share the same stable content key, with uniqueness
enforced for the compound `(content_key, locale)` value.

Language links map equivalent pages by stable route keys and courses by
`course_key`. Generate both languages statically, including localized metadata,
canonical URLs, alternate `hreflang` links, and sitemap entries. Do not use
runtime machine translation or a third-party WordPress translation plugin.

## Alternatives Considered

- Continue using Google Translate on one URL.
- Install Polylang, WPML, GTranslate, or another translation plugin.
- Keep all editorial translations in the Astro repository.
- Prefix both languages, including English.

## Consequences

- Existing English URLs remain stable while Serbian pages become independently
  indexable.
- Editors must enter and review both language variants in WordPress.
- The CMS API and admin interface require explicit locale support and migration
  of existing records to English.
- Homepage settings and all localized custom post types must prevent duplicate
  records for the same `(stable_key, locale)` pair.
- A missing required translation becomes a visible build/content validation
  failure rather than an implicit mixed-language fallback.
- No additional translation-plugin license or runtime service is required.

## Implementation coverage

The current public surface has matching English and Serbian routes for the
Homepage, Company, Consulting, Contact, Course listing and generic Course
details, NEBOSH overview and both NEBOSH details, Other Courses overview,
Banksman / Slinger, Train the Trainer, and Gallery. English remains unprefixed;
every Serbian equivalent uses `/sr/` and `lang="sr-Latn"`.

The language switcher emits direct equivalent-page links and `hreflang`
alternates for every implemented route. CMS-authored internal links are mapped
to the selected locale while external URLs, telephone links, and e-mail links
remain unchanged.
