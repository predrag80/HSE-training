# ADR-013: Production checkout and contact Sentry scope

## Status

Accepted

## Date

2026-10-03

## Context

The initial monitoring design covered the complete public frontend and added an
independent Sentry watchdog for BokaPOS fiscalization, refund and document-mail
operations. HSE Training now requires a smaller telemetry boundary: Sentry is
needed only to diagnose technical failures in the production checkout/payment
flow and production contact form. Staging telemetry, general page
performance, fiscal-operation monitoring and Sentry Cron Monitoring are outside
the required scope.

BokaPOS remains authoritative for fiscal documents and exposes its own portal,
administrator warnings and configured provider notifications. WooCommerce keeps
order notes and logs. Removing BokaPOS from Sentry does not remove the real
server cron required for WooCommerce, RaiAccept, BokaPOS and e-mail background
work.

## Decision

- Use the existing `hse-training-commerce` Sentry project for both production
  contact-page browser telemetry and production CMS checkout/contact failures.
- Give only the production Astro workflow a public DSN. Staging and local
  builds have no DSN and therefore send no Sentry telemetry.
- Initialize the Astro client only on `/contact/` and `/sr/contact/` when the
  declared environment is exactly `production`. Keep Session Replay disabled,
  sample the bounded contact transaction at 100%, and collect no identity,
  cookies, headers, bodies, query parameters, form values or stack variables.
- From the CMS, report only exceptional production order-creation failures,
  exceptional RaiAccept hosted-payment request failures and production contact
  delivery failures. Normal card declines, validation errors and successful
  submissions do not create Sentry issues.
- Require every CMS event to carry an explicit `production` checkout source or
  production contact origin. Drop staging and unclassified events before
  transport. This remains defense in depth after the CMS instances were
  separated.
- Remove the custom BokaPOS reconciliation watchdog, Sentry Cron Monitor and
  associated heartbeat. Use the official BokaPOS portal/notifications,
  WooCommerce order evidence and private server logs for those operations.
- Keep one server cron every minute. It runs due WP-Cron events and a bounded
  Action Scheduler batch with timeouts, a lock, private logs and an atomic local
  heartbeat. It does not depend on Sentry.
- Keep staging on `https://staging-cms.hsetraining.rs` with separate files,
  uploads and database. It retains sandbox payment/fiscal credentials and no
  Sentry DSN. The isolated clone was activated on 2026-10-04.

## Configuration

The production CMS keeps these server-owned values:

```php
define( 'HSE_MONITORING_SENTRY_DSN', getenv( 'HSE_MONITORING_SENTRY_DSN' ) );
define( 'HSE_MONITORING_ENVIRONMENT', 'production' );
define( 'HSE_MONITORING_ALERT_EMAIL', 'info@hsetraining.rs' );
```

The production Astro build receives `PUBLIC_SENTRY_DSN` and
`PUBLIC_SENTRY_ENVIRONMENT=production`. No other build or CMS receives a DSN.
No Sentry authentication token or source-map upload is required.

The production scheduler is:

```cron
* * * * * /usr/bin/flock -n /home/sbb22122/.hse-ops/general.lock /home/sbb22122/.hse-ops/run-cms-cron.sh >>/home/sbb22122/.hse-ops/logs/launcher.log 2>&1
```

## Verification

1. Build staging and local previews without a DSN and confirm the Sentry client
   is disabled.
2. Build production with the commerce-project DSN and confirm only the contact
   routes initialize the client.
3. Run the CMS scope integration check and confirm staging/unclassified
   contexts are dropped without network or e-mail delivery.
4. Confirm production contact failures and exceptional checkout/payment
   request failures appear in `hse-training-commerce` without personal data.
5. Confirm no `hse/monitor_bokapos_operations` action remains scheduled and no
   Sentry Cron Monitor alert remains active.
6. Confirm the general server-cron heartbeat advances every minute with
   `status=0` and ordinary WooCommerce/Action Scheduler jobs remain healthy.

## Rollback and recovery

The prior code and crontab are retained in Git and in dated private server
backups. Reintroducing broader monitoring requires a new approved scope and
privacy review; do not restore a Sentry heartbeat without also restoring its
runner and alert configuration. If the server cron is removed, first re-enable
request-triggered WP-Cron. Removing the production DSN disables reporting but
does not affect checkout, contact delivery, orders, payments or fiscalization.

## Consequences

Sentry configuration and event volume are smaller and production-focused. The
team loses independent Sentry alerts for fiscalization/refund/document-delivery
failures and must use provider/admin evidence for those cases. Staging is
isolated from production at both the frontend-build and CMS/database layers;
explicit source/origin filtering remains an additional safeguard.
