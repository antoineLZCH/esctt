<?php

function sport_life_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

function sport_life_block(array $attributes = []): string
{
    $serializedAttributes = $attributes === [] ? '' : ' ' . wp_json_encode($attributes);

    return sprintf(
        '<!-- wp:esctt/sport-life%s /-->',
        $serializedAttributes,
    );
}

test('the sport life block presents the family tournament, FFTT competitions and real jersey photos', function () {
    sport_life_load_wordpress();

    $pageContext = new WP_Block_Editor_Context([
        'post' => new WP_Post((object) ['post_type' => 'page']),
    ]);
    $allowedBlocks = apply_filters('allowed_block_types_all', true, $pageContext);
    $markup = do_blocks(sport_life_block([
        'helloAssoUrl' => 'https://www.helloasso.com/associations/example/adhesions/tournoi-des-familles',
    ]));

    expect($allowedBlocks)->toContain('esctt/sport-life')
        ->and($markup)->toContain('<section class="esctt-sport-life"')
        ->and($markup)->toContain('Tournoi des familles')
        ->and($markup)->toContain('Compétitions FFTT')
        ->and(strpos($markup, 'Tournoi des familles'))->toBeLessThan(strpos($markup, 'Compétitions FFTT'))
        ->and($markup)->toContain('href="https://www.helloasso.com/associations/example/adhesions/tournoi-des-familles"')
        ->and($markup)->toContain('target="_blank"')
        ->and($markup)->toContain('rel="noopener noreferrer"')
        ->and($markup)->toContain('alt="Photo du maillot de l’ES Colombienne vu de face"')
        ->and($markup)->toContain('alt="Photo du maillot de l’ES Colombienne vu de dos"')
        ->and($markup)->not->toContain('<iframe')
        ->and($markup)->not->toContain('<canvas');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the sport life CTA is absent until an external HelloAsso URL is configured', function () {
    sport_life_load_wordpress();

    $withoutUrl = do_blocks(sport_life_block());
    $sameSiteUrl = do_blocks(sport_life_block(['helloAssoUrl' => home_url('/inscriptions')]));
    $otherDomainUrl = do_blocks(sport_life_block(['helloAssoUrl' => 'https://example.com/tournoi']));

    expect($withoutUrl)->not->toContain('esctt-sport-life__cta')
        ->and($sameSiteUrl)->not->toContain('esctt-sport-life__cta')
        ->and($otherDomainUrl)->not->toContain('esctt-sport-life__cta');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the sport life block exposes an accessible labelled section and non-empty image alternatives', function () {
    sport_life_load_wordpress();

    $markup = do_blocks(sport_life_block());

    expect($markup)->toContain('aria-labelledby="esctt-sport-life-title"')
        ->and($markup)->toContain('id="esctt-sport-life-title"')
        ->and(substr_count($markup, 'alt="'))->toBe(2)
        ->and($markup)->not->toContain('alt=""');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
