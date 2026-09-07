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

cache_dir="$root_dir/apps/wordpress/web/app/cache"
uploads_dir="$root_dir/apps/wordpress/web/app/uploads"
mkdir -p "$cache_dir/acorn/framework/cache" "$uploads_dir"

wp=(
    docker run --rm
    --user "$(id -u):$(id -g)"
    --network host
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

partner_ids=( $("${wp[@]}" post list --post_type=esctt_partner --name=partner-test --field=ID --format=ids) )
if ((${#partner_ids[@]} == 0)); then
    partner_id=$("${wp[@]}" post create \
        --post_type=esctt_partner \
        --post_status=publish \
        --post_title='Partenaire de test' \
        --post_name=partner-test \
        --menu_order=1 \
        --porcelain)
else
    partner_id="${partner_ids[0]}"
    "${wp[@]}" post update "$partner_id" \
        --post_status=publish \
        --post_title='Partenaire de test' \
        --menu_order=1
fi

"${wp[@]}" post meta update "$partner_id" _esctt_partner_url 'https://partner.example.test/'

home_ids=( $("${wp[@]}" post list --post_type=page --name=accueil --field=ID --format=ids) )
home_content='<!-- wp:esctt/hero {"title":"Accueil","lock":{"move":true,"remove":true}} /--><!-- wp:esctt/partners /-->'
if ((${#home_ids[@]} == 0)); then
    home_id=$("${wp[@]}" post create \
        --post_type=page \
        --post_status=publish \
        --post_title='Accueil' \
        --post_name=accueil \
        --post_content="$home_content" \
        --porcelain)
else
    home_id="${home_ids[0]}"
    "${wp[@]}" post update "$home_id" \
        --post_status=publish \
        --post_title='Accueil' \
        --post_content="$home_content"
fi

"${wp[@]}" option update show_on_front page
"${wp[@]}" option update page_on_front "$home_id"

privacy_ids=( $("${wp[@]}" post list --post_type=page --name=confidentialite --field=ID --format=ids) )
if ((${#privacy_ids[@]} == 0)); then
    privacy_id=$("${wp[@]}" post create \
        --post_type=page \
        --post_status=publish \
        --post_title='Confidentialité' \
        --post_name=confidentialite \
        --post_content='<!-- wp:esctt/hero {"title":"Confidentialité","lock":{"move":true,"remove":true}} /-->' \
        --porcelain)
else
    privacy_id="${privacy_ids[0]}"
fi

important_message_id="$("${wp[@]}" post list --post_type=esctt_important --name=ci-important-message --format=ids)"
important_message_args=(
    --post_title='CI important message'
    --post_name=ci-important-message
    --post_status=publish
    --meta_input='{"_esctt_important_detail_url":"https://example.test/important-message","_esctt_important_detail_label":"Lire les détails du message important"}'
)

if [[ -n "$important_message_id" ]]; then
    "${wp[@]}" post update "$important_message_id" "${important_message_args[@]}"
else
    "${wp[@]}" post create --post_type=esctt_important "${important_message_args[@]}"
fi
