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
 * Return the structured FAQ field group used by the admin editor.
 *
 * @return array<string, mixed>
 */
function esctt_faq_field_group(): array
{
    return [
        'key' => 'group_esctt_faq',
        'title' => __('FAQ du club', 'esctt-content'),
        'fields' => [
            [
                'key' => 'field_esctt_faq_items',
                'label' => __('Questions et réponses', 'esctt-content'),
                'name' => 'faq_items',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => __('Ajouter une question', 'esctt-content'),
                'sub_fields' => [
                    [
                        'key' => 'field_esctt_faq_question',
                        'label' => __('Question', 'esctt-content'),
                        'name' => 'question',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_esctt_faq_answer',
                        'label' => __('Réponse', 'esctt-content'),
                        'name' => 'answer',
                        'type' => 'textarea',
                        'rows' => 4,
                        'new_lines' => '',
                        'required' => 1,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'esctt-faq',
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

function esctt_register_faq_fields(): void
{
    // @codeCoverageIgnoreStart
    if (! function_exists('acf_add_options_page') || ! function_exists('acf_add_local_field_group')) {
        return;
    }
    // @codeCoverageIgnoreEnd

    acf_add_options_page([
        'page_title' => __('FAQ du club', 'esctt-content'),
        'menu_title' => __('FAQ', 'esctt-content'),
        'menu_slug' => 'esctt-faq',
        'capability' => 'manage_options',
        'redirect' => false,
    ]);

    acf_add_local_field_group(esctt_faq_field_group());
}

/**
 * Read and normalize the one Admin-maintained FAQ source.
 *
 * @return array<int, array{question: string, answer: string}>
 */
function esctt_faq_items(): array
{
    // @codeCoverageIgnoreStart
    if (! function_exists('get_field')) {
        return [];
    }
    // @codeCoverageIgnoreEnd

    $rows = get_field('faq_items', 'option');

    // @codeCoverageIgnoreStart
    if (! is_array($rows)) {
        return [];
    }
    // @codeCoverageIgnoreEnd

    $items = [];

    foreach ($rows as $row) {
        if (! is_array($row)) {
            continue;
        }

        $question = trim(wp_strip_all_tags((string) ($row['question'] ?? '')));
        $answer = trim((string) ($row['answer'] ?? ''));

        if ($question === '' || $answer === '') {
            continue;
        }

        $items[] = [
            'question' => $question,
            'answer' => trim(wpautop(wp_kses_post($answer))),
        ];
    }

    return $items;
}

// @codeCoverageIgnoreStart
if (function_exists('add_action')) {
    add_action('init', 'esctt_register_faq_fields', 6);
}
// @codeCoverageIgnoreEnd
