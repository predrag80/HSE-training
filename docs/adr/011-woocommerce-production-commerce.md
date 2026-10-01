# ADR-011: WooCommerce production commerce ownership

## Status

Accepted

## Date

2026-10-01

## Context

The WooCommerce, RaiAccept, and BokaPOS sandbox flow has been exercised for
successful payment, declined payment and retry, duplicate callback delivery,
completed-order email delivery, full refund, and fiscal refund. Direct bank
transfer is also supported as a manual-settlement path. The owner selected this
integrated flow for production instead of adding a separate PostgreSQL commerce
service.

This decision supersedes ADR-005, ADR-006, and the production promotion gate in
ADR-010. It amends only the commerce boundary in ADR-002: Astro remains the
public marketing application, and WordPress remains headless for public
presentation.

## Decision

- WooCommerce is the system of record for orders, billing data, order status,
  payment-provider references, refunds, customer notification evidence, and the
  operational state used to begin course fulfillment.
- Published Course records remain the editorial source. Each purchasable Course
  is mirrored into one hidden, virtual WooCommerce product whose SKU is the
  immutable `course_key`.
- Customers purchase as guests and are never represented as WordPress
  `wp_users`. Course access and learner identity remain in the external HSE
  e-learning platform.
- RaiAccept is authoritative for card authorization, payment, failure,
  cancellation, and gateway refund outcomes. A browser success URL is never
  proof of payment. The WooCommerce gateway may change an order to paid only
  after the server integration retrieves and accepts the corresponding
  provider state.
- Provider notifications and repeated status callbacks must be idempotent. They
  must not create duplicate orders, completed-order emails, fulfillment events,
  receipts, or refunds. Order, amount, currency, merchant, and provider
  references must remain reconcilable.
- A verified paid RaiAccept order containing only synchronized virtual Course
  products moves to `completed` and becomes eligible for immediate fulfillment
  and final fiscalization.
- Direct bank transfer creates an `on-hold` order and sends the applicable
  payment instructions. It must never auto-complete from the browser. An
  administrator verifies the incoming credit in the merchant bank account and
  then changes the order to `completed`.
- BokaPOS is authoritative for final fiscal receipts and fiscal refunds. The
  current flow does not issue advance receipts. A fiscal refund must reference
  a successfully fiscalized sale and contain returned line quantities that map
  to the original receipt.
- Astro exposes course marketing pages and initiates checkout through the
  allowlisted CMS endpoint. Card details are entered only in the RaiAccept
  hosted payment interface and never pass through Astro or project browser
  code.
- The WordPress public theme remains closed except for the minimum checkout,
  order-pay, order-received, gateway callback/return, REST, AJAX, and cron
  surfaces required by commerce.
- Commerce credentials remain in server-only secret storage. WordPress and
  WooCommerce data require encrypted off-site backups, restore testing,
  retention controls, least-privilege administration, and operational
  monitoring.

## Authority boundaries

```text
WooCommerce  = order and business-workflow authority
RaiAccept    = card payment and gateway-refund authority
BokaPOS      = fiscal receipt and fiscal-refund authority
Astro        = public presentation and checkout entry point
External LMS = learner account, access, and course-delivery authority
```

## Consequences

- A separate PostgreSQL store is not part of the current commerce architecture.
  Adding one later requires a new ADR, an explicit migration and reconciliation
  design, and a single unambiguous owner for every state.
- The WordPress installation is no longer editorial-only in the transactional
  sense. It is a headless CMS plus a deliberately limited commerce host; it is
  still not the public marketing application or the learner platform.
- WooCommerce, RaiAccept, BokaPOS, and `hse-headless` become production-critical
  dependencies. Updates require a backup, compatibility review, and regression
  tests for payment, retry, email, fiscalization, and refund paths.
- The existing constant name `HSE_WOOCOMMERCE_STAGING_BRIDGE` is retained as a
  backward-compatible implementation flag. In an approved environment it means
  that the WooCommerce commerce surface is enabled; its historical name no
  longer limits the architecture to staging.
- The sandbox runbook in ADR-010 remains useful for regression testing but no
  longer defines the production ownership model.

## Production readiness

This architecture decision does not waive operational launch gates. Production
still requires supported plugin versions, protected administration, verified
backups and restore, real cron execution, monitoring, production credentials,
and a controlled real payment/refund/fiscalization smoke test before the site is
opened publicly.
