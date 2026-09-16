# ADR-010: WooCommerce and RaiAccept evaluation and production direction

## Status

Accepted for staging; WooCommerce and RaiAccept selected for production subject
to the readiness gates below

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
`HSE_WOOCOMMERCE_STAGING_BRIDGE` flag. The flag is disabled by default. The
owner has selected the same WooCommerce/RaiAccept approach for production, but
the staging flag remains in place until the sandbox matrix, bank approval,
authenticated notification handling, customer-email delivery, refund flow,
delivery-evidence retention, and rollback checks have passed.

- Published Course records are mirrored into hidden WooCommerce products whose
  SKU equals the stable `course_key`. The mirror is derived catalogue data;
  Courses remain the editorial source.
- Each Course has an explicit CMS online-purchase checkbox and checkout price.
  Checked Courses are priced in the derived product and receive an Add to
  Buy now CTA that proceeds directly to checkout; unchecked Courses have no
  checkout price and lead to the contact
  form. The current staging content enables only `nebosh-igc`.
- Astro reads only a small public product projection: stable key, integer price,
  currency, availability, and checkout initiation URL.
- WooCommerce API credentials and WordPress product IDs never cross the CMS
  boundary.
- Checkout initiation resolves `course_key` on the CMS server, prepares the
  single course for purchase, and redirects directly to WooCommerce checkout.
- The public WordPress theme remains closed except for WooCommerce checkout and
  gateway callback/return requests while the staging flag is enabled.
- RaiAccept remains in sandbox mode. The arbitrary staging price is not an
  approved production price.
- The evaluation currency is RSD, matching the bank's Internet-sales-site
  instructions. Any future foreign-currency display or charging requires prior
  written bank approval and the bank-prescribed English conversion notice.
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
- Before production, the baseline architecture documents must be reconciled so
  WooCommerce owns order and payment state while preserving the existing
  `course_key`, authenticated-notification, idempotency, retention, deployment,
  and rollback contracts. Customers must not be represented as WordPress users,
  and course delivery remains external.

## Production promotion gate

Promote the bridge through a reviewed production ADR and matching updates to
`SPEC.md`, `CONSTRAINTS.md`, and `AGENTS.md` only after the sandbox results and
Raiffeisen production requirements have been reviewed. Until then, production
payment processing remains disabled even though WooCommerce and RaiAccept are
the selected production direction.
