# Contact Delivery

Status: implemented in `hse-headless` and the Astro contact page

## Flow

1. In production, the static Astro contact page renders a Cloudflare Turnstile
   widget using the public `PUBLIC_TURNSTILE_SITE_KEY`. The form stays disabled
   until the widget supplies a token. Environments without the public key keep
   the widget disabled for isolated development and staging tests.
2. The Astro page posts JSON, including the single-use token, to
   `POST /wp-json/hse/v1/contact`.
3. The WordPress plugin validates and bounds the submitted fields.
4. Browser origins are restricted to the configured public sites; localhost is
   permitted for development.
5. A hidden honeypot and a three-messages-per-ten-minutes client limit reduce
   automated abuse.
6. When production enforcement is enabled, the plugin verifies a single-use
   Turnstile token with Cloudflare and requires the expected hostname and
   `contact` action. A separate ten-attempts-per-ten-minutes limit bounds these
   verification calls.
7. The browser resets the widget after every submission attempt because a
   Turnstile token cannot be reused.
8. The plugin renders the branded HTML body and plain-text alternative.
9. WordPress sends through the server-owned authenticated SMTP configuration.
10. The browser receives only an accepted response or a generic localized error.

The SMTP password, sender, recipient, and optional origin override remain in
server configuration. They are not embedded in the Astro bundle, plugin ZIP,
database, API response, or repository.

The Turnstile secret, hostname allowlist, expected action, and enforcement flag
are also server-owned. The plugin is deployed first with enforcement disabled;
after the Astro widget is live and sends tokens, production enables the flag.
Disabling the flag is the immediate rollback and leaves the remaining abuse
controls active.

The Turnstile site key is intentionally public and is embedded only in the
production Astro build. The secret key remains exclusively in the WordPress
server configuration.

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
`HSE_CONTACT_ALLOWED_ORIGINS` as a comma-separated allowlist. Turnstile uses
`HSE_TURNSTILE_SECRET_KEY`, `HSE_TURNSTILE_ALLOWED_HOSTNAMES`,
`HSE_TURNSTILE_ACTION`, and `HSE_TURNSTILE_REQUIRED`; the secret must not enter
the frontend bundle. Set `PUBLIC_TURNSTILE_SITE_KEY` only for frontend
environments that should display the widget. Deploy the plugin with enforcement
disabled, deploy and verify the Astro widget, and only then enable
`HSE_TURNSTILE_REQUIRED`. The generated form must point at that environment's
`WORDPRESS_API_URL`.

To roll back delivery, restore the preceding plugin and Astro release together.
The SMTP configuration values may remain defined because the preceding plugin
does not read them; remove them later during a controlled configuration change.
