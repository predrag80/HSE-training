# Crafto Consulting visual reference audit

Status: Measured baseline for Task 11
Reference: <https://crafto.themezaa.com/consulting/>
Audited: 2026-09-01

## Purpose and method

Crafto Consulting is the approved high-fidelity visual target. This audit records
the rendered design and behavior needed to reconstruct its visual system with
project-owned Astro components and CSS. It is not a source-code or DOM blueprint.

Measurements were taken in an isolated Chrome session from rendered bounding
boxes and computed styles at 375px, 768px, 1024px, and 1440px viewports. The page
was scrolled before measurement so lazy media and entry effects reached their
settled state. Values can vary by a few pixels because of scrollbars, font
rasterization, carousel state, and responsive interpolation.

No Crafto images, scripts, stylesheets, templates, class names, or Elementor
markup were copied into the project.

## Visual foundations

| Property | Observed reference value | Reconstruction guidance |
|---|---:|---|
| Primary typeface | Kumbh Sans | Use a licensed project-owned webfont with sans-serif fallback |
| Body type | 17px / 30px, weight 400 | Preserve the open, low-density reading rhythm |
| Primary text | `#292d36` | Headings, dark buttons, high-emphasis text |
| Secondary text | `#808291` | Body copy and secondary metadata |
| Accent | `#dd6531` | Eyebrows, compact highlights, primary calls to action |
| Muted surface | `#f7f7f7` | Alternating content bands |
| Light border | approximately `#e4e4e4` | Rules and low-emphasis card borders |
| Main outer row | approximately 1220px | Includes 15px column gutters on each side |
| Main content width | approximately 1190px | Four 275–276px columns with 30px gaps |
| Desktop section rhythm | 110px block padding | Use for primary content bands |
| Tablet section rhythm | 75px block padding | Applies near the 768px layout |
| Mobile section rhythm | 50px block padding | Applies around 375px |
| Common card gap | 30px | Pricing, services, team, and news grids |
| Small radius | 4px | Buttons, image cards, media |
| Card radius | 6px | Elevated pricing-style surfaces |
| Elevated shadow | `0 20px 60px rgb(0 0 0 / 8%)` | Reserved for floating white cards |
| Control transition | approximately 300ms ease-in-out | Buttons and compact interactive states |

## Responsive measurement samples

| Viewport | Header/navigation | Hero heading | Section heading | Content/card behavior |
|---:|---|---|---|---|
| 375px | 80px white mobile navigation; utility row hidden | 60px / 60px; hero media 500px high | 41.256px / 41.256px | 15px page gutters; 345px single-column service and pricing cards; 50px section padding |
| 768px | 45px utility row plus 80px compact navigation | 96.25px / 87.5px; hero media 600px high | 48.132px / 48.132px | 24px outer gutters; 720px row; services form a 2-column grid; pricing cards become one centered column; 75px section padding |
| 1024px | Approximately 91px overlaid navigation below the utility row | 110px / 100px | 55.008px / 55.008px | Services use two approximately 451px columns; pricing remains three approximately 290px cards |
| 1440px | 45px utility row plus approximately 95–96px transparent navigation over the hero | 110px / 100px, weight 400, approximately 833px wide | 55.008px / 55.008px, weight 600 | 1220px outer row; four approximately 276px service cards; three 377px pricing cards; 30px gaps; 110px section padding |

The hero is a horizontal carousel. Inactive slide headings remain outside the
viewport, so intentional horizontal slider overflow must not be mistaken for a
page-layout overflow defect.

## Section sequence and behavior

### 1. Utility header and primary navigation

- Desktop: 45px white utility strip above navigation. The main navigation is
  approximately 95px high and overlays the hero image with white links spaced by
  roughly 40px including link padding.
- Tablet: utility information remains, while navigation compacts to an 80px row.
- Mobile: utility information is removed; an 80px white bar presents logo and menu
  trigger with dark text.
- Interaction: links use short color/opacity transitions. The mobile navigation is
  a menu disclosure rather than a permanently visible link row.

### 2. Hero carousel

- Desktop: full-bleed photographic slide approximately 1000px high including the
  overlaid navigation region. Content is centered over a dark image overlay.
- The primary heading is 110px / 100px, regular weight, white, and constrained to
  roughly 833px. A compact orange button sits below it.
- Tablet: media height is approximately 600px and heading scales to about 96px.
- Mobile: media height is 500px and the centered heading becomes 60px / 60px.
- Interaction: three slides, pagination/control affordances, automatic or manual
  slide movement, and subtle text/button entry movement.

