# Staging WooCommerce/RaiAccept deployment and test

This runbook deploys the temporary experiment in ADR-010. It does not authorize
production payment processing.

## Inputs

- Astro staging: `https://staging.hsetraining.rs`
- WordPress CMS: `https://cms.hsetraining.rs`
- WooCommerce target: `10.9.4`
- Product SKUs: one hidden derived product per published Course `course_key`
- Product type: simple, virtual, sold individually, hidden from the Woo catalog
- Online sales control: Course-level **Available for online purchase** checkbox
  and positive **Online price**. The current configuration enables only
  `nebosh-igc`; unchecked Courses remain unpriced and non-purchasable
- Currency: RSD. `1000.00` remains the local sandbox amount until an approved
  test/production price is supplied; it is not an approved production price

## Deployment order

1. Put the CMS in a short maintenance window. Export the WordPress database and
   archive the current WooCommerce and `hse-headless` plugin directories. Verify
   that the backup files are non-empty and store them outside the web root.
2. Confirm the active WordPress/PHP versions meet WooCommerce 10.9.4
   requirements. Install the official WooCommerce 10.9.4 package and keep it
   active. Do not run a destructive database downgrade.
3. Deploy `hse-headless` 0.23.1, then define
   `HSE_WOOCOMMERCE_STAGING_BRIDGE=true` and
   `HSE_PUBLIC_SITE_URL=https://staging.hsetraining.rs` in server-only
   `wp-config.php`.
   On first plugin initialization the idempotent e-commerce legal migration
   publishes the reviewed English and Serbian Purchase Terms and Privacy Policy.
   It preserves the previous values in options ending in
   `_pre_ecommerce_20260916`; verify the new copy with legal counsel before
   production use.
4. Set WooCommerce currency to RSD, provision the IGC sandbox amount, and run
   the idempotent Course product synchronization. Products resolve by
   `course_key`/SKU, never by a cross-system WordPress ID.
5. Keep RaiAccept in sandbox mode, one-click payments disabled, and use the Woo
   currency (RSD). Confirm the sandbox credentials in the server admin without
   exporting or logging them.
6. Confirm the gateway notification URL is reachable from the Internet and the
   hosting firewall permits RaiAccept's documented notification source range.
7. Run WordPress checks: core checksums, plugin versions, rewrite list, all
   commerce and legal-page integration checks, and GET requests for the public
   `nebosh-igc` commerce projection plus both languages of Purchase Terms and
   Privacy Policy.
8. Build Astro with `WORDPRESS_API_URL=https://cms.hsetraining.rs`. The build
   captures the Woo price and stable checkout URL. Deploy the generated Astro
   release to `staging.hsetraining.rs` only.
9. Clear server/CDN caches, then verify the Homepage, Courses page, and IGC
   detail page all show the sandbox price and link only the IGC buy CTA to the
   CMS checkout bridge.

Prefer the idempotent product/content provisioning path over a full local
database import. When the owner explicitly requests a full staging refresh,
first export the current staging database and separately record the active
RaiAccept sandbox settings, WordPress users, SMTP settings, and other
environment-owned options. Import only the SQL prepared for
`https://cms.hsetraining.rs`, then restore or re-enter those staging-owned
settings before testing. A local database import must never be allowed to
silently replace working gateway credentials or administrator access.

Representative commands, run from the staging WordPress root after replacing
the backup directory with a private path outside the web root:

