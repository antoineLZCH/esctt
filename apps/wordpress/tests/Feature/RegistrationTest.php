<?php

function registration_test_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

function registration_test_save_document(int $postId, string $url, string $season): void
{
    $_POST['esctt_registration_document_nonce'] = wp_create_nonce('esctt_registration_document');
    $_POST['esctt_registration_document_url'] = $url;
    $_POST['esctt_registration_document_season'] = $season;
    esctt_save_registration_document($postId, get_post($postId));
    unset(
        $_POST['esctt_registration_document_nonce'],
        $_POST['esctt_registration_document_url'],
        $_POST['esctt_registration_document_season'],
    );
}

function registration_test_trigger_save(int $postId, array $fields): void
{
    $_POST = array_merge($_POST, $fields);
    esctt_save_registration_document($postId, get_post($postId));
    unset(
        $_POST['esctt_registration_document_nonce'],
        $_POST['esctt_registration_document_url'],
        $_POST['esctt_registration_document_season'],
    );
}

test('registration documents have an admin-managed model and a non-collection policy', function () {
    registration_test_load_wordpress();

    $postType = get_post_type_object('esctt_reg_document');
    $registeredMeta = get_registered_meta_keys('post', 'esctt_reg_document');
    $meta = array_keys($registeredMeta);

    expect($postType)->not->toBeNull()
        ->and($postType->show_ui)->toBeTrue()
        ->and($postType->public)->toBeFalse()
        ->and(post_type_supports('esctt_reg_document', 'title'))->toBeTrue()
        ->and(post_type_supports('esctt_reg_document', 'page-attributes'))->toBeTrue()
        ->and($meta)->toContain('_esctt_registration_document_url', '_esctt_registration_document_season')
        ->and(esctt_membership_documents_policy())->toContain('ne collecte ni ne stocke');

    wp_set_current_user(1);
    $urlAuth = $registeredMeta['_esctt_registration_document_url']['auth_callback'];
    $seasonAuth = $registeredMeta['_esctt_registration_document_season']['auth_callback'];
    expect($urlAuth(true, '_esctt_registration_document_url', 1))->toBeTrue()
        ->and($seasonAuth(true, '_esctt_registration_document_season', 1))->toBeTrue();

    wp_set_current_user(0);
    expect($urlAuth(true, '_esctt_registration_document_url', 1))->toBeFalse()
        ->and($seasonAuth(true, '_esctt_registration_document_season', 1))->toBeFalse();
    wp_set_current_user(1);

    require_once ABSPATH . 'wp-admin/includes/template.php';
    do_action('add_meta_boxes', 'esctt_reg_document', new WP_Post((object) ['ID' => 1]));
    ob_start();
    esctt_render_registration_document_meta_box(new WP_Post((object) ['ID' => 1]));
    $metaBox = ob_get_clean();

    expect($metaBox)->toContain('esctt_registration_document_nonce')
        ->and($metaBox)->toContain('documents génériques de saison')
        ->and($metaBox)->toContain('ne collecte ni ne stocke');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('published season documents are ordered, named and downloadable', function () {
    registration_test_load_wordpress();
    wp_set_current_user(1);

    $documents = [
        ['title' => 'Certificat médical — saison 2026–2027', 'order' => 2, 'url' => 'https://example.com/certificat.pdf'],
        ['title' => 'Règlement intérieur — saison 2026–2027', 'order' => 1, 'url' => 'https://example.com/reglement.pdf'],
    ];
    $postIds = [];

    foreach ($documents as $document) {
        $postId = wp_insert_post([
            'post_type' => 'esctt_reg_document',
            'post_status' => 'publish',
            'post_title' => $document['title'],
            'menu_order' => $document['order'],
        ]);
        $postIds[] = $postId;
        registration_test_save_document($postId, $document['url'], '2026–2027');
    }

    $emptyTitleId = wp_insert_post([
        'post_type' => 'esctt_reg_document',
        'post_status' => 'publish',
        'post_title' => '',
    ]);
    registration_test_save_document($emptyTitleId, 'https://example.com/unnamed.pdf', '2026–2027');

    $emptyUrlId = wp_insert_post([
        'post_type' => 'esctt_reg_document',
        'post_status' => 'publish',
        'post_title' => 'Document sans fichier',
    ]);
    registration_test_save_document($emptyUrlId, '', '2026–2027');

    $invalidUrlId = wp_insert_post([
        'post_type' => 'esctt_reg_document',
        'post_status' => 'publish',
        'post_title' => 'URL non valide',
    ]);
    registration_test_save_document($invalidUrlId, 'javascript:alert(1)', '2026–2027');

    $draftId = wp_insert_post([
        'post_type' => 'esctt_reg_document',
        'post_status' => 'draft',
        'post_title' => 'Brouillon',
    ]);
    registration_test_save_document($draftId, 'https://example.com/draft.pdf', '2026–2027');

    expect(esctt_registration_documents())->toBe([
        [
            'title' => 'Règlement intérieur — saison 2026–2027',
            'url' => 'https://example.com/reglement.pdf',
            'season' => '2026–2027',
        ],
        [
            'title' => 'Certificat médical — saison 2026–2027',
            'url' => 'https://example.com/certificat.pdf',
            'season' => '2026–2027',
        ],
    ]);

    foreach (array_merge($postIds, [$emptyTitleId, $emptyUrlId, $invalidUrlId, $draftId]) as $postId) {
        wp_delete_post($postId, true);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('registration document saves sanitize input and ignore unsafe save contexts', function () {
    registration_test_load_wordpress();
    wp_set_current_user(1);
    $postId = wp_insert_post([
        'post_type' => 'esctt_reg_document',
        'post_status' => 'draft',
        'post_title' => 'Document administré',
    ]);

    registration_test_save_document($postId, 'https://example.com/document.pdf', '<b>2026–2027</b>');
    expect(get_post_meta($postId, '_esctt_registration_document_url', true))->toBe('https://example.com/document.pdf')
        ->and(get_post_meta($postId, '_esctt_registration_document_season', true))->toBe('2026–2027');

    registration_test_trigger_save($postId, []);
    expect(get_post_meta($postId, '_esctt_registration_document_url', true))->toBe('https://example.com/document.pdf');

    registration_test_trigger_save($postId, [
        'esctt_registration_document_nonce' => 'invalid',
        'esctt_registration_document_url' => 'https://example.com/rejected.pdf',
        'esctt_registration_document_season' => 'rejected',
    ]);
    expect(get_post_meta($postId, '_esctt_registration_document_url', true))->toBe('https://example.com/document.pdf');

    registration_test_trigger_save($postId, [
        'esctt_registration_document_nonce' => wp_create_nonce('esctt_registration_document'),
    ]);
    expect(get_post_meta($postId, '_esctt_registration_document_url', true))->toBe('');

    wp_set_current_user(0);
    registration_test_trigger_save($postId, [
        'esctt_registration_document_nonce' => wp_create_nonce('esctt_registration_document'),
        'esctt_registration_document_url' => 'https://example.com/rejected-without-capability.pdf',
    ]);
    wp_set_current_user(1);
    expect(get_post_meta($postId, '_esctt_registration_document_url', true))->toBe('');

    $autosaveId = wp_insert_post([
        'post_type' => 'revision',
        'post_parent' => $postId,
        'post_status' => 'inherit',
        'post_name' => $postId . '-autosave-v1',
    ]);
    esctt_save_registration_document($autosaveId, get_post($autosaveId));

    $revisionId = wp_insert_post([
        'post_type' => 'revision',
        'post_parent' => $postId,
        'post_status' => 'inherit',
    ]);
    esctt_save_registration_document($revisionId, get_post($revisionId));

    wp_delete_post($postId, true);
    wp_delete_post($autosaveId, true);
    wp_delete_post($revisionId, true);
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the registration page puts ordered guidance and PPS HTML before editor links', function () {
    registration_test_load_wordpress();

    wp_set_current_user(1);
    $documentId = wp_insert_post([
        'post_type' => 'esctt_reg_document',
        'post_status' => 'publish',
        'post_title' => 'Règlement intérieur — saison 2026–2027',
    ]);
    registration_test_save_document($documentId, 'https://example.com/reglement.pdf', '');

    $postId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Inscriptions',
        'post_name' => 'inscriptions',
        'post_content' => '<!-- wp:esctt/hero {"title":"Inscriptions","lock":{"move":true,"remove":true}} /--><!-- wp:paragraph --><p><a href="https://www.helloasso.com/club">Finaliser</a></p><!-- /wp:paragraph -->',
    ]);
    $originalPost = $GLOBALS['post'] ?? null;
    $GLOBALS['post'] = get_post($postId);
    setup_postdata($GLOBALS['post']);

    try {
        $rendered = view('partials.content-inscriptions')->render();
        $stepsPosition = strpos($rendered, 'registration-steps-title');
        $ppsPosition = strpos($rendered, 'registration-pps-title');
        $helloAssoPosition = strpos($rendered, 'helloasso.com');
        $stepsMarkup = substr($rendered, $stepsPosition, $ppsPosition - $stepsPosition);

        expect(substr_count($rendered, 'aria-labelledby="registration-steps-title"'))->toBe(1)
            ->and(substr_count($rendered, '<ol>'))->toBe(1)
            ->and(substr_count($stepsMarkup, '<li>'))->toBe(4)
            ->and(substr_count($rendered, 'aria-labelledby="registration-pps-title"'))->toBe(1)
            ->and(substr_count($rendered, 'registration-policy'))->toBe(1)
            ->and($rendered)->toContain('Parcours Prévention Santé')
            ->and($rendered)->toContain('18 à 64 ans')
            ->and($rendered)->toContain('65 ans et plus')
            ->and($rendered)->toContain('SPID Ma Licence')
            ->and($rendered)->not->toContain('<iframe')
            ->and($rendered)->toContain('ne collecte ni ne stocke')
            ->and($rendered)->toContain('href="https://example.com/reglement.pdf"')
            ->and($rendered)->toContain('Télécharger : Règlement intérieur — saison 2026–2027')
            ->and($stepsPosition)->toBeLessThan($helloAssoPosition);

        $originalContent = $GLOBALS['post']->post_content;
        $GLOBALS['post']->post_content = '<!-- wp:esctt/hero {"title":"Inscriptions","lock":{"move":true,"remove":true}} /-->';
        expect((new \App\View\Composers\Registration())->editorContent())->toBe('');
        $GLOBALS['post']->post_content = $originalContent;
    } finally {
        wp_reset_postdata();
        $GLOBALS['post'] = $originalPost;
        wp_delete_post($postId, true);
        wp_delete_post($documentId, true);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
