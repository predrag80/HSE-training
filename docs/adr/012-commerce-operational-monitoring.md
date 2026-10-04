# ADR-012: Commerce operational monitoring

## Status

Superseded by [ADR-013](013-production-checkout-contact-sentry.md)

## Date

2026-10-01

## Context

WooCommerce, RaiAccept and BokaPOS run payment, order, fiscalization, refund and
fiscal-document delivery work asynchronously. BokaPOS already marks final
failures as needing attention and can e-mail the shop administrator, but an
alert sent by the same WordPress mail path is not an independent signal. The
provider also deliberately retries transient failures before requesting manual
intervention, while HSE Training needs earlier visibility when a paid order or
fiscal refund remains unfinished.

WordPress's request-triggered WP-Cron is not a sufficient production clock for
time-sensitive fiscal jobs because execution can be delayed when the CMS has no
traffic. Monitoring must not issue, repeat, repair or otherwise mutate a fiscal
document: BokaPOS remains authoritative for those outcomes.

## Decision

- Keep the official BokaPOS administrator notification enabled as the first
  provider-owned alert. Do not edit the BokaPOS plugin.
- Add a read-only watchdog in the repository-owned `hse-headless` plugin. It
  reads bounded status columns from the local BokaPOS journal and supported
  WooCommerce order APIs; it never reads or reports journal payloads, customer
  identities, buyer identifiers or receipt contents.
- Run the watchdog every five minutes through Action Scheduler. A real server
  cron invokes all due WordPress events every minute. Only after that command is
  proven to run successfully may request-triggered WP-Cron be disabled.
- Report final failures immediately, sale/refund operations unfinished for ten
  minutes, delivery operations unfinished for thirty minutes, completed orders
  without a fiscal receipt after ten minutes, expected refund operations that
  never appeared, and configured BokaPOS e-mail jobs that never appeared.
- Send independent events and a cron heartbeat to Sentry through a server-owned
  DSN. If Sentry is unavailable or unconfigured, send a privacy-bounded fallback
  e-mail to the configured operational recipient. If the project has not yet
  enabled Sentry Cron Monitoring, raise a one-time setup incident and keep a
  WooCommerce administrator warning visible until check-ins are accepted.
- Keep the five-minute heartbeat schedule, but allow a ten-minute check-in
  margin before Sentry reports a missed run. This absorbs bounded shared-host
  scheduler drift while still alerting when the watchdog has not completed for
  approximately fifteen minutes.
- Deduplicate alerts durably by monitored object and failure signature. Use a
  deterministic Sentry event id so a network retry cannot create another Sentry
  event. Send one recovery event when the observed state becomes healthy.
- Store only operational ids, status signatures and timestamps in monitoring
  state. Events may contain an internal order id, refund id, BokaPOS operation
  id, status, failure code, environment and administrator URL. They must not
  contain names, addresses, customer e-mail addresses, PIB/TIN values, payment
  payloads, provider secrets or receipt bodies.
- Expose a WooCommerce administrator notice when incidents remain or the
  watchdog has not completed for fifteen minutes.
- Use two Sentry projects with separate DSNs: `hse-training-web` for Astro
  browser errors and contact-form client failures, and
  `hse-training-commerce` for CMS checkout, RaiAccept, BokaPOS, refund,
  delivery and cron incidents.
- Report contact mail transport failures, WooCommerce order-creation
  exceptions and exceptional RaiAccept request failures from the
  repository-owned compatibility layer. Expected validation errors and normal
  declined-card outcomes are not operational incidents.
- Run the Astro SDK client-side only because the public build is static. Do not
  enable Session Replay. Collect no user identity, cookies, headers, request or
  response bodies, URL query parameters, stack-frame variables or contact-form
  field values. Strip query strings and fragments from event and breadcrumb
  URLs before transport. Sample contact-page traces at 100% and other public
  page traces at 10%.
- Keep Sentry's server-side default scrubbers enabled for both projects,
  prevent storage of IP addresses and additionally scrub common contact and
  billing field names. Use each project's e-mail alert for high-priority
  issues. The commerce project owns the `hse-bokapos-watchdog` Cron Monitor.

## Configuration

The CMS reads these values from constants or the server environment:

