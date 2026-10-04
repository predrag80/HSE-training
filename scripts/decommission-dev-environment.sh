#!/usr/bin/env bash

set -Eeuo pipefail

account="${1:-}"
dev_frontend_root="${2:-}"
dev_cms_root="${3:-}"
dev_frontend_domain="${4:-}"
dev_cms_domain="${5:-}"
staging_cms_root="${6:-}"
production_cms_root="${7:-}"
cpanel_host="${8:-}"

fail() {
	printf 'ERROR: %s\n' "$*" >&2
	exit 1
}

api_succeeded() {
	local response_file="$1"
	API_RESPONSE_FILE="$response_file" php -r '
		$payload = json_decode((string) file_get_contents((string) getenv("API_RESPONSE_FILE")), true);
		exit(is_array($payload) && 1 === (int) ($payload["result"]["status"] ?? 0) ? 0 : 1);
	'
}

api2_succeeded() {
	local response_file="$1"
	API_RESPONSE_FILE="$response_file" php -r '
		$payload = json_decode((string) file_get_contents((string) getenv("API_RESPONSE_FILE")), true);
		$result = $payload["cpanelresult"] ?? null;
		if (!is_array($result) || 1 !== (int) ($result["event"]["result"] ?? 0)) {
			exit(1);
		}
		foreach (($result["data"] ?? array()) as $item) {
			if (is_array($item) && 1 === (int) ($item["result"] ?? 0)) {
				exit(0);
			}
		}
		exit(1);
	'
}

resource_exists() {
	local response_file="$1"
	local needle="$2"
	API_RESPONSE_FILE="$response_file" RESOURCE_NAME="$needle" php -r '
		$payload = json_decode((string) file_get_contents((string) getenv("API_RESPONSE_FILE")), true);
		if (!is_array($payload) || 1 !== (int) ($payload["result"]["status"] ?? 0)) {
			exit(2);
		}
		$needle = strtolower((string) getenv("RESOURCE_NAME"));
		$walk = static function ($value) use (&$walk, $needle): bool {
			if (is_string($value) && strtolower($value) === $needle) {
				return true;
			}
			if (!is_array($value)) {
				return false;
			}
			foreach ($value as $key => $item) {
				if (is_string($key) && strtolower($key) === $needle) {
					return true;
				}
				if ($walk($item)) {
					return true;
				}
			}
			return false;
		};
		exit($walk($payload["result"]["data"] ?? array()) ? 0 : 1);
	'
}

remove_configured_origin() {
	local wordpress_root="$1"
	local current_origins
	local filtered_origins

	if ! wp --path="$wordpress_root" config has HSE_CONTACT_ALLOWED_ORIGINS >/dev/null 2>&1; then
		return
	fi

	current_origins="$(wp --path="$wordpress_root" config get HSE_CONTACT_ALLOWED_ORIGINS)"
	filtered_origins="$(CURRENT_ORIGINS="$current_origins" DEV_ORIGIN="https://${dev_frontend_domain}" php -r '
		$items = array_filter(array_map("trim", explode(",", (string) getenv("CURRENT_ORIGINS"))));
		$dev = rtrim((string) getenv("DEV_ORIGIN"), "/");
		$items = array_values(array_filter($items, static fn ($item): bool => rtrim((string) $item, "/") !== $dev));
		echo implode(",", $items);
	')"

	[[ -n "$filtered_origins" ]] || fail "Refusing to leave a CMS without an allowed contact origin."
	wp --path="$wordpress_root" config set HSE_CONTACT_ALLOWED_ORIGINS "$filtered_origins" --quiet
}

[[ "$account" =~ ^[a-z0-9]+$ ]] || fail "Invalid cPanel account name."
[[ "$dev_frontend_root" == "/home/${account}/dev.hsetraining.rs" ]] || fail "Unexpected dev frontend root."
[[ "$dev_cms_root" == "/home/${account}/dev-cms.hsetraining.rs" ]] || fail "Unexpected dev CMS root."
[[ "$staging_cms_root" == "/home/${account}/staging-cms.hsetraining.rs" ]] || fail "Unexpected staging CMS root."
[[ "$production_cms_root" == "/home/${account}/cms.hsetraining.rs" ]] || fail "Unexpected production CMS root."
[[ "$cpanel_host" == 's83.unlimited.rs' ]] || fail "Unexpected cPanel host."
[[ "$dev_frontend_domain" == 'dev.hsetraining.rs' ]] || fail "Unexpected dev frontend domain."
[[ "$dev_cms_domain" == 'dev-cms.hsetraining.rs' ]] || fail "Unexpected dev CMS domain."
[[ -d "$dev_frontend_root" ]] || fail "Dev frontend root is missing."
[[ -d "$dev_cms_root" && -f "$dev_cms_root/wp-config.php" ]] || fail "Dev CMS root is missing."

