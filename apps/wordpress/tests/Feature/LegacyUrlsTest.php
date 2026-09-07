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
