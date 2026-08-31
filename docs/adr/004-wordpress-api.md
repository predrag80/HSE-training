# ADR-004: WordPress Content API Boundary

## Status

Proposed

## Date

2026-08-31

## Context

Astro needs published course and site content from WordPress without inheriting WordPress presentation, customer, or payment concerns. WordPress post IDs are internal CMS identifiers and are unsafe as cross-system business identifiers.

## Proposed Decision

Expose only the content Astro needs through the WordPress API boundary. Identify courses across systems with a stable `course_key`, never with a WordPress post ID.

## Alternatives Considered

- Query the WordPress database directly.
- Use WordPress post IDs as shared course identifiers.
- Expose unrestricted WordPress data to the browser.

## Consequences

- The API contract must explicitly map CMS content to `course_key`.
- WordPress content can be migrated without changing business identifiers.
- Secrets and privileged WordPress operations must remain server-side.

