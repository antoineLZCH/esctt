<?php

test('content plugin bootstrap is present', function () {
    expect(file_exists(dirname(__DIR__, 4) . '/packages/esctt-content/esctt-content.php'))->toBeTrue();
});
