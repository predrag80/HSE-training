#!/usr/bin/env bash

set -euo pipefail

: "${DEPLOY_ENVIRONMENT:?DEPLOY_ENVIRONMENT is required}"
: "${DEPLOY_ROOT:?DEPLOY_ROOT is required}"
: "${DEPLOY_URL:?DEPLOY_URL is required}"
: "${SSH_HOST:?SSH_HOST is required}"
: "${SSH_USER:?SSH_USER is required}"
: "${SSH_PRIVATE_KEY_PATH:?SSH_PRIVATE_KEY_PATH is required}"

SSH_PORT="${SSH_PORT:-22}"
RELEASE_ID="${GITHUB_SHA:-manual}-${GITHUB_RUN_ID:-local}-${GITHUB_RUN_ATTEMPT:-1}"
REMOTE_BACKUP_ROOT=".hse-astro-backups/${DEPLOY_ENVIRONMENT}"
REMOTE_BACKUP_PATH="${REMOTE_BACKUP_ROOT}/${RELEASE_ID}"
curl_options=(
	--fail
	--show-error
	--silent
	--retry 5
	--retry-delay 2
	--output /dev/null
)

if [[ -n "${DEPLOY_HTTP_AUTH_USER:-}" || -n "${DEPLOY_HTTP_AUTH_PASSWORD:-}" ]]; then
	: "${DEPLOY_HTTP_AUTH_USER:?Both HTTP authentication values are required}"
	: "${DEPLOY_HTTP_AUTH_PASSWORD:?Both HTTP authentication values are required}"
	curl_options+=(--user "${DEPLOY_HTTP_AUTH_USER}:${DEPLOY_HTTP_AUTH_PASSWORD}")
fi

case "${DEPLOY_ROOT}" in
	/home/sbb22122/dev.hsetraining.rs|/home/sbb22122/staging.hsetraining.rs|/home/sbb22122/public_html) ;;
	*)
		echo "Refusing to deploy to unexpected root: ${DEPLOY_ROOT}" >&2
		exit 1
		;;
esac

case "${SSH_PORT}" in
	''|*[!0-9]*)
		echo "SSH_PORT must be numeric." >&2
		exit 1
		;;
esac

if [[ ! -f apps/web/dist/index.html ]]; then
	echo "Astro build output is missing." >&2
	exit 1
fi

if [[ "${DEPLOY_ENVIRONMENT}" == "production" && ! -f apps/web/dist/.htaccess ]]; then
	echo "Production security and routing rules are missing from the build output." >&2
	exit 1
fi

ssh_target="${SSH_USER}@${SSH_HOST}"
ssh_options=(
	-i "${SSH_PRIVATE_KEY_PATH}"
	-p "${SSH_PORT}"
	-o IdentitiesOnly=yes
	-o StrictHostKeyChecking=yes
)
rsync_shell="ssh -i ${SSH_PRIVATE_KEY_PATH} -p ${SSH_PORT} -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes"
preserved_paths=(
	--exclude=.htpasswd
	--exclude=.well-known/
	--exclude=cgi-bin/
	--exclude=.user.ini
	--exclude=php.ini
	--exclude=error_log
	--exclude=.ftpquota
)

if [[ "${DEPLOY_ENVIRONMENT}" != "production" ]]; then
	preserved_paths+=(--exclude=.htaccess)
fi

ssh "${ssh_options[@]}" "${ssh_target}" \
	"command -v rsync >/dev/null && test -d '${DEPLOY_ROOT}' && test -w '${DEPLOY_ROOT}'"

previous_site="$(
	ssh "${ssh_options[@]}" "${ssh_target}" \
		"test -n \"\$(find '${DEPLOY_ROOT}' -mindepth 1 -maxdepth 1 -print -quit)\" && printf yes || printf no"
)"
previous_site="${previous_site//[[:space:]]/}"

if [[ "${previous_site}" == "yes" ]]; then
	if [[ "${DEPLOY_ENVIRONMENT}" == "production" ]]; then
		ssh "${ssh_options[@]}" "${ssh_target}" \
			"mkdir -p \"\$HOME/${REMOTE_BACKUP_PATH}\" && rsync -a --delete --exclude='.htpasswd' --exclude='.well-known/' --exclude='cgi-bin/' --exclude='.user.ini' --exclude='php.ini' --exclude='error_log' --exclude='.ftpquota' '${DEPLOY_ROOT}/' \"\$HOME/${REMOTE_BACKUP_PATH}/\""
	else
		ssh "${ssh_options[@]}" "${ssh_target}" \
			"mkdir -p \"\$HOME/${REMOTE_BACKUP_PATH}\" && rsync -a --delete --exclude='.htaccess' --exclude='.htpasswd' --exclude='.well-known/' --exclude='cgi-bin/' --exclude='.user.ini' --exclude='php.ini' --exclude='error_log' --exclude='.ftpquota' '${DEPLOY_ROOT}/' \"\$HOME/${REMOTE_BACKUP_PATH}/\""
	fi
fi

rsync -az --delete-delay --delay-updates \
	"${preserved_paths[@]}" \
	-e "${rsync_shell}" \
	apps/web/dist/ "${ssh_target}:${DEPLOY_ROOT}/"

if curl "${curl_options[@]}" "${DEPLOY_URL}/" \
	&& curl "${curl_options[@]}" "${DEPLOY_URL}/contact/" \
	&& curl "${curl_options[@]}" "${DEPLOY_URL}/resources/"; then
	echo "${DEPLOY_ENVIRONMENT} deployment is healthy: ${RELEASE_ID}"
	exit 0
fi

echo "Smoke tests failed for ${DEPLOY_URL}." >&2
if [[ "${previous_site}" == "yes" ]]; then
	echo "Restoring the previous ${DEPLOY_ENVIRONMENT} release." >&2
	if [[ "${DEPLOY_ENVIRONMENT}" == "production" ]]; then
		ssh "${ssh_options[@]}" "${ssh_target}" \
			"rsync -a --delete --exclude='.htpasswd' --exclude='.well-known/' --exclude='cgi-bin/' --exclude='.user.ini' --exclude='php.ini' --exclude='error_log' --exclude='.ftpquota' \"\$HOME/${REMOTE_BACKUP_PATH}/\" '${DEPLOY_ROOT}/'"
	else
		ssh "${ssh_options[@]}" "${ssh_target}" \
			"rsync -a --delete --exclude='.htaccess' --exclude='.htpasswd' --exclude='.well-known/' --exclude='cgi-bin/' --exclude='.user.ini' --exclude='php.ini' --exclude='error_log' --exclude='.ftpquota' \"\$HOME/${REMOTE_BACKUP_PATH}/\" '${DEPLOY_ROOT}/'"
	fi
else
	echo "Rollback is unavailable because no previous site content existed." >&2
fi
exit 1
