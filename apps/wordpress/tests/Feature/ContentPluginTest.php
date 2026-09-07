<?php

test('content plugin bootstrap is present', function () {
    expect(file_exists(dirname(__DIR__, 4) . '/packages/esctt-content/esctt-content.php'))->toBeTrue();
});

test('content plugin bootstrap is loadable', function () {
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
    require_once dirname(__DIR__, 4) . '/packages/esctt-content/esctt-content.php';
    esctt_register_pricing_fields();

    expect(defined('ABSPATH'))->toBeTrue()
        ->and(function_exists('esctt_register_content_model'))->toBeTrue()
        ->and(function_exists('esctt_pricing_field_group'))->toBeTrue()
        ->and(function_exists('esctt_register_important_message'))->toBeTrue()
        ->and(function_exists('esctt_register_partner'))->toBeTrue()
        ->and(esctt_can_save_content_post(0, 'nonce', 'action'))->toBeFalse();
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
