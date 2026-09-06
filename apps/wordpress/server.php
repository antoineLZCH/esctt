<?php

declare(strict_types=1);

$documentRoot = __DIR__ . '/web';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
$segments = explode('/', trim($path, '/'));

if (in_array('..', $segments, true)) {
    require $documentRoot . '/index.php';
    return;
}

$file = $documentRoot . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if ($path !== '/' && is_dir($file) && is_file($file . '/index.php')) {
    require $file . '/index.php';
    return;
}

require $documentRoot . '/index.php';
