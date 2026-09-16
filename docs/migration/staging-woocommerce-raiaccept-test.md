# Staging WooCommerce/RaiAccept deployment and test

This runbook deploys the temporary experiment in ADR-010. It does not authorize
production payment processing.

## Inputs

- Astro staging: `https://staging.hsetraining.rs`
- WordPress CMS: `https://cms.hsetraining.rs`
- WooCommerce target: `10.9.4`
- Product key/SKU: `nebosh-igc`
- Product type: simple, virtual, sold individually, hidden from the Woo catalog
- Price: explicitly chosen sandbox amount in RSD; `1000.00` is the local test
  value and is not a production price

## Deployment order

1. Put the CMS in a short maintenance window. Export the WordPress database and
   archive the current WooCommerce and `hse-headless` plugin directories. Verify
   that the backup files are non-empty and store them outside the web root.
2. Confirm the active WordPress/PHP versions meet WooCommerce 10.9.4
   requirements. Install the official WooCommerce 10.9.4 package and keep it
   active. Do not run a destructive database downgrade.
3. Deploy `hse-headless` 0.19.0, then define
   `HSE_WOOCOMMERCE_STAGING_BRIDGE=true` in server-only `wp-config.php`.
4. Run the product provisioning script with the approved sandbox amount. It is
   idempotent and resolves the product by SKU, not by WordPress ID.
5. Keep RaiAccept in sandbox mode, one-click payments disabled, and use the Woo
   currency (RSD). Confirm the sandbox credentials in the server admin without
   exporting or logging them.
6. Confirm the gateway notification URL is reachable from the Internet and the
   hosting firewall permits RaiAccept's documented notification source range.
7. Run WordPress checks: core checksums, plugin versions, rewrite list, commerce
   REST integration, and a GET of the public `nebosh-igc` commerce projection.
8. Build Astro with `WORDPRESS_API_URL=https://cms.hsetraining.rs`. The build
   captures the Woo price and stable checkout URL. Deploy the generated Astro
   release to `staging.hsetraining.rs` only.
9. Clear server/CDN caches, then verify the Homepage, Courses page, and IGC
   detail page all show the sandbox price and link only the IGC buy CTA to the
   CMS checkout bridge.

Do not import the local WordPress database into this environment: it would
overwrite the already configured RaiAccept sandbox credentials, users, SMTP
settings, and current CMS content. Provision the product with the idempotent
script instead.

Representative commands, run from the staging WordPress root after replacing
the backup directory with a private path outside the web root:

```sh
wp db export /private/backup/hse-before-woo-10.9.4.sql
zip -rq /private/backup/woocommerce-before-10.9.4.zip wp-content/plugins/woocommerce
zip -rq /private/backup/hse-headless-before-0.19.0.zip wp-content/plugins/hse-headless
wp plugin install woocommerce --version=10.9.4 --force
wp plugin activate woocommerce hse-headless raiffeisen-payment-gateway
wp config set HSE_WOOCOMMERCE_STAGING_BRIDGE true --raw
HSE_IGC_TEST_PRICE=1000.00 wp eval-file wp-content/plugins/hse-headless/scripts/provision-nebosh-igc-product.php
wp eval-file wp-content/plugins/hse-headless/tests/commerce-rest-integration.php
wp core verify-checksums
wp plugin get woocommerce --fields=name,status,version --format=table
```

Then verify the public projection before starting the Astro build:

```sh
curl -fsS https://cms.hsetraining.rs/wp-json/hse/v1/commerce/products/nebosh-igc
```

## Payment test matrix

1. Open the staging site in a private browser session and start checkout from
   the IGC CTA. Confirm a one-item cart, the exact test amount/currency, guest
   checkout, and RaiAccept as the selected gateway.
2. Complete a successful Visa sandbox payment and verify the Woo order reaches
   the configured paid/completed status only after RaiAccept status retrieval.
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

## Rollback

1. Disable `HSE_WOOCOMMERCE_STAGING_BRIDGE` immediately; Astro then falls back
   to the existing course/contact destination on its next build.
2. Restore the preceding `hse-headless` plugin and Astro release.
3. Restore the database and WooCommerce 11.1.0 plugin together from the matched
   pre-downgrade backups if a full rollback is needed. Do not restore only the
   plugin code across an incompatible database state.
4. Re-run CMS and public smoke checks before ending the maintenance window.
