# HSE Training Headless

`hse-headless` is the Git-controlled WordPress plugin for HSE-specific headless
CMS integration.

## Current Status

The plugin currently provides only a valid, namespaced WordPress bootstrap. It
does not register content models, fields, hooks, database tables, or REST API
extensions yet.

## Responsibilities

- HSE-specific WordPress content models
- Future REST API extensions
- Headless CMS integration

These are ownership boundaries for future tasks, not claims of implemented
functionality.

## Not Responsible For

- Astro frontend
- Customer accounts
- Payments
- Lemon Squeezy
- Course delivery
- LMS functionality

The local WordPress runtime consumes this repository-owned source through a
symbolic link. WordPress core and runtime state remain outside this repository.
