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

const ESCTT_REGISTRATION_DOCUMENT_POST_TYPE = 'esctt_registration_document';
const ESCTT_REGISTRATION_DOCUMENT_URL_META = '_esctt_registration_document_url';
const ESCTT_REGISTRATION_DOCUMENT_SEASON_META = '_esctt_registration_document_season';
const ESCTT_MEMBERSHIP_DOCUMENTS_POLICY = 'Le site ne collecte ni ne stocke de document d’adhésion.';

/**
 * Explain the site's document boundary.
 */
function esctt_membership_documents_policy(): string
{
    return __(ESCTT_MEMBERSHIP_DOCUMENTS_POLICY, 'esctt-content');
}

/**
 * Return a safe public URL for a season document.
 */
function esctt_registration_document_url(string $url): string
{
    $url = esc_url_raw($url);

    return wp_http_validate_url($url) ? $url : '';
}

/**
 * Return the published documents managed by an Admin.
 *
 * @return array<int, array{title: string, url: string, season: string}>
 */
function esctt_registration_documents(): array
{
    $postIds = get_posts([
        'post_type' => ESCTT_REGISTRATION_DOCUMENT_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => [
            'menu_order' => 'ASC',
            'title' => 'ASC',
        ],
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);
    $documents = [];

    foreach ($postIds as $postId) {
        $title = get_the_title($postId);
        $url = esctt_registration_document_url((string) get_post_meta($postId, ESCTT_REGISTRATION_DOCUMENT_URL_META, true));

        if ($title === '' || $url === '') {
            continue;
        }

        $documents[] = [
            'title' => $title,
            'url' => $url,
            'season' => sanitize_text_field((string) get_post_meta($postId, ESCTT_REGISTRATION_DOCUMENT_SEASON_META, true)),
        ];
    }

    return $documents;
}

/**
 * Register the content model hooks after WordPress is available.
 */
function esctt_content_bootstrap(): void
{
    add_action('init', function (): void {
        register_post_type(ESCTT_REGISTRATION_DOCUMENT_POST_TYPE, [
            'labels' => [
                'name' => __('Documents de saison', 'esctt-content'),
                'singular_name' => __('Document de saison', 'esctt-content'),
                'add_new_item' => __('Ajouter un document de saison', 'esctt-content'),
                'edit_item' => __('Modifier le document de saison', 'esctt-content'),
                'menu_name' => __('Documents de saison', 'esctt-content'),
            ],
            'description' => __('Documents génériques applicables à une saison.', 'esctt-content'),
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-media-document',
            'supports' => ['title', 'page-attributes'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'rewrite' => false,
        ]);

        register_post_meta(ESCTT_REGISTRATION_DOCUMENT_POST_TYPE, ESCTT_REGISTRATION_DOCUMENT_URL_META, [
            'single' => true,
            'type' => 'string',
            'show_in_rest' => true,
            'sanitize_callback' => 'esctt_registration_document_url',
            'auth_callback' => static fn (bool $allowed, string $metaKey, int $postId): bool => current_user_can('edit_post', $postId),
        ]);
        register_post_meta(ESCTT_REGISTRATION_DOCUMENT_POST_TYPE, ESCTT_REGISTRATION_DOCUMENT_SEASON_META, [
            'single' => true,
            'type' => 'string',
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => static fn (bool $allowed, string $metaKey, int $postId): bool => current_user_can('edit_post', $postId),
        ]);
    });

    add_action('add_meta_boxes', function (): void {
        add_meta_box(
            'esctt-registration-document-details',
            __('Document de saison', 'esctt-content'),
            'esctt_render_registration_document_meta_box',
            ESCTT_REGISTRATION_DOCUMENT_POST_TYPE,
            'normal',
            'high',
        );
    });

    add_action('save_post_' . ESCTT_REGISTRATION_DOCUMENT_POST_TYPE, 'esctt_save_registration_document', 10, 2);
}

/**
 * Render the Admin fields for one generic season document.
 */
function esctt_render_registration_document_meta_box(WP_Post $post): void
{
    wp_nonce_field('esctt_registration_document', 'esctt_registration_document_nonce');
    $url = (string) get_post_meta($post->ID, ESCTT_REGISTRATION_DOCUMENT_URL_META, true);
    $season = (string) get_post_meta($post->ID, ESCTT_REGISTRATION_DOCUMENT_SEASON_META, true);
    ?>
    <p><?php esc_html_e('Donnez au document un nom explicite, par exemple « Règlement intérieur — saison 2026–2027 ».', 'esctt-content'); ?></p>
    <p>
        <label for="esctt-registration-document-season"><strong><?php esc_html_e('Saison', 'esctt-content'); ?></strong></label><br>
        <input class="widefat" type="text" id="esctt-registration-document-season" name="esctt_registration_document_season" value="<?php echo esc_attr($season); ?>" placeholder="2026–2027">
    </p>
    <p>
        <label for="esctt-registration-document-url"><strong><?php esc_html_e('URL du document', 'esctt-content'); ?></strong></label><br>
        <input class="widefat" type="url" id="esctt-registration-document-url" name="esctt_registration_document_url" value="<?php echo esc_attr($url); ?>" placeholder="https://…">
    </p>
    <p class="description"><?php echo esc_html(esctt_membership_documents_policy()); ?> <?php esc_html_e('Gérez ici uniquement des documents génériques de saison.', 'esctt-content'); ?></p>
    <?php
}

/**
 * Persist only Admin-managed season metadata; there is no member upload path.
 */
function esctt_save_registration_document(int $postId, WP_Post $post): void
{
    if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
        return;
    }

    if (! current_user_can('edit_post', $postId)) {
        return;
    }

    $nonce = isset($_POST['esctt_registration_document_nonce'])
        ? (string) wp_unslash($_POST['esctt_registration_document_nonce'])
        : '';

    if (! wp_verify_nonce($nonce, 'esctt_registration_document')) {
        return;
    }

    $url = isset($_POST['esctt_registration_document_url'])
        ? esctt_registration_document_url((string) wp_unslash($_POST['esctt_registration_document_url']))
        : '';
    $season = isset($_POST['esctt_registration_document_season'])
        ? sanitize_text_field((string) wp_unslash($_POST['esctt_registration_document_season']))
        : '';

    update_post_meta($postId, ESCTT_REGISTRATION_DOCUMENT_URL_META, $url);
    update_post_meta($postId, ESCTT_REGISTRATION_DOCUMENT_SEASON_META, $season);
}
