# CMS Editability Map

Status: reflects `hse-headless` 0.15.0 and the Astro application on 2026-09-14

WordPress owns reviewed editorial content. Astro owns presentation, public
routes, navigation behavior, and interface copy unless a field is explicitly
listed below.

## Editable in WordPress

| Area | Editable content |
|---|---|
| Homepage hero | Up to five localized slides: title fragments, primary CTA, supporting link, image, order, and stable slide key. |
| Shared company profile | About eyebrow, headline, description, two images, CTA labels and URLs, signature, three value statements, and five team-member names, roles, and photos. This content is shared by Homepage and Company Page. |
| Company Page | Localized hero title/eyebrow and intro CTA in addition to the shared company profile. |
| Courses | Localized course title, slug, rich description, short description, display-only price, featured image, order, stable `course_key`, Homepage card label/CTA, and Homepage visibility. |
| Courses landing | Localized SEO fields, hero eyebrow/title/introduction, and empty-state copy. |
| NEBOSH overview | Localized SEO and hero copy, four benefit items, intro copy, course-selection labels, CTA labels, and testimonial. |
| Consulting services | Localized service title, labels, short and detailed descriptions, CTA, image, order, stable key, and Homepage visibility. |
| References | Localized testimonial, author, role/organisation, order, stable key, Homepage visibility, and Homepage accent selection. |
| Legal pages | Privacy Policy, Terms and Conditions, and Copyright in English and Serbian: SEO fields, hero copy, last-updated text, and up to seven rich-text sections. |

## Partly editable in WordPress

| Public area | CMS-controlled | Still controlled by Astro code |
|---|---|---|
| Homepage | Slides, shared company profile/team, promoted services, promoted courses, and selected references. | Section headings around those collections, generic CTA copy, client-logo presentation, layout, and animation. |
| Company Page | Hero, company profile, value statements, team, and intro CTA. | Timeline copy, client list/logos, supporting section labels, layout, and animation. |
| Courses Page | Page hero and NEBOSH course cards. | Training-category headings/cards and shared pricing helper text. |
| NEBOSH and course details | Course title, description, price, image, promotion fields, and NEBOSH overview copy. | Qualification-specific facts, outcomes, assessment/FAQ labels, decorative imagery, and layout. |
| Browse HSE Talent / Consulting | Service collection and service CTAs. | Page-specific narrative sections, industry lists, HSE Directory explanatory copy, and layout. |
| Custom Training Design | Course title, short/rich description, price, and image when its Course record exists. | Remaining editorial sections, video presentation copy, and layout. |

## Not currently editable in WordPress

- Header and footer navigation labels, menu hierarchy, route definitions, social
  links, and language-switching behavior.
- Contact Page headings, contact details, map configuration, and form labels.
- Gallery headings and gallery image collection.
- Training overview content and the Banksman / Slinger and Train the Trainer
  detail-page copy.
- Most shared interface labels such as buttons, accessibility text, empty-state
  helper labels, and scroll prompts.
- Public URLs, page composition, section order, CSS, responsive behavior,
  typography, imagery that is part of the design rather than a CMS record,
  animations, and browser interactions.
- Build, deployment, payment, customer, and LMS state. These are deliberately
  outside WordPress under the project architecture.

## Publishing behavior

English and Serbian records are isolated. Astro validates every required CMS
response during its static build. Missing or incomplete required content stops
the build instead of silently mixing languages or publishing placeholders.
