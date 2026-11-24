# Site Assistant

Status: implemented as a floating assistant on every public page (first slice;
content lives in the repository)

## Purpose and boundaries

The assistant helps visitors find the right page on this website. It is a
rule-based guide, not a conversation partner:

- no AI, language model, or external chat service;
- no server endpoint: answers come from static files generated at build time;
- it links only to published pages of this website and to the company's own
  phone number and e-mail address (`apps/web/src/lib/contact.ts`), never to
  another website;
- nothing the visitor types leaves the browser. Once the visitor opens or uses
  the assistant, the conversation is kept only in `sessionStorage` of the
  current tab, so it continues across pages and is deleted when the tab closes.
  No cookies or `localStorage` are used.

## Flow

1. Each static build writes one content file per locale: `/assistant.json`
   (English) and `/sr/assistant.json` (Serbian). The endpoints in
   `apps/web/src/pages/` call `loadAssistantContent()` from
   `apps/web/src/lib/assistant/assistant-content.ts`.
2. The content is a list of topics. Each topic has a button label, answer
   paragraphs, links, follow-up topics, and keyword stems. Answers reuse the
   site's existing approved copy (the IGC course questions, the Training
   overview, the Browse HSE Talent introduction, the company description, and
   the footer enquiry text). `apps/web/src/i18n/assistant-ui.ts` holds only the
   assistant's own navigation and conversation wording.
3. `BaseLayout` renders the launcher button. Opening the panel downloads the
   current locale's content once and shows the greeting with six topic buttons:
   NEBOSH courses, Training, Buying a course and access, Consulting and HSE
   talent, About HSE Training, and Contact. Each answer offers its follow-ups
   and a "Start over" button.
4. A typed question is answered in the browser
   (`apps/web/src/lib/assistant/assistant-match.ts`):
   - the text is limited to 160 characters, case- and diacritic-folded (`đ`
     also matches `dj`), and question words, fillers, and words that describe
     the whole site (HSE, DOO, Belgrade, Serbia) are dropped;
   - the topic whose keyword stems match the most remaining words wins; a typed
     word matches a stem when it starts with it, so Serbian inflections match
     (`platim` → `plat`, `obuke` → `obuk`); ties keep topic order, which lists
     specific questions before broad topics;
   - the first typed question also downloads the header search index. Up to
     three same-site pages containing a query word are suggested. Beside a
     matched topic, a page must match in its title or summary;
   - without a topic or page match, the fallback answer offers the contact
     form, phone, and e-mail.

## Opening by itself

The assistant offers itself once per tab:

- 5 seconds after a page is shown, the panel opens without taking focus and
  greets the visitor;
- it waits while the cookie consent is shown and opens 5 seconds after the
  choice is made;
- it does not open while the tab is hidden, while another dialog is open, or
  while the visitor is typing in a form field; the next page tries again;
- it does not open when the visitor already opened or used the assistant in
  this tab, or when `sessionStorage` is unavailable, because the once-per-tab
  rule could not be kept;
- the `hse-site-assistant:auto-opened` key records that it opened.

A panel that opened by itself closes again after 15 seconds without use,
without moving focus. Holding the pointer over the panel pauses the countdown,
and any use (click, tap, scroll, key, or focus inside) cancels it for good. A
panel the visitor opened is never closed automatically, so there is no time
limit on reading or answering.

Until the visitor uses it, an automatically opened panel stores no
conversation; an ignored greeting leaves only the auto-opened flag.

## Conversation across pages

Following a link loads a new static page, so the conversation is stored as data
(messages made of text and link blocks, the last topic, and whether the panel
was open) under one `sessionStorage` key per locale
(`hse-site-assistant:en`, `hse-site-assistant:sr`). Switching language starts
a conversation in that language. Only the 40 most recent messages are kept.

On the next page the stored conversation is validated by
`parseAssistantHistory()` in `apps/web/src/lib/assistant/assistant-history.ts`
before it is drawn, so stored links pass the same same-site rule as the
downloaded content. When the panel was open and the screen is at least 36rem
wide, it reopens with the history and the last topic's choices, without moving
focus away from the page. On narrower screens, where the panel covers most of
the page, it stays closed until the visitor opens it. If storage is disabled or
full, the assistant still works on each page but starts again after
navigation.

