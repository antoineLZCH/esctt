<?php

test('provisioned WordPress boots the project theme', function () {
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';

    expect(wp_get_theme()->get_stylesheet())->toBe('esctt')
        ->and(get_option('blogname'))->not->toBe('');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
