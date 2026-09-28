# Astro deployment to Unlimited

The Astro frontend is deployed independently from WordPress:

| Branch | GitHub environment | Public URL | Document root |
|---|---|---|---|
| `develop` | `dev` | `https://dev.hsetraining.rs` | `/home/sbb22122/dev.hsetraining.rs` |
| `main` | `staging` | `https://staging.hsetraining.rs` | `/home/sbb22122/staging.hsetraining.rs` |

WordPress, the HSE plugin, uploads, and the database are not deployed by these
workflows. Both Astro builds read published content from
`https://cms.hsetraining.rs`.

## Required GitHub environment secrets

Both workflows connect with these non-secret values:

| Setting | Value |
|---|---|
| SSH hostname | `s83.unlimited.rs` |
| SSH port | `9780` |
| SSH user | `sbb22122` |

Create GitHub environments named `dev` and `staging`. Add these secrets to each
environment:

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

## Deployment behavior

An Astro source change pushed to `develop` deploys only dev. An Astro source
change pushed to `main` deploys only staging. Both workflows can also be run
manually from GitHub Actions, which is useful after publishing CMS content.

Every run installs locked dependencies, runs tests, performs Astro/TypeScript
checks, lints the application, and creates a new static build. Before upload,
the current frontend is copied to a server-side backup. The deployment keeps
server-managed `.htaccess`, `.htpasswd`, `.well-known`, `cgi-bin`, and PHP configuration
files untouched. Homepage, contact, and resources smoke tests run after upload;
the previous frontend is restored automatically if they fail.

Server backups are stored outside the public document roots under
`~/.hse-astro-backups/`. Review and prune old successful backups periodically
after confirming the active release.

## First activation

1. Create and authorize a dedicated SSH deployment key.
2. Verify the Unlimited SSH hostname, port, user, and host fingerprint.
3. Configure the two required secrets in both GitHub environments.
4. Run the dev workflow manually and verify the site.
5. Run the staging workflow manually and verify the site.
6. Only after both checks pass, rely on branch-triggered deployments.

Changes to a workflow or the shared deployment script trigger the matching
environment as well, so configure and verify its secrets before pushing the
initial automation commit.