### 3. Three value propositions

- Desktop: three equal columns inside the main row, each combining a fine orange
  line icon with a 20px semibold title and short body copy.
- Tablet: three columns remain but tighten within a 720px row.
- Mobile: items stack vertically with generous separation inside a 365px-tall band.
- Background is white; content is left-aligned at all observed widths.

### 4. Company vision / about split

- Desktop: near-even media/content split. Two portrait images overlap inside the
  left half; the right half contains an eyebrow, 55px heading, body copy, two small
  text-style actions, and a rating row.
- Tablet and mobile: images appear before the copy. The copy becomes a full-width
  column with the responsive section-heading size.
- Image treatment uses approximately 4px corners and deliberate overlap rather than
  a generic framed card.

### 5. Consulting services

- Centered eyebrow and section heading precede four portrait image cards.
- Desktop cards measure approximately 276 × 374px; the image ratio is close to
  600:815. Cards have a 4px outer radius and around 41px inset overlay content.
- At 1024px cards become two columns around 451px wide; at 768px they are two
  columns around 324px; mobile uses a single 345 × 469px card.
- Content is overlaid near the lower edge with an orange category pill, a 26px
  white heading, and low-emphasis descriptive copy.
- Interaction: image/overlay movement and link treatment on hover; CSS is sufficient.

### 6. Pricing introduction and offer cards

- The introduction is a two-column row: large left heading/copy/action and a right
  compact accordion. It stacks on mobile.
- Desktop pricing cards form three 377 × 538px white surfaces with 30px gaps,
  59–65px internal padding, 6px radius, and a soft 20px/60px shadow.
- At 1024px the three cards narrow to about 290px. At 768px they become a centered
  single column about 460px wide. At 375px they fill the 345px content width.
- Card title is 26px, price follows the responsive section-heading scale, and the
  dark full-width action is 46px high with 12px uppercase text, 14px/26px padding,
  and 4px radius.
- A compact three-item guarantee row follows the cards and stacks on small screens.

### 7. Client proof / video panel

- A centered dark photographic panel contains a circular play control, uppercase
  eyebrow, large white statement, and a row of client wordmarks.
- Desktop presentation is landscape and centered within the page; mobile uses a
  tall crop with centered text and wrapped logos.
- The play control implies a modal or external video interaction and requires
  explicit behavior before implementation.

### 8. Leading experts

- Centered eyebrow and heading followed by four portrait cards.
- Desktop: four approximately 275px-wide columns. Tablet reduces columns; mobile
  becomes one full-width portrait per row.
- Names and roles sit below images with restrained spacing and no heavy card chrome.
- Team imagery and identities require approved HSE content before homepage mapping.

### 9. Latest news

- Muted `#f7f7f7` section with centered eyebrow and heading.
- Desktop: three approximately 377px-wide image/content cards. The visible image
  area is landscape; the full card keeps copy and metadata below.
- Mobile: one card per row with the same image-first hierarchy.
- Interaction: restrained image hover treatment and linked title/meta states.

### 10. Contact call-to-action strip

- Full-width photographic band with a dark overlay.
- Desktop content is horizontal: large heading at left, circular orange icon, and
  contact prompt/address at right.
- Mobile content stacks and centers while preserving the image background.

### 11. Footer

- Desktop footer is approximately 424px high and uses four information columns,
  followed by a thin legal row.
- Mobile columns wrap/stack, with contact and newsletter fields remaining readable.
- White background, dark headings, gray body copy, fine dividers, and compact links.

## Accessibility differences to correct

The reference does not expose an `h1` in its rendered heading structure; its hero
uses an `h3`. The Astro implementation must keep exactly one page `h1` and preserve
logical `h2`/`h3` order. Reference color, focus, and interaction behavior are visual
inputs, not permission to copy accessibility defects. Native links/buttons,
keyboard-visible focus, decorative-image handling, reduced-motion support, and
44px mobile targets remain mandatory.

## Asset requirements for later homepage work

Near-1:1 homepage reconstruction will require approved HSE-specific assets for:

- three hero-carousel compositions or an explicit decision to use fewer slides;
- overlapping company/about portraits;
- four portrait service images;
- instructor/team portraits and approved names/roles;
- three news/article images and real article content;
- client logos that HSE Training is authorized to display; and
- a contact-strip background image.

Until those are supplied or generated under an approved asset task, layout proofs
must use project-owned imagery and must not download Crafto media.
