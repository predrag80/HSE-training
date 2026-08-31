# ADR-001: Astro as the Public Frontend

## Status

Proposed

## Date

2026-08-31

## Context

The project needs a fast public website and application that is independent of WordPress presentation concerns. Most pages can be generated statically, while a small number of operations require server-side handling.

## Proposed Decision

Use Astro with TypeScript as the public application. Prefer static pages and introduce Astro server endpoints only for requirements that need server-side execution.

## Alternatives Considered

- Continue serving the public website from WordPress.
- Use a client-side React application.

## Consequences

- Public presentation and CMS administration remain separate.
- Interactive client-side JavaScript must be added selectively.
- Server-side capabilities remain explicit rather than becoming the default.

