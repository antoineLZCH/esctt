<?php

/**
 * Plugin Name: ESCTT Composer plugins
 * Description: Loads the Composer-managed SCF and ESCTT content plugins in order.
 * Version: 0.1.0
 * License: GPL-2.0-or-later
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

foreach ([
    WP_PLUGIN_DIR . '/secure-custom-fields/secure-custom-fields.php',
    WP_PLUGIN_DIR . '/esctt-content/esctt-content.php',
] as $plugin) {
    if (! is_readable($plugin)) {
        throw new RuntimeException("Missing Composer-managed plugin: {$plugin}");
    }

    require_once $plugin;
}
