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

const ESCTT_IMPORTANT_MESSAGE_POST_TYPE = 'esctt_important';
const ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META = '_esctt_important_detail_url';
const ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META = '_esctt_important_detail_label';

function esctt_register_important_message(): void
{
    register_post_type(ESCTT_IMPORTANT_MESSAGE_POST_TYPE, [
        'labels' => [
            'name' => __('Messages importants', 'esctt-content'),
            'singular_name' => __('Message important', 'esctt-content'),
            'add_new_item' => __('Ajouter un message important', 'esctt-content'),
            'edit_item' => __('Modifier le message important', 'esctt-content'),
            'new_item' => __('Nouveau message important', 'esctt-content'),
            'view_item' => __('Voir le message important', 'esctt-content'),
            'search_items' => __('Rechercher des messages importants', 'esctt-content'),
            'not_found' => __('Aucun message important trouvé.', 'esctt-content'),
            'menu_name' => __('Messages importants', 'esctt-content'),
        ],
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'supports' => ['title'],
        'rewrite' => false,
        'query_var' => false,
    ]);
}

function esctt_important_message_meta_auth($allowed, string $meta_key, int $post_id, int $user_id): bool
{
    return user_can($user_id, 'edit_post', $post_id);
}

function esctt_register_important_message_meta(): void
{
    register_post_meta(ESCTT_IMPORTANT_MESSAGE_POST_TYPE, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'esc_url_raw',
        'auth_callback' => 'esctt_important_message_meta_auth',
    ]);

    register_post_meta(ESCTT_IMPORTANT_MESSAGE_POST_TYPE, ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => 'esctt_important_message_meta_auth',
    ]);
}

function esctt_register_important_message_meta_box(): void
{
    add_meta_box(
        'esctt-important-message-details',
        __('Détails du message', 'esctt-content'),
        'esctt_render_important_message_meta_box',
        ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'normal',
        'default',
    );
}

function esctt_render_important_message_meta_box(WP_Post $post): void
{
    wp_nonce_field('esctt_save_important_message', 'esctt_important_message_nonce');
    $detailUrl = (string) get_post_meta($post->ID, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META, true);
    $detailLabel = (string) get_post_meta($post->ID, ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META, true);
    ?>
    <p>
        <strong><?php esc_html_e('Activation', 'esctt-content'); ?></strong>
        <?php esc_html_e('Publiez ce message pour l’afficher. Un seul message publié est conservé à la fois ; publier celui-ci désactive les autres.', 'esctt-content'); ?>
        <?php esc_html_e('Repassez-le en brouillon pour le désactiver.', 'esctt-content'); ?>
    </p>
    <p>
        <label for="esctt-important-detail-url"><?php esc_html_e('Lien de détail (facultatif)', 'esctt-content'); ?></label>
        <input id="esctt-important-detail-url" name="esctt_important_detail_url" type="url" value="<?php echo esc_attr($detailUrl); ?>" class="widefat" autocomplete="url">
    </p>
    <p>
        <label for="esctt-important-detail-label"><?php esc_html_e('Libellé accessible du lien', 'esctt-content'); ?></label>
        <input id="esctt-important-detail-label" name="esctt_important_detail_label" type="text" value="<?php echo esc_attr($detailLabel); ?>" class="widefat">
    </p>
    <p class="description">
        <?php esc_html_e('Si aucun libellé n’est fourni, un libellé accessible par défaut sera utilisé.', 'esctt-content'); ?>
    </p>
    <?php
}

function esctt_can_save_important_message(int $post_id): bool
{
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
        return false;
    }

    if (! current_user_can('edit_post', $post_id)
        || ! isset($_POST['esctt_important_message_nonce'])
        || ! is_string($_POST['esctt_important_message_nonce'])) {
        return false;
    }

    return wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['esctt_important_message_nonce'])),
        'esctt_save_important_message',
    ) !== false;
}

function esctt_save_important_message(int $post_id): void
{
    if (! esctt_can_save_important_message($post_id)) {
        return;
    }

    $detailUrl = isset($_POST['esctt_important_detail_url']) && is_string($_POST['esctt_important_detail_url'])
        ? esc_url_raw(wp_unslash($_POST['esctt_important_detail_url']))
        : '';
    $detailLabel = isset($_POST['esctt_important_detail_label']) && is_string($_POST['esctt_important_detail_label'])
        ? sanitize_text_field(wp_unslash($_POST['esctt_important_detail_label']))
        : '';

    if ($detailUrl === '') {
        delete_post_meta($post_id, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META);
        delete_post_meta($post_id, ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META);

        return;
    }

    update_post_meta($post_id, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META, $detailUrl);
    update_post_meta(
        $post_id,
        ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META,
        $detailLabel !== '' ? $detailLabel : __('En savoir plus sur cette information', 'esctt-content'),
    );
}

function esctt_deactivate_other_important_messages(int $post_id, WP_Post $post): void
{
    static $updating = false;

    if ($updating
        || $post->post_type !== ESCTT_IMPORTANT_MESSAGE_POST_TYPE
        || $post->post_status !== 'publish') {
        return;
    }

    $otherMessages = get_posts([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'publish',
        'post__not_in' => [$post_id],
        'posts_per_page' => -1,
    ]);

    if ($otherMessages === []) {
        return;
    }

    $updating = true;

    foreach ($otherMessages as $otherMessage) {
        wp_update_post([
            'ID' => $otherMessage->ID,
            'post_status' => 'draft',
        ]);
    }

    $updating = false;
}

function esctt_get_active_important_message(): ?WP_Post
{
    $messages = get_posts([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'orderby' => 'modified',
        'order' => 'DESC',
    ]);

    return $messages[0] ?? null;
}

/**
 * @return array{message: string, detail_url: string, detail_label: ?string}|null
 */
function esctt_get_active_important_message_data(): ?array
{
    $post = esctt_get_active_important_message();

    if (! $post instanceof WP_Post) {
        return null;
    }

    $message = trim(get_the_title($post));

    if ($message === '') {
        return null;
    }

    $detailUrl = esc_url_raw((string) get_post_meta($post->ID, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META, true));
    $detailLabel = sanitize_text_field((string) get_post_meta($post->ID, ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META, true));

    return [
        'message' => $message,
        'detail_url' => $detailUrl,
        'detail_label' => $detailUrl === ''
            ? null
            : ($detailLabel !== '' ? $detailLabel : __('En savoir plus sur cette information', 'esctt-content')),
    ];
}

add_action('init', 'esctt_register_important_message', 5);
add_action('init', 'esctt_register_important_message_meta', 6);
add_action('add_meta_boxes', 'esctt_register_important_message_meta_box');
add_action('save_post_' . ESCTT_IMPORTANT_MESSAGE_POST_TYPE, 'esctt_save_important_message');
add_action('save_post_' . ESCTT_IMPORTANT_MESSAGE_POST_TYPE, 'esctt_deactivate_other_important_messages', 20, 2);
