<?php

test('content plugin bootstrap is loadable', function () {
    if (! defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__, 2) . '/web/wp/');
    }

    require_once dirname(__DIR__, 4) . '/packages/esctt-content/esctt-content.php';
    esctt_register_pricing_fields();

    expect(defined('ABSPATH'))->toBeTrue();
});