for protected_root in "$staging_cms_root" "$production_cms_root"; do
	[[ -d "$protected_root" && -f "$protected_root/wp-config.php" ]] || fail "A protected CMS root is missing: ${protected_root}"
	wp --path="$protected_root" core is-installed --quiet || fail "A protected CMS is not healthy: ${protected_root}"
done

wp --path="$dev_cms_root" core is-installed --quiet || fail "Dev CMS is not healthy enough to inventory safely."

dev_database="$(wp --path="$dev_cms_root" config get DB_NAME)"
dev_database_user="$(wp --path="$dev_cms_root" config get DB_USER)"
staging_database="$(wp --path="$staging_cms_root" config get DB_NAME)"
staging_database_user="$(wp --path="$staging_cms_root" config get DB_USER)"
production_database="$(wp --path="$production_cms_root" config get DB_NAME)"
production_database_user="$(wp --path="$production_cms_root" config get DB_USER)"

[[ "$dev_database" =~ ^${account}_[A-Za-z0-9_]+$ ]] || fail "Dev database does not belong to the expected cPanel account."
[[ "$dev_database_user" =~ ^${account}_[A-Za-z0-9_]+$ ]] || fail "Dev database user does not belong to the expected cPanel account."
[[ "$dev_database" != "$staging_database" && "$dev_database" != "$production_database" ]] || fail "Dev shares a protected database."
[[ "$dev_database_user" != "$staging_database_user" && "$dev_database_user" != "$production_database_user" ]] || fail "Dev shares a protected database user."

command -v curl >/dev/null || fail "curl is required for the cPanel API call."

work_dir="$(mktemp -d "/home/${account}/.hse-dev-decommission.XXXXXX")"
cpanel_token_name="hse_dev_rm_$(date +%s)"
cpanel_token=""

cleanup() {
	if [[ -n "$cpanel_token_name" ]]; then
		uapi --output=json Tokens revoke name="$cpanel_token_name" >/dev/null 2>&1 || true
	fi
	rm -rf -- "$work_dir"
}
trap cleanup EXIT

printf 'Dev decommission inventory\n'
printf 'frontend_root=%s\n' "$dev_frontend_root"
printf 'cms_root=%s\n' "$dev_cms_root"
printf 'cms_database=%s\n' "$dev_database"
printf 'cms_database_user=%s\n' "$dev_database_user"
printf 'staging_database=%s\n' "$staging_database"
printf 'production_database=%s\n' "$production_database"

printf 'Removing dev-only cron entries and constants...\n'
existing_crontab="$(crontab -l 2>/dev/null || true)"
printf '%s\n' "$existing_crontab" \
	| awk '!/dev-cms\.hsetraining\.rs/ && !/dev\.hsetraining\.rs/ && !/hse-dev/ && !/HSE dev/' \
	> "${work_dir}/crontab"
crontab "${work_dir}/crontab"

for protected_root in "$staging_cms_root" "$production_cms_root"; do
	remove_configured_origin "$protected_root"
	wp --path="$protected_root" config delete HSE_PUBLIC_SITE_DEV_URL --quiet 2>/dev/null || true
	wp --path="$protected_root" config delete HSE_COMMERCE_DEV_ADMIN_EMAIL --quiet 2>/dev/null || true
	php -l "${protected_root}/wp-config.php" >/dev/null
done

