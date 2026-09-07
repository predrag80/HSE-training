# Astro Staging Deployment

## Scope

This process deploys only the static Astro application from `apps/web` to:

```text
https://staging.hsetraining.rs
```

WordPress remains independently hosted at `https://cms.hsetraining.rs`. Neither
WordPress core nor its database is copied to Hetzner by this workflow.

## Runtime Layout

```text
/var/www/hsetraining-staging/
├── current -> releases/<release-id>
└── releases/
    └── <release-id>/
```

Nginx serves `current`. Each deployment first uploads a complete release and
then changes the symlink atomically.

## GitHub Configuration

Create a repository environment named `staging` in **Settings → Environments**.
Restrict its deployment branch to `main`, then add this environment secret:

| Name | Value |
|---|---|
| `HETZNER_SSH_PRIVATE_KEY` | Complete private key dedicated to GitHub Actions |

The matching public key is authorized only for the unprivileged `deploy` user
on the Hetzner host. Do not add the private key to Git, repository variables,
workflow YAML, logs, or an `.env` file.

The deploy key generated for this environment is stored locally at:

```text
~/.ssh/hse_staging_github_actions
```

Copy it directly to the clipboard when creating the GitHub secret:

```sh
pbcopy < ~/.ssh/hse_staging_github_actions
```

## Automatic and Manual Deployment

The `Deploy Astro to staging` workflow runs after a push to `main` changes the
Astro application, Node version, workflow, or pinned SSH host key. It can also
be started from **Actions → Deploy Astro to staging → Run workflow**.

Manual dispatch is useful after publishing WordPress content because Astro
pages are statically generated. Automatic WordPress-to-GitHub rebuilds are not
part of this deployment slice.

The workflow builds with:

```text
WORDPRESS_API_URL=https://cms.hsetraining.rs
```

Before uploading, it must pass:

```sh
npm --prefix apps/web run test
npm --prefix apps/web run check
npm --prefix apps/web run lint
npm --prefix apps/web run build
```

## Verification

After deployment, verify the workflow run and these URLs:

```sh
curl --fail --show-error --silent --output /dev/null https://staging.hsetraining.rs/
curl --fail --show-error --silent --output /dev/null https://staging.hsetraining.rs/contact/
```

Both commands must exit successfully. The response must include
`X-Robots-Tag: noindex, nofollow` while the site is staging-only.

## Rollback

The workflow records the previous `current` target and restores it automatically
if either smoke test fails. For a manual rollback, select a known-good release
and atomically replace the symlink as an operator with server access:

```sh
ln -sfn /var/www/hsetraining-staging/releases/<known-good-release> \
  /var/www/hsetraining-staging/current.next
mv -Tf /var/www/hsetraining-staging/current.next \
  /var/www/hsetraining-staging/current
```

Confirm the homepage and contact page after rollback. Do not delete old releases
until the active `current` target and the required rollback release have been
verified.

## Server Configuration

The version-controlled Nginx reference is:

```text
infra/nginx/hsetraining-staging.conf
```

The active server copy is
`/etc/nginx/sites-available/hsetraining-staging.conf`. Changes to it are manual
operator actions and require `nginx -t` before reload. Certbot manages the TLS
certificate and renewal timer.
