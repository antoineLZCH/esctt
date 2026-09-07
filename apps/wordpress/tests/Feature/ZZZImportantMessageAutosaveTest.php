<?php

test('important message saves reject autosaves', function () {
    if (! defined('DOING_AUTOSAVE')) {
        define('DOING_AUTOSAVE', true);
    }

    expect(esctt_can_save_important_message(0))->toBeFalse();
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
