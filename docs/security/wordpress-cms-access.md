# WordPress CMS Access

## Public Boundary

The production WordPress installation is not the public marketing application.
It is the editorial CMS and the limited WooCommerce commerce host approved by
ADR-011. The HSE Headless plugin returns HTTP 404 for ordinary requests that
would otherwise render a WordPress theme. It leaves these required operations
intact:

- published REST API responses used by Astro;
- authenticated WordPress administration;
- login, logout, and password-reset flows;
- WooCommerce checkout, `order-pay`, and `order-received`;
- RaiAccept gateway return/callback and required WooCommerce API requests;
- WordPress AJAX and cron requests.

Astro remains the only public marketing frontend. The CMS renders only the
branded commerce surfaces required to complete or retry an order.

## Runtime Log Protection

PHP, WordPress, WooCommerce, payment, and fiscalization logs must never be
downloadable from a public document root. Production PHP errors are written to
the hosting account's private `logs` directory, outside the CMS document root.
`WP_DEBUG` and `WP_DEBUG_LOG` remain disabled outside a bounded diagnostic
session.

Unlimited/LiteSpeed document roots include the deny rules and response headers
from `infra/apache/public-security-baseline.htaccess` outside generated
WordPress and cPanel sections. WooCommerce additionally maintains its own deny
rule inside `wp-content/uploads/wc-logs`. Astro staging keeps Nginx access and
error logs under `/var/log/nginx`, outside its static document root.

After a hosting migration, PHP-version change, WordPress restore, or document
root change, verify representative paths without reading their contents:

```sh
curl -I https://cms.hsetraining.rs/error_log
curl -I https://cms.hsetraining.rs/wp-content/debug.log
curl -I https://dev.hsetraining.rs/error_log
curl -I https://staging.hsetraining.rs/error_log
```

Every response must be `403` or `404`, never `200` or a redirect to a
downloadable file. Also verify a real WooCommerce log path from the
administrator's **WooCommerce → Status → Logs** screen returns `403` to an
anonymous request. Runtime logs that appear in a public document root must be
moved to private server storage and restricted to the hosting account.

## XML-RPC And Core Documents

No approved HSE CMS, WooCommerce, RaiAccept, BokaPOS, or Astro integration uses
XML-RPC. The HSE plugin disables authenticated XML-RPC and removes all methods,
while the web server blocks `/xmlrpc.php` before PHP executes. WordPress
`readme.html` and `license.txt` remain available to core checksum and update
workflows on disk but are blocked from anonymous HTTP access.

Anonymous `GET` and `POST` requests to `/xmlrpc.php`, `/readme.html`, and
`/license.txt` must return `403` or `404`.

## Security Response Headers

CMS, checkout, dev, staging, and the future production Astro document root use
the following baseline:

- one-year HTTPS-only transport through `Strict-Transport-Security`;
- MIME sniffing protection and same-origin framing;
- `strict-origin-when-cross-origin` referrer handling;
- disabled camera, microphone, geolocation, and USB browser capabilities;
- a conservative Content Security Policy protecting the base URL, embedded
  objects, frame ancestors, and mixed-content upgrades without restricting the
  approved RaiAccept redirect or WooCommerce checkout resources; and
- removal of the PHP version response header.

The production domain must receive the same baseline before DNS cutover. Do not
add HSTS `includeSubDomains` or `preload` until every live subdomain is confirmed
HTTPS-only.

## Administrator Two-Factor Authentication

Two-factor authentication is a production-launch control. Before DNS cutover,
enable TOTP-based 2FA for both administrator accounts, store each recovery-code
set separately, verify login and recovery in a private browser session, and
retain one tested hosting-level recovery path. Do not enforce 2FA until both
administrators have completed enrollment.

## Login Entry Route

Use the maintained **WPS Hide Login** plugin to expose the login form at:

```text
https://cms.hsetraining.rs/hse_panel/
```

Set its redirect target to `404`. Anonymous requests to `/wp-login.php` and
`/wp-admin/` then end at a not-found response. WordPress still uses the physical
`wp-admin` directory after successful authentication; neither core files nor
directories are renamed. Version 1.9.19 was verified locally; the third-party
plugin is installed from the official WordPress plugin directory and is not
vendored into this repository.

Install and configure it through the WordPress administrator:

1. Open **Plugins → Add New** and install **WPS Hide Login**.
2. Activate the plugin.
3. Open **Settings → WPS Hide Login**.
4. Set **Login url** to `hse_panel`.
5. Set **Redirection url** to `404`.
6. Save, open `/hse_panel/` in a private browser window, and verify login before
   ending the existing administrator session.

Do not use this route as the only security control. Keep strong unique
administrator passwords, HTTPS, updates, backups, and hosting-level abuse
protection in place.

## Recovery

If the custom route fails, disable `wps-hide-login` through cPanel File Manager
by renaming its directory under `wp-content/plugins`. WordPress then restores
the standard login URL without modifying core files or the database.

If closing the frontend interferes with an unexpected integration, deactivate
`hse-headless` through cPanel, diagnose the request type, and add only the
narrowest required exception before reactivating it.

## Verification

After every CMS plugin deployment, verify:

```text
/                                      404
/wp-json/                              200
/wp-json/hse/v1/homepage               200
/hse_panel/                            200
/wp-login.php                          404
/wp-admin/ (anonymous)                 302 to /404/, then 404
/wp-admin/ (authenticated)             200
```
