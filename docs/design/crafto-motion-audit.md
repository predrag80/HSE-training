# Crafto Consulting motion audit

Status: Classification only; complex motion is outside Task 11
Reference: <https://crafto.themezaa.com/consulting/>
Audited: 2026-09-01

## Policy

Motion should be reconstructed from visible behavior, not copied scripts. Use CSS
first, then small project-owned JavaScript using native browser APIs. A third-party
animation dependency requires a separate approved task. Every non-essential effect
must be disabled or reduced under `prefers-reduced-motion: reduce`.

## Observed behavior and implementation classification

| Behavior | Visible result | Classification | Task 11 action |
|---|---|---|---|
| Navigation and button hover | Color/opacity changes over about 300ms; compact controls retain their dimensions | CSS sufficient | Establish transition tokens and accessible focus states |
| Service/news media hover | Image or overlay shifts/scales while text remains legible | CSS sufficient | Define transform/opacity language; apply only where semantically linked |
| Pricing/card hover | Shadow and emphasis change without relayout | CSS sufficient | Keep transforms paint-only and restrained |
| Entry reveal | Sections initially use opacity/translation and settle as they enter the viewport; observed transitions range from roughly 400–700ms, with some longer decorative movement | Small vanilla JS required | Document only; later use `IntersectionObserver` if approved |
| Hero carousel | Three full-bleed slides move horizontally with pagination and timed/manual changes | Small vanilla JS required | Defer; carousel is homepage functionality, not a design-system primitive |
| Pricing accordion | Plus/minus rows expand with an approximately 400ms max-height transition | Native disclosure plus small CSS/JS enhancement | Defer until real HSE content maps to the interaction |
| Client video control | Circular play control launches a video experience | Small vanilla JS or native dialog required | Defer until an approved video and destination exist |
| Team hover details | Additional social/action affordances appear over or near portrait media | CSS sufficient when links exist | Defer until real team content and links are approved |
| Decorative dots/lines | Small accent marks add low-amplitude movement or reveal | CSS sufficient | Optional; do not compromise reduced-motion or performance |
| Numeric rating/counters | Rating and count are visually emphasized; animation is not required to understand them | CSS sufficient; JS optional | Render static values unless a later task proves counter motion is valuable |

## Computed transition inventory

The settled reference contains many 300ms `ease`/`ease-in-out` transitions for
controls and page-builder wrappers, plus smaller groups around 400–700ms using
cubic-bezier curves. One observed transform transition uses approximately 650ms.
The reconstruction should not reproduce blanket `transition: all` rules. Tokens
should target only color, background, border, box-shadow, opacity, and transform.

## Design-system proof boundary

Task 11 may implement button, link, card, and focus transitions in CSS. It must not
implement the hero carousel, scroll-reveal controller, video modal, counter engine,
or an animation library. Those behaviors belong to later vertical slices with real
content, keyboard behavior, reduced-motion fallbacks, and browser tests.
