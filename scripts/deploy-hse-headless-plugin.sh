#!/usr/bin/env bash

set -euo pipefail

if [[ $# -ne 4 ]]; then
  echo "Usage: deploy-hse-headless-plugin.sh <wordpress-root> <archive> <sha256> <version>" >&2
  exit 64
fi

wordpress_root=${1%/}
archive_path=$2
expected_sha=$3
expected_version=$4
plugin_root="$wordpress_root/wp-content/plugins/hse-headless"

if [[ ! -d "$wordpress_root/wp-content/plugins" || ! -f "$archive_path" ]]; then
  echo "WordPress or the plugin archive is unavailable." >&2
  exit 1
fi

if [[ ! "$expected_sha" =~ ^[a-f0-9]{64}$ || ! "$expected_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "The expected plugin checksum or version is invalid." >&2
  exit 1
fi

actual_sha=$(sha256sum "$archive_path" | awk '{print $1}')
if [[ "$actual_sha" != "$expected_sha" ]]; then
  echo "The uploaded plugin archive checksum does not match." >&2
  exit 1
fi

wp --path="$wordpress_root" core is-installed

work_dir=$(mktemp -d "$HOME/.hse-plugin-deploy.XXXXXX")
backup_root="$HOME/.hse-plugin-backups"
backup_path="$backup_root/hse-headless-before-${expected_version}-$(date -u +%Y%m%d%H%M%S)"
backup_created=0
replacement_installed=0
was_active=0

mkdir -p "$backup_root"
chmod 700 "$backup_root"

if wp --path="$wordpress_root" plugin is-active hse-headless; then
  was_active=1
fi

cleanup() {
  status=$?

  if [[ $status -ne 0 ]]; then
    if [[ $replacement_installed -eq 1 && -d "$plugin_root" ]]; then
      mv "$plugin_root" "$work_dir/failed-hse-headless"
    fi
    if [[ $backup_created -eq 1 && -d "$backup_path" ]]; then
      mv "$backup_path" "$plugin_root"
    fi
    if [[ $backup_created -eq 1 && $was_active -eq 1 ]]; then
      wp --path="$wordpress_root" plugin activate hse-headless >/dev/null 2>&1 || true
    fi
    if [[ $backup_created -eq 1 ]]; then
      echo "Plugin deployment failed; the previous version was restored." >&2
    else
      echo "Plugin deployment failed before a restorable version was available." >&2
    fi
  fi

  rm -rf "$work_dir"
  rm -f "$archive_path"
  exit "$status"
}
trap cleanup EXIT

unzip -q "$archive_path" -d "$work_dir"
candidate="$work_dir/hse-headless"

if [[ ! -f "$candidate/hse-headless.php" ]]; then
  echo "The archive does not contain the expected plugin root." >&2
  exit 1
fi

if ! grep -Fq "Version: $expected_version" "$candidate/hse-headless.php"; then
  echo "The archive plugin version does not match the requested version." >&2
  exit 1
fi

find "$candidate" -type f -name '*.php' -exec sh -c '
  for php_file do
    php -l "$php_file" >/dev/null || exit 1
  done
' sh {} +

if [[ -d "$plugin_root" ]]; then
  mv "$plugin_root" "$backup_path"
  backup_created=1
fi

mv "$candidate" "$plugin_root"
replacement_installed=1

if [[ $was_active -eq 1 ]]; then
  wp --path="$wordpress_root" plugin activate hse-headless >/dev/null
fi

installed_version=$(wp --path="$wordpress_root" plugin get hse-headless --field=version)
if [[ "$installed_version" != "$expected_version" ]]; then
  echo "WordPress did not load the expected plugin version." >&2
  exit 1
fi

wp --path="$wordpress_root" eval-file "$plugin_root/tests/contact-rest-integration.php"

replacement_installed=0
echo "HSE Headless $expected_version deployed successfully. Backup: $backup_path"
