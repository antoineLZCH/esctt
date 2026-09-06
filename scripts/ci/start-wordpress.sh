#!/usr/bin/env bash
set -Eeuo pipefail

root_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$root_dir"

: "${WP_HOME:?WP_HOME is required}"
log_file="${RUNNER_TEMP:-/tmp}/esctt-wordpress.log"
pid_file="${RUNNER_TEMP:-/tmp}/esctt-wordpress.pid"

php -S 127.0.0.1:8080 -t apps/wordpress/web apps/wordpress/server.php >"$log_file" 2>&1 &
echo "$!" >"$pid_file"

for _ in {1..30}; do
    if php -r '$url = getenv("WP_HOME"); exit(@file_get_contents($url) === false ? 1 : 0);'; then
        exit 0
    fi

    if ! kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        cat "$log_file"
        exit 1
    fi

    sleep 1
done

cat "$log_file"
exit 1
