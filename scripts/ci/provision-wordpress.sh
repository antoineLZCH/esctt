#!/usr/bin/env bash
set -Eeuo pipefail

root_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"

: "${WP_HOME:?WP_HOME is required}"
: "${DB_NAME:?DB_NAME is required}"
: "${DB_USER:?DB_USER is required}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"
: "${DB_HOST:?DB_HOST is required}"

wp_env=(
    WP_ENV
    WP_HOME
    WP_SITEURL
    DB_NAME
    DB_USER
    DB_PASSWORD
    DB_HOST
    AUTH_KEY
    SECURE_AUTH_KEY
    LOGGED_IN_KEY
    NONCE_KEY
    AUTH_SALT
    SECURE_AUTH_SALT
    LOGGED_IN_SALT
    NONCE_SALT
)

docker_env=()
for variable in "${wp_env[@]}"; do
    docker_env+=(--env "$variable")
done

wp=(
    docker run --rm --network host
    "${docker_env[@]}"
    --volume "$root_dir:/var/www/html"
    --workdir /var/www/html/apps/wordpress
    wordpress:cli-php8.4
    wp
)

if ! "${wp[@]}" core is-installed --skip-plugins --skip-themes; then
    admin_password="$(php -r 'echo bin2hex(random_bytes(16));')"
    "${wp[@]}" core install \
        --url="$WP_HOME" \
        --title='ES Colombienne Tennis de table' \
        --admin_user=ci \
        --admin_password="$admin_password" \
        --admin_email=ci@example.test \
        --skip-email \
        --skip-plugins \
        --skip-themes
fi

"${wp[@]}" theme activate esctt --skip-plugins
"${wp[@]}" plugin activate secure-custom-fields esctt-content --skip-themes
