# Astro staging deployment

Staging is now deployed to the Unlimited document root:

```text
/home/sbb22122/staging.hsetraining.rs
```

Pushes with Astro changes on `main` deploy staging. WordPress, the HSE plugin,
uploads, and its database remain manual and are not copied by the workflow.

The complete dev/staging configuration, GitHub secrets, verification, backup,
and rollback procedure is documented in [unlimited.md](./unlimited.md).
