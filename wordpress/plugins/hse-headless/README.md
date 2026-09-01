# HSE Training Headless

`hse-headless` is the Git-controlled WordPress plugin for HSE-specific headless
CMS integration.

## Current Status

The plugin provides the first headless content slice: an editor-managed Course
post type with registered metadata, validation, and core REST API support.

## Responsibilities

- HSE-specific WordPress content models
- Course metadata validation and admin editing
- Small REST API extensions needed by headless consumers
- Headless CMS integration

## Course API

Published Courses are available at:

```text
/wp-json/wp/v2/courses
```

The full contract and lookup behavior are documented in
[`docs/api/wordpress-courses.md`](../../../docs/api/wordpress-courses.md).

Run the focused local integration checks from the WordPress runtime:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/integration.php
```

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
