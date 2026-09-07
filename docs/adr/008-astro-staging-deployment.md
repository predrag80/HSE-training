# ADR-008: Astro Staging Deployment

## Status

Accepted

## Date

2026-09-07

## Context

The static Astro application needs a repeatable staging deployment that is
independent of the WordPress runtime. The staging host is a Hetzner Ubuntu
server, while the production WordPress CMS remains on Serbia Broadband hosting
at `cms.hsetraining.rs`.

Staging deployments must not require root access from CI, expose SSH secrets in
the repository, or leave the site in a partially uploaded state.

## Decision

Deploy `apps/web` to `staging.hsetraining.rs` with GitHub Actions after relevant
changes reach `main`, with manual dispatch available for content rebuilds.

The workflow:

1. installs locked Node dependencies;
2. runs tests, Astro/TypeScript checks, linting, and the static build;
3. builds against the published WordPress API at `cms.hsetraining.rs`;
4. uploads `dist` over SSH as an immutable release owned by an unprivileged
   `deploy` user;
5. atomically switches the Nginx `current` symlink; and
6. smoke-tests the staging homepage and contact page, restoring the previous
   release if either check fails.

The CI private key is an environment secret named
`HETZNER_SSH_PRIVATE_KEY`. The server public key is pinned in the repository so
CI does not disable SSH host verification. Nginx and TLS configuration remain
operator-managed server configuration because changing them requires elevated
privileges.

## Alternatives Considered

- Build directly on the Hetzner server after a Git pull.
- Deploy files in place without release directories.
- Run the Astro site in a container.
- Deploy WordPress and Astro through the same workflow.

## Consequences

- A source push cannot expose a half-uploaded static build.
- CI has narrowly scoped filesystem access and no server administration rights.
- A failed post-deployment smoke test restores the previous release.
- WordPress publishing does not automatically rebuild Astro; editors or
  operators use manual workflow dispatch until a separately reviewed trigger is
  added.
- Nginx configuration, certificate renewal, release retention, and server
  backups remain explicit operational responsibilities.
