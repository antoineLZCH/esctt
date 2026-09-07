<?php

function legacy_urls_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

function legacy_urls_page(string $title, string $slug, int $parent = 0): int
{
    return wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_parent' => $parent,
        'post_content' => '<!-- wp:esctt/hero {"title":"Page","lock":{"move":true,"remove":true}} /-->',
    ], true);
}

function legacy_urls_enable_pretty_permalinks(): string
{
    global $wp_rewrite;

    $previous = (string) get_option('permalink_structure');
    $wp_rewrite->set_permalink_structure('/%postname%/');
    flush_rewrite_rules(false);

    return $previous;
}

function legacy_urls_restore_permalinks(string $permalinkStructure): void
{
    global $wp_rewrite;

    $wp_rewrite->set_permalink_structure($permalinkStructure);
    flush_rewrite_rules(false);
}

test('a published page slug change records its old hierarchical URL', function () {
    legacy_urls_load_wordpress();

    $previousPermalinkStructure = legacy_urls_enable_pretty_permalinks();
    $parentId = legacy_urls_page('Parent page', 'legacy-parent-' . wp_generate_password(6, false, false));
    $childId = legacy_urls_page('Child page', 'legacy-child-' . wp_generate_password(6, false, false), $parentId);
    $oldPath = wp_parse_url(get_permalink($childId), PHP_URL_PATH);

    try {
        expect($parentId)->toBeInt()
            ->and($childId)->toBeInt()
            ->and($oldPath)->toBeString();

        wp_update_post([
            'ID' => $childId,
            'post_name' => 'new-child-' . wp_generate_password(6, false, false),
        ]);

        $redirects = get_posts([
            'post_type' => ESCTT_REDIRECT_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => ESCTT_REDIRECT_SOURCE_META,
            'meta_value' => esctt_redirect_source_path((string) $oldPath),
            'fields' => 'ids',
        ]);

        expect($redirects)->toHaveCount(1)
            ->and((int) get_post_meta($redirects[0], ESCTT_REDIRECT_TARGET_META, true))->toBe($childId);
    } finally {
        wp_delete_post($childId, true);
        wp_delete_post($parentId, true);
        legacy_urls_restore_permalinks($previousPermalinkStructure);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the verified legacy inventory registers only current destinations', function () {
    legacy_urls_load_wordpress();

    expect(esctt_inventoried_redirects())->toBe([
        '/accueil/' => 'front',
        '/politique-de-confidentialite/' => 'confidentialite',
    ]);

    esctt_register_inventoried_redirects();
    $frontPageId = (int) get_option('page_on_front');
    $redirect = esctt_find_legacy_redirect('/accueil/');

    expect($frontPageId)->toBeGreaterThan(0)
        ->and($redirect)->toMatchArray([
            'target_id' => $frontPageId,
            'url' => (string) get_permalink($frontPageId),
        ]);
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the redirect registry accepts internal paths but never external targets', function () {
    legacy_urls_load_wordpress();

    $redirectType = get_post_type_object(ESCTT_REDIRECT_POST_TYPE);

    expect($redirectType)->not->toBeFalse()
        ->and($redirectType->show_ui)->toBeTrue()
        ->and($redirectType->show_in_rest)->toBeTrue()
        ->and(esctt_redirect_source_path('/ancienne-page/?tracking=1'))->toBe('/ancienne-page')
        ->and(esctt_redirect_source_path('https://evil.example/ancienne-page/'))->toBe('')
        ->and(esctt_redirect_source_path('//evil.example/ancienne-page/'))->toBe('')
        ->and(esctt_redirect_location('/nouvelle-page/#contact', '/ancienne-page/?utm_source=legacy'))->toBe('/nouvelle-page/?utm_source=legacy#contact')
        ->and(esctt_redirect_location('/nouvelle-page/', '/ancienne-page/'))->toBe('/nouvelle-page/');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the redirect registry covers invalid paths, admin fields and redirect edge cases', function () {
    legacy_urls_load_wordpress();

    $previousPermalinkStructure = legacy_urls_enable_pretty_permalinks();
    $targetId = legacy_urls_page('Redirect target', 'redirect-target-' . wp_generate_password(6, false, false));
    $loopAId = legacy_urls_page('Redirect loop A', 'redirect-loop-a-' . wp_generate_password(6, false, false));
    $loopBId = legacy_urls_page('Redirect loop B', 'redirect-loop-b-' . wp_generate_password(6, false, false));
    $redirectId = wp_insert_post([
        'post_type' => ESCTT_REDIRECT_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => 'Redirect fixture',
    ], true);
    $draftId = wp_insert_post([
        'post_type' => ESCTT_REDIRECT_POST_TYPE,
        'post_status' => 'draft',
        'post_title' => 'Draft redirect fixture',
    ], true);
    $ancestorId = legacy_urls_page('Redirect ancestor', 'redirect-ancestor-' . wp_generate_password(6, false, false));
    $descendantId = legacy_urls_page('Redirect descendant', 'redirect-descendant-' . wp_generate_password(6, false, false), $ancestorId);
    $cycleAId = legacy_urls_page('Redirect cycle A', 'redirect-cycle-a-' . wp_generate_password(6, false, false));
    $cycleBId = legacy_urls_page('Redirect cycle B', 'redirect-cycle-b-' . wp_generate_password(6, false, false), $cycleAId);
    $revisionId = wp_insert_post([
        'post_type' => 'revision',
        'post_parent' => $redirectId,
        'post_status' => 'inherit',
        'post_title' => 'Redirect revision fixture',
    ], true);
    if (is_wp_error($revisionId)) {
        throw new RuntimeException('Unable to create redirect revision fixture.');
    }
    $loopAPath = esctt_page_path($loopAId);
    $loopBPath = esctt_page_path($loopBId);
    esctt_upsert_page_redirect($loopBPath, $loopAId);
    $loopBRedirect = esctt_find_legacy_redirect($loopBPath);
    $loopARedirectId = wp_insert_post([
        'post_type' => ESCTT_REDIRECT_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => 'Redirect loop A fixture',
    ], true);
    if (is_wp_error($loopARedirectId)) {
        throw new RuntimeException('Unable to create redirect loop fixture.');
    }
    update_post_meta($loopARedirectId, ESCTT_REDIRECT_SOURCE_META, $loopAPath);
    update_post_meta($loopARedirectId, ESCTT_REDIRECT_TARGET_META, $loopBId);
    global $wpdb;
    $wpdb->update($wpdb->posts, ['post_parent' => $cycleBId], ['ID' => $cycleAId]);
    clean_post_cache($cycleAId);
    $originalPost = $_POST;
    $originalUserId = get_current_user_id();
    wp_set_current_user(1);

    try {
        esctt_register_redirect_model();
        do_action('add_meta_boxes_' . ESCTT_REDIRECT_POST_TYPE);

        ob_start();
        esctt_render_redirect_meta_box(get_post($redirectId));
        $metaBox = ob_get_clean();

        expect(esctt_redirect_source_path(''))->toBe('')
            ->and(esctt_redirect_source_path("/null\0path"))->toBe('')
            ->and(esctt_redirect_source_path('relative/path'))->toBe('')
            ->and(esctt_redirect_path_from_url('https://evil.example/path'))->toBe('')
            ->and(esctt_redirect_path_from_url('http://'))->toBe('')
            ->and(esctt_redirect_path_from_url('http://user:pass@127.0.0.1/path'))->toBe('')
            ->and(esctt_redirect_path_from_url('http://127.0.0.1:9999/path'))->toBe('')
            ->and(esctt_page_path(0))->toBe('')
            ->and(esctt_redirect_target_is_valid(0))->toBeFalse()
            ->and(esctt_find_legacy_redirect(''))->toBeNull()
            ->and(esctt_page_is_descendant($descendantId, $ancestorId))->toBeTrue()
            ->and(esctt_page_is_descendant($cycleAId, 999999))->toBeFalse()
            ->and(esctt_redirect_would_loop('', 0))->toBeFalse()
            ->and(esctt_redirect_would_loop('/unrelated-source', $targetId))->toBeFalse()
            ->and(esctt_redirect_would_loop($loopAPath, $loopBId))->toBeTrue()
            ->and(esctt_redirect_would_loop('/unrelated-source', $loopAId))->toBeTrue()
            ->and($loopBRedirect)->not->toBeNull()
            ->and(esctt_can_save_redirect($revisionId))->toBeFalse()
            ->and($metaBox)->toContain('esctt-redirect-source')
            ->and($metaBox)->toContain('esctt-redirect-target');

        esctt_upsert_page_redirect('', $targetId);
        esctt_upsert_page_redirect('/invalid-target/', 0);
        esctt_upsert_page_redirect(esctt_page_path($targetId), $targetId);
        esctt_upsert_page_redirect($loopAPath, $loopBId);
        $forceInsertError = '__return_true';
        add_filter('wp_insert_post_empty_content', $forceInsertError);
        esctt_upsert_page_redirect('/forced-insert-error', $targetId);
        remove_filter('wp_insert_post_empty_content', $forceInsertError);

        update_post_meta($draftId, ESCTT_REDIRECT_SOURCE_META, '/draft-source');
        update_post_meta($draftId, ESCTT_REDIRECT_TARGET_META, $targetId);
        esctt_upsert_page_redirect('/draft-source', $targetId);

        $_POST = [
            'esctt_redirect_nonce' => wp_create_nonce('esctt_save_redirect'),
            'esctt_redirect_source' => '/manual-source/',
            'esctt_redirect_target' => (string) $targetId,
        ];
        expect(esctt_can_save_redirect($redirectId))->toBeTrue();
        esctt_save_redirect($redirectId);

        expect(get_post_meta($redirectId, ESCTT_REDIRECT_SOURCE_META, true))->toBe('/manual-source')
            ->and((int) get_post_meta($redirectId, ESCTT_REDIRECT_TARGET_META, true))->toBe($targetId);

        $_POST = [];
        expect(esctt_can_save_redirect($redirectId))->toBeFalse();
        $_POST = ['esctt_redirect_nonce' => wp_create_nonce('esctt_save_redirect')];
        esctt_save_redirect($redirectId);

        $_POST = [
            'esctt_redirect_nonce' => wp_create_nonce('esctt_save_redirect'),
            'esctt_redirect_source' => '/invalid-target',
            'esctt_redirect_target' => '0',
        ];
        esctt_save_redirect($redirectId);

        update_post_meta($redirectId, ESCTT_REDIRECT_SOURCE_META, '/manual-source');
        update_post_meta($redirectId, ESCTT_REDIRECT_TARGET_META, $targetId);
        $_POST = [
            'esctt_redirect_nonce' => wp_create_nonce('esctt_save_redirect'),
            'esctt_redirect_source' => 'https://evil.example/path',
            'esctt_redirect_target' => (string) $targetId,
        ];
        esctt_save_redirect($redirectId);
        expect(get_post_meta($redirectId, ESCTT_REDIRECT_SOURCE_META, true))->toBe('');

        update_post_meta($redirectId, ESCTT_REDIRECT_SOURCE_META, '/invalid-target');
        update_post_meta($redirectId, ESCTT_REDIRECT_TARGET_META, 0);
        expect(esctt_find_legacy_redirect('/invalid-target'))->toBeNull();

        update_post_meta($redirectId, ESCTT_REDIRECT_SOURCE_META, esctt_page_path($targetId));
        update_post_meta($redirectId, ESCTT_REDIRECT_TARGET_META, $targetId);
        expect(esctt_find_legacy_redirect(esctt_page_path($targetId)))->toBeNull()
            ->and(esctt_redirect_location('/new/?existing=1#fragment', '/old/?utm_source=legacy'))->toBe('/new/?existing=1&utm_source=legacy#fragment');
    } finally {
        $_POST = $originalPost;
        wp_set_current_user($originalUserId);
        wp_delete_post($redirectId, true);
        wp_delete_post($draftId, true);
        wp_delete_post($targetId, true);
        wp_delete_post($ancestorId, true);
        wp_delete_post($descendantId, true);
        wp_delete_post($cycleAId, true);
        wp_delete_post($cycleBId, true);
        wp_delete_post($revisionId, true);
        wp_delete_post((int) ($loopBRedirect['post_id'] ?? 0), true);
        wp_delete_post($loopARedirectId, true);
        wp_delete_post($loopAId, true);
        wp_delete_post($loopBId, true);
        legacy_urls_restore_permalinks($previousPermalinkStructure);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);


test('successive page slug changes resolve every old URL to the current page', function () {
    legacy_urls_load_wordpress();

    $previousPermalinkStructure = legacy_urls_enable_pretty_permalinks();
    $parentId = legacy_urls_page('Successive parent', 'successive-parent-' . wp_generate_password(6, false, false));
    $childId = legacy_urls_page('Successive child', 'successive-child-' . wp_generate_password(6, false, false), $parentId);
    $redirectIds = [];

    try {
        $firstPath = (string) wp_parse_url(get_permalink($childId), PHP_URL_PATH);
        wp_update_post([
            'ID' => $childId,
            'post_name' => 'successive-child-one-' . wp_generate_password(6, false, false),
        ]);
        $secondPath = (string) wp_parse_url(get_permalink($childId), PHP_URL_PATH);
        wp_update_post([
            'ID' => $childId,
            'post_name' => 'successive-child-two-' . wp_generate_password(6, false, false),
        ]);
        $currentUrl = (string) get_permalink($childId);

        $firstRedirect = esctt_find_legacy_redirect($firstPath . '?utm_source=legacy');
        $secondRedirect = esctt_find_legacy_redirect($secondPath);
        $redirectIds = get_posts([
            'post_type' => ESCTT_REDIRECT_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => ESCTT_REDIRECT_TARGET_META,
            'meta_value' => $childId,
            'fields' => 'ids',
        ]);

        expect($firstRedirect)->toMatchArray(['target_id' => $childId, 'url' => $currentUrl])
            ->and($secondRedirect)->toMatchArray(['target_id' => $childId, 'url' => $currentUrl])
            ->and($firstRedirect['url'])->not->toBe($secondPath)
            ->and($redirectIds)->toHaveCount(2)
            ->and(esctt_redirect_location($firstRedirect['url'], $firstPath . '?utm_source=legacy'))->toBe($currentUrl . '?utm_source=legacy');
    } finally {
        foreach ($redirectIds as $redirectId) {
            wp_delete_post((int) $redirectId, true);
        }
        wp_delete_post($childId, true);
        wp_delete_post($parentId, true);
        legacy_urls_restore_permalinks($previousPermalinkStructure);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('changing a page parent records old paths for the page and its descendants', function () {
    legacy_urls_load_wordpress();

    $previousPermalinkStructure = legacy_urls_enable_pretty_permalinks();
    $parentId = legacy_urls_page('Parent before', 'parent-before-' . wp_generate_password(6, false, false));
    $childId = legacy_urls_page('Child before', 'child-before-' . wp_generate_password(6, false, false), $parentId);
    $oldParentPath = (string) wp_parse_url(get_permalink($parentId), PHP_URL_PATH);
    $oldChildPath = (string) wp_parse_url(get_permalink($childId), PHP_URL_PATH);
    $redirectIds = [];

    try {
        wp_update_post([
            'ID' => $parentId,
            'post_name' => 'parent-after-' . wp_generate_password(6, false, false),
        ]);

        $parentRedirect = esctt_find_legacy_redirect($oldParentPath);
        $childRedirect = esctt_find_legacy_redirect($oldChildPath);
        $redirectIds = get_posts([
            'post_type' => ESCTT_REDIRECT_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => ESCTT_REDIRECT_TARGET_META,
                    'value' => [$parentId, $childId],
                    'compare' => 'IN',
                ],
            ],
            'fields' => 'ids',
        ]);

        expect($parentRedirect['target_id'])->toBe($parentId)
            ->and($childRedirect['target_id'])->toBe($childId)
            ->and($parentRedirect['url'])->toBe((string) get_permalink($parentId))
            ->and($childRedirect['url'])->toBe((string) get_permalink($childId))
            ->and($redirectIds)->toHaveCount(2);
    } finally {
        foreach ($redirectIds as $redirectId) {
            wp_delete_post((int) $redirectId, true);
        }
        wp_delete_post($childId, true);
        wp_delete_post($parentId, true);
        legacy_urls_restore_permalinks($previousPermalinkStructure);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