```php
define( 'HSE_MONITORING_SENTRY_DSN', getenv( 'HSE_MONITORING_SENTRY_DSN' ) );
define( 'HSE_MONITORING_ENVIRONMENT', 'production' );
define( 'HSE_MONITORING_ALERT_EMAIL', 'alerts@hsetraining.rs' );
```

The DSN is a public ingestion key, but it remains environment-owned so
non-production and production traffic can be separated without changing plugin
code. No Sentry authentication token belongs in WordPress.

The Astro build receives the public web-project DSN and environment through
`PUBLIC_SENTRY_DSN` and `PUBLIC_SENTRY_ENVIRONMENT`. Source-map upload is
disabled until a narrowly scoped Sentry build token is explicitly approved and
stored as a protected CI secret; runtime error and trace ingestion does not
require that token.

## Production scheduler

Production uses the repository-owned
`scripts/run-wordpress-operations.sh` wrapper from the private
`~/.hse-ops/run-cms-cron.sh` path. The wrapper writes bounded private logs and
atomic heartbeat files below `~/.hse-ops/`, applies a hard timeout to every
invocation, and has two modes:

- `general` runs due WP-Cron events every minute while excluding the Action
  Scheduler bridge, then processes one bounded Action Scheduler batch outside
  the `hse-monitoring` group;
- `monitor` checks only the `hse-monitoring` group every minute with a separate
  lock. Action Scheduler executes the recurring watchdog when its five-minute
  interval is due, without a boundary race and without letting a busy commerce
  queue delay the Sentry heartbeat.

The production crontab is:

```cron
* * * * * /usr/bin/flock -n /home/sbb22122/.hse-ops/general.lock /home/sbb22122/.hse-ops/run-cms-cron.sh general >>/home/sbb22122/.hse-ops/logs/launcher.log 2>&1
* * * * * /usr/bin/flock -n /home/sbb22122/.hse-ops/monitor.lock /home/sbb22122/.hse-ops/run-cms-cron.sh monitor >>/home/sbb22122/.hse-ops/logs/launcher.log 2>&1
```

`DISABLE_WP_CRON` remains enabled only while these server jobs are installed and
their `status=0` heartbeat files continue to advance.

## Verification

1. Run the plugin monitoring integration check.
2. Run one watchdog action manually and confirm its Sentry event/check-in.
3. Confirm the recurring `hse/monitor_bokapos_operations` action is pending.
4. Add and manually execute the server cron command.
5. Confirm both the WordPress cron timestamp and watchdog timestamp advance.
6. Disable request-triggered WP-Cron and repeat the check after at least five
   minutes.
7. In BokaPOS sandbox, exercise a successful sale and fiscal refund, then a
   controlled failure; confirm exactly one incident and one recovery event.
8. Build Astro with the web DSN, force a non-personal test error and confirm it
   appears only in `hse-training-web` with the expected environment tag.
9. Simulate a contact delivery failure without real enquiry data and confirm
   one grouped `contact_delivery_*` issue appears in
   `hse-training-commerce`.
10. Confirm both `~/.hse-ops/state/general.heartbeat` and
    `~/.hse-ops/state/monitor.heartbeat` advance each minute with `status=0`;
    verify at least three consecutive completed watchdog actions at the
    approximately five-minute recurring interval before launch.

## Rollback and recovery

Re-enable request-triggered WP-Cron before removing the server cron. A dated
crontab and `wp-config.php` copy must be kept under the private
`~/.hse-ops/backups/` directory before scheduler changes. To roll back the
split scheduler, restore that crontab and only then remove or disable the
wrapper. Deactivating `hse-headless` removes only its recurring monitoring
action and lock; it does not change BokaPOS operations or fiscal documents.
Removing the Sentry constants causes incident reporting to fall back to
operational e-mail. Monitoring state can be removed without affecting
WooCommerce or BokaPOS business state. Removing the Astro integration and its
two public environment variables disables browser telemetry without affecting
the static application.

## Consequences

The CMS gains an independent view of fiscal operational health without becoming
a second fiscalization engine. Sentry becomes an operational dependency for the
preferred alert path, while the existing BokaPOS administrator notification and
bounded WordPress e-mail fallback remain available.
