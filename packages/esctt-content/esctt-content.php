<?php

/**
 * Plugin Name: ESCTT Content
 * Description: Content model for ES Colombienne Tennis de table.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.3
 * Requires Plugins: secure-custom-fields
 * License: GPL-2.0-or-later
 * Text Domain: esctt-content
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Return the structured pricing field group used by the admin editor.
 *
 * @return array<string, mixed>
 */
function esctt_pricing_field_group(): array
{
    return [
        'key' => 'group_esctt_pricing',
        'title' => __('Tarifs du club', 'esctt-content'),
        'fields' => [
            [
                'key' => 'field_esctt_tariff_categories',
                'label' => __('Catégories tarifaires', 'esctt-content'),
                'name' => 'tariff_categories',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Ajouter une catégorie tarifaire', 'esctt-content'),
                'sub_fields' => [
                    [
                        'key' => 'field_esctt_tariff_category_label',
                        'label' => __('Catégorie tarifaire', 'esctt-content'),
                        'name' => 'label',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_esctt_colombes_loisir',
                        'label' => __('Colombes — Loisir', 'esctt-content'),
                        'name' => 'colombes_loisir',
                        'type' => 'number',
                        'min' => 0,
                        'step' => 0.01,
                        'prepend' => '€',
                    ],
                    [
                        'key' => 'field_esctt_colombes_competition',
                        'label' => __('Colombes — Compétition', 'esctt-content'),
                        'name' => 'colombes_competition',
                        'type' => 'number',
                        'min' => 0,
                        'step' => 0.01,
                        'prepend' => '€',
                    ],
                    [
                        'key' => 'field_esctt_hors_colombes_loisir',
                        'label' => __('Hors Colombes — Loisir', 'esctt-content'),
                        'name' => 'hors_colombes_loisir',
                        'type' => 'number',
                        'min' => 0,
                        'step' => 0.01,
                        'prepend' => '€',
                    ],
                    [
                        'key' => 'field_esctt_hors_colombes_competition',
                        'label' => __('Hors Colombes — Compétition', 'esctt-content'),
                        'name' => 'hors_colombes_competition',
                        'type' => 'number',
                        'min' => 0,
                        'step' => 0.01,
                        'prepend' => '€',
                    ],
                ],
            ],
            [
                'key' => 'field_esctt_player_profiles',
                'label' => __('Profils de joueur', 'esctt-content'),
                'instructions' => __('Référentiel éditorial séparé des catégories tarifaires.', 'esctt-content'),
                'name' => 'player_profiles',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Ajouter un profil de joueur', 'esctt-content'),
                'sub_fields' => [
                    [
                        'key' => 'field_esctt_player_profile_label',
                        'label' => __('Profil de joueur', 'esctt-content'),
                        'name' => 'label',
                        'type' => 'text',
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_esctt_jersey_price',
                'label' => __('Prix du maillot', 'esctt-content'),
                'name' => 'jersey_price',
                'type' => 'number',
                'min' => 0,
                'step' => 0.01,
                'prepend' => '€',
            ],
            [
                'key' => 'field_esctt_pass_plus_acceptance',
                'label' => __('Pass+ accepté', 'esctt-content'),
                'name' => 'pass_plus_acceptance',
                'type' => 'select',
                'choices' => [
                    '' => __('À renseigner', 'esctt-content'),
                    'yes' => __('Oui', 'esctt-content'),
                    'no' => __('Non', 'esctt-content'),
                ],
                'default_value' => '',
                'return_format' => 'value',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'esctt-pricing',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'active' => true,
        'show_in_rest' => false,
    ];
}

function esctt_register_pricing_fields(): void
{
    if (! function_exists('acf_add_options_page') || ! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_options_page([
        'page_title' => __('Tarifs du club', 'esctt-content'),
        'menu_title' => __('Tarifs', 'esctt-content'),
        'menu_slug' => 'esctt-pricing',
        'capability' => 'manage_options',
        'redirect' => false,
    ]);

    acf_add_local_field_group(esctt_pricing_field_group());
}

if (function_exists('acf_add_options_page') && function_exists('acf_add_local_field_group')) {
    esctt_register_pricing_fields();
} elseif (function_exists('add_action')) {
    add_action('acf/init', 'esctt_register_pricing_fields');
}
