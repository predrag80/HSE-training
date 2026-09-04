# Implementation Plan: Task 12 Homepage 1:1 Crafto Reconstruction

Status: Proposed — requires owner review before implementation
Prepared: 2026-09-01

## Overview

Reconstruct the HSE Training homepage from the measured Crafto Consulting
reference in nine independently verifiable slices. The result will preserve the
reference's section order, proportions, typography, responsive behavior, and
relevant interactions while using semantic Astro components, HSE-owned content,
and approved assets. Crafto/Elementor code and unlicensed assets remain out of
scope.

Detailed acceptance criteria and verification commands are tracked in
[`todo.md`](./todo.md).

## Current baseline

- Task 11 established the measured tokens and shared `Container`, `Section`,
  `Button`, `Eyebrow`, and `SectionHeading` primitives.
- The homepage already has provisional hero, expertise, course, about,
  testimonial, and contact components.
- One 1730 × 909 project-owned hero image is available.
- One real course is available through the local WordPress CMS.
- The legacy public site supplies approved company, services, testimonial, and
  contact copy, but the repository does not yet contain approved team portraits,
  four service images, three blog images, client logos, or a dedicated CTA image.

## Architecture decisions

- Keep the homepage static-first. Astro components render the section HTML at
  build time; client JavaScript is allowed only for a proven interaction.
- Keep one focused Astro component per homepage section. Do not assemble the
  entire page and its styles in `index.astro`.
- Reuse the Task 11 tokens and primitives. Extend them only when a measured
  reference behavior is shared by at least two sections.
- Use the single existing CMS course honestly. Do not duplicate it to imitate a
  three-card pricing grid; the layout must expand naturally when more courses
  exist.
- Do not render fake carousel controls for a one-slide hero, fake staff members,
  fake client logos, or invented articles. Content-dependent sections remain
  conditional until their content and assets are approved.
- Do not add React, an animation library, a page builder, or another dependency.
  CSS handles hover and media effects; any later carousel uses small native
  JavaScript with pause, keyboard, and reduced-motion behavior.
- Blog content belongs to WordPress. A homepage blog grid requires a separate,
  explicit published-post API contract inside slice 12.7 rather than hardcoded
  production articles.

## Dependency order

```text
Task 11 audit and design tokens
          |
          +--> 12.1 Header
          |       |
          |       +--> 12.2 Hero
          |
          +--> 12.3 Values/about
          +--> 12.4 Services
          +--> 12.5 CMS course/pricing
          +--> 12.6 Proof/experts
          +--> 12.7 WordPress posts/blog
          +--> 12.8 CTA/footer
                          |
                          +--> 12.9 Full responsive QA
```

Content-dependent slices may be developed after their required assets are
approved, but the final page order and 12.9 regression pass are sequential.

## Task list

### Phase 1 — Above the fold

- [ ] 12.1 Header/navigation reconstruction
- [ ] 12.2 Hero reconstruction
- [ ] 12.3 Value propositions and about section

### Checkpoint A

- [ ] Header and first three sections match measured mobile and desktop geometry.
- [ ] Lint, Astro check, tests, and build pass.
- [ ] Browser console and network panels are clean.

### Phase 2 — Commercial proof

- [x] 12.4 Services section
- [ ] 12.5 Pricing/course section
- [ ] 12.6 Client proof and experts/team section

### Checkpoint B

- [ ] Real HSE content is used; unavailable content is omitted rather than invented.
- [ ] Cards collapse at the measured breakpoints without overflow.
- [ ] Lint, Astro check, tests, and build pass.

### Phase 3 — Publishing and conversion

- [ ] 12.7 Blog/news section
- [ ] 12.8 CTA and footer
- [ ] 12.9 Responsive visual QA and final review

### Checkpoint C — Complete

- [ ] All approved sections pass the 375, 768, 1024, and 1440px comparison matrix.
- [ ] Accessibility tree, keyboard flow, reduced motion, network, and console are clean.
- [ ] Full quality suite and CMS-backed static build pass.
- [ ] Any content-blocked reference section is documented for owner review.

## Fidelity targets

Where the reference and HSE content map directly, the rendered implementation
should meet these tolerances after fonts and images settle:

| Measurement | Target tolerance |
|---|---:|
| Header row heights | ±2px |
| Main container/card widths | ±3px |
| Section padding | ±4px |
| Heading size/line height | ±1px |
| Grid gaps and card spacing | ±3px |
| Horizontal page overflow | 0px |

Screenshot comparison must evaluate composition, cropping, hierarchy, and
responsive stacking. Pixel-perfect image comparison is not a useful gate when
HSE imagery and copy intentionally differ from Crafto.

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Missing approved imagery | High visual fidelity cannot be completed | Approve supplied images, migrate owner-controlled legacy assets, or commission/generate new HSE assets before the affected slice |
| Only one hero image | A three-slide carousel would be fake | Build the measured single-slide composition first; add carousel behavior only after two more approved compositions exist |
| Only one CMS course | Reference has three pricing cards | Center one truthful card and retain the responsive multi-course grid |
| Team identities/portraits are not confirmed | Publishing could invent or misrepresent people | Keep the team portion conditional until names, roles, portraits, and permissions are approved |
| Blog has no local published-post contract | Hardcoded posts would violate CMS ownership | Define and test the minimal published-post API in 12.7 before rendering the grid |
| Legacy copy contains errors/outdated standards | Repeating it could damage credibility | Treat legacy content as a source for owner review, not automatically approved final copy |
| Visual effects increase payload or motion | Performance/accessibility regression | Use optimized local images, CSS transforms/opacity, no new framework, and reduced-motion fallbacks |

## Open questions requiring owner decisions

1. Should the missing hero, about, services, team, blog, and CTA images be
   supplied, migrated from owner-controlled legacy media after a rights review,
   or generated as a separate approved asset task?
2. Should the first release use one static hero composition, or wait for three
   approved compositions before implementing the carousel?
3. Which people may be shown in the experts/team section, with what approved
   names, roles, qualifications, portraits, and links?
4. Should the two legacy news items be migrated into the local WordPress CMS, or
   should the blog launch with newly approved articles?
5. Is the existing public-site testimonial copy approved verbatim after spelling
   and attribution review?

These decisions block final content fidelity for the affected slices, but not
the header, measured layout foundations, truthful single-course section, or
responsive QA setup.
