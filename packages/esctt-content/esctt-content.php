<?php

/**
 * Plugin Name: ESCTT Content
 * Description: Content model for ES Colombienne Tennis de table.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.3
 * Requires Plugins: secure-custom-fields
 * License: GPL-2.0-or-later
 * Text Domain: esctt-content
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const ESCTT_PARTNER_POST_TYPE = 'esctt_partner';
const ESCTT_PARTNER_URL_META = '_esctt_partner_url';

function esctt_register_partner(): void
{
    register_post_type(ESCTT_PARTNER_POST_TYPE, [
        'labels' => [
            'name' => __('Partenaires', 'esctt-content'),
            'singular_name' => __('Partenaire', 'esctt-content'),
            'add_new_item' => __('Ajouter un partenaire', 'esctt-content'),
            'edit_item' => __('Modifier le partenaire', 'esctt-content'),
            'new_item' => __('Nouveau partenaire', 'esctt-content'),
            'view_item' => __('Voir le partenaire', 'esctt-content'),
            'search_items' => __('Rechercher des partenaires', 'esctt-content'),
            'not_found' => __('Aucun partenaire trouvé.', 'esctt-content'),
            'menu_name' => __('Partenaires', 'esctt-content'),
        ],
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title', 'page-attributes'],
        'rewrite' => false,
        'query_var' => false,
    ]);
}

function esctt_partner_meta_auth($allowed, string $meta_key, int $post_id, int $user_id): bool
{
    return user_can($user_id, 'edit_post', $post_id);
}

function esctt_sanitize_partner_url(string $url): string
{
    $url = esc_url_raw(trim($url), ['http', 'https']);
    $parts = wp_parse_url($url);

    if (! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
        return '';
    }

    return $url;
}

function esctt_register_partner_meta(): void
{
    register_post_meta(ESCTT_PARTNER_POST_TYPE, ESCTT_PARTNER_URL_META, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'esctt_sanitize_partner_url',
        'auth_callback' => 'esctt_partner_meta_auth',
    ]);
}

function esctt_register_partner_meta_box(): void
{
    add_meta_box(
        'esctt-partner-details',
        __('Détails du partenaire', 'esctt-content'),
        'esctt_render_partner_meta_box',
        ESCTT_PARTNER_POST_TYPE,
        'normal',
        'default',
    );
}

function esctt_render_partner_meta_box(WP_Post $post): void
{
    wp_nonce_field('esctt_save_partner', 'esctt_partner_nonce');
    $url = (string) get_post_meta($post->ID, ESCTT_PARTNER_URL_META, true);
    ?>
    <p>
        <label for="esctt-partner-url"><?php esc_html_e('Lien du partenaire', 'esctt-content'); ?></label>
        <input id="esctt-partner-url" name="esctt_partner_url" type="url" value="<?php echo esc_attr($url); ?>" class="widefat" autocomplete="url" placeholder="https://…">
    </p>
    <p>
        <label for="esctt-partner-menu-order"><?php esc_html_e('Ordre d’affichage', 'esctt-content'); ?></label>
        <input id="esctt-partner-menu-order" name="esctt_partner_menu_order" type="number" value="<?php echo esc_attr((string) $post->menu_order); ?>" class="small-text" min="0" step="1">
    </p>
    <p class="description">
        <?php esc_html_e('Les liens partenaires sont ouverts dans une nouvelle fenêtre avec une indication accessible. Les partenaires publiés apparaissent dans le bloc Partenaires.', 'esctt-content'); ?>
    </p>
    <?php
}

function esctt_can_save_partner(int $post_id): bool
{
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
        return false;
    }

    if (get_post_type($post_id) !== ESCTT_PARTNER_POST_TYPE
        || ! current_user_can('edit_post', $post_id)
        || ! isset($_POST['esctt_partner_nonce'])
        || ! is_string($_POST['esctt_partner_nonce'])) {
        return false;
    }

    return wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['esctt_partner_nonce'])),
        'esctt_save_partner',
    ) !== false;
}

function esctt_save_partner(int $post_id): void
{
    static $saving = false;

    if ($saving || ! esctt_can_save_partner($post_id)) {
        return;
    }

    $saving = true;

    try {
        $url = isset($_POST['esctt_partner_url']) && is_string($_POST['esctt_partner_url'])
            ? esctt_sanitize_partner_url(wp_unslash($_POST['esctt_partner_url']))
            : '';

        if ($url === '') {
            delete_post_meta($post_id, ESCTT_PARTNER_URL_META);
        } else {
            update_post_meta($post_id, ESCTT_PARTNER_URL_META, $url);
        }

        $order = isset($_POST['esctt_partner_menu_order']) && is_scalar($_POST['esctt_partner_menu_order'])
            ? max(0, (int) $_POST['esctt_partner_menu_order'])
            : 0;
        $currentOrder = (int) get_post_field('menu_order', $post_id);

        if ($currentOrder !== $order) {
            wp_update_post([
                'ID' => $post_id,
                'menu_order' => $order,
            ]);
        }
    } finally {
        $saving = false;
    }
}

/**
 * @return WP_Post[]
 */
function esctt_get_published_partners(): array
{
    return get_posts([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => [
            'menu_order' => 'ASC',
            'title' => 'ASC',
        ],
        'order' => 'ASC',
    ]);
}

add_action('init', 'esctt_register_partner', 5);
add_action('init', 'esctt_register_partner_meta', 6);
add_action('add_meta_boxes_' . ESCTT_PARTNER_POST_TYPE, 'esctt_register_partner_meta_box');
add_action('save_post_' . ESCTT_PARTNER_POST_TYPE, 'esctt_save_partner');