```sh
wp db export /private/backup/hse-before-woo-10.9.4.sql
zip -rq /private/backup/woocommerce-before-10.9.4.zip wp-content/plugins/woocommerce
zip -rq /private/backup/hse-headless-before-0.22.0.zip wp-content/plugins/hse-headless
wp plugin install woocommerce --version=10.9.4 --force
wp plugin activate woocommerce hse-headless raiffeisen-payment-gateway
wp config set HSE_WOOCOMMERCE_STAGING_BRIDGE true --raw
wp config set HSE_PUBLIC_SITE_URL https://staging.hsetraining.rs
wp option update woocommerce_currency RSD
wp option update woocommerce_price_num_decimals 2
wp option update woocommerce_email_base_color '#292d36'
wp option update woocommerce_email_background_color '#f7f7f7'
wp option update woocommerce_email_body_background_color '#ffffff'
wp option update woocommerce_email_text_color '#292d36'
HSE_IGC_TEST_PRICE=1000.00 wp eval-file wp-content/plugins/hse-headless/scripts/provision-nebosh-igc-product.php
wp eval-file wp-content/plugins/hse-headless/scripts/sync-course-products.php
wp eval-file wp-content/plugins/hse-headless/tests/legal-pages-integration.php
wp eval-file wp-content/plugins/hse-headless/tests/commerce-rest-integration.php
wp eval-file wp-content/plugins/hse-headless/tests/commerce-presentation-integration.php
wp eval-file wp-content/plugins/hse-headless/tests/commerce-product-sync-integration.php
wp core verify-checksums
wp plugin get woocommerce --fields=name,status,version --format=table
```

Then verify the public projection before starting the Astro build:

```sh
curl -fsS https://cms.hsetraining.rs/wp-json/hse/v1/commerce/products/nebosh-igc
curl -fsS 'https://cms.hsetraining.rs/wp-json/hse/v1/legal-pages/terms?lang=en'
curl -fsS 'https://cms.hsetraining.rs/wp-json/hse/v1/legal-pages/terms?lang=sr'
curl -fsS 'https://cms.hsetraining.rs/wp-json/hse/v1/legal-pages/privacy?lang=en'
curl -fsS 'https://cms.hsetraining.rs/wp-json/hse/v1/legal-pages/privacy?lang=sr'
```

## Payment test matrix

1. Open both English and Serbian staging routes in private browser sessions and
   start checkout from each IGC Buy now CTA. Confirm a one-item checkout, the exact RSD
   amount, matching checkout language, guest checkout, branded responsive
   shell, required legal acknowledgement, and RaiAccept as the selected gateway.
   Confirm the footer and checkout show the official Raiffeisen Bank, Visa,
   Mastercard, Maestro, DinaCard, Visa Secure and Mastercard Identity Check
   assets, and that the bank and 3-D Secure marks open their approved external
   pages.
2. Complete a successful Visa sandbox payment and verify the Woo order reaches
   the configured paid/completed status only after RaiAccept status retrieval.
   Confirm the order-received page reports a pending state before provider
   confirmation and a paid state only after Woo receives that confirmation.
3. Repeat with the documented generic-decline, expired-card, insufficient-funds,
   and incorrect-CVV cards. Confirm no failed attempt becomes paid.
4. Exercise cancel/back navigation and direct loading of the return URL. Confirm
   neither path alone changes payment state.
5. Repeat the webhook/notification for the successful transaction when the
   sandbox permits it. Confirm the existing order is updated without a duplicate
   order or duplicate fulfillment action.
6. Test the configured refund flow and reconcile the Woo order, RaiAccept
   dashboard transaction, amount, currency, and provider transaction ID.
7. Review Woo and gateway logs for correlation data only; redact credentials,
   full payment payloads, and unnecessary customer data before sharing.
8. Confirm that successful, pending/held, failed, cancelled and refunded order
   states trigger the configured Woo customer emails. Each successful delivery
   records only the email identifier and UTC timestamp in private order metadata
   and notes; retain provider status history, the enrolment/access email and
   relevant server logs for at least 120 days as transaction-delivery evidence.

## Rollback

1. Disable `HSE_WOOCOMMERCE_STAGING_BRIDGE` immediately; Astro then falls back
   to the existing course/contact destination on its next build.
2. Restore the preceding `hse-headless` plugin and Astro release.
3. Restore the database and WooCommerce 11.1.0 plugin together from the matched
   pre-downgrade backups if a full rollback is needed. Do not restore only the
   plugin code across an incompatible database state.
4. Re-run CMS and public smoke checks before ending the maintenance window.
