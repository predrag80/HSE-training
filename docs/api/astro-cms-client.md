# Astro CMS Client

Status: Implemented local contract

## Purpose

The CMS client is the only application boundary between Astro code and the
published WordPress content APIs. Pages import domain-facing functions from
`src/lib/cms`; they do not construct WordPress URLs or consume raw WordPress
response objects.

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
getCourses(locale?: Locale): Promise<Course[]>
getCourseBySlug(slug: string, locale?: Locale): Promise<Course | null>
getCourseByKey(courseKey: string, locale?: Locale): Promise<Course | null>
getHomepageCourses(locale?: Locale): Promise<Course[]>
getCoursesLandingPage(locale?: Locale): Promise<CoursesLandingPageContent>
getNeboshOverviewPage(locale?: Locale): Promise<NeboshOverviewPageContent>
getHomepage(locale?: Locale): Promise<Homepage>
getCompanyPage(locale?: Locale): Promise<CompanyPageContent>
getServices(locale?: Locale): Promise<ServiceCollection>
getReferences(locale?: Locale): Promise<ReferenceCollection>
getHomepageReferences(locale?: Locale): Promise<readonly Reference[]>
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

`getHomepageCourses()` requests only Courses explicitly selected by an editor,
orders them by the WordPress Order field, and requires one to three complete
promotion records. The Oil & Gas legacy Course can therefore remain published
without being presented as available for enrolment.

`getHomepage()` consumes the versioned, composed HSE endpoint. The current
domain shape contains a Hero slide collection, the shared Company profile, and
the selected Homepage Services. `getCompanyPage()` consumes the same profile
alongside Company-specific hero and intro action data. `getServices(locale)`
returns the canonical ordered consulting collection for the requested language
and rejects cross-language responses. Layout, component selection,
section order, and animation remain in Astro rather than entering the CMS
contract.

`getCoursesLandingPage()` and `getNeboshOverviewPage()` consume the fixed Course
page documents. Astro uses their editorial copy and metadata while retaining
complete ownership of component structure, images, routes, and motion.

`getReferences()` returns the canonical testimonial collection.
`getHomepageReferences()` selects the ordered one-to-four records explicitly
marked for the Homepage.

## Internal Course shape

```ts
interface Course {
  readonly locale: 'en' | 'sr';
  readonly slug: string;
  readonly courseKey: string;
  readonly title: string;
  readonly shortDescription: string;
  readonly descriptionHtml: string;
  readonly featuredImageUrl: string | null;
  readonly visiblePrice: string | null;
  readonly homepageLabel: string;
  readonly homepageCtaLabel: string;
  readonly featuredOnHomepage: boolean;
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

Lookups always add `lang=en|sr` and one of these documented filters:
```text
?lang=<locale>&slug=<encoded-slug>
?lang=<locale>&course_key=<canonical-course-key>
```

Homepage content uses:

```text
GET /wp-json/hse/v1/homepage
```

Company Page content uses:

```text
GET /wp-json/hse/v1/company
```

Course page content uses:

```text
GET /wp-json/hse/v1/course-pages/courses?lang=<locale>
GET /wp-json/hse/v1/course-pages/nebosh?lang=<locale>
```

Consulting Service content uses:

```text
GET /wp-json/hse/v1/services
```

Reference content uses:

```text
GET /wp-json/hse/v1/references
```

The Homepage mapper requires contract version 1, the stable `home` page key, at least one
Hero slide, safe links, and complete HTTP(S) image metadata. WordPress IDs do
not enter the Astro Homepage model. Raw DTOs and snake_case-to-camelCase mapping
remain private to the CMS module. Extra WordPress properties are ignored.

## Validation and errors

External JSON is checked manually at the CMS boundary; the client does not
blindly cast response bodies and does not add a schema dependency for these
small contracts. Course validation requires a published status, canonical
`course_key`, slug, rendered title, unprotected rendered content, scalar
metadata, and coherent featured-media data. Homepage validation checks its
versioned document, content keys, text, links, slides, featured Services, and
image metadata. Service validation additionally requires canonical keys, a
boolean Homepage selection, safe CTA data, and complete images.

Reference validation requires the requested locale, a canonical key, complete
plain-text attribution, and boolean Homepage presentation flags. Course,
Homepage, Company, Service, and Reference mappers reject cross-language
responses rather than silently falling back.

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

Astro executes Homepage and Course data fetching during the static build. The
production build environment must be able to reach
`https://cms.hsetraining.rs`; published CMS changes appear after a new build.
Runtime caching and the authenticated CMS-triggered rebuild remain separate
deployment decisions.

References:

- [Astro data fetching](https://docs.astro.build/en/guides/data-fetching/)
- [Astro environment schema](https://docs.astro.build/en/reference/configuration-reference/#envschema)
- [Node.js `AbortSignal.timeout`](https://nodejs.org/api/globals.html#static-method-abortsignaltimeoutdelay)
