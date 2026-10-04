# Astro staging deployment

The staging Astro frontend is deployed to the Hetzner release root:

```text
/var/www/hsetraining-staging/current
```

Pushes with Astro changes on `main` deploy staging. The build reads from the
isolated `https://staging-cms.hsetraining.rs` WordPress instance on Unlimited.
That CMS has separate WordPress files, uploads and database, keeps sandbox-only
commerce settings, and does not receive production Sentry or content-deploy
credentials.

WordPress/plugin/database maintenance remains separate from the Astro deploy.
Ordinary staging frontend deployments never copy or replace CMS data.

The complete staging/production configuration, GitHub secrets, verification, backup,
and rollback procedure is documented in [unlimited.md](./unlimited.md).
