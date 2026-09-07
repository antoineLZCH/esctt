#!/usr/bin/env bash
set -Eeuo pipefail

root_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
coverage_dir="$root_dir/apps/wordpress/build/coverage"
mkdir -p "$coverage_dir"

cd "$root_dir/apps/wordpress"
ESCTT_WORDPRESS_TESTS=1 XDEBUG_MODE=coverage vendor/bin/pest \
    --coverage \
    --path-coverage \
    --coverage-html="$coverage_dir/html" \
    --min=100 \
    --coverage-clover="$coverage_dir/clover.xml"

php "$root_dir/scripts/check-php-coverage.php" "$coverage_dir/clover.xml"
