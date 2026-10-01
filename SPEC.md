# HSE Training Project Specification

Status: Active production baseline
Last reviewed: 2026-10-01

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

- The production public application is `https://hsetraining.rs` on an
  Unlimited.rs Optimum+ shared-hosting account.
- The production CMS is `https://cms.hsetraining.rs` on the same Unlimited.rs
  Optimum+ account after a provider-managed migration from Serbia Broadband.
- Production email hosting will move from Serbia Broadband to Unlimited.rs as
  part of the same provider-managed migration.
- Unlimited.rs technical support has confirmed that the Optimum+ account can
  run the Astro application, WordPress/WooCommerce, the required server
  endpoints, and email delivery for this architecture.
- `https://staging.hsetraining.rs` remains the Astro staging environment on
  Hetzner and is operationally separate from production.
- Astro with TypeScript is the public application.
- WordPress is headless for public presentation and hosts the approved
  WooCommerce commerce runtime.
- WooCommerce is the system of record for orders and business-workflow state.
- Card payment uses Raiffeisen Bank's hosted RaiAccept gateway. BokaPOS provides
  Serbian fiscalization for completed sales and mapped fiscal refunds.
- After a verified purchase, the customer receives instructions for the HSE
  e-learning platform and can begin the digital course immediately. Standard
  course access lasts 12 months; any later extension fee is handled by HSE
  Training outside this website.
