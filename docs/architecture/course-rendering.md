# Course rendering

Status: Implemented

## Routes and data flow

The Astro application exposes these public Course routes:

- `/courses` lists all published Courses.
- `/courses/[slug]` renders one detail page for each published Course slug.

Both routes consume the domain-oriented CMS client in `apps/web/src/lib/cms`.
Page and component code does not fetch WordPress directly or depend on raw
WordPress response fields.

```text
WordPress Course
  -> WordPress REST API
  -> Astro CMS client validation and mapping
  -> Astro Course routes and components
  -> static HTML
```

## Static generation

Astro fetches the Course collection during the production build. The listing
is rendered from that collection, and `getStaticPaths()` creates one detail
path per unique slug. Duplicate slugs stop the build with a clear error rather
than allowing one generated page to overwrite another.

An empty published collection produces a useful listing empty state and no
Course detail paths. A slug that was not generated is handled by the static
host as a normal 404.

If WordPress or its Course API is unavailable, the CMS client throws an
explicit error and the build fails. The build does not publish an incomplete or
empty Course site as a fallback. A later deployment task must decide how a
WordPress publication triggers a new Astro build.

## Content and identity

Detail pages render `descriptionHtml` intentionally with Astro's trusted HTML
rendering mechanism. This field is editor-authored WordPress marketing content
validated by the CMS adapter; visitor-provided HTML must never be mixed into
it.

The domain `courseKey` remains available to the page through its `Course`
object for future payment and business-state integrations, but it is not
rendered in public markup. WordPress post IDs do not cross the CMS boundary.
