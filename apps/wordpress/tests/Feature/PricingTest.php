<?php

function pricing_test_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
    require_once dirname(__DIR__, 4) . '/packages/esctt-content/esctt-content.php';
    esctt_register_pricing_fields();
}

/**
 * @param array<int, array<string, mixed>> $fields
 * @return array<string, mixed>
 */
function pricing_test_field(array $fields, string $name): array
{
    foreach ($fields as $field) {
        if (($field['name'] ?? '') === $name) {
            return $field;
        }
    }

    return [];
}

test('pricing fields keep tariff categories separate from player profiles', function () {
    pricing_test_load_wordpress();

    $group = acf_get_field_group('group_esctt_pricing');
    $fields = acf_get_fields($group);
    $categories = pricing_test_field($fields, 'tariff_categories');
    $profiles = pricing_test_field($fields, 'player_profiles');
    $categoryFields = acf_get_fields($categories);
    $profileFields = acf_get_fields($profiles);
    $optionsPages = array_values(array_filter(
        acf_get_options_pages() ?: [],
        static fn(array $page): bool => ($page['menu_slug'] ?? '') === 'esctt-pricing',
    ));

    expect($group['title'])->toBe('Tarifs du club')
        ->and($optionsPages)->toHaveCount(1)
        ->and($categories['name'])->toBe('tariff_categories')
        ->and($profiles['name'])->toBe('player_profiles')
        ->and(wp_list_pluck($fields, 'name'))->toContain('jersey_price', 'pass_plus_acceptance')
        ->and(wp_list_pluck($categoryFields, 'name'))->toContain(
            'label',
            'colombes_loisir',
            'colombes_competition',
            'hors_colombes_loisir',
            'hors_colombes_competition',
        )
        ->and(wp_list_pluck($categoryFields, 'name'))->not->toContain('player_profile')
        ->and(wp_list_pluck($profileFields, 'name'))->toContain('label');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('public pricing rendering keeps every axis and supplementary value visible', function () {
    pricing_test_load_wordpress();

    $html = \App\pricing_matrix([
        'tariff_categories' => [[
            'label' => 'Catégorie test',
            'colombes_loisir' => 25,
            'colombes_competition' => 35,
            'hors_colombes_loisir' => 45,
            'hors_colombes_competition' => 55,
        ]],
        'player_profiles' => [['label' => 'Adulte loisir']],
        'jersey_price' => null,
        'pass_plus_acceptance' => '',
    ]);

    expect($html)->toContain('Catégorie test')
        ->and($html)->toContain('Colombes')
        ->and($html)->toContain('Hors Colombes')
        ->and($html)->toContain('Loisir')
        ->and($html)->toContain('Compétition')
        ->and($html)->toContain('25')
        ->and($html)->toContain('35')
        ->and($html)->toContain('45')
        ->and($html)->toContain('55')
        ->and($html)->toContain('Prix du maillot')
        ->and($html)->toContain('Pass+')
        ->and($html)->toContain('Montant à renseigner')
        ->and($html)->toContain('À renseigner')
        ->and($html)->toContain('Adulte loisir')
        ->and(substr_count($html, 'data-pricing-option'))->toBe(4);
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('pricing helpers preserve explicit empty and nonnumeric values', function () {
    pricing_test_load_wordpress();

    $model = \App\pricing_model();
    $empty = \App\pricing_matrix([
        'tariff_categories' => null,
        'player_profiles' => null,
        'jersey_price' => null,
        'pass_plus_acceptance' => '',
    ]);
    $invalid = \App\pricing_matrix([
        'tariff_categories' => ['not-a-category'],
        'player_profiles' => [],
        'jersey_price' => null,
        'pass_plus_acceptance' => '',
    ]);

    expect($model['tariff_categories'])->toBeArray()
        ->and($model['player_profiles'])->toBeArray()
        ->and(\App\pricing_amount('À confirmer'))->toBe('À confirmer')
        ->and(\App\pricing_pass_plus_label('yes'))->toBe('Oui')
        ->and(\App\pricing_pass_plus_label('no'))->toBe('Non')
        ->and($empty)->toContain('Ajoutez une catégorie tarifaire')
        ->and($invalid)->toContain('Ajoutez une catégorie tarifaire');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
