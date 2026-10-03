# ADR-014: CMS-triggered production Astro deploy

## Status

Accepted on 2026-10-03.

## Context

Astro renders editorial WordPress content during a static build. Saving a Hero
Slide or another CMS document therefore updates the public API immediately but
cannot alter the already generated HTML. Requiring the owner to request a
manual deploy after every editorial change does not meet the production editing
workflow.

## Decision

The repository-owned WordPress plugin queues one debounced WP-Cron event after
an approved public content record, localized settings document, or Media
Library attachment changes. Production's real server cron processes the event
within one minute. The event calls GitHub's workflow-dispatch API for
`deploy-production.yml` on the `production` branch. The existing workflow
validates, builds, uploads, atomically activates, and smoke-tests the release.

Only the production CMS defines `HSE_CONTENT_DEPLOY_ENABLED` and the dedicated
`HSE_CONTENT_DEPLOY_GITHUB_TOKEN`. The credential is a fine-grained token
restricted to the `predrag80/HSE-training` repository with Actions write
permission. It remains in server configuration and is never stored in the
database, plugin ZIP, browser bundle, API response, log, or repository.

Course, Training, Hero Slide, Service, Reference, Resource, and attachment
changes trigger a build. The localized Company, Course Page, and Legal Page
settings documents do the same. WooCommerce orders and other operational data
never trigger a frontend build.

Multiple edits in one request or before the scheduled event runs collapse into
one workflow dispatch. Temporary GitHub/network failures receive three bounded
retries; invalid configuration and authorization failures surface as an
administrator notice and require correction or a manual production deploy.

## Consequences

Content appears after the production workflow finishes, normally within a few
minutes rather than at the instant WordPress saves. Static performance,
validated bilingual builds, atomic release activation, and rollback releases
are preserved. A malformed or incomplete CMS response still fails the build
instead of publishing a broken page.

## Recovery

Set `HSE_CONTENT_DEPLOY_ENABLED` to `false` to stop automatic dispatch without
affecting CMS editing or the live site. Run the production workflow manually
while investigating. Removing the token revokes CMS access to GitHub but also
causes an administrator warning until the feature flag is disabled.
