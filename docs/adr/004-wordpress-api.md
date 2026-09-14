# ADR-004: WordPress Content API Boundary

## Status

Proposed

## Date

2026-08-31

## Context

Astro needs published course and site content from WordPress without inheriting WordPress presentation, customer, or payment concerns. WordPress post IDs are internal CMS identifiers and are unsafe as cross-system business identifiers.

## Proposed Decision

Use separate WordPress post types and core posts controllers for NEBOSH Courses
and professional Trainings. Courses are exposed at `/wp-json/wp/v2/courses`;
Trainings are exposed at `/wp-json/wp/v2/trainings`. Both types register the
same required scalar marketing metadata and the `course_key` collection filter
that the core controller lacks.

Astro will validate and map the WordPress response at a CMS adapter boundary.
Identify courses across systems with a stable `course_key`, never with a
WordPress post ID.

## Alternatives Considered

- Query the WordPress database directly.
- Use WordPress post IDs as shared course identifiers.
- Expose unrestricted WordPress data to the browser.

## Consequences

- The API contract must explicitly map CMS content to `course_key`.
- Editors can manage professional Trainings independently from NEBOSH Courses,
  while `course_key` uniqueness remains enforced across both post types within
  each locale.
- Core WordPress status permissions continue to govern public and privileged
  reads.
- The public contract retains WordPress's rendered title/content structure; an
  Astro adapter prevents that transport shape from spreading through the app.
- WordPress content can be migrated without changing business identifiers.
- Secrets and privileged WordPress operations must remain server-side.
