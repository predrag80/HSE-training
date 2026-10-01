# Repository Guidelines

## Project Structure

- Read `SPEC.md` and `CONSTRAINTS.md` before writing code; do not weaken either
  document merely to make a change pass.
- Preserve existing files and keep changes scoped to the active task.
- Place the web application under `apps/web/`.
- Place the headless WordPress plugin under `wordpress/plugins/hse-headless/`.
- Record architecture decisions in `docs/adr/` and supporting documentation under `docs/architecture/` and `docs/migration/`.

## Architectural Rules

- WordPress is headless for public presentation and also hosts the approved
  WooCommerce commerce runtime.
- Customers must not use `wp_users`.
- Astro is the public application.
- WordPress post IDs are not cross-system business identifiers.
- Use `course_key` as the stable course identifier.
- WooCommerce owns orders, order status, checkout data, refunds, and operational
  commerce evidence.
- RaiAccept is authoritative for card-payment and card-refund outcomes.
- BokaPOS is authoritative for fiscal receipts and fiscal refunds.
- Course delivery remains external.
- Do not build LMS functionality.
- Do not build authentication without an explicit requirement.

## Payment and Security Rules

- Card-payment confirmation must come from the RaiAccept server integration and
  its authenticated provider-status retrieval, never from a browser redirect.
- A direct-bank-transfer order remains `on-hold` until an administrator verifies
  the incoming bank credit and moves it to `completed`.
- Never trust checkout success redirects as payment confirmation.
- Payment callbacks, status changes, emails, fulfillment, and fiscalization must
  be idempotent.
- Never expose secrets to browser code.

## Delivery Rules

- Prefer static Astro pages.
- Use server endpoints only where needed.
- Implement features in small vertical slices.
