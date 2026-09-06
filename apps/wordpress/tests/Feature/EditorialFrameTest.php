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
    $pageBlocks = apply_filters('allowed_block_types_all', true, ['post_type' => 'page']);
    $otherBlocks = apply_filters('allowed_block_types_all', true, ['post_type' => 'post']);
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
        ->and($registeredPage->template)->toBe($pageArgs['template'])
        ->and($registeredPage->template_lock)->toBeFalse();
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

    $invalidData = apply_filters('wp_insert_post_data', [
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_content' => $invalidHero,
    ], [], [], false);
    $validData = apply_filters('wp_insert_post_data', [
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_content' => $valid,
    ], [], [], false);
    $draftData = apply_filters('wp_insert_post_data', [
        'post_type' => 'page',
        'post_status' => 'draft',
        'post_content' => $invalidHero,
    ], [], [], false);
    $postData = apply_filters('wp_insert_post_data', [
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_content' => $invalidHero,
    ], [], [], false);

    require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    wp_dequeue_script('wp-dom-ready');
    set_current_screen('post');
    get_current_screen()->is_block_editor(true);
    ob_start();
    do_action('admin_head');
    ob_end_clean();
    $GLOBALS['current_screen'] = null;

    expect($invalidData['post_status'])->toBe('draft')
        ->and($validData['post_status'])->toBe('publish')
        ->and($draftData['post_status'])->toBe('draft')
        ->and($postData['post_status'])->toBe('publish');

    $postId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Invalid page',
        'post_content' => $invalidHero,
    ], true);

    expect($postId)->toBeInt()
        ->and(get_post_status($postId))->toBe('draft');

    wp_delete_post($postId, true);
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
