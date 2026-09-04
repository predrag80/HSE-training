# Task 12 Checklist: Homepage 1:1 Crafto Reconstruction

Status: Proposed — implementation starts after owner approval of the plan

## 12.1 Header/navigation reconstruction

**Description:** Reconstruct the reference utility and navigation shell while
preserving the HSE brand and semantic navigation. The homepage desktop navigation
may overlay the hero; internal pages must retain a readable solid header.

**Acceptance criteria:**

- [ ] Header heights, container alignment, link spacing, active state, and homepage overlay match the Task 11 measurements at 375, 768, 1024, and 1440px.
- [ ] Mobile navigation uses native disclosure semantics, a 44px target, logical focus order, and no off-screen overflow.
- [ ] Internal course routes retain sufficient contrast and their existing behavior.

**Verification:**

- [ ] `npm --prefix apps/web run check`
- [ ] Browser screenshots and keyboard pass on `/` and `/courses` at mobile and desktop widths.
- [ ] Accessibility tree exposes named desktop and mobile navigation landmarks.

**Dependencies:** Task 11 foundations
**Files likely touched:** `SiteHeader.astro`, `BaseLayout.astro`, `HomeHero.astro`
**Estimated scope:** Medium (3 files)

## 12.2 Hero reconstruction

**Description:** Rebuild the hero's measured full-bleed composition, overlay,
110/100px desktop heading, 60/60px mobile heading, CTA placement, and image crops.
Start with one honest slide unless three approved compositions exist.

**Acceptance criteria:**

- [ ] Hero resolves to approximately 500px mobile, 600px tablet, and the measured full desktop composition without layout shift.
- [ ] The page has one semantic `h1`; its line length, alignment, overlay contrast, and CTA placement reproduce the reference behavior.
- [ ] Any carousel is implemented only with multiple approved slides and includes controls, pause behavior, keyboard access, and reduced-motion handling.

**Verification:**

- [ ] LCP image has explicit dimensions, responsive sizing, high priority, and no lazy loading.
- [ ] Browser screenshots at 375, 768, 1024, and 1440px match the measurement table.
- [ ] Console/network are clean and no unnecessary client JavaScript is emitted.

**Dependencies:** 12.1; approved hero asset decision
**Files likely touched:** `HomeHero.astro`, `index.astro`, approved files under `public/images/` or `src/assets/`
**Estimated scope:** Medium (2–4 files)

## 12.3 Value propositions and about section

**Description:** Map the reference's three-value row and company-vision split to
verified HSE services and beliefs from the existing public content. Use the
overlapping portrait composition only when approved HSE imagery exists.

**Acceptance criteria:**

- [ ] Three value propositions reproduce the measured desktop columns and mobile stack using verified HSE claims.
- [ ] About content uses the reference split, heading rhythm, body measure, and responsive media-before-copy order.
- [ ] Missing portraits produce a documented content gate, not copied Crafto imagery or fabricated people.

**Verification:**

- [ ] Heading hierarchy is `h1` then section `h2` and item `h3` headings.
- [ ] Screenshots verify mobile stacking and desktop split geometry.
- [ ] Focus, contrast, and 200% text resize checks pass.

**Dependencies:** 12.2; approved about-image decision
**Files likely touched:** `HomeExpertise.astro`, `HomeAbout.astro`, approved image assets
**Estimated scope:** Medium (2–4 files)

## Checkpoint A — Above the fold

- [ ] `npm --prefix apps/web run lint`
- [ ] `npm --prefix apps/web run check`
- [ ] `npm --prefix apps/web run test`
- [ ] `npm --prefix apps/web run build`
- [ ] Browser console, network, keyboard, and responsive screenshots are clean.

## 12.4 Services section

**Description:** Add four reference-shaped portrait service cards mapped to
approved HSE offerings such as training, HSEQ systems, consultancy, and on-site
delivery. Cards use project-owned media, bottom overlays, and CSS hover treatment.

