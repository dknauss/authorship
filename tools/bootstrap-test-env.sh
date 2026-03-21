#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/tests/.env"

force=false

usage() {
	cat <<'EOF'
Usage: ./tools/bootstrap-test-env.sh [--force]

Writes tests/.env for the local PHPUnit/WP-CLI test database.

Environment variable overrides:
  WP_TESTS_DB_NAME
  WP_TESTS_DB_USER
  WP_TESTS_DB_PASS
  WP_TESTS_DB_HOST

Behavior:
  - Respects explicit WP_TESTS_DB_* environment variables first.
  - Otherwise tries to detect a single running Docker mysql/mariadb container
    with a published 3306/tcp port and root password.
  - Falls back to the defaults from tests/.env.dist.

Use --force to overwrite an existing tests/.env.
EOF
}

for arg in "$@"; do
	case "$arg" in
		--force)
			force=true
			;;
		-h|--help)
			usage
			exit 0
			;;
		*)
			echo "Unknown argument: $arg" >&2
			usage >&2
			exit 2
			;;
	esac
done

if [[ -f "${ENV_FILE}" && "${force}" != "true" ]]; then
	echo "Refusing to overwrite existing ${ENV_FILE}. Use --force to replace it." >&2
	exit 1
fi

default_db_name="wordpress_test"
default_db_user="root"
default_db_pass=""
default_db_host="localhost"

detect_docker_mysql() {
	if ! command -v docker >/dev/null 2>&1; then
		return
	fi

	local mysql_container_names
	mysql_container_names="$(
		docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'mysql|mariadb' || true
	)"

	if [[ -z "${mysql_container_names}" ]]; then
		return
	fi

	if [[ "$(printf '%s\n' "${mysql_container_names}" | wc -l | tr -d ' ')" -ne 1 ]]; then
		return
	fi

	local container env_lines detected_port detected_name detected_pass
	container="${mysql_container_names}"
	detected_port="$(
		docker inspect \
			--format '{{range $port, $bindings := .NetworkSettings.Ports}}{{if eq $port "3306/tcp"}}{{(index $bindings 0).HostPort}}{{end}}{{end}}' \
			"${container}" 2>/dev/null || true
	)"

	if [[ -z "${detected_port}" ]]; then
		return
	fi

	env_lines="$(
		docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' "${container}" 2>/dev/null || true
	)"
	detected_name="$(printf '%s\n' "${env_lines}" | awk -F= '$1=="MYSQL_DATABASE"{print $2}' | head -n1)"
	detected_pass="$(printf '%s\n' "${env_lines}" | awk -F= '$1=="MYSQL_ROOT_PASSWORD"{print $2}' | head -n1)"

	default_db_host="127.0.0.1:${detected_port}"

	if [[ -n "${detected_name}" ]]; then
		default_db_name="${detected_name}_test"
	fi

	if [[ -n "${detected_pass}" ]]; then
		default_db_pass="${detected_pass}"
	fi
}

detect_docker_mysql

db_name="${WP_TESTS_DB_NAME:-${default_db_name}}"
db_user="${WP_TESTS_DB_USER:-${default_db_user}}"
db_pass="${WP_TESTS_DB_PASS:-${default_db_pass}}"
db_host="${WP_TESTS_DB_HOST:-${default_db_host}}"

cat > "${ENV_FILE}" <<EOF
WP_TESTS_DB_NAME="${db_name}"
WP_TESTS_DB_USER="${db_user}"
WP_TESTS_DB_PASS="${db_pass}"
WP_TESTS_DB_HOST="${db_host}"
EOF

echo "Wrote ${ENV_FILE}"
echo "  WP_TESTS_DB_NAME=${db_name}"
echo "  WP_TESTS_DB_USER=${db_user}"
echo "  WP_TESTS_DB_HOST=${db_host}"

if ! command -v mysql >/dev/null 2>&1 && [[ -x /opt/homebrew/opt/mysql-client/bin/mysql ]]; then
	echo
	echo 'mysql is not currently on PATH.'
	echo 'For wp db reset/db check commands, add:'
	echo '  export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"'
fi
