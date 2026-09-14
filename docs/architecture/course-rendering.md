# Course rendering

Status: Implemented

## Routes and data flow

The Astro application exposes parallel English and Serbian Course routes:

- `/courses/` and `/sr/courses/` list published Courses for their language.
- `/courses/[slug]/` and `/sr/courses/[slug]/` render generic Course details.
- `/nebosh/` and `/sr/nebosh/` provide the designed NEBOSH overview.
- `/training/` and `/sr/training/` provide the CMS-managed Training overview.
- The three bespoke NEBOSH and three bespoke Training Course routes use their existing paths under both the
  unprefixed English site and the `/sr/` Serbian site.

Both routes consume the domain-oriented CMS client in `apps/web/src/lib/cms`.
Page and component code does not fetch WordPress directly or depend on raw
WordPress response fields.

Page-level headings, introductions, metadata, NEBOSH benefits, and selection
copy come from **Courses → Course pages**. Training overview copy is edited at
**Trainings → Training page**. Individual cards and details come from the
separate Course and Training records identified by `(course_key, locale)`.

```text
WordPress Course / Training
  -> separate WordPress REST collections
  -> Astro CMS client validation and mapping
  -> Astro Course routes and components
  -> static HTML
```

## Static generation

Astro fetches both language collections during the production build. Each
listing uses only its requested locale. Generic `getStaticPaths()` entries pair
translations by `course_key`, allowing English and Serbian to use different
editorial slugs while the language switcher still reaches the matching Course.
Duplicate slugs, duplicate translated keys, missing translations, or
same-language pairs stop the build with a clear error.

An empty published collection produces a useful localized listing empty state
and no generic detail paths. A slug that was not generated is handled by the
static host as a normal 404. Bespoke NEBOSH and Training routes require their matching
`(course_key, locale)` CMS record and fail the build when it is missing.

If WordPress or its Course API is unavailable, the CMS client throws an
explicit error and the build fails. The build does not publish an incomplete or
empty Course site as a fallback. A later deployment task must decide how a
WordPress publication triggers a new Astro build.

## Content and identity

Detail pages render `descriptionHtml` intentionally with Astro's trusted HTML
rendering mechanism. This field is editor-authored WordPress marketing content
validated by the CMS adapter; visitor-provided HTML must never be mixed into
it.

The domain `courseKey` remains the Course business identity for future payment
and business-state integrations. The `(courseKey, locale)` pair selects an
editorial translation, while the locale-specific slug is only a route input.
WordPress post IDs do not cross the CMS boundary.
