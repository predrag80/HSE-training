# Local Development

Last verified: 2026-08-31  
Host: macOS 26.6 on Apple Silicon (`arm64`)  
Shell: zsh (`/bin/zsh`)  
Homebrew prefix: `/opt/homebrew`

## Architecture

Development uses native macOS services installed with Homebrew. Docker, Docker
Desktop, OrbStack, Colima, DDEV, LocalWP, MAMP, and XAMPP are not required.

| Concern | Native development runtime | Intended address |
|---|---|---|
| Astro | Node.js LTS managed by `fnm` | `http://localhost:4321` |
| WordPress | Nginx -> PHP 8.4 / PHP-FPM -> MySQL 8.4 | `http://cms.hsetraining.test` |

The Git repository and runtime are deliberately separate:

- `~/Development/HSE-training` contains Git history, application code, the
  repository-owned WordPress plugin, documentation, tasks, and future CI/CD.
- `~/Sites/hsetraining-cms` is the local WordPress runtime location and must not
  be committed to this repository.

The WordPress runtime is installed at `~/Sites/hsetraining-cms`. Its Nginx
virtual host listens on loopback only at `127.0.0.1:80`, and `/etc/hosts` maps
`cms.hsetraining.test` to that address. Astro has not been created or installed.

## Installed Toolchain

| Tool | Verified version | Notes |
|---|---|---|
| Homebrew | 6.0.20 | Existing installation; `brew doctor` reports ready |
| Git | 2.53.0 | Existing identity preserved; GitHub SSH authentication verified |
| PHP CLI | 8.4.25 | Versioned Homebrew formula selected on `PATH` |
| PHP-FPM | 8.4.25 | Homebrew service; listens on `127.0.0.1:9000` |
| MySQL | 8.4.11 | LTS formula; listens on `127.0.0.1:3306` only |
| Nginx | 1.31.4 | Root-owned service for port 80; CMS virtual host is loopback-only |
| Composer | 2.10.3 | Official verified PHAR; executes with PHP 8.4.25 |
| WP-CLI | 2.12.0 | Official checksum-verified PHAR; executes with PHP 8.4.25 |
| fnm | 1.39.0 | Initialized from `~/.zshrc` |
| Node.js | 24.20.0 LTS | Installed and selected through `fnm` |
| npm | 11.19.0 | Bundled with the selected Node.js release |

PHP includes the WordPress-relevant modules `curl`, `dom`, `exif`, `fileinfo`,
`filter`, `hash`, `intl`, `json`, `libxml`, `mbstring`, `mysqli`, `openssl`,
`pcre`, `PDO`, `pdo_mysql`, `session`, `SimpleXML`, `sodium`, `xml`, and `zip`.
No extra PHP extension packages were needed.

Composer and WP-CLI intentionally use the official PHAR installation methods.
The current Homebrew formula for each depends on generic PHP 8.5, so installing
those formulae would introduce the wrong project PHP runtime.

The machine also retains a pre-existing Homebrew Node 25 installation. A new
zsh session places the fnm-managed Node 24 LTS executable first on `PATH`; the
older installation was not removed because removal was outside this task.

## Shell Initialization

`~/.zshrc` contains the versioned PHP/MySQL paths and the official zsh `fnm`
initialization:

```zsh
export PATH="$(brew --prefix php@8.4)/bin:$(brew --prefix php@8.4)/sbin:$(brew --prefix mysql@8.4)/bin:$PATH"
eval "$(fnm env --use-on-cd --shell zsh)"
```

Open a new terminal, or run `source ~/.zshrc`, after changing the shell file.
When the Astro project is created, add a repository Node version declaration so
`fnm` can switch versions automatically on directory changes.

## Service Commands

Inspect all Homebrew services:

```sh
brew services list
```

Manage PHP-FPM:

```sh
brew services start php@8.4
brew services restart php@8.4
brew services stop php@8.4
```

Manage MySQL:

```sh
brew services start mysql@8.4
brew services restart mysql@8.4
brew services stop mysql@8.4
mysqladmin ping
```

Manage Nginx:

