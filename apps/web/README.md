# HSE Training web application

Static-first Astro frontend for HSE Training. WordPress remains the editorial
CMS and the approved WooCommerce commerce runtime.

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

Copy `.env.example` to `.env` for local development. `WORDPRESS_API_URL` selects
the CMS origin and `HSE_CHECKOUT_SOURCE` identifies the frontend that started
checkout.

`PUBLIC_SENTRY_DSN` enables the client SDK only when
`PUBLIC_SENTRY_ENVIRONMENT=production` and the browser is on `/contact/` or
`/sr/contact/`. Dev and staging builds do not receive a DSN. The DSN is a public
ingestion key; no Sentry auth token is exposed to browser code. The SDK does not
enable Session Replay or collect user identity, cookies, headers, bodies, query
parameters or form values. Production contact-page traces are sampled at 100%;
other public pages send no Sentry telemetry.

Public pages remain static/prerendered. Contact delivery and all checkout,
payment, refund and fiscalization operations stay on the CMS runtime.
