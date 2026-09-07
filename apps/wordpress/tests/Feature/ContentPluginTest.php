<?php

test('content plugin bootstrap is loadable', function () {
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';

    expect(defined('ABSPATH'))->toBeTrue();
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
