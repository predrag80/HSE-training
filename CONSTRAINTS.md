# HSE Training Project Constraints

Status: Written baseline; automation expands with the first application slice
Last reviewed: 2026-08-31

This file defines the minimum quality, security, architecture, and scope bar for
all project work. It must be read with `AGENTS.md`, `SPEC.md`, and the ADRs before
implementation begins. A failing constraint blocks completion unless an explicit
temporary exception is recorded below.

## Floor

These rules apply immediately and require no application tooling:

- Do not commit secrets, credentials, private keys, database dumps, production
  data, `.env` files, WordPress runtime files, or generated build output.
- Do not introduce suppression comments such as `@ts-ignore`,
  `eslint-disable`, `istanbul ignore`, `nosemgrep`, or `gitleaks:allow` merely to
  make a check pass.
- Do not leave unimplemented required behavior as thrown "not implemented"
  errors, empty handlers, silent `catch` blocks, placeholder returns, or TODOs.
- Do not skip or delete tests, remove assertions, or lower a threshold to make a
  change pass.
- Do not weaken this file in the same change that is blocked by it.
- Do not overwrite or discard unrelated user changes in the working tree.
- Do not add a dependency, service, abstraction, or deployment component without
  a current requirement and a simpler-alternative check.
- Every commit must be one coherent, reviewable change with a descriptive
  message and no unrelated cleanup.
- Every claim of completion must identify the command or observable result that
  proves it.

## Architectural Boundaries

### Public application

- Astro is the only public application.
- Prefer static Astro pages. Use server endpoints only for work that requires a
  trusted server environment.
- Client-side JavaScript must be justified by a documented interaction.
- React requires an explicit requirement; it is not a default Astro dependency.
- Astro may render course marketing content but must not host instructional
  content or implement learner functionality.

### WordPress

- WordPress is a headless editorial CMS only.
- Customers must not be created in or represented by `wp_users`.
- WordPress must not own payments, purchases, fulfillment, or authentication for
  public customers.
- Astro consumes WordPress through a documented API boundary, never by querying
  the WordPress database directly.
- WordPress post IDs must not cross the CMS boundary as business identifiers.

### Stable identifiers

- `course_key` is the stable course identifier across WordPress, Astro,
  PostgreSQL, payment metadata, and external fulfillment.
- A course title, slug, WordPress post ID, provider product ID, or LMS ID must
  not replace `course_key`.
- `course_key` is immutable after publication.
- Designs must support multiple course keys even while production has one
  course.

### Business and payment state

- PostgreSQL owns payment, purchase, webhook-processing, and fulfillment state.
- Browser code and WordPress must not connect directly to PostgreSQL.
- Payment confirmation must come from a verified provider webhook.
- A checkout success or cancellation redirect must never mutate authoritative
  payment or fulfillment state.
- Webhook processing must be idempotent. The provider event identifier requires
  a database uniqueness guarantee, and duplicate delivery must not duplicate a
  purchase or fulfillment action.
- Every database schema change requires a reviewed migration and an explicit
  rollback or forward-recovery plan.

### Course delivery

- Course delivery remains in a third-party LMS.
- Do not build lessons, quizzes, progress tracking, certificates, learner
  profiles, or other LMS functionality.
- Do not add customer authentication without an explicit, separately specified
  requirement.
- Initial LMS access provisioning remains an owner-operated fulfillment step.
  Automation requires a new integration decision and threat review.

### Design boundary

- Crafto Consulting is design inspiration only.
- Do not import Crafto, Elementor, page-builder architecture, copied proprietary
  code, or unlicensed assets.
- New visual work must use project-owned implementation and appropriately
  licensed assets.

## Security and Privacy

- Treat browser input, WordPress responses, webhook payloads, provider metadata,
  and LMS data as untrusted until validated.
- Verify webhook authenticity before accepting provider claims or changing
  business state.
- Keep payment, database, CMS, email, and LMS credentials in server-only secret
  storage. Never expose them through client bundles, public environment
  variables, HTML, logs, or API responses.
- Store only personal data required for purchase reconciliation, contact
  handling, and external fulfillment.
- Define retention and deletion behavior before storing contact-message bodies
  or additional customer data.
