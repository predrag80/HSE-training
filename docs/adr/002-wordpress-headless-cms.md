# ADR-002: WordPress as a Headless CMS

## Status

Proposed

## Date

2026-08-31

## Context

The owner needs an established editorial interface, but customer identity, payments, and course delivery do not belong in WordPress.

## Proposed Decision

Keep WordPress as a headless CMS only. Customers are not WordPress users and must not use `wp_users`.

## Alternatives Considered

- Keep WordPress as both CMS and public application.
- Move editorial content into the Astro repository.

## Consequences

- WordPress remains focused on editorial content.
- Astro owns all public presentation.
- Business and payment state must be stored outside WordPress.

