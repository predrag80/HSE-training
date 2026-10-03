# Astro deployment to Unlimited

The Astro frontend is deployed independently from WordPress:

| Branch | GitHub environment | Public URL | Document root |
|---|---|---|---|
| `develop` | `dev` | `https://dev.hsetraining.rs` | `/home/sbb22122/dev.hsetraining.rs` |
| `main` | `staging` | `https://staging.hsetraining.rs` | Hetzner `/var/www/hsetraining-staging/current` |
| `production` | `production` | `https://hsetraining.rs` | `/home/sbb22122/public_html` |

WordPress, the HSE plugin, uploads, and the database are not deployed by these
workflows. The CMS origin is selected per frontend environment:

| Frontend | WordPress origin | Data isolation |
|---|---|---|
| dev | `https://dev-cms.hsetraining.rs` | Separate WordPress files and database copied from the shared CMS; sandbox payments only |
| staging | `https://cms.hsetraining.rs` | Existing shared CMS |
| production | `https://cms.hsetraining.rs` | Existing shared CMS until the production CMS migration is planned separately |

After the production launch is stable, staging will move to
`https://staging-cms.hsetraining.rs` with a separate database, files and uploads
copied from production at a controlled point in time. The clone will use only
sandbox RaiAccept/BokaPOS credentials and no Sentry DSN. Until then, explicit
checkout-source and contact-origin filtering prevents staging requests against
the shared CMS from entering production Sentry.

The dev CMS has its own salts, administrator sessions, database and uploads
copy. Search indexing and request-triggered WP-Cron are disabled there; a
separate server cron runs its due jobs once per minute with a dedicated lock.
Its order IDs and public order numbers use a reserved high test range so
requests sent with the shared sandbox credentials cannot collide with staging
orders. Content changes made in one CMS no longer appear in the other CMS
automatically.

## Required GitHub environment secrets

The Unlimited dev and production workflows connect with these non-secret values:

| Setting | Value |
|---|---|
| SSH hostname | `s83.unlimited.rs` |
| SSH port | `9780` |
| SSH user | `sbb22122` |

Create GitHub environments named `dev` and `production` for Unlimited. Add
these secrets to each environment:

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

An Astro source change pushed to `develop` deploys only dev. An Astro source
change pushed to `main` deploys only staging on Hetzner. A push to `production`
builds the production configuration and uploads it under the non-public
`~/.hse-astro-releases/production/` directory on Unlimited.

The dev workflow builds against `dev-cms.hsetraining.rs`; the staging and
production workflows continue to build against `cms.hsetraining.rs`. Updating
the HSE WordPress plugin or publishing CMS content is therefore a separate
operation for dev and for the shared staging/production CMS.

### Production CMS content trigger

Production remains a validated static Astro build. Install `hse-headless`
0.34.0 or later and configure the production CMS with a dedicated fine-grained
GitHub token restricted to this repository with **Actions: Read and write**:

```php
define( 'HSE_CONTENT_DEPLOY_ENABLED', true );
define( 'HSE_CONTENT_DEPLOY_GITHUB_TOKEN', getenv( 'HSE_CONTENT_DEPLOY_GITHUB_TOKEN' ) );
```

The token value must remain in private server configuration. Do not enable the
trigger on dev or staging. An editorial save queues one debounced WP-Cron event;
the once-per-minute server cron dispatches `deploy-production.yml` against the
`production` branch. The public change appears after the workflow validates and
atomically activates the new release, normally within a few minutes.

Verify the integration without contacting GitHub:

```sh
wp eval-file wp-content/plugins/hse-headless/tests/content-deploy-trigger-integration.php
```

For rollback, set `HSE_CONTENT_DEPLOY_ENABLED` to `false` and run production
deploys manually. This stops automatic builds without changing stored CMS
content or the active frontend release.

Production activation is deliberately locked while the existing PHP website
must remain online. The workflow changes `/home/sbb22122/public_html` only when
the GitHub environment variable `PRODUCTION_DEPLOY_ENABLED` is exactly `true`.
Keep it `false` until production payment and fiscalization credentials are
configured, a final release is prepared, and the launch window begins. Once it
is enabled, every later push to `production` deploys automatically.

Every run installs locked dependencies, runs tests, performs Astro/TypeScript
checks, lints the application, and creates a new static build. Before an active
deployment, the current frontend is copied to a server-side backup. Dev keeps
its server-managed `.htaccess`; production installs the repository-owned
security and canonical-host rules while preserving `.htpasswd`, `.well-known`,
`cgi-bin`, and PHP configuration files. Homepage, contact, and resources smoke
tests run after activation; the previous site is restored automatically if
they fail.

Server backups are stored outside the public document roots under
`~/.hse-astro-backups/`. Review and prune old successful backups periodically
after confirming the active release.

## First activation

1. Create and authorize a dedicated SSH deployment key.
2. Verify the Unlimited SSH hostname, port, user, and host fingerprint.
3. Configure the two required secrets in the `dev` and `production` GitHub environments.
4. Keep `PRODUCTION_DEPLOY_ENABLED=false` and push the release to `production`.
5. Verify that the workflow prepared a release without changing `public_html`.
6. Configure and verify the production RaiAccept and BokaPOS credentials.
7. Take the final database and `public_html` backup.
8. Set `PRODUCTION_DEPLOY_ENABLED=true` and manually rerun the production workflow.
9. Complete the real low-value payment, fiscal receipt, email and refund checks before opening the site publicly.

Changes to a workflow or the shared deployment script trigger the matching
environment as well, so configure and verify its secrets before pushing the
initial automation commit.
