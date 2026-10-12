# Site Search

Status: implemented in the Astro header search dialog

## Flow

1. Each static build writes one search index per locale:
   `/search-index.json` (English) and `/sr/search-index.json` (Serbian). The
   endpoints in `apps/web/src/pages/` call `loadSearchEntries()` from
   `apps/web/src/lib/search/search-index.ts`.
2. The index contains the published content the public site already shows:
   - the three NEBOSH courses and three trainings listed on `/courses/`;
   - the landing pages (courses, NEBOSH, training, company, browse HSE talent,
     resources, gallery, contact) and the four legal pages;
   - Services, linked to the services section of `/browse-hse-talent/`;
   - Free Resources, linked to their external URL or download.

   Every entry carries a type, title, summary, URL, undisplayed keywords, and an
   `external` flag. Keywords come from course descriptions, service details,
   and resource topics, converted to bounded plain text. The CMS is read through
   the existing validated client, so a missing translation or invalid response
   fails the build instead of publishing a partial index.
3. Opening the header search dialog downloads the current locale's index once.
   Typing at least two characters shows up to eight matches below the field.
   The matching code lives in `apps/web/src/lib/search/search-match.ts`.
4. Matching is case- and diacritic-insensitive, and `đ` also matches `dj`. A
   result must contain every query word. Title matches rank above summary and
   keyword matches; ties keep index order (courses, trainings, pages, services,
   resources).
5. Enter opens the first result. Arrow keys move between the field and the
   results. External resources open in a new tab, as on the Resources page.

## Client-side JavaScript justification

The site is static and has no search server. The dialog script is the
documented interaction that downloads the static index (about 12–14 KB) and
filters it in the browser. Results are built with DOM APIs and `textContent`,
never HTML, and the downloaded index is validated before use.

## Freshness

The index is part of every production build, including CMS-triggered content
deploys. Production serves `.json` files with `Cache-Control: no-cache`, so
visitors receive the index from the latest deploy.
