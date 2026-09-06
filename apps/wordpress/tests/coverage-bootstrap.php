<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (function_exists('xdebug_set_filter')) {
    xdebug_set_filter(XDEBUG_FILTER_CODE_COVERAGE, XDEBUG_PATH_INCLUDE, [
        dirname(__DIR__, 3) . '/packages/esctt-content',
        dirname(__DIR__, 3) . '/packages/theme/app',
        dirname(__DIR__, 3) . '/packages/theme/functions.php',
    ]);
}