- Contact endpoints require field validation, bounded input sizes, abuse
  controls, generic public errors, and non-sensitive server logging.
- Logs may contain correlation identifiers and state transitions but must not
  contain credentials, webhook signatures, full payment payloads, or unnecessary
  personal data.
- Production PostgreSQL and administrative interfaces require appropriate
  access controls. The unauthenticated CMS surface is limited to explicitly
  published content APIs.

## Explicit Scope Exclusions

Do not introduce these without a reviewed, explicit requirement demonstrating
why the current architecture cannot meet it:

- Kubernetes
- microservices
- Redis
- infrastructure queue services
- React
- customer authentication
- complex Docker infrastructure
- an HSE-hosted LMS
- Elementor, Crafto, WooCommerce, or a WordPress page-builder architecture

An exclusion is not permission to build a home-grown substitute. Prefer the
simplest design that meets the current requirement.

## Current Enforced Checks

These checks run today:

| Dimension | Required result | Checked by | Runs at |
|---|---|---|---|
| Diff hygiene | Zero whitespace errors | `git diff --check` | before staging and commit |
| Scope | Only intended paths staged | `git diff --cached --name-status` | before commit |
| Review | Entire staged change reviewed | `git diff --cached` | before commit |
| CMS install | WordPress reports installed | `wp core is-installed` in `~/Sites/hsetraining-cms` | CMS changes |
| CMS integrity | Core matches official checksums | `wp core verify-checksums` in `~/Sites/hsetraining-cms` | CMS changes |
| CMS routing | Rewrite rules are available | `wp rewrite list` in `~/Sites/hsetraining-cms` | CMS/API changes |

WordPress checksum verification is the current external check: its expected
state comes from the official distribution rather than from project-authored
tests.

## Application Gates to Activate with Astro

The Astro scaffolding task must create these scripts before application code is
merged:

| Dimension | Required result | Canonical command |
|---|---|---|
| Build | Successful production build | `npm --prefix apps/web run build` |
| Types | Zero TypeScript/Astro errors | `npm --prefix apps/web run check` |
| Lint | Zero configured lint errors | `npm --prefix apps/web run lint` |
| Tests | Zero failing or skipped required tests | `npm --prefix apps/web run test` |

After the first executable slice, measure and record rather than invent the
initial values for:

- changed-line and project test coverage;
- production JavaScript and CSS size;
- accessibility findings against a running local or preview URL;
- page performance and Core Web Vitals;
- dependency and source-code security findings.

Each selected dimension must name an installed tool, an executable command, the
measured baseline, and where it runs before it becomes an enforced numerical
gate. Once a baseline improves, ratchet it upward; do not allow it to regress to
the earlier value. Local task-end checks should target a 90-second budget so
they remain routinely usable; slower external checks belong in CI. The
90-second default is a workflow budget, not permission to skip necessary tests.

## Definition of Done

A code or configuration slice is complete only when:

- its behavior traces to `SPEC.md` or an approved task;
- the smallest relevant tests prove success and failure paths;
- all active commands in this file pass;
- external input is validated and failures are explicit;
- no new secret, suppression, stub, skipped test, or unapproved dependency is
  present in the diff;
- architecture documentation and ADRs match the implementation;
- the staged diff contains only the intended slice;
- operational changes include a verification and rollback/recovery note;
- the commit message explains the purpose of the change.

Documentation-only changes do not require nonexistent application commands, but
they must pass diff hygiene, scope review, factual consistency, and secret
review.

## Exceptions

Exceptions require explicit human approval before merge and must be narrow,
owned, and temporary. The default maximum lifetime is 90 days so a workaround
cannot quietly become permanent architecture.

| ID | Rule | Path/scope | Reason | Owner | Expires | Removal task |
|---|---|---|---|---|---|---|
| — | No active exceptions | — | — | — | — | — |

An expired exception blocks new work in its scope. Tightening a constraint needs
normal review; weakening one requires a separate commit and explicit rationale.

## Review Triggers

Review and update this file when:

- Astro and its quality tooling are scaffolded;
- a measured baseline becomes available;
- a payment, contact, LMS, or CMS integration is introduced;
- personal-data scope changes;
- deployment architecture changes;
- an exception is proposed or expires;
- a new ADR changes a boundary recorded here.
