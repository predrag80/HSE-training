# Repository Guidelines

## Project Structure

- Preserve existing files and keep changes scoped to the active task.
- Place the web application under `apps/web/`.
- Place the headless WordPress plugin under `wordpress/plugins/hse-headless/`.
- Record architecture decisions in `docs/adr/` and supporting documentation under `docs/architecture/` and `docs/migration/`.

## Architectural Rules

- WordPress is CMS only.
- Customers must not use `wp_users`.
- Astro is the public application.
- WordPress post IDs are not cross-system business identifiers.
- Use `course_key` as the stable course identifier.
- PostgreSQL owns payment and business state.
- Course delivery remains external.
- Do not build LMS functionality.
- Do not build authentication without an explicit requirement.

## Payment and Security Rules

- Payment confirmation must come from verified webhooks.
- Never trust checkout success redirects as payment confirmation.
- Webhook processing must be idempotent.
- Never expose secrets to browser code.

## Delivery Rules

- Prefer static Astro pages.
- Use server endpoints only where needed.
- Implement features in small vertical slices.

