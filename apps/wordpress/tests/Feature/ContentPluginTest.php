<?php

test('content plugin bootstrap is loadable', function () {
    if (getenv('ESCTT_WORDPRESS_TESTS') === '1') {
        putenv('APP_RUNNING_IN_CONSOLE=false');
        require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
        if (! function_exists('esctt_content_bootstrap')) {
            require_once dirname(__DIR__, 4) . '/packages/esctt-content/esctt-content.php';
        }
        esctt_content_bootstrap();
        esctt_content_bootstrap();
        esctt_register_registration_document_model();
    } else {
        if (! defined('ABSPATH')) {
            define('ABSPATH', dirname(__DIR__, 2) . '/web/wp/');
        }

        require_once dirname(__DIR__, 4) . '/packages/esctt-content/esctt-content.php';
    }

    expect(defined('ABSPATH'))->toBeTrue();
});
