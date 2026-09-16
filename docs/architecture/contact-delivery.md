# Contact Delivery

Status: implemented in `hse-headless` 0.18.0 and the Astro contact page

## Flow

1. The static Astro contact page posts JSON to `POST /wp-json/hse/v1/contact`.
2. The WordPress plugin validates and bounds the submitted fields.
3. Browser origins are restricted to the configured public sites; localhost is
   permitted for development.
4. A hidden honeypot and a three-messages-per-ten-minutes client limit reduce
   automated abuse.
5. The plugin renders the branded HTML body and plain-text alternative.
6. WordPress sends through the server-owned authenticated SMTP configuration.
7. The browser receives only an accepted response or a generic localized error.

The SMTP password, sender, recipient, and optional origin override remain in
server configuration. They are not embedded in the Astro bundle, plugin ZIP,
database, API response, or repository.

## Personal data

The endpoint processes name, email address, optional phone number, message,
language, and request time only for email delivery. It does not create a
WordPress user, post, option, custom table row, or other persistent contact
record. Rate limiting stores only a salted, non-reversible client-address hash
and a counter in an expiring WordPress transient. Failure logs contain a random
request ID and no submitted contact details.

Delivered enquiries are retained only in the configured recipient mailbox and
are therefore governed by that mailbox's access, retention, and deletion
policy. Operationally unnecessary enquiries should be deleted from the mailbox
when no longer needed.

## Verification

The endpoint integration test intercepts `wp_mail()` before transport and
checks the success, validation, origin, honeypot, and rate-limit paths without
sending an email:

```sh
wp eval-file ~/Development/HSE-training/wordpress/plugins/hse-headless/tests/contact-rest-integration.php
```

The frontend request helper has unit coverage, and the normal Astro test,
check, lint, and production build commands cover its compiled integration.

## Deployment and rollback

Before enabling delivery on an environment, define all `HSE_SMTP_*` settings,
`HSE_MAIL_FROM`, and `HSE_MAIL_TO` in server-only configuration. Optionally set
`HSE_CONTACT_ALLOWED_ORIGINS` as a comma-separated allowlist. Then deploy the
plugin and rebuild the static Astro site so the generated form points at that
environment's `WORDPRESS_API_URL`.

To roll back delivery, restore the preceding plugin and Astro release together.
The SMTP configuration values may remain defined because the preceding plugin
does not read them; remove them later during a controlled configuration change.
