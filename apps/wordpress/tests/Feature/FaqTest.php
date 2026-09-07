<?php

function faq_test_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

test('the FAQ source is an Admin-managed structured options field group', function () {
    faq_test_load_wordpress();
    esctt_register_faq_fields();

    expect(esctt_faq_field_group())->toMatchArray([
        'key' => 'group_esctt_faq',
        'title' => 'FAQ du club',
        'location' => [[[
            'param' => 'options_page',
            'operator' => '==',
            'value' => 'esctt-faq',
        ]]],
    ])
        ->and(esctt_faq_field_group()['fields'][0]['name'])->toBe('faq_items')
        ->and(esctt_faq_field_group()['fields'][0]['type'])->toBe('repeater')
        ->and(esctt_faq_field_group()['fields'][0]['sub_fields'][0]['name'])->toBe('question')
        ->and(esctt_faq_field_group()['fields'][0]['sub_fields'][1]['name'])->toBe('answer');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the FAQ source filters incomplete rows before either view reads it', function () {
    faq_test_load_wordpress();

    $source = [
        ['question' => 'Question 1', 'answer' => 'Answer 1'],
        ['question' => '', 'answer' => 'Missing question'],
        ['question' => 'Missing answer', 'answer' => ''],
        'Ignored non-array row',
        ['question' => 'Question 2', 'answer' => '<strong>Answer 2</strong>'],
    ];
    $filter = static function () use ($source): array {
        return $source;
    };
    add_filter('acf/load_value/name=faq_items', $filter, 99, 3);

    try {
        expect(esctt_faq_items())->toBe([
            ['question' => 'Question 1', 'answer' => '<p>Answer 1</p>'],
            ['question' => 'Question 2', 'answer' => '<p><strong>Answer 2</strong></p>'],
        ]);

        $emptyFilter = static function (): null {
            return null;
        };
        add_filter('acf/load_value/name=faq_items', $emptyFilter, 99, 3);

        try {
            expect(esctt_faq_items())->toBe([]);
        } finally {
            remove_filter('acf/load_value/name=faq_items', $emptyFilter, 99);
        }
    } finally {
        remove_filter('acf/load_value/name=faq_items', $filter, 99);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('home and dedicated FAQ views share source rows and native disclosure semantics', function () {
    faq_test_load_wordpress();

    $source = [
        ['question' => 'Question 1', 'answer' => 'Answer 1'],
        ['question' => 'Question 2', 'answer' => 'Answer 2'],
    ];
    $filter = static function () use ($source): array {
        return $source;
    };
    add_filter('acf/load_value/name=faq_items', $filter, 99, 3);

    try {
        $home = \App\faq_markup(1, 'home');
        $full = \App\faq_markup(null, 'page');
    } finally {
        remove_filter('acf/load_value/name=faq_items', $filter, 99);
    }

    expect(substr_count($home, '<details'))->toBe(1)
        ->and($home)->toContain('Question 1')
        ->and($home)->toContain('Answer 1')
        ->and($home)->not->toContain('Question 2')
        ->and($full)->toContain('Question 1')
        ->and($full)->toContain('Answer 1')
        ->and($full)->toContain('Question 2')
        ->and($full)->toContain('Answer 2')
        ->and($full)->toContain('aria-labelledby="faq-page-question-0"')
        ->and($full)->toContain('aria-controls="faq-page-answer-0"')
        ->and($full)->toContain('role="region"')
        ->and($full)->toContain('<summary id="faq-page-question-0"')
        ->and($full)->not->toContain('aria-expanded="false"');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
