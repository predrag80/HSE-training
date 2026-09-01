# HSE Training Project Specification

Status: Draft baseline for review
Last reviewed: 2026-09-01

## Objective

Replace the public presentation of the existing
[`www.hsetraining.rs`](https://www.hsetraining.rs/) site with a fast,
static-first Astro application while retaining WordPress as the owner's
editorial CMS.

The system will initially sell one course, but every public route, content
contract, and business-state model must support multiple courses without a
structural rewrite. Customers can discover courses, submit the contact form,
and begin checkout without creating an HSE Training account. The actual
learning experience and learner access remain in a third-party LMS.

Success means the public site and CMS are operationally separate, payment state
is trustworthy, and the owner can fulfill a verified purchase without turning
WordPress or Astro into an LMS.

## Confirmed Assumptions

- The production public application is `https://hsetraining.rs` on Hetzner.
- The production CMS is `https://cms.hsetraining.rs` on the existing Serbia
  Broadband hosting.
- Astro with TypeScript is the public application.
- WordPress is a headless CMS only.
- PostgreSQL is the system of record for payment and business state.
- Lemon Squeezy is the expected payment provider, subject to final account,
  product, tax, and webhook validation before implementation.
- Initial course access is provisioned manually by the owner in an external LMS
  after a verified purchase.
- No local customer authentication is required.
- Crafto Consulting may inform visual direction only. Its implementation,
  Elementor structure, and proprietary assets are not project architecture.

## Capability Map

| Module ID | Responsibility | Depends on |
|---|---|---|
| `content-cms` | Editorial course and site content; published content API | — |
| `commerce-state` | Purchases, payment events, and fulfillment state | — |
| `payment-integration` | Checkout creation and verified webhook ingestion | `commerce-state` |
| `public-web` | Public pages and course discovery | `content-cms` |
| `contact-delivery` | Server-side contact-form validation and delivery | `public-web` |
| `course-fulfillment` | Owner workflow for granting external LMS access | `commerce-state`, `payment-integration` |

Build order:

1. Establish `content-cms` contracts and `commerce-state` identifiers.
2. Build a static public course slice against `content-cms`.
3. Add `contact-delivery` as the first server-side form slice.
4. Add `payment-integration` with verified, idempotent webhooks.
5. Add the `course-fulfillment` owner workflow.

The module IDs are stable planning identifiers. A later plan may split work
inside a module but must not silently change ownership or dependency direction.

## System Architecture

```text
Visitors
   |
   v
Astro + TypeScript on Hetzner
   |              |                 |
   | content      | business state  | checkout/webhooks
   v              v                 v
WordPress      PostgreSQL       Lemon Squeezy
on SBB hosting                       |
                                     v
                            verified purchase
                                     |
                                     v
                         owner provisions access
                                     |
                                     v
                             third-party LMS
```

### Astro public application

- Owns all public presentation at `hsetraining.rs`.
- Prefers static output for pages that do not require request-time work.
- Uses server endpoints only for operations such as contact submission,
  checkout creation, and payment webhooks.
- May render public course descriptions obtained from WordPress, but must not
  store or serve instructional course materials.
- Must not require React unless a later feature demonstrates a clear need.

### WordPress content CMS

- Remains on the current Serbia Broadband hosting.
- Is available to editors at `cms.hsetraining.rs` in production.
- Owns editorial site content and public course marketing content.
- Does not own customers, purchases, payment state, or course access.
- Must never model customers as `wp_users`.
- Exposes only the published fields needed by Astro through an explicit API
  boundary.

### PostgreSQL business store

- Owns purchase, payment-event, and fulfillment state.
- Stores the provider identifiers needed for reconciliation and idempotency.
- Is not queried directly by WordPress or browser code.
- Uses explicit migrations for every schema change.

### Lemon Squeezy payment integration

- Creates or links checkout sessions for a stable `course_key`.
- Treats browser success redirects as informational only.
- Confirms payment only after verifying the webhook signature and event.
- Processes duplicate webhook deliveries without duplicate purchases or
  fulfillment actions.

### External course delivery

- The third-party LMS stores and delivers instructional content.
- Customers do not need an HSE Training application account.
- Initial fulfillment is a documented manual owner action after verified
  purchase; automation is a later integration decision.
- The project must not reproduce LMS functions such as lessons, progress,
  quizzes, certificates, or learner authentication.

## Stable Course Identity

`course_key` is the cross-system identifier for a course.

- It is assigned deliberately, unique, and immutable after publication.
- It uses lowercase ASCII letters and numbers separated by single hyphens, with
  a maximum length of 80 characters.
- It must not be derived from or replaced by a WordPress post ID.
- WordPress, PostgreSQL, payment metadata, and LMS fulfillment references use
  the same value.
- Display titles, URLs, WordPress IDs, and payment-provider product IDs may
  change without changing `course_key`.
- The initial implementation must handle collections and lookup by
  `course_key`, even while only one course exists.

The initial key is `nebosh-igc`. Other systems must apply the same canonical
format when the PostgreSQL and payment mappings are introduced.

## Primary Data Flows

### Published content

1. The owner edits content in WordPress.
2. WordPress publishes an API representation keyed by `course_key`.
3. Astro validates the response before rendering it.
4. The public site displays marketing content without exposing privileged CMS
   operations or credentials.

### Checkout and payment confirmation

1. A visitor chooses a course identified by `course_key`.
2. An Astro server operation creates or resolves the provider checkout.
3. The browser is redirected to Lemon Squeezy.
4. A return redirect may show a pending message but grants no access.
5. A webhook endpoint verifies the provider signature before parsing trusted
   payment state.
6. PostgreSQL records the provider event using a unique event identifier.
7. The purchase state is updated transactionally and duplicate deliveries
   become no-ops with an auditable outcome.

### Course fulfillment

1. A verified paid purchase enters a fulfillment queue represented in
   PostgreSQL state, not an infrastructure queue service.
2. The owner uses the purchaser details and `course_key` to grant access in the
   third-party LMS.
3. The owner records fulfillment status and timestamp.
4. Failures remain retryable without altering the verified payment record.

### Contact submission

1. The browser submits to an Astro server endpoint.
2. The endpoint validates and normalizes accepted fields.
3. Server-side abuse protection is applied.
4. The message is delivered through a provider selected before implementation.
5. Secrets, provider credentials, and internal errors never reach browser code.

## Project Structure

```text
apps/web/                         Astro public application
wordpress/plugins/hse-headless/   Repository-owned CMS integration plugin
docs/adr/                         Architectural decision records
docs/architecture/                Supporting architecture documentation
docs/migration/                   Existing-site migration documentation
tasks/                            Approved plans and implementation tasks
AGENTS.md                         Permanent agent and architecture rules
SPEC.md                           Product and system contract
CONSTRAINTS.md                    Quality and scope contract
```

The WordPress runtime at `~/Sites/hsetraining-cms` is machine-local state and is
never part of this repository.

## Command Contract

There is no Astro application, package manifest, test runner, linter, or CI
configuration yet. The task that scaffolds Astro must provide these commands
without changing their intent:

```sh
npm --prefix apps/web run dev
npm --prefix apps/web run build
npm --prefix apps/web run check
npm --prefix apps/web run lint
npm --prefix apps/web run test
```

Until then, repository documentation can be checked with:

```sh
git diff --check
git status --short
```

The existing local WordPress runtime can be checked independently with:

```sh
cd ~/Sites/hsetraining-cms
wp core is-installed
wp core verify-checksums
wp rewrite list
```

## Code Style Contract

- TypeScript must use strict type checking once the application exists.
- Domain names use `camelCase` in TypeScript and explicit wire/storage mappings
  where external systems use `snake_case`.
- Prefer small, framework-independent functions for domain rules.
- Validate data at every external boundary; do not hide uncertainty with casts.
- Astro components remain server-rendered or static unless client-side
  JavaScript is necessary for a documented interaction.

Representative naming:

```ts
export interface CourseSummary {
  readonly courseKey: string;
  readonly title: string;
  readonly checkoutAvailable: boolean;
}
```

## Testing Strategy

- Unit tests cover content mapping, input validation, payment state transitions,
  and idempotency decisions.
- Integration tests cover WordPress API contracts, PostgreSQL transactions, and
  verified webhook processing using provider fixtures or test mode.
- Endpoint tests cover contact validation, abuse controls, error handling, and
  the rule that a checkout redirect cannot confirm payment.
- Browser tests cover public course discovery, contact submission, checkout
  initiation, responsive behavior, and keyboard accessibility.
- Deployment smoke tests cover the public site, CMS API availability, and
  health of required server-side operations without exposing secrets.
- No skipped or deleted test may be used to make a change pass.

Framework and coverage tooling will be selected with the Astro scaffold. The
resulting commands and measured baselines must be recorded in `CONSTRAINTS.md`
before application functionality is merged.

## Boundaries

### Always

- Use `course_key` across system boundaries.
- Validate WordPress, browser, payment-provider, and LMS integration data.
- Verify payment webhooks and process them idempotently.
- Keep payment/business state in PostgreSQL.
- Keep secrets in server-only configuration.
- Prefer static Astro pages and small vertical implementation slices.
- Add or update an ADR when an architectural choice changes.

### Ask first

- Add a runtime dependency or hosted infrastructure service.
- Change a public API, database schema, or stable identifier format.
- Automate LMS provisioning or customer communications.
- Store additional personal data or change retention behavior.
- Introduce customer authentication, client-side UI frameworks, queues, caches,
  containers, or new deployment infrastructure.

### Never

- Use WordPress as the public application or customer database.
- Use WordPress post IDs as business identifiers.
- Trust checkout success redirects as payment confirmation.
- Expose secrets or privileged CMS/database access to browser code.
- Host course lessons in Astro or build LMS functionality.
- Copy Crafto/Elementor architecture or unlicensed proprietary assets.
- Commit WordPress core, runtime uploads, databases, credentials, or `.env`
  files.

## Success Criteria

- `hsetraining.rs` is served by the Astro application on Hetzner.
- `cms.hsetraining.rs` remains an editor-only headless WordPress installation on
  the current Serbia Broadband hosting.
- Adding a second course requires content/data entries, not a structural rewrite.
- Public course data is exchanged using stable `course_key` values.
- Customers can browse, contact the owner, and purchase without local accounts.
- Contact submissions are validated and delivered server-side.
- A success redirect alone cannot mark a purchase paid or trigger fulfillment.
- Valid payment webhooks create one durable business outcome even when delivered
  repeatedly.
- PostgreSQL contains auditable payment and fulfillment state.
- The owner can identify paid, unfulfilled purchases and record external LMS
  access delivery.
- Instructional content and learner activity remain outside Astro and WordPress.
- Build, type, lint, test, security, accessibility, and performance gates are
  defined and passing before production launch.

## Open Questions

1. Which WordPress fields and existing-site content must be migrated, and which
   current URLs require redirects?
2. Which third-party LMS will be used, and what exact manual fulfillment steps
   and evidence are required?
3. Has Lemon Squeezy been approved for the required products, currencies, tax,
   invoices, refunds, and Serbian business context?
4. Where will PostgreSQL run in production, and what backup, recovery, and
   operator-access model will it use?
5. Which provider will deliver contact messages, what anti-spam mechanism is
   acceptable, and how long may submissions/logs be retained?
6. How should WordPress publishing trigger Astro content refresh or deployment?
7. What languages, accessibility target, analytics, cookie-consent, and SEO
   requirements apply to the public launch?