**Acceptance criteria:**

- [x] Cards measure approximately 276 × 374px at 1440px, form two columns at tablet/laptop widths, and become one 345px column on mobile.
- [x] Every service title and description is traceable to approved HSE content and every meaningful image has appropriate alternative text.
- [x] Hover/focus effects use only transform/opacity/shadow tokens and respect reduced motion.

**Verification:**

- [x] Responsive grid and image crop measurements match the reference audit.
- [x] Keyboard and focus-visible states expose the same information as hover.
- [x] Below-fold images are optimized, dimensioned, lazy-loaded, and asynchronously decoded.

**Dependencies:** 12.3; four approved service images
**Files likely touched:** new `HomeServices.astro`, `index.astro`, approved image assets
**Estimated scope:** Medium (2–5 files)

## 12.5 Pricing/course section

**Description:** Reconstruct the reference pricing composition using real CMS
courses. The initial one-course state remains truthful and centered; the grid
supports three cards when future courses are published.

**Acceptance criteria:**

- [ ] CMS courses render as 377px desktop offer cards, approximately 290px in a three-column 1024px grid, and 345px mobile cards.
- [ ] One course is rendered once; price, title, description, and route come from the validated CMS contract.
- [ ] Empty and CMS-failure behavior remains explicit and does not expose secrets or create fake offers.

**Verification:**

- [ ] Unit tests cover zero, one, and multiple course layout data paths where logic exists.
- [ ] Local WordPress-backed `/` and `/courses/[slug]` flows render successfully.
- [ ] Pricing/course screenshots match the measured card geometry at all four widths.

**Dependencies:** 12.4; local CMS available
**Files likely touched:** `HomeCourses.astro`, `CourseCard.astro`, `index.astro`, focused tests
**Estimated scope:** Medium (3–5 files)

## 12.6 Client proof and experts/team section

**Description:** Reconstruct the dark proof panel with an approved testimonial,
then add the expert portrait grid only for confirmed HSE people and assets. Do not
implement a video control without an approved video destination.

**Acceptance criteria:**

- [ ] Testimonial text and attribution are owner-approved and presented as a semantic `blockquote`.
- [ ] The proof panel reproduces the reference's centered dark composition without fake video or client-logo controls.
- [ ] Team cards render only confirmed names, roles, portraits, and links; otherwise the omission is documented.

**Verification:**

- [ ] Mobile and desktop screenshots confirm panel crop, text measure, and team-grid collapse.
- [ ] Images and any icon-only controls have correct accessible names/alternative text.
- [ ] No unapproved personal data or external embed is introduced.

**Dependencies:** 12.5; testimonial approval; team content/asset approval
**Files likely touched:** `HomeReference.astro`, optional new `HomeExperts.astro`, `index.astro`, approved assets
**Estimated scope:** Medium (2–5 files)

## Checkpoint B — Commercial proof

- [ ] `npm --prefix apps/web run lint`
- [ ] `npm --prefix apps/web run check`
- [ ] `npm --prefix apps/web run test`
- [ ] `npm --prefix apps/web run build`
- [ ] No reference section contains fabricated HSE claims, people, logos, prices, or outcomes.

## 12.7 Blog/news section

Deliver this section as two sub-increments so the public content contract and its
frontend consumer remain independently reviewable.

### 12.7a Published-post API contract

**Description:** Add a minimal WordPress contract for homepage news cards. Do not
expose drafts, private fields, editor capabilities, or WordPress user data.

**Acceptance criteria:**

- [ ] WordPress exposes only the published post fields required for cards, with a documented and tested response contract.
- [ ] Results are bounded, ordered deliberately, and exclude draft/private posts.
- [ ] The contract uses a stable public identifier/slug and does not leak WordPress user records.

**Verification:**

- [ ] WordPress endpoint tests cover published/draft filtering and field shape.
- [ ] Local REST response and WordPress quality checks pass.

