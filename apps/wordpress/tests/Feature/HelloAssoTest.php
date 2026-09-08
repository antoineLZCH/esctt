<?php

function helloasso_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

function helloasso_block(array $attributes = []): string
{
    $serializedAttributes = $attributes === [] ? '' : ' ' . wp_json_encode($attributes);

    return sprintf(
        '<!-- wp:esctt/helloasso%s /-->',
        $serializedAttributes,
    );
}

test('the HelloAsso block renders a widget and a permanent direct fallback', function () {
    helloasso_load_wordpress();

    $pageContext = new WP_Block_Editor_Context([
        'post' => new WP_Post((object) ['post_type' => 'page']),
    ]);
    $membershipUrl = 'https://www.helloasso.com/associations/example/adhesions/adhesion-2026';
    $widgetUrl = $membershipUrl . '/widget';
    $markup = do_blocks(helloasso_block([
        'membershipUrl' => $membershipUrl,
        'widgetUrl' => $widgetUrl,
    ]));

    expect(apply_filters('allowed_block_types_all', true, $pageContext))->toContain('esctt/helloasso')
        ->and($markup)->toContain('<section class="esctt-helloasso"')
        ->and($markup)->toContain('<iframe')
        ->and($markup)->toContain('src="https://www.helloasso.com/associations/example/adhesions/adhesion-2026/widget"')
        ->and($markup)->toContain('data-helloasso-widget="true"')
        ->and($markup)->toContain('title="Formulaire d’adhésion HelloAsso"')
        ->and($markup)->toContain('width="100%"')
        ->and($markup)->toContain('height: 750px')
        ->and($markup)->not->toContain('onload=')
        ->and($markup)->toContain('loading="lazy"')
        ->and($markup)->toContain('class="esctt-helloasso__fallback"')
        ->and(strpos($markup, 'esctt-helloasso__fallback'))->toBeLessThan(strpos($markup, '<iframe'))
        ->and($markup)->toContain('href="https://www.helloasso.com/associations/example/adhesions/adhesion-2026"')
        ->and($markup)->toContain('Ouvrir le formulaire d’adhésion HelloAsso')
        ->and($markup)->toContain('Les pièces d’adhésion sont collectées par HelloAsso, pas par le site du club.')
        ->and($markup)->not->toContain('api.helloasso');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the HelloAsso block validates distinct membership and widget URLs', function () {
    helloasso_load_wordpress();

    expect(\App\helloasso_membership_url('https://www.helloasso.com/associations/example/adhesions/adhesion-2026'))->toBe('https://www.helloasso.com/associations/example/adhesions/adhesion-2026')
        ->and(\App\helloasso_widget_url('https://www.helloasso.com/associations/example/adhesions/adhesion-2026/widget'))->toBe('https://www.helloasso.com/associations/example/adhesions/adhesion-2026/widget')
        ->and(\App\helloasso_widget_url('https://example.com/associations/example/adhesions/adhesion-2026/widget'))->toBeNull()
        ->and(\App\helloasso_widget_url('https://www.helloasso.com/associations/example/adhesions/adhesion-2026'))->toBeNull()
        ->and(\App\helloasso_membership_url('https://example.com/associations/example/adhesions/adhesion-2026'))->toBeNull()
        ->and(\App\helloasso_membership_url('https://www.helloasso.com/not-a-membership'))->toBeNull()
        ->and(\App\helloasso_membership_url('javascript:alert(1)'))->toBeNull()
        ->and(\App\helloasso_widget_url('http://'))->toBeNull()
        ->and(\App\helloasso_widget_url('https://user:pass@www.helloasso.com/associations/example/adhesions/adhesion-2026/widget'))->toBeNull()
        ->and(\App\helloasso_widget_url('https://[invalid'))->toBeNull()
        ->and(\App\helloasso_widget_url('https://www.helloasso.com/associations/example/adhesions/adhesion-2026/widget#fragment'))->toBeNull()
        ->and(\App\helloasso_membership_url('https://user:pass@www.helloasso.com/associations/example/adhesions/adhesion-2026'))->toBeNull()
        ->and(\App\helloasso_membership_url('http://www.helloasso.com/associations/example/adhesions/adhesion-2026'))->toBeNull()
        ->and(\App\helloasso_membership_url('http://'))->toBeNull()
        ->and(\App\helloasso_membership_url('https://[invalid'))->toBeNull()
        ->and(\App\helloasso_membership_url('https://www.helloasso.com'))->toBeNull()
        ->and(\App\helloasso_membership_url('https://www.helloasso.com?source=site'))->toBeNull()
        ->and(\App\helloasso_membership_url('/associations/example/adhesions/adhesion-2026'))->toBeNull()
        ->and(\App\helloasso_membership_url('https:///associations/example/adhesions/adhesion-2026'))->toBeNull()
        ->and(\App\helloasso_membership_url('//www.helloasso.com/associations/example/adhesions/adhesion-2026'))->toBeNull()
        ->and(do_blocks(helloasso_block()))->not->toContain('esctt-helloasso__fallback')
        ->and(do_blocks(helloasso_block(['membershipUrl' => 'https://example.com/adhesion', 'widgetUrl' => 'https://example.com/widget'])))->not->toContain('esctt-helloasso__fallback')
        ->and(do_blocks(helloasso_block(['membershipUrl' => 'https://www.helloasso.com/associations/example/adhesions/adhesion-2026', 'widgetUrl' => 'https://www.helloasso.com/associations/other/adhesions/other/widget'])))->not->toContain('esctt-helloasso__fallback');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the HelloAsso block renders through the Inscriptions editor content', function () {
    helloasso_load_wordpress();

    $postId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'draft',
        'post_title' => 'Inscriptions test HelloAsso',
        'post_content' => '<!-- wp:esctt/hero {"title":"Inscriptions","lock":{"move":true,"remove":true}} /-->' . helloasso_block([
            'membershipUrl' => 'https://www.helloasso.com/associations/example/adhesions/adhesion-2026',
            'widgetUrl' => 'https://www.helloasso.com/associations/example/adhesions/adhesion-2026/widget',
        ]),
    ]);
    $originalPost = $GLOBALS['post'] ?? null;
    $GLOBALS['post'] = get_post($postId);
    setup_postdata($GLOBALS['post']);

    try {
        $editorContent = (new \App\View\Composers\Registration())->editorContent();

        expect($editorContent)->toContain('esctt-helloasso__fallback')
            ->and($editorContent)->toContain('haWidget')
            ->and($editorContent)->toContain('/adhesion-2026/widget')
            ->and($editorContent)->toContain('Les pièces d’adhésion sont collectées par HelloAsso, pas par le site du club.');
    } finally {
        wp_reset_postdata();
        $GLOBALS['post'] = $originalPost;
        wp_delete_post($postId, true);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
