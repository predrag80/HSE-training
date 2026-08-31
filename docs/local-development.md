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
| WordPress | Nginx -> PHP 8.4 / PHP-FPM -> MySQL 8.4 | `http://cms.hsetraining.local` |

The Git repository and runtime are deliberately separate:

- `~/Development/HSE-training` contains Git history, application code, the
  repository-owned WordPress plugin, documentation, tasks, and future CI/CD.
- `~/Sites/hsetraining-cms` is the local WordPress runtime location and must not
  be committed to this repository.

The Nginx virtual host and local-domain routing are deferred to the WordPress
runtime task. The default Homebrew Nginx page currently remains on port 8080.
Astro has not been created or installed.

## Installed Toolchain

| Tool | Verified version | Notes |
|---|---|---|
| Homebrew | 6.0.20 | Existing installation; `brew doctor` reports ready |
| Git | 2.53.0 | Existing identity preserved; GitHub SSH authentication verified |
| PHP CLI | 8.4.25 | Versioned Homebrew formula selected on `PATH` |
| PHP-FPM | 8.4.25 | Homebrew service; listens on `127.0.0.1:9000` |
| MySQL | 8.4.11 | LTS formula; listens on `127.0.0.1:3306` only |
| Nginx | 1.31.4 | Homebrew configuration; default port 8080 |
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
brew services stop php@8.4
```

Manage MySQL:

```sh
brew services start mysql@8.4
brew services stop mysql@8.4
mysqladmin ping
```

Manage Nginx:

```sh
brew services start nginx
brew services stop nginx
nginx -t
```

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
- Nginx: port 8080

PostgreSQL 15 was already installed and running locally on port 5432 before this
task. It was neither installed nor reconfigured here; application use remains
deferred until the commerce/payment domain needs it.

OrbStack and DDEV were installed during an earlier setup, and a WordPress runtime
already exists at `~/Sites/hsetraining-cms`. That pre-existing runtime was not
created, removed, or modified by this environment-preparation task. DDEV has no
running project/container and OrbStack reports `Stopped`, so the HSE native stack
does not depend on either tool.

An OrbStack background process nevertheless still holds ports 80 and 443. This
does not affect Homebrew Nginx on port 8080, but it must be resolved before the
future `cms.hsetraining.local` virtual host can bind to port 80. Do not terminate
or uninstall it without first confirming that no other project uses it.

## Deferred Work

- Do not install or re-install WordPress in this task.
- Do not create the Nginx virtual host or change local hostname routing yet.
- Do not create the WordPress database yet.
- Do not scaffold Astro yet.
- Do not connect to production services or create production credentials.

## Official References

- [Homebrew PHP 8.4 formula](https://formulae.brew.sh/formula/php@8.4)
- [Homebrew MySQL 8.4 formula](https://formulae.brew.sh/formula/mysql@8.4)
- [Homebrew Nginx formula](https://formulae.brew.sh/formula/nginx)
- [Composer installation](https://getcomposer.org/download/)
- [WP-CLI installation](https://make.wordpress.org/cli/handbook/guides/installing/)
- [fnm zsh setup](https://github.com/Schniz/fnm#shell-setup)
- [Node.js release status](https://nodejs.org/en/about/previous-releases)
- [Astro Node.js requirements](https://docs.astro.build/en/install-and-setup/)
