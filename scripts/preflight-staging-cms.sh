#!/usr/bin/env bash

set -Eeuo pipefail

account="${1:-}"
source_root="${2:-}"
target_root="${3:-}"
target_domain="${4:-}"

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

[[ "$account" =~ ^[a-z0-9]+$ ]] || fail "Invalid cPanel account name."
[[ "$source_root" == "/home/${account}/"* ]] || fail "Source root is outside the cPanel account."
[[ "$target_root" == "/home/${account}/"* ]] || fail "Target root is outside the cPanel account."
[[ "$target_domain" =~ ^[a-z0-9.-]+$ ]] || fail "Invalid staging CMS domain."

printf 'Staging CMS preflight\n'
printf 'source_root=%s\n' "$source_root"
printf 'target_root=%s\n' "$target_root"
printf 'target_domain=%s\n' "$target_domain"

for command_name in php wp rsync mysqldump mysql uapi cpapi2 crontab flock timeout; do
  if command -v "$command_name" >/dev/null 2>&1; then
    printf 'command:%s=available\n' "$command_name"
  else
    printf 'command:%s=missing\n' "$command_name"
  fi
done

if [[ -x /usr/local/cpanel/bin/cpapi2 ]]; then
  printf 'command:/usr/local/cpanel/bin/cpapi2=available\n'
else
  printf 'command:/usr/local/cpanel/bin/cpapi2=missing\n'
fi

[[ -d "$source_root" ]] || fail "Source WordPress root does not exist."
[[ -f "$source_root/wp-config.php" ]] || fail "Source wp-config.php does not exist."

if [[ -e "$target_root" ]]; then
  printf 'target_root_state=present\n'
else
  printf 'target_root_state=missing\n'
fi

wp --path="$source_root" core is-installed --quiet || fail "Source WordPress installation is not healthy."
printf 'source_wordpress_version=%s\n' "$(wp --path="$source_root" core version)"
printf 'source_siteurl=%s\n' "$(wp --path="$source_root" option get siteurl)"
printf 'source_home=%s\n' "$(wp --path="$source_root" option get home)"
printf 'source_hse_plugin_version=%s\n' "$(wp --path="$source_root" plugin get hse-headless --field=version 2>/dev/null || printf 'missing')"
printf 'source_order_count=%s\n' "$(wp --path="$source_root" wc shop_order list --format=count 2>/dev/null || printf 'unavailable')"

table_prefix="$(wp --path="$source_root" config get table_prefix 2>/dev/null || true)"
[[ "$table_prefix" =~ ^[A-Za-z0-9_]+$ ]] || fail "Unable to determine a safe WordPress table prefix."
printf 'source_table_prefix=%s\n' "$table_prefix"

printf 'source_environment_constants=' 
wp --path="$source_root" config list --fields=name --format=csv 2>/dev/null \
  | tail -n +2 \
  | grep -E '^(WP_ENVIRONMENT_TYPE|HSE_[A-Z0-9_]+)$' \
  | sort \
  | paste -sd, - || true
printf '\n'

printf 'source_commerce_option_names=' 
wp --path="$source_root" db query \
  "SELECT option_name FROM ${table_prefix}options WHERE option_name LIKE '%raiaccept%' OR option_name LIKE '%bokapos%' ORDER BY option_name" \
  --skip-column-names 2>/dev/null \
  | paste -sd, - || true
printf '\n'

domain_json="$(uapi --output=json DomainInfo list_domains)"
DOMAIN_JSON="$domain_json" TARGET_DOMAIN="$target_domain" php -r '
  $payload = json_decode((string) getenv("DOMAIN_JSON"), true);
  if (!is_array($payload) || 1 !== (int) ($payload["result"]["status"] ?? 0)) {
      fwrite(STDERR, "ERROR: Unable to list cPanel domains.\n");
      exit(1);
  }
  $needle = strtolower((string) getenv("TARGET_DOMAIN"));
  $found = false;
  $walk = static function ($value) use (&$walk, &$found, $needle): void {
      if (is_string($value) && strtolower($value) === $needle) {
          $found = true;
          return;
      }
      if (is_array($value)) {
          foreach ($value as $item) {
              $walk($item);
          }
      }
  };
  $walk($payload["result"]["data"] ?? array());
  echo "target_domain_state=" . ($found ? "present" : "missing") . PHP_EOL;
'

if uapi --output=json Mysql get_restrictions >/tmp/hse-staging-mysql-restrictions.json 2>/dev/null; then
  MYSQL_RESTRICTIONS_JSON="$(cat /tmp/hse-staging-mysql-restrictions.json)" php -r '
    $payload = json_decode((string) getenv("MYSQL_RESTRICTIONS_JSON"), true);
    echo "mysql_restrictions_api=" . (1 === (int) ($payload["result"]["status"] ?? 0) ? "available" : "unavailable") . PHP_EOL;
  '
else
  printf 'mysql_restrictions_api=unavailable\n'
fi
rm -f /tmp/hse-staging-mysql-restrictions.json

if uapi --output=json AddonDomain listaddondomains >/tmp/hse-staging-addon-domain.json 2>/dev/null; then
  ADDON_DOMAIN_JSON="$(cat /tmp/hse-staging-addon-domain.json)" php -r '
    $payload = json_decode((string) getenv("ADDON_DOMAIN_JSON"), true);
    echo "uapi_addon_domain_api=" . (1 === (int) ($payload["result"]["status"] ?? 0) ? "available" : "unavailable") . PHP_EOL;
  '
else
  printf 'uapi_addon_domain_api=unavailable\n'
fi
rm -f /tmp/hse-staging-addon-domain.json

cpapi2_binary="$(command -v cpapi2 2>/dev/null || true)"
if [[ -z "$cpapi2_binary" && -x /usr/local/cpanel/bin/cpapi2 ]]; then
  cpapi2_binary=/usr/local/cpanel/bin/cpapi2
fi

if [[ -n "$cpapi2_binary" ]] && "$cpapi2_binary" --output=json AddonDomain listaddondomains >/tmp/hse-staging-cpapi2-addon-domain.json 2>/dev/null; then
  CPAPI2_ADDON_DOMAIN_JSON="$(cat /tmp/hse-staging-cpapi2-addon-domain.json)" php -r '
    $payload = json_decode((string) getenv("CPAPI2_ADDON_DOMAIN_JSON"), true);
    $data = $payload["cpanelresult"]["data"] ?? null;
    echo "cpapi2_addon_domain_api=" . (is_array($data) ? "available" : "unavailable") . PHP_EOL;
  '
else
  printf 'cpapi2_addon_domain_api=unavailable\n'
fi
rm -f /tmp/hse-staging-cpapi2-addon-domain.json

printf 'preflight=passed\n'
