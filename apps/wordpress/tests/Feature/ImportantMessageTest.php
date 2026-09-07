<?php

function important_message_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

function important_message_admin_id(): int
{
    $admins = get_users([
        'role' => 'administrator',
        'number' => 1,
    ]);

    return (int) $admins[0]->ID;
}

test('important messages expose an admin-managed WordPress model', function () {
    important_message_load_wordpress();

    $postType = get_post_type_object(ESCTT_IMPORTANT_MESSAGE_POST_TYPE);
    $messageId = wp_insert_post([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'draft',
        'post_title' => 'Message administrable',
    ], true);

    try {
        expect($postType)->toBeInstanceOf(WP_Post_Type::class)
            ->and($postType->public)->toBeFalse()
            ->and($postType->show_ui)->toBeTrue()
            ->and($postType->show_in_rest)->toBeTrue()
            ->and(post_type_supports(ESCTT_IMPORTANT_MESSAGE_POST_TYPE, 'title'))->toBeTrue()
            ->and($messageId)->toBeInt()
            ->and(esctt_important_message_meta_auth(true, 'detail_url', $messageId, important_message_admin_id()))->toBeTrue();

        do_action('add_meta_boxes', ESCTT_IMPORTANT_MESSAGE_POST_TYPE, get_post($messageId));

        ob_start();
        esctt_render_important_message_meta_box(get_post($messageId));
        $metaBox = ob_get_clean();

        expect($metaBox)->toContain('esctt_important_detail_url')
            ->and($metaBox)->toContain('esctt_important_detail_label')
            ->and($metaBox)->toContain('Un seul message publié');
    } finally {
        if (is_int($messageId)) {
            wp_delete_post($messageId, true);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('important messages activate and deactivate as one public message', function () {
    important_message_load_wordpress();

    $first = wp_insert_post([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'draft',
        'post_title' => 'Première information',
    ], true);
    $second = wp_insert_post([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'draft',
        'post_title' => 'Deuxième information',
    ], true);

    try {
        expect($first)->toBeInt()
            ->and($second)->toBeInt();

        wp_update_post(['ID' => $first, 'post_status' => 'publish']);

        expect(esctt_get_active_important_message()?->ID)->toBe($first);

        wp_update_post(['ID' => $second, 'post_status' => 'publish']);

        expect(get_post_status($first))->toBe('draft')
            ->and(get_post_status($second))->toBe('publish')
            ->and(esctt_get_active_important_message()?->ID)->toBe($second)
            ->and(get_posts([
                'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ]))->toHaveCount(1);

        wp_update_post(['ID' => $second, 'post_status' => 'draft']);

        expect(esctt_get_active_important_message())->toBeNull()
            ->and((new \App\View\Composers\App())->importantMessage())->toBeNull();
    } finally {
        if (is_int($first)) {
            wp_delete_post($first, true);
        }

        if (is_int($second)) {
            wp_delete_post($second, true);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('active important messages expose an optional labelled detail link', function () {
    important_message_load_wordpress();

    $emptyMessageId = wp_insert_post([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => '',
    ], true);

    try {
        expect($emptyMessageId)->toBeInt()
            ->and((new \App\View\Composers\App())->importantMessage())->toBeNull();
    } finally {
        if (is_int($emptyMessageId)) {
            wp_delete_post($emptyMessageId, true);
        }
    }

    $messageId = wp_insert_post([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => 'Information avec détail',
    ], true);

    try {
        expect($messageId)->toBeInt();

        $composer = new \App\View\Composers\App();
        expect($composer->importantMessage())->toBe([
            'message' => 'Information avec détail',
            'detail_url' => '',
            'detail_label' => null,
        ]);

        update_post_meta($messageId, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META, 'https://example.test/detail');

        expect($composer->importantMessage())->toBe([
            'message' => 'Information avec détail',
            'detail_url' => 'https://example.test/detail',
            'detail_label' => 'En savoir plus sur cette information',
        ]);

        update_post_meta($messageId, ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META, 'Voir le détail');

        expect($composer->importantMessage())->toBe([
            'message' => 'Information avec détail',
            'detail_url' => 'https://example.test/detail',
            'detail_label' => 'Voir le détail',
        ]);
    } finally {
        if (is_int($messageId)) {
            wp_delete_post($messageId, true);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('important message fields validate admin saves', function () {
    important_message_load_wordpress();

    $adminId = important_message_admin_id();
    wp_set_current_user($adminId);
    $messageId = wp_insert_post([
        'post_type' => ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
        'post_status' => 'draft',
        'post_title' => 'Message à valider',
    ], true);
    $originalPost = $_POST;

    try {
        expect($messageId)->toBeInt();

        $_POST = [];
        expect(esctt_can_save_important_message($messageId))->toBeFalse();

        $_POST = ['esctt_important_message_nonce' => 'invalid'];
        expect(esctt_can_save_important_message($messageId))->toBeFalse();

        $_POST = ['esctt_important_message_nonce' => ['invalid']];
        expect(esctt_can_save_important_message($messageId))->toBeFalse();

        wp_set_current_user(0);
        $_POST = ['esctt_important_message_nonce' => wp_create_nonce('esctt_save_important_message')];
        expect(esctt_can_save_important_message($messageId))->toBeFalse();

        wp_set_current_user($adminId);
        $_POST = [
            'esctt_important_message_nonce' => wp_create_nonce('esctt_save_important_message'),
            'esctt_important_detail_url' => 'https://example.test/validated',
            'esctt_important_detail_label' => 'Lire le détail',
        ];
        expect(esctt_can_save_important_message($messageId))->toBeTrue();
        esctt_save_important_message($messageId);

        expect(get_post_meta($messageId, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META, true))->toBe('https://example.test/validated')
            ->and(get_post_meta($messageId, ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META, true))->toBe('Lire le détail');

        $_POST = [
            'esctt_important_message_nonce' => wp_create_nonce('esctt_save_important_message'),
            'esctt_important_detail_url' => ['not-a-string'],
            'esctt_important_detail_label' => ['not-a-string'],
        ];
        esctt_save_important_message($messageId);

        expect(get_post_meta($messageId, ESCTT_IMPORTANT_MESSAGE_DETAIL_URL_META, true))->toBe('');

        $_POST = [
            'esctt_important_message_nonce' => wp_create_nonce('esctt_save_important_message'),
            'esctt_important_detail_url' => 'https://example.test/default-label',
        ];
        esctt_save_important_message($messageId);

        expect(get_post_meta($messageId, ESCTT_IMPORTANT_MESSAGE_DETAIL_LABEL_META, true))->toBe('En savoir plus sur cette information');

        $revisionParent = wp_insert_post([
            'post_type' => 'post',
            'post_status' => 'draft',
            'post_title' => 'Revision parent',
        ], true);
        $revisionId = is_int($revisionParent) ? wp_save_post_revision($revisionParent) : false;
        expect($revisionId)->toBeInt()
            ->and(esctt_can_save_important_message($revisionId))->toBeFalse();

        if (! defined('DOING_AUTOSAVE')) {
            define('DOING_AUTOSAVE', true);
        }

        expect(esctt_can_save_important_message($messageId))->toBeFalse();
    } finally {
        $_POST = $originalPost;
        wp_set_current_user(0);

        if (is_int($revisionParent ?? null)) {
            wp_delete_post($revisionParent, true);
        }

        if (is_int($messageId)) {
            wp_delete_post($messageId, true);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
