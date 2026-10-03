#!/usr/bin/env bash

set -u

umask 077

SITE_ROOT="${HSE_WP_SITE_ROOT:-/home/sbb22122/cms.hsetraining.rs}"
OPS_ROOT="${HSE_WP_OPS_ROOT:-/home/sbb22122/.hse-ops}"
STATE_ROOT="${OPS_ROOT}/state"
LOG_ROOT="${OPS_ROOT}/logs"
WP_CLI="${HSE_WP_CLI:-/usr/local/bin/wp}"
TIMEOUT_BIN="${HSE_TIMEOUT_BIN:-/usr/bin/timeout}"
MAX_LOG_BYTES="${HSE_MAX_LOG_BYTES:-1048576}"

export PATH="/usr/local/bin:/usr/bin:/bin"
export LANG="C.UTF-8"

if [[ ! -d "${SITE_ROOT}" ]]; then
	echo "WordPress root does not exist: ${SITE_ROOT}" >&2
	exit 66
fi

if [[ ! -x "${WP_CLI}" || ! -x "${TIMEOUT_BIN}" ]]; then
	echo "Required command is unavailable." >&2
	exit 69
fi

mkdir -p "${STATE_ROOT}" "${LOG_ROOT}"

LOG_FILE="${LOG_ROOT}/general.log"
HEARTBEAT_FILE="${STATE_ROOT}/general.heartbeat"

if [[ -f "${LOG_FILE}" ]]; then
	log_size="$(wc -c < "${LOG_FILE}")"
	if [[ "${log_size}" =~ ^[0-9]+$ ]] && (( log_size > MAX_LOG_BYTES )); then
		mv -f "${LOG_FILE}" "${LOG_FILE}.previous"
	fi
fi

started_at="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
started_epoch="$(date +%s)"
status=0

record_status() {
	local command_status="$1"

	if (( command_status != 0 )) && (( status == 0 )); then
		status="${command_status}"
	fi
}

run_with_timeout() {
	local timeout_seconds="$1"
	shift

	"${TIMEOUT_BIN}" --signal=TERM --kill-after=15s "${timeout_seconds}" \
		"${WP_CLI}" --path="${SITE_ROOT}" --no-color "$@" >> "${LOG_FILE}" 2>&1
	local command_status=$?
	record_status "${command_status}"
	return 0
}

printf '[%s] mode=general start\n' "${started_at}" >> "${LOG_FILE}"

# Action Scheduler is executed directly below, so exclude its WP-Cron bridge.
run_with_timeout 180 cron event run --due-now \
	--exclude=action_scheduler_run_queue --quiet
run_with_timeout 180 action-scheduler run \
	--batch-size=25 --batches=1 --quiet

finished_at="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
finished_epoch="$(date +%s)"
duration_seconds="$(( finished_epoch - started_epoch ))"
heartbeat_tmp="${HEARTBEAT_FILE}.tmp.$$"

{
	printf 'mode=general\n'
	printf 'started_at=%s\n' "${started_at}"
	printf 'finished_at=%s\n' "${finished_at}"
	printf 'duration_seconds=%s\n' "${duration_seconds}"
	printf 'status=%s\n' "${status}"
} > "${heartbeat_tmp}"
mv -f "${heartbeat_tmp}" "${HEARTBEAT_FILE}"

printf '[%s] mode=general finish status=%s duration_seconds=%s\n' \
	"${finished_at}" "${status}" "${duration_seconds}" >> "${LOG_FILE}"

exit "${status}"
