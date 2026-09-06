<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
$segments = explode('/', trim($path, '/'));

if (in_array('..', $segments, true)) {
    require __DIR__ . '/index.php';
    return;
}

$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if ($path !== '/' && is_dir($file) && is_file($file . '/index.php')) {
    require $file . '/index.php';
    return;
}

require __DIR__ . '/index.php';