**Dependencies:** 12.6; decision on legacy versus new posts
**Files likely touched:** WordPress plugin endpoint/model, focused PHP tests, API documentation
**Estimated scope:** Medium (3–5 files)

### 12.7b Astro blog/news section

**Description:** Consume the published-post contract, validate it at the CMS
boundary, and render the measured news grid without hardcoded production posts.

**Acceptance criteria:**

- [ ] Astro validates, limits, and renders up to three posts with a meaningful empty state.
- [ ] Malformed or unavailable CMS responses follow the project's explicit error behavior.
- [ ] The grid reproduces the reference's 377px desktop cards and single-column mobile behavior with optimized media.

**Verification:**

- [ ] Astro client tests cover valid, malformed, unavailable, and empty responses.
- [ ] Local CMS, browser network, static build, and responsive screenshots pass.

**Dependencies:** 12.7a
**Files likely touched:** Astro CMS client/types/tests, new `HomeBlog.astro`, `index.astro`
**Estimated scope:** Medium (4–5 files)

## 12.8 CTA and footer

**Description:** Reconstruct the photographic CTA strip and reference footer
rhythm using real HSE contact details. Newsletter UI is excluded unless a real
server-side subscription requirement is separately approved.

**Acceptance criteria:**

- [ ] CTA uses an approved image, dark overlay, measured desktop horizontal layout, and centered mobile stack.
- [ ] Contact links are semantic and keyboard accessible; phone/address/email match approved business data.
- [ ] Footer matches the reference's light treatment, column rhythm, dividers, and responsive stack without a nonfunctional form.

**Verification:**

- [ ] Contact links work and expose descriptive accessible names.
- [ ] CTA and footer screenshots match mobile/desktop composition and have zero overflow.
- [ ] No client-side form behavior or secret is introduced.

**Dependencies:** 12.7; approved CTA asset
**Files likely touched:** `HomeContact.astro`, `SiteFooter.astro`, approved CTA asset
**Estimated scope:** Medium (2–3 files)

## 12.9 Responsive visual QA and final review

**Description:** Compare the complete homepage against the Task 11 reference at
375, 768, 1024, and 1440px, correct measurable differences, and apply the full
quality and five-axis code-review gates.

**Acceptance criteria:**

- [ ] Header, hero, section spacing, grids, card widths, typography, image crops, CTA, and footer meet the plan's stated tolerances wherever content maps directly.
- [ ] Page has zero horizontal overflow, console errors/warnings, failed required requests, accessibility-tree errors, or keyboard traps.
- [ ] Missing content-dependent sections and intentional HSE adaptations are recorded with owner decisions.

**Verification:**

- [ ] Browser screenshots at 375, 768, 1024, and 1440px plus open/closed mobile navigation states.
- [ ] Keyboard, focus-visible, accessibility-tree, reduced-motion, 200% text resize, and contrast checks.
- [ ] Record Lighthouse/Core Web Vitals and production CSS/JavaScript/image payload baselines.
- [ ] `git diff --check`
- [ ] `npm --prefix apps/web run lint`
- [ ] `npm --prefix apps/web run check`
- [ ] `npm --prefix apps/web run test`
- [ ] `npm --prefix apps/web run build`
- [ ] Five-axis code review: correctness, readability, architecture, security, and performance.

**Dependencies:** 12.1–12.8
**Files likely touched:** affected homepage components, `docs/design/`, focused tests
**Estimated scope:** Medium (QA-driven corrections and documentation)

## Checkpoint C — Definition of Done

- [ ] Approved section sequence matches the reference.
- [ ] Real HSE content and project-owned/approved assets only.
- [ ] No Crafto/Elementor code, vendor class leakage, new UI framework, or unjustified dependency.
- [ ] No dead code, debug output, suppression, skipped test, stub, or secret in the diff.
- [ ] All tests/checks/build and runtime verification pass.
- [ ] Human review completed before commit or deployment.