printf 'Removing dev cPanel subdomains...\n'
token_expiry="$(( $(date +%s) + 600 ))"
domain_cleanup_pending=0
set +e
uapi --output=json Tokens create_full_access name="$cpanel_token_name" expires_at="$token_expiry" > "${work_dir}/create-token.json" 2>/dev/null
token_create_status=$?
set -e
chmod 600 "${work_dir}/create-token.json"
if [[ "$token_create_status" -eq 0 ]] && api_succeeded "${work_dir}/create-token.json"; then
	cpanel_token="$(API_RESPONSE_FILE="${work_dir}/create-token.json" php -r '
		$payload = json_decode((string) file_get_contents((string) getenv("API_RESPONSE_FILE")), true);
		echo (string) ($payload["result"]["data"]["token"] ?? "");
	')"
	[[ "$cpanel_token" =~ ^[A-Za-z0-9]+$ ]] || fail "cPanel returned an invalid temporary API token."
	printf 'header = "Authorization: cpanel %s:%s"\n' "$account" "$cpanel_token" > "${work_dir}/curl.conf"
	chmod 600 "${work_dir}/curl.conf"

	for domain in "$dev_frontend_domain" "$dev_cms_domain"; do
		curl --fail --silent --show-error --get \
			--config "${work_dir}/curl.conf" \
			--data-urlencode "cpanel_jsonapi_user=${account}" \
			--data-urlencode 'cpanel_jsonapi_apiversion=2' \
			--data-urlencode 'cpanel_jsonapi_module=SubDomain' \
			--data-urlencode 'cpanel_jsonapi_func=delsubdomain' \
			--data-urlencode "domain=${domain}" \
			"https://${cpanel_host}:2083/json-api/cpanel" > "${work_dir}/delete-${domain}.json"
		api2_succeeded "${work_dir}/delete-${domain}.json" || fail "cPanel API2 could not remove ${domain}."
	done

	uapi --output=json Tokens revoke name="$cpanel_token_name" > "${work_dir}/revoke-token.json"
	api_succeeded "${work_dir}/revoke-token.json" || fail "cPanel could not revoke the temporary API token."
	cpanel_token=""
	cpanel_token_name=""
else
	domain_cleanup_pending=1
	printf 'WARNING: cPanel blocks API token creation over shared-host SSH; domain registrations require an authenticated cPanel session.\n'
fi

printf 'Removing dev database and database user...\n'
uapi --output=json Mysql delete_database name="$dev_database" > "${work_dir}/delete-database.json"
api_succeeded "${work_dir}/delete-database.json" || fail "cPanel could not remove the dev database."
uapi --output=json Mysql delete_user name="$dev_database_user" > "${work_dir}/delete-database-user.json"
api_succeeded "${work_dir}/delete-database-user.json" || fail "cPanel could not remove the dev database user."

printf 'Removing dev document roots and deployment backups...\n'
rm -rf -- "$dev_frontend_root" "$dev_cms_root" "/home/${account}/.hse-astro-backups/dev"
rm -f -- "/home/${account}/.hse-dev-wp-cron.lock" "/home/${account}/.hse-dev-cms-cron.lock"
if [[ -d "/home/${account}/.hse-ops/dev" ]]; then
	rm -rf -- "/home/${account}/.hse-ops/dev"
fi

[[ ! -e "$dev_frontend_root" ]] || fail "Dev frontend root still exists."
[[ ! -e "$dev_cms_root" ]] || fail "Dev CMS root still exists."
wp --path="$staging_cms_root" core is-installed --quiet || fail "Staging CMS failed its post-removal health check."
wp --path="$production_cms_root" core is-installed --quiet || fail "Production CMS failed its post-removal health check."

uapi --output=json DomainInfo list_domains > "${work_dir}/domains.json"
for domain in "$dev_frontend_domain" "$dev_cms_domain"; do
	set +e
	resource_exists "${work_dir}/domains.json" "$domain"
	domain_state=$?
	set -e
	if [[ "$domain_cleanup_pending" -eq 0 ]]; then
		[[ "$domain_state" -eq 1 ]] || fail "Dev domain is still registered in cPanel: ${domain}"
	elif [[ "$domain_state" -eq 0 ]]; then
		printf 'pending_cpanel_domain=%s\n' "$domain"
	elif [[ "$domain_state" -ne 1 ]]; then
		fail "Could not verify the cPanel domain inventory."
	fi
done

uapi --output=json Mysql list_databases > "${work_dir}/databases.json"
set +e
resource_exists "${work_dir}/databases.json" "$dev_database"
database_state=$?
set -e
[[ "$database_state" -eq 1 ]] || fail "Dev database still exists."

uapi --output=json Mysql list_users > "${work_dir}/database-users.json"
set +e
resource_exists "${work_dir}/database-users.json" "$dev_database_user"
database_user_state=$?
set -e
[[ "$database_user_state" -eq 1 ]] || fail "Dev database user still exists."

if crontab -l 2>/dev/null | grep -Eq 'dev-cms\.hsetraining\.rs|dev\.hsetraining\.rs|hse-dev|HSE dev'; then
	fail "A dev cron entry still exists."
fi

printf 'dev_environment=removed\n'
if [[ "$domain_cleanup_pending" -eq 1 ]]; then
	printf 'dev_domain_registrations=pending_authenticated_cpanel_removal\n'
else
	printf 'dev_domain_registrations=removed\n'
fi
printf 'staging_cms=healthy\n'
printf 'production_cms=healthy\n'
