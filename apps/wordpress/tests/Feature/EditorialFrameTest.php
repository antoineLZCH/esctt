<?php

function editorial_frame_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

function editorial_frame_hero(array $attributes = []): string
{
    return sprintf(
        '<!-- wp:esctt/hero %s /-->',
        wp_json_encode(array_merge([
            'title' => 'ES Colombienne',
            'compact' => false,
            'lock' => [
                'move' => true,
                'remove' => true,
            ],
        ], $attributes)),
    );
}

function editorial_frame_block(string $name, string $content = '', array $attributes = []): string
{
    $serializedName = str_starts_with($name, 'core/') ? substr($name, 5) : $name;
    $serializedAttributes = $attributes === [] ? '' : wp_json_encode($attributes) . ' ';

    if ($content === '') {
        return sprintf('<!-- wp:%s %s/-->', $serializedName, $serializedAttributes);
    }

    return sprintf(
        '<!-- wp:%s %s-->%s<!-- /wp:%s -->',
        $serializedName,
        $serializedAttributes,
        $content,
        $serializedName,
    );
}

test('pages expose a locked hero template and a limited block catalogue', function () {
    editorial_frame_load_wordpress();

    $pageArgs = apply_filters('register_post_type_args', ['supports' => []], 'page');
    $otherArgs = apply_filters('register_post_type_args', ['supports' => []], 'post');
    $pageContext = new WP_Block_Editor_Context([
        'post' => new WP_Post((object) ['post_type' => 'page']),
    ]);
    $otherContext = new WP_Block_Editor_Context([
        'post' => new WP_Post((object) ['post_type' => 'post']),
    ]);
    $pageBlocks = apply_filters('allowed_block_types_all', true, $pageContext);
    $otherBlocks = apply_filters('allowed_block_types_all', true, $otherContext);
    $emptyBlocks = apply_filters('allowed_block_types_all', true, new WP_Block_Editor_Context());
    $actualPageBlocks = get_allowed_block_types($pageContext);
    $registeredPage = get_post_type_object('page');

    expect($pageArgs['template'])->toBe([
        [
            'esctt/hero',
            ['lock' => ['move' => true, 'remove' => true]],
        ],
    ])
        ->and($pageArgs['template_lock'])->toBeFalse()
        ->and($otherArgs)->toBe(['supports' => []])
        ->and($pageBlocks)->toContain('esctt/hero', 'core/paragraph', 'core/heading')
        ->and($pageBlocks)->not->toContain('core/html')
        ->and($otherBlocks)->toBeTrue()
        ->and($emptyBlocks)->toBeTrue()
        ->and($actualPageBlocks)->toContain('esctt/hero')
        ->and($registeredPage->template)->toBe($pageArgs['template'])
        ->and($registeredPage->template_lock)->toBeFalse();
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('native page operations cover drafts, previews, publication, navigation and revisions', function () {
    editorial_frame_load_wordpress();

    $pageArgs = apply_filters('register_post_type_args', [
        'supports' => [],
        'show_in_nav_menus' => false,
    ], 'page');

    expect($pageArgs['supports'])->toContain('page-attributes', 'revisions')
        ->and($pageArgs['show_in_nav_menus'])->toBeTrue()
        ->and(get_post_type_object('page')->show_in_nav_menus)->toBeTrue()
        ->and(post_type_supports('page', 'revisions'))->toBeTrue();

    $originalUserId = get_current_user_id();
    $userIds = get_users(['number' => 1, 'fields' => 'ID']);
    $pageId = 0;
    $menuId = 0;
    $previousLocations = get_theme_mod('nav_menu_locations', null);
    $deploymentTriggered = false;
    $deploymentHook = static function () use (&$deploymentTriggered): void {
        $deploymentTriggered = true;
    };
    $originalContent = editorial_frame_hero(['title' => 'Page éditoriale']);
    $revisedContent = editorial_frame_hero(['title' => 'Version publiée']);
    $slug = 'page-editoriale-' . strtolower(wp_generate_password(8, false, false));

    try {
        wp_set_current_user((int) ($userIds[0] ?? 0));
        add_action('esctt_technical_deploy', $deploymentHook);

        $pageId = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => 'Page éditoriale',
            'post_name' => $slug,
            'post_content' => $originalContent,
        ], true);

        expect($pageId)->toBeInt()
            ->and(get_post_status($pageId))->toBe('draft')
            ->and(get_post_field('post_name', $pageId))->toBe($slug)
            ->and(get_preview_post_link($pageId))->toContain('preview=true');

        $menuId = wp_create_nav_menu('Navigation éditoriale ' . wp_generate_password(8, false, false));
        $menuItemId = wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => 'Page éditoriale',
            'menu-item-object' => 'page',
            'menu-item-object-id' => $pageId,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
        ]);
        $locations = is_array($previousLocations) ? $previousLocations : [];
        $locations['primary_navigation'] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);
        $menuItems = wp_get_nav_menu_items($menuId);

        expect($menuItemId)->toBeInt()
            ->and($menuItems)->toHaveCount(1)
            ->and((int) $menuItems[0]->object_id)->toBe($pageId);

        expect(wp_update_post([
            'ID' => $pageId,
            'post_status' => 'publish',
        ], true))->toBe($pageId)
            ->and(get_post_status($pageId))->toBe('publish')
            ->and($deploymentTriggered)->toBeFalse();

        $navigation = wp_nav_menu([
            'theme_location' => 'primary_navigation',
            'echo' => false,
        ]);

        expect($navigation)->toContain(get_permalink($pageId));

        expect(wp_update_post([
            'ID' => $pageId,
            'post_content' => $revisedContent,
        ], true))->toBe($pageId);

        $revisionId = 0;
        foreach (wp_get_post_revisions($pageId) as $revision) {
            if ($revision->post_content === $originalContent) {
                $revisionId = (int) $revision->ID;
                break;
            }
        }

        expect($revisionId)->toBeGreaterThan(0)
            ->and(wp_restore_post_revision($revisionId))->toBe($pageId)
            ->and(get_post_field('post_content', $pageId))->toBe($originalContent);
    } finally {
        remove_action('esctt_technical_deploy', $deploymentHook);
        wp_set_current_user($originalUserId);
        if (is_int($menuId) && $menuId > 0) {
            wp_delete_nav_menu($menuId);
        }
        if (is_int($pageId) && $pageId > 0) {
            wp_delete_post($pageId, true);
        }
        if (null === $previousLocations) {
            remove_theme_mod('nav_menu_locations');
        } else {
            set_theme_mod('nav_menu_locations', $previousLocations);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('published page content is validated at the REST and database seams', function () {
    editorial_frame_load_wordpress();

    $valid = editorial_frame_hero(['compact' => true])
        . editorial_frame_block('core/group', editorial_frame_block('core/heading', '<h2>Horaires</h2>', ['level' => 2]));
    $invalidHero = editorial_frame_block('core/paragraph', '<p>Avant le hero</p>') . editorial_frame_hero();
    $unlockedHero = editorial_frame_hero(['lock' => ['move' => true, 'remove' => false]]);
    $unknownBlock = editorial_frame_hero() . editorial_frame_block('core/html', '<p>Non autorisé</p>');
    $nestedUnknownBlock = editorial_frame_hero()
        . editorial_frame_block('core/group', editorial_frame_block('core/html', '<p>Non autorisé</p>'));
    $duplicateHero = editorial_frame_hero() . editorial_frame_hero();
    $extraHeading = editorial_frame_hero() . editorial_frame_block('core/heading', '<h1>Autre titre</h1>', ['level' => 1]);
    $rawHeading = editorial_frame_hero() . editorial_frame_block('core/paragraph', '<h1>Autre titre</h1>');

    expect(apply_filters('rest_pre_insert_page', (object) [
        'post_status' => 'publish',
        'post_content' => $valid,
    ]))->toBeObject()
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'draft',
            'post_content' => $invalidHero,
        ]))->toBeObject()
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'publish',
            'post_content' => $invalidHero,
        ])->get_error_code())->toBe('esctt_hero_required')
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'publish',
            'post_content' => $unlockedHero,
        ])->get_error_code())->toBe('esctt_hero_locked')
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'publish',
            'post_content' => $unknownBlock,
        ])->get_error_code())->toBe('esctt_block_not_allowed')
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'publish',
            'post_content' => $nestedUnknownBlock,
        ])->get_error_code())->toBe('esctt_block_not_allowed')
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'publish',
            'post_content' => $duplicateHero,
        ])->get_error_code())->toBe('esctt_hero_unique')
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'future',
            'post_content' => $extraHeading,
        ])->get_error_code())->toBe('esctt_h1_unique')
        ->and(apply_filters('rest_pre_insert_page', (object) [
            'post_status' => 'publish',
            'post_content' => $rawHeading,
        ])->get_error_code())->toBe('esctt_h1_unique');

    $invalidContent = apply_filters('wp_insert_post_empty_content', false, [
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_content' => $invalidHero,
    ]);
    $validContent = apply_filters('wp_insert_post_empty_content', false, [
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_content' => $valid,
    ]);
    $draftContent = apply_filters('wp_insert_post_empty_content', false, [
        'post_type' => 'page',
        'post_status' => 'draft',
        'post_content' => $invalidHero,
    ]);
    $postContent = apply_filters('wp_insert_post_empty_content', false, [
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_content' => $invalidHero,
    ]);
    $alreadyEmpty = apply_filters('wp_insert_post_empty_content', true, [
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_content' => $valid,
    ]);

    require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    wp_dequeue_script('wp-dom-ready');
    set_current_screen('post');
    get_current_screen()->is_block_editor(true);
    ob_start();
    do_action('admin_head');
    ob_end_clean();
    $GLOBALS['current_screen'] = null;

    expect($invalidContent)->toBeTrue()
        ->and($validContent)->toBeFalse()
        ->and($draftContent)->toBeFalse()
        ->and($postContent)->toBeFalse()
        ->and($alreadyEmpty)->toBeTrue();

    $postId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Invalid page',
        'post_content' => $invalidHero,
    ], true);

    expect($postId)->toBeInstanceOf(WP_Error::class)
        ->and($postId->get_error_code())->toBe('empty_content')
        ->and(wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Invalid page',
            'post_content' => $invalidHero,
        ]))->toBe(0);
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the public page renders the hero as its only h1', function () {
    editorial_frame_load_wordpress();

    $compact = do_blocks(editorial_frame_hero([
        'title' => 'Bienvenue au club',
        'compact' => true,
    ]));
    $regular = do_blocks(editorial_frame_hero([
        'title' => 'Bienvenue au club',
        'compact' => false,
    ]));

    expect(substr_count($compact, '<h1'))->toBe(1)
        ->and($compact)->toContain('esctt-hero--compact')
        ->and($compact)->toContain('Bienvenue au club')
        ->and($regular)->not->toContain('esctt-hero--compact');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
