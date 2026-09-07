<?php

function schedules_load_wordpress(): void
{
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';
}

function schedules_create_location(string $name, string $address): int
{
    $locationId = wp_insert_post([
        'post_type' => 'esctt_location',
        'post_status' => 'publish',
        'post_title' => $name,
    ]);

    update_post_meta($locationId, '_esctt_location_address', $address);

    return $locationId;
}

function schedules_create_slot(
    string $title,
    int $day,
    string $start,
    string $end,
    int $locationId,
    string $profile,
): int {
    $slotId = wp_insert_post([
        'post_type' => 'esctt_practice_slot',
        'post_status' => 'draft',
        'post_title' => $title,
    ]);

    update_post_meta($slotId, '_esctt_practice_day', (string) $day);
    update_post_meta($slotId, '_esctt_practice_start', $start);
    update_post_meta($slotId, '_esctt_practice_end', $end);
    update_post_meta($slotId, '_esctt_practice_location', $locationId);
    wp_set_object_terms($slotId, $profile, 'esctt_player_profile');
    wp_update_post([
        'ID' => $slotId,
        'post_status' => 'publish',
    ]);

    return $slotId;
}

test('the schedule model exposes editable locations, profiles, and slots', function () {
    schedules_load_wordpress();

    $location = get_post_type_object('esctt_location');
    $slot = get_post_type_object('esctt_practice_slot');
    $profiles = get_taxonomy('esctt_player_profile');
    $profileTerms = get_terms([
        'taxonomy' => 'esctt_player_profile',
        'hide_empty' => false,
    ]);

    expect($location)->not->toBeFalse()
        ->and($location->show_ui)->toBeTrue()
        ->and($location->show_in_rest)->toBeTrue()
        ->and($slot)->not->toBeFalse()
        ->and($slot->show_ui)->toBeTrue()
        ->and($slot->show_in_rest)->toBeTrue()
        ->and($profiles)->not->toBeFalse()
        ->and($profiles->show_ui)->toBeTrue()
        ->and($profiles->show_in_rest)->toBeTrue()
        ->and(wp_list_pluck($profileTerms, 'slug'))->toContain('jeune', 'adulte-loisir', 'adulte-competition');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('published slots require a supported player profile', function () {
    schedules_load_wordpress();

    $locationId = schedules_create_location('Salle de test', '1 avenue du Club, Colombes');
    $slotId = wp_insert_post([
        'post_type' => 'esctt_practice_slot',
        'post_status' => 'draft',
        'post_title' => 'Sans profil',
    ]);
    update_post_meta($slotId, '_esctt_practice_day', '1');
    update_post_meta($slotId, '_esctt_practice_start', '18:00');
    update_post_meta($slotId, '_esctt_practice_end', '20:00');
    update_post_meta($slotId, '_esctt_practice_location', $locationId);

    try {
        wp_update_post([
            'ID' => $slotId,
            'post_status' => 'publish',
        ]);

        expect(get_post_status($slotId))->toBe('draft')
            ->and(do_blocks('<!-- wp:esctt/practice-schedules /-->'))->not->toContain('Sans profil');
    } finally {
        wp_delete_post($slotId, true);
        wp_delete_post($locationId, true);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('published slots render by day and start time with live location and profile data', function () {
    schedules_load_wordpress();

    $locationId = schedules_create_location('Gymnase Colombes', '12 rue des Sports, Colombes');
    $lateSlotId = schedules_create_slot('Soirée tardive', 1, '22:00', '01:00', $locationId, 'jeune');
    $morningSlotId = schedules_create_slot('Matin', 1, '08:00', '10:00', $locationId, 'adulte-loisir');
    $tuesdaySlotId = schedules_create_slot('Mardi soir', 2, '18:00', '20:00', $locationId, 'adulte-competition');

    try {
        $html = do_blocks('<!-- wp:esctt/practice-schedules /-->');

        expect($html)->toContain('Gymnase Colombes')
            ->and($html)->toContain('12 rue des Sports, Colombes')
            ->and($html)->toContain('22:00')
            ->and($html)->toContain('01:00')
            ->and($html)->toContain('Jeune')
            ->and(strpos($html, 'Lundi'))->toBeLessThan(strpos($html, 'Mardi'))
            ->and(strpos($html, 'Matin'))->toBeLessThan(strpos($html, 'Soirée tardive'))
            ->and(strpos($html, 'Soirée tardive'))->toBeLessThan(strpos($html, 'Mardi soir'));

        wp_update_post(['ID' => $lateSlotId, 'post_title' => 'Édition publiée']);
        $updatedHtml = do_blocks('<!-- wp:esctt/practice-schedules /-->');

        expect($updatedHtml)->toContain('Édition publiée')
            ->and($updatedHtml)->not->toContain('Soirée tardive');
    } finally {
        wp_delete_post($lateSlotId, true);
        wp_delete_post($morningSlotId, true);
        wp_delete_post($tuesdaySlotId, true);
        wp_delete_post($locationId, true);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
