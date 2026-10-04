#!/usr/bin/env bash

set -Eeuo pipefail

account="${1:-}"
source_root="${2:-}"
target_root="${3:-}"
target_domain="${4:-}"
root_domain="${5:-}"
frontend_domain="${6:-}"
database_name="${7:-}"
database_user="${8:-}"
admin_email="${9:-}"
plugin_archive="${10:-}"
config_sanitizer="${11:-}"
security_fragment="${12:-}"
robots_file="${13:-}"

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

database_resource_exists() {
	local resource_type="$1"
	local resource_name="$2"
	local response_file="$3"
	API_RESPONSE_FILE="$response_file" RESOURCE_TYPE="$resource_type" RESOURCE_NAME="$resource_name" php -r '
		$payload = json_decode((string) file_get_contents((string) getenv("API_RESPONSE_FILE")), true);
		if (!is_array($payload) || 1 !== (int) ($payload["result"]["status"] ?? 0)) {
			exit(2);
		}
		$needle = (string) getenv("RESOURCE_NAME");
		$type = (string) getenv("RESOURCE_TYPE");
		$data = $payload["result"]["data"] ?? array();
		$walk = static function ($value) use (&$walk, $needle, $type): bool {
			if (is_string($value)) {
				return $value === $needle;
			}
			if (!is_array($value)) {
				return false;
			}
		foreach ($value as $key => $item) {
			if ((is_string($key) && $key === $needle) || (($key === "database" || $key === "name" || $key === "user") && is_string($item) && $item === $needle)) {
					return true;
				}
				if ($walk($item)) {
					return true;
				}
			}
			return false;
		};
		exit($walk($data) ? 0 : 1);
	'
}

table_exists() {
	local table_name="$1"
	[[ "$(wp --path="$build_root" db query "SHOW TABLES LIKE '${table_name}'" --skip-column-names 2>/dev/null || true)" == "$table_name" ]]
}

