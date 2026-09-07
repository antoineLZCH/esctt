<?php

function club_menu_test_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    if (! defined('WP_ADMIN')) {
        define('WP_ADMIN', true);
    }
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

test('club content is grouped under one admin menu', function () {
    club_menu_test_load_wordpress();

    $admin = get_users([
        'role' => 'administrator',
        'number' => 1,
    ])[0] ?? null;
    wp_set_current_user($admin->ID);

    global $menu, $submenu;
    $menu = [];
    $submenu = [];
    do_action('admin_menu');

    $clubMenu = array_values(array_filter(
        $menu,
        static fn(array $item): bool => ($item[2] ?? '') === ESCTT_CLUB_MENU_SLUG,
    ));
    $clubSubmenuSlugs = wp_list_pluck($submenu[ESCTT_CLUB_MENU_SLUG] ?? [], 2);
    $optionsPages = acf_get_options_pages() ?: [];

    expect($clubMenu)->toHaveCount(1)
        ->and($clubMenu[0][0])->toBe('Club')
        ->and($clubSubmenuSlugs)->toContain(
            'edit.php?post_type=' . ESCTT_LOCATION_POST_TYPE,
            'edit.php?post_type=' . ESCTT_PRACTICE_SLOT_POST_TYPE,
            'edit.php?post_type=' . ESCTT_IMPORTANT_MESSAGE_POST_TYPE,
            'edit.php?post_type=' . ESCTT_PARTNER_POST_TYPE,
            'edit-tags.php?taxonomy=' . ESCTT_PLAYER_PROFILE_TAXONOMY . '&post_type=' . ESCTT_PRACTICE_SLOT_POST_TYPE,
        )
        ->and($optionsPages['esctt-faq']['parent_slug'])->toBe(ESCTT_CLUB_MENU_SLUG)
        ->and($optionsPages['esctt-pricing']['parent_slug'])->toBe(ESCTT_CLUB_MENU_SLUG);

    ob_start();
    esctt_render_club_menu();
    $clubPage = ob_get_clean();
    expect($clubPage)->toContain('>Club<', 'Gérez les contenus du club depuis le sous-menu.');

    wp_set_current_user(0);
    ob_start();
    esctt_render_club_menu();
    $restrictedPage = ob_get_clean();
    wp_set_current_user($admin->ID);

    expect($restrictedPage)->toBe('');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
