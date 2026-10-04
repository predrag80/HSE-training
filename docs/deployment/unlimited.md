# Astro deployment to Unlimited

The Astro frontend is deployed independently from WordPress:

| Branch | GitHub environment | Public URL | Document root |
|---|---|---|---|
| `main` | `staging` | `https://staging.hsetraining.rs` | Hetzner `/var/www/hsetraining-staging/current` |
| `production` | `production` | `https://hsetraining.rs` | `/home/sbb22122/public_html` |

WordPress, the HSE plugin, uploads, and the database are not deployed by these
workflows. The CMS origin is selected per frontend environment:

| Frontend | WordPress origin | Data isolation |
|---|---|---|
| staging | `https://staging-cms.hsetraining.rs` | Separate WordPress files, uploads and database; sandbox payments only |
| production | `https://cms.hsetraining.rs` | Production WordPress files, database and live payment/fiscal credentials |

The isolated staging CMS was provisioned on 2026-10-04 with new salts, a
dedicated database and copied non-production uploads. Historical orders,
sessions, scheduled actions and gateway access-token caches were removed before
activation. Its order IDs and public order numbers begin in the reserved
`700000` range. The clone keeps sandbox RaiAccept/BokaPOS configuration, has no
Sentry DSN, cannot trigger production content deploys and is blocked from search
indexing.

The staging and production CMS instances remain independent. Content, plugin,
upload and database changes made in one do not appear in the other
automatically.

## Required GitHub environment secrets

The Unlimited production workflow connects with these non-secret values:

| Setting | Value |
|---|---|
| SSH hostname | `s83.unlimited.rs` |
| SSH port | `9780` |
| SSH user | `sbb22122` |

Create the GitHub environment named `production` for Unlimited and add these
secrets:

| Secret | Purpose |
|---|---|
| `UNLIMITED_SSH_PRIVATE_KEY` | Private half of the dedicated deployment key |
| `UNLIMITED_SSH_KNOWN_HOSTS` | Verified host-key line for `[s83.unlimited.rs]:9780` |

If an environment is protected with HTTP Basic Authentication, also add both
optional secrets below. Leave both unset when the environment is public.

| Optional secret | Purpose |
|---|---|
| `UNLIMITED_HTTP_AUTH_USER` | Username used by post-deployment smoke tests |
| `UNLIMITED_HTTP_AUTH_PASSWORD` | Password used by post-deployment smoke tests |

Never commit the private key, hosting password, or an unverified host key.
Authorize only the matching public key in the Unlimited account.

Staging remains on Hetzner and uses `HETZNER_SSH_PRIVATE_KEY`. When its Nginx
virtual host is protected with HTTP Basic Authentication, the staging GitHub
environment must also contain `STAGING_HTTP_AUTH_USER` and
`STAGING_HTTP_AUTH_PASSWORD` so post-deployment smoke tests can authenticate.
HTTP authentication is limited to the Astro preview hosts; the WordPress CMS,
REST API and payment callback hosts remain reachable by approved integrations.

## Deployment behavior

An Astro source change pushed to `main` deploys staging on Hetzner. A push to
`production` builds the production configuration and uploads it under the
non-public `~/.hse-astro-releases/production/` directory on Unlimited.

Staging builds against `staging-cms.hsetraining.rs`, and production builds
against `cms.hsetraining.rs`. Updating the HSE WordPress plugin or publishing
CMS content is therefore a separate operation in each environment.

### Production CMS content trigger

Production remains a validated static Astro build. Install `hse-headless`
0.34.0 or later and configure the production CMS with a dedicated fine-grained
GitHub token restricted to this repository with **Actions: Read and write**:

```php
define( 'HSE_CONTENT_DEPLOY_ENABLED', true );
define( 'HSE_CONTENT_DEPLOY_GITHUB_TOKEN', getenv( 'HSE_CONTENT_DEPLOY_GITHUB_TOKEN' ) );
```

The token value must remain in private server configuration. Do not enable the
trigger on staging. An editorial save queues one debounced WP-Cron event;
the once-per-minute server cron dispatches `deploy-production.yml` against the
`production` branch. The public change appears after the workflow validates and
atomically activates the new release, normally within a few minutes.

Store the token as the encrypted GitHub Actions secret
`HSE_CONTENT_DEPLOY_GITHUB_TOKEN`, then run the manual
`configure-production-cms.yml` workflow. The workflow uses the production SSH
environment, writes the token to `/home/sbb22122/.hse-content-deploy.php` with
private permissions, backs up `wp-config.php`, and connects that private file to
WordPress. It finishes by requesting one production build through the CMS so the
credential and dispatch path are verified end to end. Re-run the workflow when
rotating the token.

Verify the integration without contacting GitHub:

```sh
wp eval-file wp-content/plugins/hse-headless/tests/content-deploy-trigger-integration.php
```

For rollback, set `HSE_CONTENT_DEPLOY_ENABLED` to `false` and run production
deploys manually. This stops automatic builds without changing stored CMS
content or the active frontend release.

### Production Turnstile secret

Store `HSE_TURNSTILE_SECRET_KEY` as an encrypted secret in the GitHub
`production` environment, then run the manual
`configure-production-turnstile.yml` workflow. It writes the secret and the
fixed hostname/action policy to `/home/sbb22122/.hse-turnstile.php`, applies
private permissions, connects that file to `wp-config.php`, and verifies the
configuration without printing the secret. The initial workflow deliberately
sets `HSE_TURNSTILE_REQUIRED` to `false`; enforcement is enabled only after the
CMS plugin and Astro widget are both live and a token-bearing request has been
verified.

The emergency rollback is to set `HSE_TURNSTILE_REQUIRED` back to `false` in
the private file. This leaves the existing origin, honeypot, validation, size,
and rate-limit controls active while stopping mandatory Cloudflare validation.

Production releases of the repository-owned CMS plugin use the manual
`deploy-production-plugin.yml` workflow. It packages the version on the
selected `production` revision, verifies its checksum and PHP syntax, moves the
previous plugin to the private `~/.hse-plugin-backups/` directory, and runs the
contact REST integration check without sending e-mail. Any failed activation,
version check, or integration check restores the previous plugin directory.

Production activation is deliberately locked while the existing PHP website
must remain online. The workflow changes `/home/sbb22122/public_html` only when
the GitHub environment variable `PRODUCTION_DEPLOY_ENABLED` is exactly `true`.
Keep it `false` until production payment and fiscalization credentials are
configured, a final release is prepared, and the launch window begins. Once it
is enabled, every later push to `production` deploys automatically.

Every run installs locked dependencies, runs tests, performs Astro/TypeScript
checks, lints the application, and creates a new static build. Before an active
production deployment, the current frontend is copied to a server-side backup.
Production installs the repository-owned security and canonical-host rules
while preserving `.htpasswd`, `.well-known`, `cgi-bin`, and PHP configuration
files. Homepage, contact, and resources smoke tests run after activation; the
previous site is restored automatically if they fail.

Server backups are stored outside the public document roots under
`~/.hse-astro-backups/`. Review and prune old successful backups periodically
after confirming the active release.

## Environment maintenance

1. Merge approved frontend changes to `main` and verify staging.
2. Promote the tested commits to `production`.
3. Confirm the production workflow, public smoke tests and CMS health.
4. Apply WordPress/plugin/database changes independently to staging and
   production, using sandbox credentials only on staging.
5. Retain a private database and frontend backup before material production
   changes.

Changes to a workflow or the shared deployment script trigger the matching
environment as well, so configure and verify its secrets before pushing the
initial automation commit.
