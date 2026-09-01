# Astro Course CMS Client

Status: Implemented local contract

## Purpose

The CMS client is the only application boundary between Astro code and the
published WordPress Course API. Future pages import the domain-facing functions
from `src/lib/cms`; they do not construct WordPress URLs or consume raw
WordPress response objects.

## Configuration

`WORDPRESS_API_URL` is a required, server-context Astro environment variable.
It contains the CMS origin without a REST path:

```env
WORDPRESS_API_URL=http://cms.hsetraining.test
```

Production will use `https://cms.hsetraining.rs`. Astro's environment schema
checks that the value is a URL, and the CMS client additionally requires an
HTTP or HTTPS scheme. The value is not imported into browser code.

## Public interface

```ts
getCourses(): Promise<Course[]>
getCourseBySlug(slug: string): Promise<Course | null>
getCourseByKey(courseKey: string): Promise<Course | null>
```

`getCourses()` returns published Courses in newest-first WordPress date order.
The current request asks WordPress for up to 100 records, which supports the
planned multi-course model without adding pagination machinery before it is
needed.

Both lookup functions use WordPress collection filters. A zero-item response is
translated to `null`; an HTTP, network, timeout, or validation failure throws a
`CmsError` instead. This preserves the distinction future Astro routes need
between missing content and a failed CMS dependency.

`getCourseByKey()` sends an exact `course_key` filter to WordPress instead of
downloading the collection. It accepts only the canonical stable-key format.
`getCourseBySlug()` uses URL query encoding so slug content cannot add or modify
other query parameters.

## Internal Course shape

```ts
interface Course {
  readonly slug: string;
  readonly courseKey: string;
  readonly title: string;
  readonly shortDescription: string;
  readonly descriptionHtml: string;
  readonly featuredImageUrl: string | null;
  readonly visiblePrice: string | null;
  readonly status: 'publish';
}
```

`courseKey` is the cross-system business identifier. The mutable `slug` is a
route input only, and WordPress post IDs do not enter the application model.
`visiblePrice` is display text, not payment state.

## WordPress requests

All functions consume the core collection endpoint:

```text
GET /wp-json/wp/v2/courses
```

The client adds `_fields` to limit the response, and
`_embed=wp:featuredmedia` to obtain a featured-image URL without a second media
request per Course. A Course with `featured_media: 0` maps to
`featuredImageUrl: null`; a referenced image without valid embedded HTTP(S)
media data is an invalid CMS response.

Lookups add one of these documented filters:

```text
?slug=<encoded-slug>
?course_key=<canonical-course-key>
```

The raw DTO and snake_case-to-camelCase mapping remain private to the CMS
module. Extra WordPress properties are ignored.

## Validation and errors

External JSON is checked manually at the CMS boundary; the client does not
blindly cast response bodies and does not add a schema dependency for this
small contract. It requires a published status, canonical `course_key`, slug,
rendered title, unprotected rendered content, scalar metadata, and coherent
featured-media data.

`CmsError.code` provides these stable categories:

| Code | Meaning |
|---|---|
| `configuration` | The CMS base URL cannot be used safely. |
| `invalid-query` | A lookup value is empty, too long, or non-canonical. |
| `unavailable` | The CMS cannot be reached or the request exceeds 10 seconds. |
| `http` | WordPress returns a non-2xx response; `status` contains the HTTP status. |
| `invalid-response` | JSON or the Course response shape violates the contract. |

The client performs no automatic retries. Error messages do not include
response bodies, credentials, or raw infrastructure details.

## HTML content

`shortDescription` is plain editorial text. `descriptionHtml` is trusted
WordPress-generated marketing HTML authored by CMS editors; it is deliberately
named as HTML and is not rendered in this client task. A future rendering slice
must opt in intentionally to HTML rendering and must not mix untrusted visitor
input into this value.

## Static build consequence

Astro executes data fetching for statically rendered components during the
build. When Course pages are added, the production build environment must be
able to reach `https://cms.hsetraining.rs`; published CMS changes will appear
after a new build. Runtime caching and CMS-triggered rebuilds remain separate
deployment decisions.

References:

- [Astro data fetching](https://docs.astro.build/en/guides/data-fetching/)
- [Astro environment schema](https://docs.astro.build/en/reference/configuration-reference/#envschema)
- [Node.js `AbortSignal.timeout`](https://nodejs.org/api/globals.html#static-method-abortsignaltimeoutdelay)
