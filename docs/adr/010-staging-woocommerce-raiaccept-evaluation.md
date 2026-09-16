# ADR-010: Staging-only WooCommerce and RaiAccept evaluation

## Status

Accepted as a temporary staging experiment; not accepted for production

## Date

2026-09-16

## Context

The owner has installed WooCommerce and the Raiffeisen Payment Gateway plugin,
configured RaiAccept sandbox credentials, and explicitly requested an end-to-end
staging payment test for the initial `nebosh-igc` course. RaiAccept documents
support for WooCommerce 6.x through 10.x, while the local CMS had WooCommerce
11.1.0 installed.

The baseline architecture keeps WordPress editorial-only and makes PostgreSQL
authoritative for purchases and verified webhook state. Replacing that
architecture silently would invalidate existing security and idempotency
requirements. A bounded experiment is therefore required before any production
decision.

## Decision

Evaluate WooCommerce 10.9.4 and RaiAccept on staging behind the server-only
`HSE_WOOCOMMERCE_STAGING_BRIDGE` flag. The flag is disabled by default.

- The WooCommerce product SKU equals the stable `course_key` (`nebosh-igc`).
- Astro reads only a small public product projection: stable key, integer price,
  currency, availability, and checkout initiation URL.
- WooCommerce API credentials and WordPress product IDs never cross the CMS
  boundary.
- Checkout initiation resolves `course_key` on the CMS server, creates a
  one-item cart, and redirects to WooCommerce checkout.
- The public WordPress theme remains closed except for WooCommerce checkout and
  gateway callback/return requests while the staging flag is enabled.
- RaiAccept remains in sandbox mode. The arbitrary staging price is not an
  approved production price.
- Successful browser redirects are evidence for the test only. Production must
  still use authenticated, idempotent server notification processing and an
  approved system of record.

## Consequences

- Staging can test the installed gateway with the actual Astro CTA and stable
  course identity.
- The experiment introduces a temporary public checkout surface on the CMS
  host, but not on unflagged environments.
- WooCommerce's database version may remain newer after a code downgrade; the
  experiment must pass checkout and gateway regression tests and must retain a
  pre-downgrade database/plugin backup.
- A separate architecture decision is required before production. It must
  either remove this bridge and implement the specified Astro/PostgreSQL
  payment slice, or deliberately supersede the existing payment ownership,
  webhook, retention, deployment, and rollback contracts.

## Removal trigger

Remove the bridge or promote it through a new reviewed ADR no later than
2026-10-31, after the sandbox results and Raiffeisen production requirements
have been reviewed.