## Privacy policy

The Privacy Policy cookie section describes the assistant in both languages:
it is automated and not AI, typed questions stay in the browser, the
conversation is kept in tab-scoped session storage (the keys are named), and
visitors are asked not to enter personal data. The `hse-headless` legal
migration version 7 adds this paragraph after the necessary-storage paragraph
and updates the policy date, keeping the previous text as a backup option. It
runs when the plugin release reaches each CMS.

## Keeping links on this website

Three independent checks enforce the boundary:

1. `assertAssistantContent()` fails the build when a link is not a same-site
   path, when a same-site path is not a published route
   (`hasLocalizedContentPath`), or when a phone or e-mail link is not the
   company's own. Protocol-relative (`//host`) and backslash (`/\host`) forms
   are rejected because browsers treat them as another host.
2. The browser validates the downloaded content and the stored conversation
   and drops any link that fails the same rule.
3. Page suggestions come only from search-index entries with
   `external: false` and a same-site URL, so the Free Resources links never
   appear.

Answers, links, and labels are inserted with DOM APIs and `textContent`, never
as HTML.

## Client-side JavaScript justification

The assistant is the documented interaction that opens the panel, downloads the
static content, matches typed questions, and renders answers. Measured on the
2026-11-24 build:

- the script loads on every page: 10.1 KB (4.1 KB gzip), sharing the
  `search-match` chunk with the header search;
- the content file loads only when the panel opens: about 6.9 KB (2.3 KB gzip)
  per locale;
- the search index (about 12–14 KB) loads only on the first typed question.

## Accessibility

- The launcher is a button with `aria-expanded` and `aria-controls`; its label
  switches between open and close.
- The panel is a non-modal `role="dialog"` labelled by its title. Opening it
  with the launcher moves focus to the panel; opening by itself or on arrival
  does not move focus. Escape closes it from anywhere inside and returns focus
  to the launcher. After a topic button is used, focus moves to the first
  new suggestion because the used button is replaced.
- Messages are in a `role="log"` live region, and each message starts with a
  visually hidden speaker name.
- On screens narrower than 36rem the open panel is a compact card above the
  launcher, at most 32rem or 68% of the viewport high, so the top of the page
  stays visible. The text field uses 16px text so iOS does not zoom.
- Only an ignored, automatically opened panel closes by itself (see "Opening by
  itself"); conversations the visitor started have no time limit. Motion
  is removed for `prefers-reduced-motion`, and the assistant is hidden in print.
- The assistant sits above page content and the header but below the cookie
  consent, which stays on top until the visitor makes a choice.

## Design review

The approved Crafto Consulting reference has no chat or assistant component, so
there is no reference section to reproduce. The panel uses the project's design
tokens and the cookie-consent card treatment (white card, orange top border).
The launcher and panel header show an inline SVG mark tied to the site's
subject: an outlined speech bubble with a filled safety shield, white on the
orange badge, so the button reads as a conversation about safety. The owner
chose it from nine drawn options. The mark tilts slightly on hover or keyboard
focus unless reduced motion is requested.
This is recorded for owner review. The layout was checked in a browser at
390 × 844 (phone, compact card), 820 × 1180 (tablet), and 1440 × 900
(desktop).

## Changing the content

- Wording: `apps/web/src/i18n/assistant-ui.ts` (both locales).
- Topics, links, follow-ups, and keyword stems:
  `apps/web/src/lib/assistant/assistant-content.ts`. Stems are written without
  diacritics and must be single words of three or more letters.
- `apps/web/src/lib/assistant/*.test.ts` checks that both locales offer the same
  topics, every link stays on the website, representative questions in both
  languages reach the intended topic, and a stored conversation cannot bring
  back an off-site link.

## Freshness

The content files are part of every production build. Production serves
`.json` files with `Cache-Control: no-cache`, as for the search index.

## Not in this slice

- Editing topics in WordPress. Moving the content to the `hse-headless` plugin
  adds a content type that must trigger the CMS deploy defined by ADR-014, so it
  needs its own ADR.
- Recording questions the assistant could not answer. That needs a server
  endpoint and a retention rule (see the privacy rules in `CONSTRAINTS.md`).
