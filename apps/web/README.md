# HSE Training web application

Static-first Astro frontend for HSE Training. WordPress integration and course
pages are intentionally deferred to later tasks.

## Commands

Run commands from this directory:

```sh
npm install
npm run dev
npm run check
npm run lint
npm run test
npm run build
npm run preview
```

The development server is available at `http://localhost:4321`.

## Environment

Copy `.env.example` to `.env` for local development. The configured CMS URL is
server-only application configuration; no CMS request is made yet.

Public pages remain static/prerendered. A server adapter will be selected only
when a later contact or payment endpoint requires request-time execution.
