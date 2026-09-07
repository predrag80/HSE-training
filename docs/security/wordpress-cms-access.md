# WordPress CMS Access

## Public Boundary

The production WordPress installation is an editorial CMS, not a public
application. The HSE Headless plugin returns HTTP 404 for requests that would
otherwise render a WordPress theme. It leaves these required operations intact:

- published REST API responses used by Astro;
- authenticated WordPress administration;
- login, logout, and password-reset flows;
- WordPress AJAX and cron requests; and
- XML-RPC until a separate compatibility and security decision disables it.

The Astro application remains the only public frontend.

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