[[ "$account" =~ ^[a-z0-9]+$ ]] || fail "Invalid cPanel account name."
[[ "$source_root" == "/home/${account}/"* ]] || fail "Source root is outside the cPanel account."
[[ "$target_root" == "/home/${account}/"* ]] || fail "Target root is outside the cPanel account."
[[ "$target_domain" =~ ^[a-z0-9.-]+$ ]] || fail "Invalid staging CMS domain."
[[ "$root_domain" =~ ^[a-z0-9.-]+$ ]] || fail "Invalid root domain."
[[ "$target_domain" == *."$root_domain" ]] || fail "Staging CMS domain is not a child of the root domain."
[[ "$frontend_domain" =~ ^[a-z0-9.-]+$ ]] || fail "Invalid staging frontend domain."
[[ "$database_name" =~ ^[A-Za-z0-9_]+$ ]] || fail "Invalid database name."
[[ "$database_user" =~ ^[A-Za-z0-9_]+$ ]] || fail "Invalid database user."
[[ "$admin_email" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || fail "Invalid staging administrator email."
[[ -d "$source_root" && -f "$source_root/wp-config.php" ]] || fail "Source WordPress installation is unavailable."
[[ ! -e "$target_root" ]] || fail "Target WordPress root already exists; refusing to overwrite it."
[[ -f "$plugin_archive" ]] || fail "HSE plugin archive is missing."
[[ -f "$config_sanitizer" ]] || fail "Configuration sanitizer is missing."
[[ -f "$security_fragment" ]] || fail "Staging security fragment is missing."
[[ -f "$robots_file" ]] || fail "Staging robots.txt is missing."

source_domain="$(wp --path="$source_root" option get siteurl | sed -E 's#^https?://##; s#/$##')"
[[ "$source_domain" == "dev-cms.hsetraining.rs" ]] || fail "The source is not the isolated dev CMS."
wp --path="$source_root" core is-installed --quiet || fail "Source WordPress installation is not healthy."

domain_response="$(mktemp "/home/${account}/.hse-staging-domain-list.XXXXXX")"
database_response="$(mktemp "/home/${account}/.hse-staging-database-list.XXXXXX")"
user_response="$(mktemp "/home/${account}/.hse-staging-user-list.XXXXXX")"
work_dir="$(mktemp -d "/home/${account}/.hse-staging-cms-build.XXXXXX")"
build_root="${work_dir}/wordpress"
dump_path="${work_dir}/source.sql"
client_config="${work_dir}/mysql-client.cnf"
trap 'rm -f "$domain_response" "$database_response" "$user_response"; rm -rf "$work_dir"' EXIT

uapi --output=json DomainInfo list_domains > "$domain_response"
set +e
TARGET_DOMAIN="$target_domain" API_RESPONSE_FILE="$domain_response" php -r '
	$payload = json_decode((string) file_get_contents((string) getenv("API_RESPONSE_FILE")), true);
	if (!is_array($payload) || 1 !== (int) ($payload["result"]["status"] ?? 0)) {
		exit(2);
	}
	$needle = strtolower((string) getenv("TARGET_DOMAIN"));
	$walk = static function ($value) use (&$walk, $needle): bool {
		if (is_string($value)) {
			return strtolower($value) === $needle;
		}
		if (is_array($value)) {
			foreach ($value as $item) {
				if ($walk($item)) {
					return true;
				}
			}
		}
		return false;
	};
	exit($walk($payload["result"]["data"] ?? array()) ? 0 : 1);
'
domain_state=$?
set -e
[[ "$domain_state" -ne 0 ]] || fail "Target domain already exists; refusing to overwrite it."
[[ "$domain_state" -eq 1 ]] || fail "Unable to inspect existing cPanel domains."

uapi --output=json Mysql list_databases > "$database_response"
set +e
database_resource_exists database "$database_name" "$database_response"
database_state=$?
set -e
if [[ "$database_state" -eq 2 ]]; then
	fail "Unable to inspect existing databases."
fi

uapi --output=json Mysql list_users > "$user_response"
set +e
database_resource_exists user "$database_user" "$user_response"
user_state=$?
set -e
if [[ "$user_state" -eq 2 ]]; then
	fail "Unable to inspect existing database users."
fi

if [[ "$database_state" -eq 0 || "$user_state" -eq 0 ]]; then
	printf 'Removing resources left by the incomplete staging provisioning attempt...\n'
	if [[ "$database_state" -eq 0 ]]; then
		uapi --output=json Mysql delete_database name="$database_name" > "${work_dir}/delete-database.json"
		api_succeeded "${work_dir}/delete-database.json" || fail "cPanel could not remove the incomplete staging database."
	fi
	if [[ "$user_state" -eq 0 ]]; then
		uapi --output=json Mysql delete_user name="$database_user" > "${work_dir}/delete-user.json"
		api_succeeded "${work_dir}/delete-user.json" || fail "cPanel could not remove the incomplete staging database user."
	fi
fi

database_password="$(php -r 'echo bin2hex(random_bytes(24));')"

printf 'Creating the isolated staging database...\n'
uapi --output=json Mysql create_database name="$database_name" > "${work_dir}/create-database.json"
api_succeeded "${work_dir}/create-database.json" || fail "cPanel could not create the staging database."
uapi --output=json Mysql create_user name="$database_user" password="$database_password" > "${work_dir}/create-user.json"
api_succeeded "${work_dir}/create-user.json" || fail "cPanel could not create the staging database user."
uapi --output=json Mysql set_privileges_on_database user="$database_user" database="$database_name" privileges='ALL PRIVILEGES' > "${work_dir}/set-privileges.json"
api_succeeded "${work_dir}/set-privileges.json" || fail "cPanel could not grant staging database privileges."

cat > "$client_config" <<EOF
[client]
user=${database_user}
password=${database_password}
host=localhost
EOF
chmod 600 "$client_config"

printf 'Copying the dev CMS database and files into a private build directory...\n'
source_db_name="$(wp --path="$source_root" config get DB_NAME)"
source_db_user="$(wp --path="$source_root" config get DB_USER)"
source_db_password="$(wp --path="$source_root" config get DB_PASSWORD)"
source_db_host="$(wp --path="$source_root" config get DB_HOST)"
[[ "$source_db_name" =~ ^[A-Za-z0-9_]+$ ]] || fail "Invalid source database name."
[[ "$source_db_user" =~ ^[A-Za-z0-9_]+$ ]] || fail "Invalid source database user."

source_host_name="$source_db_host"
source_host_arguments=()
if [[ "$source_db_host" =~ ^([^:]+):([0-9]+)$ ]]; then
	source_host_name="${BASH_REMATCH[1]}"
	source_host_arguments+=(--port="${BASH_REMATCH[2]}")
fi

MYSQL_PWD="$source_db_password" mysqldump \
	--host="$source_host_name" \
	"${source_host_arguments[@]}" \
	--user="$source_db_user" \
	--single-transaction \
	--quick \
	--skip-lock-tables \
	--default-character-set=utf8mb4 \
	"$source_db_name" > "$dump_path"
chmod 600 "$dump_path"
[[ -s "$dump_path" ]] || fail "The source database export is empty."
mysql --defaults-extra-file="$client_config" "$database_name" < "$dump_path"

install -d -m 750 "$build_root"
rsync -a \
	--exclude='.well-known/' \
	--exclude='error_log' \
	--exclude='wp-content/cache/' \
	--exclude='wp-content/litespeed/' \
	--exclude='wp-content/upgrade/' \
	--exclude='wp-content/uploads/wc-logs/' \
	--exclude='wp-content/wflogs/' \
	"${source_root}/" "${build_root}/"

rm -rf "${build_root}/wp-content/plugins/hse-headless"
tar -xzf "$plugin_archive" -C "${build_root}/wp-content/plugins"
php "$config_sanitizer" "${build_root}/wp-config.php"

wp_config=(wp --path="$build_root" config set)
"${wp_config[@]}" DB_NAME "$database_name" --quiet
"${wp_config[@]}" DB_USER "$database_user" --quiet
"${wp_config[@]}" DB_PASSWORD "$database_password" --quiet
"${wp_config[@]}" DB_HOST localhost --quiet
"${wp_config[@]}" WP_HOME "https://${target_domain}" --quiet
"${wp_config[@]}" WP_SITEURL "https://${target_domain}" --quiet
"${wp_config[@]}" WP_ENVIRONMENT_TYPE staging --quiet
"${wp_config[@]}" WP_DEBUG false --raw --quiet
"${wp_config[@]}" WP_DEBUG_LOG false --raw --quiet
"${wp_config[@]}" WP_DEBUG_DISPLAY false --raw --quiet
"${wp_config[@]}" DISABLE_WP_CRON true --raw --quiet
"${wp_config[@]}" HSE_WOOCOMMERCE_STAGING_BRIDGE true --raw --quiet
"${wp_config[@]}" HSE_MONITORING_ENVIRONMENT staging --quiet
"${wp_config[@]}" HSE_CONTENT_DEPLOY_ENABLED false --raw --quiet
"${wp_config[@]}" HSE_COMMERCE_ADMIN_EMAIL "$admin_email" --quiet
"${wp_config[@]}" HSE_COMMERCE_STAGING_ADMIN_EMAIL "$admin_email" --quiet
"${wp_config[@]}" HSE_MAIL_TO "$admin_email" --quiet
"${wp_config[@]}" HSE_CONTACT_ALLOWED_ORIGINS "https://${frontend_domain}" --quiet
"${wp_config[@]}" HSE_PUBLIC_SITE_URL 'https://hsetraining.rs' --quiet
"${wp_config[@]}" HSE_PUBLIC_SITE_DEV_URL 'https://dev.hsetraining.rs' --quiet
"${wp_config[@]}" HSE_PUBLIC_SITE_STAGING_URL "https://${frontend_domain}" --quiet
wp --path="$build_root" config delete HSE_MONITORING_SENTRY_DSN --quiet 2>/dev/null || true
wp --path="$build_root" config delete HSE_CONTENT_DEPLOY_GITHUB_TOKEN --quiet 2>/dev/null || true
wp --path="$build_root" config shuffle-salts --quiet
php -l "${build_root}/wp-config.php" >/dev/null

wp --path="$build_root" core is-installed --quiet || fail "The cloned WordPress database is not healthy."
wp --path="$build_root" search-replace "https://${source_domain}" "https://${target_domain}" --all-tables-with-prefix --precise --skip-columns=guid --quiet
wp --path="$build_root" search-replace "http://${source_domain}" "https://${target_domain}" --all-tables-with-prefix --precise --skip-columns=guid --quiet
wp --path="$build_root" search-replace 'https://dev.hsetraining.rs' "https://${frontend_domain}" --all-tables-with-prefix --precise --skip-columns=guid --quiet
wp --path="$build_root" option update home "https://${target_domain}" --quiet
wp --path="$build_root" option update siteurl "https://${target_domain}" --quiet
wp --path="$build_root" option update admin_email "$admin_email" --quiet
wp --path="$build_root" option delete new_admin_email --quiet 2>/dev/null || true
wp --path="$build_root" option update blog_public 0 --quiet
wp --path="$build_root" option delete raiaccept_auth_tokens_production --quiet 2>/dev/null || true
wp --path="$build_root" option delete raiaccept_auth_tokens_sandbox --quiet 2>/dev/null || true
wp --path="$build_root" option delete cron --quiet 2>/dev/null || true
wp --path="$build_root" option update hse_public_order_number_counter 699999 --quiet

table_prefix="$(wp --path="$build_root" config get table_prefix)"
[[ "$table_prefix" =~ ^[A-Za-z0-9_]+$ ]] || fail "Unable to determine a safe WordPress table prefix."

wp --path="$build_root" db query "DELETE commentmeta FROM ${table_prefix}commentmeta AS commentmeta INNER JOIN ${table_prefix}comments AS comments ON comments.comment_ID = commentmeta.comment_id WHERE comments.comment_type = 'order_note'; DELETE FROM ${table_prefix}comments WHERE comment_type = 'order_note'; DELETE postmeta FROM ${table_prefix}postmeta AS postmeta INNER JOIN ${table_prefix}posts AS posts ON posts.ID = postmeta.post_id WHERE posts.post_type IN ('shop_order', 'shop_order_refund'); DELETE FROM ${table_prefix}posts WHERE post_type IN ('shop_order', 'shop_order_refund');" --quiet

for table_suffix in \
	wc_order_addresses \
	wc_order_operational_data \
	wc_orders_meta \
	wc_orders \
	woocommerce_order_itemmeta \
	woocommerce_order_items \
	woocommerce_sessions \
	actionscheduler_logs \
	actionscheduler_actions \
	actionscheduler_claims \
	actionscheduler_groups; do
	table_name="${table_prefix}${table_suffix}"
	if table_exists "$table_name"; then
		wp --path="$build_root" db query "TRUNCATE TABLE ${table_name}" --quiet
	fi
done

wp --path="$build_root" db query "ALTER TABLE ${table_prefix}posts AUTO_INCREMENT = 700000" --quiet
if table_exists "${table_prefix}wc_orders"; then
	wp --path="$build_root" db query "ALTER TABLE ${table_prefix}wc_orders AUTO_INCREMENT = 700000" --quiet
fi

wp --path="$build_root" transient delete --all --quiet 2>/dev/null || true
wp --path="$build_root" cache flush --quiet 2>/dev/null || true
wp --path="$build_root" plugin deactivate hse-headless --quiet 2>/dev/null || true
wp --path="$build_root" plugin activate hse-headless --quiet

original_htaccess="${work_dir}/wordpress.htaccess"
if [[ -f "${build_root}/.htaccess" ]]; then
	mv "${build_root}/.htaccess" "$original_htaccess"
else
	: > "$original_htaccess"
fi
{
	cat "$security_fragment"
	printf '\n'
	cat "$original_htaccess"
} > "${build_root}/.htaccess"
cp "$robots_file" "${build_root}/robots.txt"
chmod 640 "${build_root}/wp-config.php"

printf 'Creating the staging CMS subdomain and publishing the isolated clone...\n'
uapi --output=json SubDomain addsubdomain domain="${target_domain%%.${root_domain}}" rootdomain="$root_domain" dir="$target_root" disallowdot=1 > "${work_dir}/create-domain.json"
api_succeeded "${work_dir}/create-domain.json" || fail "cPanel could not create the staging CMS subdomain."

install -d -m 755 "$target_root"
rsync -a "${build_root}/" "${target_root}/"
wp --path="$target_root" rewrite flush --hard --quiet

cron_marker='# HSE staging CMS WordPress cron'
existing_crontab="$(crontab -l 2>/dev/null || true)"
if ! grep -Fq "$cron_marker" <<< "$existing_crontab"; then
	{
		printf '%s\n' "$existing_crontab"
		printf '%s\n' "$cron_marker"
		printf '*/5 * * * * flock -n /home/%s/.hse-staging-wp-cron.lock timeout 240 php %s/wp-cron.php >/dev/null 2>&1\n' "$account" "$target_root"
	} | crontab -
fi

uapi --output=json SSL start_autossl_check > "${work_dir}/autossl.json" 2>/dev/null || true

printf 'Verifying staging CMS isolation...\n'
[[ "$(wp --path="$target_root" option get home)" == "https://${target_domain}" ]] || fail "Unexpected staging CMS home URL."
[[ "$(wp --path="$target_root" config get DB_NAME)" == "$database_name" ]] || fail "Staging CMS does not use the isolated database."
[[ "$(wp --path="$target_root" config get WP_ENVIRONMENT_TYPE)" == 'staging' ]] || fail "Unexpected WordPress environment type."
wp --path="$target_root" eval 'exit(defined("HSE_WOOCOMMERCE_STAGING_BRIDGE") && HSE_WOOCOMMERCE_STAGING_BRIDGE === true ? 0 : 1);' >/dev/null || fail "The staging commerce bridge is disabled."
wp --path="$target_root" eval 'exit(defined("HSE_CONTENT_DEPLOY_ENABLED") && HSE_CONTENT_DEPLOY_ENABLED === false ? 0 : 1);' >/dev/null || fail "Production content deploy is enabled on staging."
[[ "$(wp --path="$target_root" plugin get hse-headless --field=version)" == '0.34.0' ]] || fail "Unexpected HSE plugin version."
wp --path="$target_root" core is-installed --quiet || fail "Published staging WordPress installation is not healthy."

printf 'staging_cms_domain=https://%s\n' "$target_domain"
printf 'staging_cms_database=%s\n' "$database_name"
printf 'staging_cms_admin_email=%s\n' "$admin_email"
printf 'staging_cms_plugin_version=%s\n' "$(wp --path="$target_root" plugin get hse-headless --field=version)"
printf 'provision=passed\n'
