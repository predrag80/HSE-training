# HSE Training design direction

Status: Approved visual target; HSE content and brand details remain subject to owner approval

## Intent

The [Crafto Consulting demo](https://crafto.themezaa.com/consulting/) is the
approved primary visual target. Corresponding frontend sections should reproduce
its visible layout, proportions, spacing, typography hierarchy, composition,
responsive behavior, and relevant interactions with high fidelity while using
HSE Training content and brand expression.

The implementation remains independent. Crafto's theme, Elementor DOM, class
names, CSS, scripts, templates, shortcodes, and unapproved assets are not project
dependencies. Visual equivalence does not require DOM equivalence: semantic Astro
components and project CSS should produce the equivalent rendered result with a
simpler structure.

## Foundations

- Color: adapt the reference palette to the approved HSE Training identity without
  changing the intended contrast, hierarchy, or balance of corresponding sections.
- Type: reproduce the reference hierarchy, scale, line length, weight, and heading
  treatments as closely as practical using an approved web-font or performant
  fallback strategy.
- Shape: match the reference's card, button, border, radius, and image treatments
  for corresponding sections.
- Imagery: use project-owned or appropriately licensed HSE training, workplace, and
  safety imagery positioned and cropped to match the approved reference.
- Motion: identify relevant visible reference interactions and reimplement them
  independently with CSS, native browser APIs, or minimal JavaScript. Respect
  `prefers-reduced-motion`; do not copy vendor animation scripts.

## Layout and responsive behavior

Container widths, gutters, section rhythm, breakpoints, stacking, column collapse,
font resizing, image cropping, navigation behavior, and card grids should be
derived from the visible Crafto reference rather than retained from an earlier
generic direction. Fidelity must be checked at representative mobile, tablet,
laptop, and desktop widths, including at least 375px, 768px, 1024px, and 1440px.
Equivalent behavior should use clean project CSS rather than reference-site hacks.

## Component policy

Analyze the visible reference, identify reusable UI structures, and implement them
as focused Astro components with semantic HTML and project-specific class names.
`Container`, `Section`, and `Button` remain valid primitives where they can express
the reference faithfully; they should be revised or supplemented when visual
evidence requires it. Avoid page-builder wrapper markup and do not mirror
Crafto/Elementor's component or class architecture.

Crafto supplies the approved structure and design; HSE Training supplies the
content. Map services, courses, instructors, team members, prices, testimonials,
and clients only when real approved HSE content exists. Record non-mappable
sections for owner review instead of inventing content.

## Implemented foundation

The first reconstructed foundation is based on the measurements in
[`crafto-reference-audit.md`](./crafto-reference-audit.md). It establishes:

- self-hosted Kumbh Sans variable font files from the official Google Fonts
  repository under the SIL Open Font License;
- a 1220px main container, 760px reading container, 30px grid gap, responsive
  gutters, and section spacing that resolves to 50px on mobile and 110px on
  desktop;
- the reference's 55px desktop section-heading scale and 41px mobile scale, dark
  `#292d36` ink, `#f7f7f7` muted surfaces, 4–6px radii, restrained shadows, and
  300–400ms property-specific transitions;
- reusable `Container`, `Section`, `Button`, `Eyebrow`, and `SectionHeading`
  Astro primitives with no client-side framework or hydration;
- mobile navigation below 1024px, a hidden utility bar below 768px, and desktop
  shell dimensions derived from the reference; and
- `/courses` and `/courses/[slug]` as the initial proof routes. These routes use
  the reconstructed foundation without inventing homepage content.

The reference orange (`#dd6531`) remains available for large text and decorative
details. Normal-size text and primary controls use the darker `#b84c20` variant
because the reference color does not meet WCAG AA contrast for those uses. Body
copy uses `#6b6e7b` rather than the reference's lighter gray for the same reason.
The motion classification and deferrals are recorded in
[`crafto-motion-audit.md`](./crafto-motion-audit.md).