- No local customer authentication is required.
- The [Crafto Consulting demo](https://crafto.themezaa.com/consulting/) is the
  approved primary visual target for the public frontend. Corresponding HSE
  Training sections should reproduce it with high visual fidelity while using
  an independent Astro implementation and project-owned or approved assets.

## Capability Map

| Module ID | Responsibility | Depends on |
|---|---|---|
| `content-cms` | Editorial course and site content; published content API | — |
| `commerce-state` | WooCommerce orders, status, refunds, notifications, and fulfillment readiness | `content-cms` |
| `payment-integration` | RaiAccept checkout and server-confirmed payment/refund outcomes | `commerce-state` |
| `fiscalization` | BokaPOS sale receipts and fiscal refunds | `commerce-state`, `payment-integration` |
| `public-web` | Public pages and course discovery | `content-cms` |
| `contact-delivery` | Server-side contact-form validation and delivery | `public-web` |
| `course-fulfillment` | Owner workflow for granting external LMS access | `commerce-state`, `payment-integration` |

Build order:

1. Establish `content-cms` contracts and stable commerce identifiers.
2. Build a static public course slice against `content-cms`.
3. Add `contact-delivery` as the first server-side form slice.
4. Add `payment-integration` with server-confirmed, idempotent callbacks.
5. Add `fiscalization` for completed sales and refunds.
6. Add the `course-fulfillment` owner workflow.

The module IDs are stable planning identifiers. A later plan may split work
inside a module but must not silently change ownership or dependency direction.

## System Architecture

```text
Visitors
   |
   v
Astro + TypeScript on Unlimited.rs Optimum+
   | content API             | checkout entry
   v                         v
WordPress CMS + WooCommerce on Unlimited.rs
   | order/payment refs      | fiscal request
   v                         v
RaiAccept hosted gateway   BokaPOS
   | verified outcome        | fiscal receipt/refund
   +------------+------------+
                v
       WooCommerce completed order
                |
                v
       external course fulfillment
```

### Astro public application

- Owns all public presentation at `hsetraining.rs`.
- Prefers static output for pages that do not require request-time work.
- Links only to documented WordPress REST/contact and WooCommerce checkout
  endpoints; Astro does not accept payment notifications or mutate order state.
- Is built as static output and deployed independently from the WordPress
  runtime.
- May render public course descriptions obtained from WordPress, but must not
  store or serve instructional course materials.
- Must not require React unless a later feature demonstrates a clear need.

### WordPress CMS and WooCommerce commerce host

- Runs on the Unlimited.rs Optimum+ account after Unlimited.rs technical
  support completes the WordPress, database, and email migration from Serbia
  Broadband.
- Is available to editors at `cms.hsetraining.rs` in production.
- Owns editorial site content and public course marketing content.
- Hosts WooCommerce as the system of record for orders, billing data, order
  status, payment references, refunds, customer notification evidence, and
  fulfillment readiness.
- Must never model customers as `wp_users`.
- Does not own learner identity, LMS access, or instructional content.
- Exposes only the published fields needed by Astro through an explicit API
  boundary, plus the minimum public WooCommerce surfaces required for checkout,
  payment return/callback, order payment, and order confirmation.

### Bilingual content

- English and Serbian are the supported public content languages.
- English is the default locale and keeps the existing unprefixed public URLs;
  Serbian uses the `/sr/` URL prefix and Serbian Latin (`sr-Latn`) document
  language metadata.
- Each language has a real, indexable Astro route. Runtime machine translation
  and same-URL language replacement are not part of the architecture.
- Navigation and other application-interface copy live in reviewed Astro locale
  dictionaries. Editorial marketing content lives in WordPress in both
  languages and is returned through the repository-owned `hse-headless` plugin.
- The project will not install Polylang, WPML, GTranslate, or another WordPress
  translation plugin for this content model.
- Translated CMS records share their stable content key and differ by locale.
  WordPress post IDs and localized slugs are not translation identifiers.
- Language switching links equivalent routes by stable route or content key;
  course translations are paired by `course_key`.
- Static builds validate each localized CMS response and must not silently mix
  English editorial content into a published Serbian page.
- Both locale variants expose appropriate document language, canonical URL,
  alternate `hreflang`, metadata, and sitemap entries.

### WooCommerce business store

- Uses the WordPress database through WooCommerce's supported data-access layer
  and HPOS-compatible order APIs.
- Owns orders and business-workflow state while retaining provider identifiers
  needed for reconciliation and idempotency.
- Stores only the billing and operational data required for purchase,
  fiscalization, refund, notification, and course fulfillment.
- Is never queried directly by Astro or browser code.
- Requires matched database/code backups and a tested recovery plan for schema
  or plugin-version changes.

### RaiAccept card-payment integration

- Creates a hosted checkout request from a WooCommerce order containing the
  stable `course_key`/SKU, internal order ID, exact amount, and currency.
- Redirects the customer to the bank or its processor for card entry,
  authentication, and authorization; HSE Training browser code never collects
  or stores card details.
- Treats browser success, failure, and cancellation redirects as informational
  only.
- Confirms card payment only through the RaiAccept server integration after it
  retrieves and accepts the provider's authoritative transaction state.
- Reconciles merchant identity, order/reference, amount, currency, transaction
  status, and provider transaction identifier before changing order state.
- Processes repeated notifications idempotently without duplicate orders,
  emails, fulfillment, receipts, or refunds.
- Keeps gateway-specific behavior in the official RaiAccept plugin plus the
  repository-owned, update-safe compatibility layer; provider plugin files are
  not edited directly.

### Direct bank transfer

- Creates a WooCommerce `on-hold` order and sends the approved domestic or
  international payment instructions.
- Does not infer settlement from the checkout browser flow.
- Requires an administrator to verify the incoming bank credit before moving
  the order to `completed`.

### BokaPOS fiscalization

- Treats BokaPOS as authoritative for fiscal receipts and fiscal refunds.
- Fiscalizes the final sale when the WooCommerce order is `completed`; the
  current flow does not issue an advance receipt.
- Requires a fiscal refund to reference the original fiscalized sale and to
  include returned line quantities that map to the original receipt.
- Sends the official document through exactly one configured customer channel
  and retains enough delivery evidence for operational reconciliation.
- A read-only, idempotent watchdog reports failed or delayed sale
  fiscalization, fiscal refunds and fiscal-document e-mail delivery without
  issuing or retrying a fiscal document. It uses an independent Sentry channel,
  a privacy-bounded e-mail fallback and a real server cron heartbeat.

### External course delivery

- The HSE e-learning platform stores and delivers instructional content.
- Customers do not need an HSE Training application account.
- After verified payment, the purchaser receives the account-creation path and
  can begin the course immediately in the external LMS.
- Standard course access lasts 12 months. Continued access may require an
  additional extension fee charged by HSE Training outside the website; the
  amount is not currently published through this commerce flow.
- The project must not reproduce LMS functions such as lessons, progress,
  quizzes, certificates, or learner authentication.

## Frontend Design Contract

### Approved visual target

The Astro public application should reproduce the approved
[Crafto Consulting demo](https://crafto.themezaa.com/consulting/) as closely as
practical on a visually near-1:1 basis for corresponding sections, while
adapting the content and brand expression to HSE Training.

Visual fidelity includes:

- section structure and composition;
- layout proportions and container widths;
- whitespace and vertical rhythm;
- typography scale, hierarchy, and heading treatments;
- card dimensions and composition;
- image placement and cropping;
- button dimensions and visual states;
- borders, radii, and backgrounds;
- navigation behavior;
- responsive stacking, column collapse, resizing, and spacing; and
- approved transitions and animations that contribute to the visible
  experience.

The visual target applies across mobile, tablet, laptop, and desktop layouts;
desktop similarity alone does not satisfy this requirement. Equivalent
responsive behavior must be implemented with clean project CSS rather than
copying device-specific reference-site hacks.

### Independent implementation

High visual fidelity must not create implementation coupling to Crafto or
Elementor. The frontend uses Astro components, Astro layouts, semantic HTML,
project-specific class names, project-owned CSS, CSS Grid and Flexbox, and the
minimum JavaScript required by approved interactions.

Do not:

- copy Elementor DOM structure or unnecessary page-builder wrapper markup;
- copy Elementor-generated or Crafto CSS class names;
- copy WordPress shortcodes or templates;
- copy Crafto CSS or vendor JavaScript architecture;
- install or depend on Crafto or Elementor; or
- depend on Crafto assets that are not licensed and approved for this project.

Visual equivalence does not require DOM equivalence. Semantic Astro markup may
be materially simpler than the reference markup provided the rendered result
and behavior closely match the approved visual target.

“Convert Crafto to Astro” means:

```text
analyze visible layout and behavior
        ↓
identify reusable UI structures
        ↓
reimplement those structures independently
        ↓
Astro components + semantic HTML + project CSS
```

It does not mean copying source HTML into an `.astro` file or copying
Elementor-generated HTML, CSS, and JavaScript and patching it until it works.

### HSE Training content substitution

Crafto supplies the approved structure and visual behavior; HSE Training
supplies the content. Business copy becomes HSE Training copy, services become
HSE services, experts become real HSE instructors or team members where
applicable, and pricing or offer cards become real HSE courses where applicable.

Do not reproduce Crafto placeholder content or invent HSE facts merely to fill
a reference section. If a reference section has no logical HSE business purpose
or lacks approved HSE content, record the discrepancy for owner review rather
than forcing or fabricating content.

### Interaction and animation policy

Visible Crafto interactions that are relevant to the approved experience should
be analyzed and reimplemented independently. Prefer CSS, native browser APIs,
and minimal JavaScript. Adding an animation library such as GSAP requires a
separate task with a documented need and dependency review. Crafto animation
scripts must not be copied.

This visual requirement does not change the system architecture: Astro remains
the public application, WordPress remains headless for public presentation,
WooCommerce remains the commerce-state store, RaiAccept remains the external
card-payment provider, and BokaPOS remains the fiscal authority.

## Stable Course Identity

`course_key` is the cross-system identifier for a course.

- It is assigned deliberately, unique, and immutable after publication.
- It uses lowercase ASCII letters and numbers separated by single hyphens, with
  a maximum length of 80 characters.
- It must not be derived from or replaced by a WordPress post ID.
- WordPress Course records, WooCommerce product SKUs, payment metadata, and LMS
  fulfillment references use the same value.
- Display titles, URLs, WordPress IDs, and payment-provider product IDs may
  change without changing `course_key`.
- The initial implementation must handle collections and lookup by
  `course_key`, even while only one course exists.

The initial key is `nebosh-igc`. New Course records and derived WooCommerce
products must apply the same canonical format.

## Primary Data Flows

### Published content

1. The owner edits content in WordPress.
2. WordPress publishes an API representation keyed by `course_key`.
3. Astro validates the response before rendering it.
4. The public site displays marketing content without exposing privileged CMS
   operations or credentials.

### Checkout and payment confirmation

1. A visitor chooses a course identified by `course_key`.
2. The Astro CTA opens the allowlisted CMS initiation endpoint, which resolves
   the `course_key` to a hidden WooCommerce product and creates the order/cart.
3. WooCommerce creates a RaiAccept checkout for the order ID, exact amount and
   currency, and the browser continues to the hosted payment interface.
4. A return redirect may show a pending message but grants no access.
5. The RaiAccept server integration receives the callback and retrieves the
   provider's authoritative transaction state.
6. The integration reconciles the WooCommerce order/reference, amount,
   currency, merchant, status, and provider transaction identifier.
7. WooCommerce records the verified outcome and moves an eligible paid virtual
   Course order to `completed`. Duplicate callbacks, emails, fulfillment and
   fiscalization become no-ops with an auditable outcome.

### Course fulfillment

1. A verified paid purchase reaches WooCommerce `completed`, the authoritative
   fulfillment-ready state.
2. The purchaser receives instructions or a link to create an account in the
   third-party LMS.
3. The purchaser can create the account and begin the course immediately.
4. Delivery and account-creation evidence is recorded and failures remain
   retryable without altering the verified payment record.

### Contact submission

1. The browser submits to an Astro server endpoint.
2. The endpoint validates and normalizes accepted fields.
3. Server-side abuse protection, request-size limits, rate controls, and spam
   mitigation are applied before delivery.
4. The message is delivered to an approved HSE Training mailbox through the
   Unlimited.rs email service using server-only SMTP credentials or another
   provider-supported authenticated delivery mechanism.
5. A sender-supplied address is used as reply-to data, never as the trusted
   envelope sender.
6. Contact-message bodies are not persisted in the application or WooCommerce
   by default; any later retention requires an explicit privacy decision.
7. Secrets, provider credentials, personal data, and internal errors never
   reach browser code or public logs.

## Hosting and Deployment

### Production

- Unlimited.rs Optimum+ hosts the public Astro application at
  `hsetraining.rs`, the headless WordPress CMS at `cms.hsetraining.rs`, and the
  domain's email service.
- WordPress and Astro remain separate applications and document roots even
  when they share one hosting account.
- Optimum+ support for Astro, PHP/WordPress/WooCommerce, Git, SSH, and email has
  been confirmed for the project. Exact account paths, process
  commands, ports, credentials, and resource limits are recorded during
  deployment setup without reopening the hosting decision.
- Static Astro pages are built in CI and deployed as generated assets. Contact,
  checkout, gateway callback, order, email, and fiscalization operations run in
  the WordPress/WooCommerce integration, not in the Astro build.
- The production workflow uses `https://cms.hsetraining.rs` as the WordPress API
  origin, deploys only reviewed build output and required server artifacts, and
  performs public-site, CMS API, contact-endpoint, and payment-endpoint health
  checks appropriate to the implemented slice.
- Deployment credentials, bank credentials, SMTP credentials, and database
  credentials remain in protected server or CI secret storage.

### Staging

- Hetzner continues to host `staging.hsetraining.rs`.
- Staging uses non-production payment credentials and bank test endpoints.
- Staging must not send test contact messages to unintended production
  recipients or mutate production payment/business state.
- Production deployment remains a distinct controlled workflow; a staging
  deployment does not implicitly publish to production.

### Provider-managed migration

- Unlimited.rs technical support owns the transfer of the existing WordPress
  runtime, database, and email service from Serbia Broadband.
- Before DNS cutover, HSE Training verifies WordPress admin access, the hidden
  login route, pretty permalinks, `/wp-json/`, the HSE REST endpoints, media,
  TLS, inbound and outbound email, and the required MX, SPF, DKIM, and DMARC
  records.
- The existing SBB service is retained until post-cutover verification and a
  documented rollback window are complete.

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

The Astro application provides these local quality and lifecycle commands:

```sh
npm --prefix apps/web run dev
npm --prefix apps/web run build
npm --prefix apps/web run check
npm --prefix apps/web run lint
npm --prefix apps/web run test
```

Repository documentation can be checked with:

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
- Integration tests cover WordPress/WooCommerce contracts, RaiAccept
  notification and status retrieval, idempotent completion/email behavior,
  BokaPOS fiscalization, and refund processing using provider test environments.
- Endpoint tests cover contact validation, abuse controls, error handling, and
  the rule that a checkout redirect cannot confirm payment.
- Browser tests cover public course discovery, contact submission, checkout
  initiation, responsive behavior, and keyboard accessibility.
- Deployment smoke tests cover the public site, CMS API availability, and
  health of required server-side operations without exposing secrets.
- No skipped or deleted test may be used to make a change pass.

Astro Check validates TypeScript and Astro templates, ESLint provides static
linting, and Vitest is the unit-test runner. The bootstrap baselines are recorded
in `CONSTRAINTS.md`; behavior tests and representative coverage measurements
must accompany application functionality as it is introduced.

## Boundaries

### Always

- Use `course_key` across system boundaries.
- Validate WordPress, browser, payment-provider, and LMS integration data.
- Verify card-payment outcomes through the RaiAccept server integration and
  process callbacks idempotently.
- Keep order and business-workflow state in WooCommerce, gateway outcomes in
  RaiAccept, and fiscal outcomes in BokaPOS.
- Keep secrets in server-only configuration.
- Prefer static Astro pages and small vertical implementation slices.
- Reproduce corresponding Crafto Consulting sections with high visual fidelity
  using HSE Training content and an independent Astro implementation.
- Record reference sections that cannot be mapped honestly to approved HSE
  content for owner review.
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
- Copy Crafto/Elementor source architecture, generated class names, vendor
  scripts, unnecessary wrapper markup, or unlicensed proprietary assets.
- Commit WordPress core, runtime uploads, databases, credentials, or `.env`
  files.

## Success Criteria

- `hsetraining.rs` is served by the Astro application on Unlimited.rs
  Optimum+.
- `cms.hsetraining.rs` is a headless WordPress CMS and limited WooCommerce
  commerce host on the same Unlimited.rs Optimum+ account with an independent
  application root; ordinary theme/storefront pages remain closed.
- `staging.hsetraining.rs` remains the Astro staging environment on Hetzner.
- Domain email is delivered through Unlimited.rs with validated MX, SPF, DKIM,
  and DMARC configuration after migration.
- Adding a second course requires content/data entries, not a structural rewrite.
- Public course data is exchanged using stable `course_key` values.
- Customers can browse, contact the owner, and purchase without local accounts.
- Contact submissions are validated and delivered server-side.
- A success redirect alone cannot mark a purchase paid or trigger fulfillment.
- Valid RaiAccept callbacks create one durable WooCommerce business outcome even
  when delivered repeatedly.
- WooCommerce contains auditable order, payment-reference, refund,
  notification, and fulfillment-ready state.
- The owner can identify paid purchases and review external LMS account-creation
  and access-delivery evidence.
- Instructional content and learner activity remain outside Astro and WordPress.
- Corresponding public frontend sections match the approved Crafto Consulting
  visual target with high fidelity across mobile, tablet, laptop, and desktop.
- The high-fidelity frontend uses semantic Astro markup, project-specific CSS,
  approved assets, and no Crafto/Elementor implementation dependency.
- Build, type, lint, test, security, accessibility, and performance gates are
  defined and passing before production launch.

## Open Questions

1. Which WordPress fields and existing-site content must be migrated, and which
   current URLs require redirects?
2. What account-creation and delivery evidence is available from the HSE
   e-learning platform for the 12-month access period?
3. Which production RaiAccept credentials, supported currencies/cards,
   settlement rules, refund operations, 3D Secure settings, callback controls,
   and formal bank approval apply at launch?
4. What WordPress/WooCommerce backup retention, off-site storage,
   restore-test procedure, and operator-access model will be used in production?
5. Which Unlimited.rs SMTP or authenticated mail-delivery configuration will
   the contact endpoint use, what anti-spam mechanism is acceptable, and is the
   default no-storage policy sufficient for submissions and logs?
6. How should WordPress publishing trigger Astro content refresh or deployment?
7. What accessibility target, analytics, cookie-consent, and additional SEO
   requirements apply to the bilingual public launch?
8. Which Crafto sections lack a valid mapping to approved HSE Training content,
   and should each be omitted, deferred, or populated after owner-supplied
   content becomes available?
