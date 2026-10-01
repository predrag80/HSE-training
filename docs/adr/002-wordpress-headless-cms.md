# ADR-002: WordPress as a Headless CMS

## Status

Accepted; the commerce boundary is amended by ADR-011

## Date

2026-08-31

## Context

The owner needs an established editorial interface, while customer identity and
course delivery do not belong in WordPress. ADR-011 later selected WooCommerce
inside the same installation as the transactional commerce runtime.

## Proposed Decision

Keep WordPress headless for public presentation. Customers are not WordPress
users and must not use `wp_users`. WooCommerce may own commerce state under the
separate controls recorded in ADR-011.

## Alternatives Considered

- Keep WordPress as both CMS and public application.
- Move editorial content into the Astro repository.

## Consequences

- WordPress remains focused on editorial content.
- Astro owns all public presentation.
- Editorial content and WooCommerce transactional data remain separate logical
  responsibilities even though they share the WordPress database/runtime.
