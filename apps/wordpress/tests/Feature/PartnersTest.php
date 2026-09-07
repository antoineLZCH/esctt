<?php

function partners_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';

}

function partners_block(): string
{
    return '<!-- wp:esctt/partners /-->';
}

function partners_admin_id(): int
{
    $admins = get_users([
        'role' => 'administrator',
        'number' => 1,
    ]);

    return (int) $admins[0]->ID;
}

test('partners expose an admin-managed ordered WordPress model', function () {
    partners_load_wordpress();

    $postType = get_post_type_object(ESCTT_PARTNER_POST_TYPE);
    $partnerId = wp_insert_post([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'draft',
        'post_title' => 'Partenaire administrable',
    ], true);
    $adminId = partners_admin_id();
    $originalPost = $_POST;
    $originalUser = get_current_user_id();

    try {
        wp_set_current_user($adminId);

        expect($postType)->toBeInstanceOf(WP_Post_Type::class)
            ->and($postType->public)->toBeFalse()
            ->and($postType->show_ui)->toBeTrue()
            ->and($postType->show_in_rest)->toBeTrue()
            ->and(post_type_supports(ESCTT_PARTNER_POST_TYPE, 'title'))->toBeTrue()
            ->and(post_type_supports(ESCTT_PARTNER_POST_TYPE, 'page-attributes'))->toBeTrue()
            ->and($partnerId)->toBeInt();

        require_once ABSPATH . 'wp-admin/includes/template.php';
        do_action('add_meta_boxes_' . ESCTT_PARTNER_POST_TYPE, get_post($partnerId));

        ob_start();
        esctt_render_partner_meta_box(get_post($partnerId));
        $metaBox = ob_get_clean();

        expect($metaBox)->toContain('esctt_partner_url')
            ->and($metaBox)->toContain('esctt_partner_menu_order')
            ->and($metaBox)->toContain('Lien du partenaire')
            ->and(esctt_partner_meta_auth(true, ESCTT_PARTNER_URL_META, $partnerId, $adminId))->toBeTrue()
            ->and(esctt_partner_meta_auth(true, ESCTT_PARTNER_URL_META, $partnerId, 0))->toBeFalse()
            ->and(esctt_sanitize_partner_url('https:/relative'))->toBe('');

        $_POST = [];
        expect(esctt_can_save_partner($partnerId))->toBeFalse();
        expect(esctt_can_save_partner(0))->toBeFalse();
        esctt_save_partner($partnerId);

        $_POST = ['esctt_partner_nonce' => 'invalid'];
        expect(esctt_can_save_partner($partnerId))->toBeFalse();

        $_POST = ['esctt_partner_nonce' => ['invalid']];
        expect(esctt_can_save_partner($partnerId))->toBeFalse();

        wp_set_current_user(0);
        $_POST = ['esctt_partner_nonce' => wp_create_nonce('esctt_save_partner')];
        expect(esctt_can_save_partner($partnerId))->toBeFalse();

        wp_set_current_user($adminId);
        $_POST = [
            'esctt_partner_nonce' => wp_create_nonce('esctt_save_partner'),
            'esctt_partner_url' => 'https://partner.example.test/',
            'esctt_partner_menu_order' => '7',
        ];
        esctt_save_partner($partnerId);

        expect(get_post_meta($partnerId, ESCTT_PARTNER_URL_META, true))->toBe('https://partner.example.test/')
            ->and(get_post($partnerId)->menu_order)->toBe(7);

        $_POST['esctt_partner_url'] = 'javascript:alert(1)';
        esctt_save_partner($partnerId);

        expect(get_post_meta($partnerId, ESCTT_PARTNER_URL_META, true))->toBe('');

        $_POST = [
            'esctt_partner_nonce' => wp_create_nonce('esctt_save_partner'),
            'esctt_partner_url' => ['invalid'],
            'esctt_partner_menu_order' => ['invalid'],
        ];
        esctt_save_partner($partnerId);

        expect(get_post_meta($partnerId, ESCTT_PARTNER_URL_META, true))->toBe('')
            ->and(get_post($partnerId)->menu_order)->toBe(0);

        $revisionId = wp_insert_post([
            'post_type' => 'revision',
            'post_parent' => $partnerId,
            'post_status' => 'inherit',
            'post_title' => 'Revision partenaire',
        ], true);
        expect($revisionId)->toBeInt()
            ->and(esctt_can_save_partner($revisionId))->toBeFalse();
        wp_delete_post($revisionId, true);
    } finally {
        $_POST = $originalPost;
        wp_set_current_user($originalUser);

        if (is_int($partnerId)) {
            wp_delete_post($partnerId, true);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('published partner changes render in order with explicit external behavior', function () {
    partners_load_wordpress();

    $pageContext = new WP_Block_Editor_Context([
        'post' => new WP_Post((object) ['post_type' => 'page']),
    ]);
    $allowedBlocks = apply_filters('allowed_block_types_all', true, $pageContext);

    $first = wp_insert_post([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => 'Partenaire Alpha',
        'menu_order' => 20,
    ], true);
    $second = wp_insert_post([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => 'Partenaire Beta',
        'menu_order' => 10,
    ], true);
    $withoutUrl = wp_insert_post([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => 'Partenaire sans site',
        'menu_order' => 30,
    ], true);
    $emptyName = wp_insert_post([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => '',
        'menu_order' => 15,
    ], true);

    try {
        expect($first)->toBeInt()
            ->and($second)->toBeInt()
            ->and($withoutUrl)->toBeInt()
            ->and($emptyName)->toBeInt()
            ->and($allowedBlocks)->toContain('esctt/partners');

        update_post_meta($first, ESCTT_PARTNER_URL_META, 'https://alpha.example.test/');
        update_post_meta($second, ESCTT_PARTNER_URL_META, 'https://beta.example.test/');

        $markup = do_blocks(partners_block());

        expect($markup)->toContain('<section class="esctt-partners"')
            ->and($markup)->toContain('aria-labelledby="esctt-partners-title"')
            ->and($markup)->toContain('<h2 id="esctt-partners-title">Partenaires</h2>')
            ->and($markup)->toContain('Partenaire Alpha')
            ->and($markup)->toContain('Partenaire Beta')
            ->and($markup)->toContain('Partenaire sans site')
            ->and($markup)->toContain('href="https://beta.example.test/"')
            ->and($markup)->toContain('target="_blank"')
            ->and($markup)->toContain('rel="noopener noreferrer"')
            ->and($markup)->toContain('ouvre dans une nouvelle fenêtre')
            ->and(strpos($markup, 'Partenaire Beta'))->toBeLessThan(strpos($markup, 'Partenaire Alpha'));

        wp_update_post([
            'ID' => $first,
            'post_title' => 'Partenaire Alpha mis à jour',
            'menu_order' => 0,
        ]);
        wp_delete_post($second, true);

        $updatedMarkup = do_blocks(partners_block());

        expect($updatedMarkup)->toContain('Partenaire Alpha mis à jour')
            ->and($updatedMarkup)->not->toContain('Partenaire Beta')
            ->and($updatedMarkup)->toContain('Partenaire sans site');
    } finally {
        if (is_int($first)) {
            wp_delete_post($first, true);
        }

        if (is_int($second)) {
            wp_delete_post($second, true);
        }

        if (is_int($withoutUrl)) {
            wp_delete_post($withoutUrl, true);
        }

        if (is_int($emptyName)) {
            wp_delete_post($emptyName, true);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('an empty published partner entry does not create an empty public section', function () {
    partners_load_wordpress();

    $publishedPartners = esctt_get_published_partners();
    $originalOrders = [];
    $emptyPartner = wp_insert_post([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => '',
    ], true);

    foreach ($publishedPartners as $partner) {
        $originalOrders[$partner->ID] = $partner->menu_order;
        wp_update_post([
            'ID' => $partner->ID,
            'post_status' => 'draft',
        ]);
    }

    try {
        expect($emptyPartner)->toBeInt()
            ->and(do_blocks(partners_block()))->toBe('');
    } finally {
        if (is_int($emptyPartner)) {
            wp_delete_post($emptyPartner, true);
        }

        expect(do_blocks(partners_block()))->toBe('')
            ->and(\App\render_partners([]))->toBe('');

        foreach ($originalOrders as $partnerId => $menuOrder) {
            wp_update_post([
                'ID' => $partnerId,
                'post_status' => 'publish',
                'menu_order' => $menuOrder,
            ]);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('a single published partner renders without list-shape assumptions', function () {
    partners_load_wordpress();

    $publishedPartners = esctt_get_published_partners();
    $originalOrders = [];
    $singlePartner = wp_insert_post([
        'post_type' => ESCTT_PARTNER_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => 'Partenaire unique',
    ], true);

    foreach ($publishedPartners as $partner) {
        $originalOrders[$partner->ID] = $partner->menu_order;
        wp_update_post([
            'ID' => $partner->ID,
            'post_status' => 'draft',
        ]);
    }

    try {
        update_post_meta($singlePartner, ESCTT_PARTNER_URL_META, 'https://single.example.test/');

        $markup = do_blocks(partners_block());

        expect($singlePartner)->toBeInt()
            ->and($markup)->toContain('Partenaire unique')
            ->and(substr_count($markup, '<li>'))->toBe(1);
    } finally {
        if (is_int($singlePartner)) {
            wp_delete_post($singlePartner, true);
        }

        foreach ($originalOrders as $partnerId => $menuOrder) {
            wp_update_post([
                'ID' => $partnerId,
                'post_status' => 'publish',
                'menu_order' => $menuOrder,
            ]);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('partner saves reject autosave requests', function () {
    partners_load_wordpress();

    if (! defined('DOING_AUTOSAVE')) {
        define('DOING_AUTOSAVE', true);
    }

    expect(esctt_can_save_partner(0))->toBeFalse();
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