```sh
sudo brew services start nginx
sudo brew services restart nginx
sudo brew services stop nginx
sudo nginx -t
```

Nginx runs as a root-owned Homebrew service because macOS reserves port 80 for
privileged processes. The worker processes run as the local macOS account so
PHP and WordPress files remain accessible without broadening filesystem
permissions.

Useful configuration locations can be derived without hardcoding the Homebrew
prefix:

```sh
brew --prefix php@8.4
brew --prefix mysql@8.4
brew --prefix nginx
php --ini
php-fpm -tt
```

## Current Services and Ports

The native services are active and configured to restart at login:

- PHP-FPM: `127.0.0.1:9000`
- MySQL 8.4: `127.0.0.1:3306`
- Nginx CMS virtual host: `127.0.0.1:80`
- Nginx default Homebrew server: port 8080

PostgreSQL 15 was already installed and running locally on port 5432 before this
task. It was neither installed nor reconfigured here; application use remains
deferred until the commerce/payment domain needs it.

OrbStack and DDEV from an earlier setup have been uninstalled. The old DDEV
configuration and the pre-existing WordPress configuration/core directories
were archived outside the web root under `~/Sites/.hsetraining-cms-ddev-backup-*`.
The native runtime has no dependency on DDEV or OrbStack.

## Local WordPress Runtime

The local CMS is a clean WordPress 7.1 installation:

- Site: `http://cms.hsetraining.test`
- Admin: `http://cms.hsetraining.test/wp-admin/`
- REST API index: `http://cms.hsetraining.test/wp-json/`
- REST content types: `http://cms.hsetraining.test/wp-json/wp/v2/types`
- Runtime directory: `~/Sites/hsetraining-cms`
- Nginx virtual host: `/opt/homebrew/etc/nginx/servers/hsetraining-cms.conf`
- Database: `hsetraining_cms`
- Database user: `hsetraining_wp@127.0.0.1`, scoped to that database only
- Admin username: `hse_local_admin`
- Permalink structure: `/%postname%/`
- Environment type: `local`

The database and WordPress admin passwords are strong generated values stored
in macOS Keychain. They are intentionally absent from this repository and its
documentation. Use the Keychain Access application to retrieve them when
needed; the matching service names are `com.hsetraining.local.mysql` and
`com.hsetraining.local.wordpress`.

`wp-config.php` is outside Git and readable only by the local account. It enables
debug logging while keeping errors out of HTTP responses. Review
`~/Sites/hsetraining-cms/wp-content/debug.log` when diagnosing local failures.

Common WP-CLI checks:

```sh
cd ~/Sites/hsetraining-cms
wp core version
wp core verify-checksums
wp rewrite list
wp plugin list
wp option get home
wp option get siteurl
```

The installation was verified through a real browser and authenticated HTTP
session: the frontend, admin dashboard, CSS/JavaScript assets, REST API, and
sample pretty-permalink page all load successfully. Requests for `wp-config.php`
and dotfiles are denied by Nginx.

## Deferred Work

- Do not scaffold Astro yet.
- Do not connect to production services or create production credentials.

## Official References

- [Homebrew PHP 8.4 formula](https://formulae.brew.sh/formula/php@8.4)
- [Homebrew MySQL 8.4 formula](https://formulae.brew.sh/formula/mysql@8.4)
- [Homebrew Nginx formula](https://formulae.brew.sh/formula/nginx)
- [Composer installation](https://getcomposer.org/download/)
- [WP-CLI installation](https://make.wordpress.org/cli/handbook/guides/installing/)
- [WP-CLI core commands](https://developer.wordpress.org/cli/commands/core/)
- [WP-CLI rewrite structure](https://developer.wordpress.org/cli/commands/rewrite/structure/)
- [WordPress debugging](https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/)
- [Nginx `try_files`](https://nginx.org/en/docs/http/ngx_http_core_module.html#try_files)
- [Nginx FastCGI module](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html)
- [fnm zsh setup](https://github.com/Schniz/fnm#shell-setup)
- [Node.js release status](https://nodejs.org/en/about/previous-releases)
- [Astro Node.js requirements](https://docs.astro.build/en/install-and-setup/)
