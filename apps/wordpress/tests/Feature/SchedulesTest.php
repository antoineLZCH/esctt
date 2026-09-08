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
    $jeune = get_term_by('slug', 'jeune', 'esctt_player_profile');
    wp_delete_term($jeune->term_id, 'esctt_player_profile');
    esctt_seed_player_profiles();
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

test('admins can edit and inspect location and slot fields', function () {
    schedules_load_wordpress();

    $admin = get_users([
        'role' => 'administrator',
        'number' => 1,
    ])[0] ?? null;
    $previousUserId = get_current_user_id();
    wp_set_current_user($admin->ID);
    $previousPost = $_POST;
    $locationId = wp_insert_post([
        'post_type' => 'esctt_location',
        'post_status' => 'publish',
        'post_title' => 'Salle admin',
    ]);
    $slotId = wp_insert_post([
        'post_type' => 'esctt_practice_slot',
        'post_status' => 'draft',
        'post_title' => 'Créneau admin',
    ]);

    try {
        $_POST = [
            'esctt_location_nonce' => wp_create_nonce('esctt_save_location'),
            'esctt_location_address' => '5 rue du Test, Colombes',
        ];
        esctt_save_location($locationId);

        ob_start();
        esctt_render_location_meta_box(get_post($locationId));
        $locationFields = ob_get_clean();

        expect($locationFields)->toContain('5 rue du Test, Colombes', 'required')
            ->and(esctt_location_is_valid($locationId))->toBeTrue()
            ->and(esctt_location_is_valid(0))->toBeFalse()
            ->and(esctt_practice_slot_is_valid(0))->toBeFalse()
            ->and(esctt_is_valid_day(''))->toBeFalse();

        $_POST['esctt_location_address'] = '';
        esctt_save_location($locationId);
        expect(get_post_meta($locationId, '_esctt_location_address', true))->toBe('');

        $_POST['esctt_location_address'] = [];
        esctt_save_location($locationId);

        $_POST = [
            'esctt_location_nonce' => wp_create_nonce('esctt_save_location'),
            'esctt_location_address' => '5 rue du Test, Colombes',
        ];
        esctt_save_location($locationId);
        require_once ABSPATH . 'wp-admin/includes/template.php';
        esctt_register_content_meta_boxes();
        $_POST = [
            'esctt_practice_slot_nonce' => wp_create_nonce('esctt_save_practice_slot'),
            'esctt_practice_day' => '2',
            'esctt_practice_start' => '20:00',
            'esctt_practice_end' => '22:00',
            'esctt_practice_location' => (string) $locationId,
        ];
        esctt_save_practice_slot($slotId);

        ob_start();
        esctt_render_practice_slot_meta_box(get_post($slotId));
        $slotFields = ob_get_clean();

        expect($slotFields)->toContain('Mardi', '20:00', 'Salle admin')
            ->and(get_post_meta($slotId, '_esctt_practice_day', true))->toBe('2')
            ->and(esctt_content_meta_auth(false, '_esctt_location_address', $locationId, $admin->ID))->toBeTrue();

        $_POST = [
            'esctt_practice_slot_nonce' => wp_create_nonce('esctt_save_practice_slot'),
            'esctt_practice_day' => '8',
            'esctt_practice_start' => 'invalid',
            'esctt_practice_end' => 'invalid',
            'esctt_practice_location' => '0',
        ];
        esctt_save_practice_slot($slotId);

        expect(get_post_meta($slotId, '_esctt_practice_day', true))->toBe('')
            ->and(get_post_meta($slotId, '_esctt_practice_start', true))->toBe('')
            ->and(get_post_meta($slotId, '_esctt_practice_end', true))->toBe('')
            ->and(get_post_meta($slotId, '_esctt_practice_location', true))->toBe('');

        $_POST = [
            'esctt_practice_slot_nonce' => wp_create_nonce('esctt_save_practice_slot'),
        ];
        esctt_save_practice_slot($slotId);

        wp_set_current_user(0);
        $_POST = [
            'esctt_location_nonce' => wp_create_nonce('esctt_save_location'),
            'esctt_location_address' => 'Unauthorized address',
        ];
        esctt_save_location($locationId);
        wp_set_current_user($admin->ID);

        $revisionId = wp_insert_post([
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'post_parent' => $locationId,
            'post_title' => 'Location revision',
        ]);
        $_POST = [
            'esctt_location_nonce' => wp_create_nonce('esctt_save_location'),
            'esctt_location_address' => 'Revision address',
        ];
        esctt_save_location($revisionId);
    } finally {
        wp_set_current_user($previousUserId);
        $_POST = $previousPost;
        if (! empty($revisionId)) {
            wp_delete_post($revisionId, true);
        }
        wp_delete_post($slotId, true);
        wp_delete_post($locationId, true);
    }
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

        $termsError = static fn() => new WP_Error('terms_failed');
        add_filter('get_object_terms', $termsError, 99, 4);
        expect(esctt_practice_slot_is_valid($lateSlotId))->toBeFalse();
        remove_filter('get_object_terms', $termsError, 99);

        $profileTermsError = static function ($terms, $objectIds, $taxonomies, $args) {
            return isset($args['fields']) && $args['fields'] === 'slugs'
                ? $terms
                : new WP_Error('profile_terms_failed');
        };
        add_filter('get_object_terms', $profileTermsError, 99, 4);
        expect(esctt_published_practice_slots())->not->toBeEmpty();
        remove_filter('get_object_terms', $profileTermsError, 99);

        wp_delete_object_term_relationships($tuesdaySlotId, 'esctt_player_profile');
        expect(do_blocks('<!-- wp:esctt/practice-schedules /-->'))->not->toContain('Mardi soir');
        wp_set_object_terms($tuesdaySlotId, 'adulte-competition', 'esctt_player_profile');

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

test('an empty schedule renders an explicit empty state', function () {
    schedules_load_wordpress();

    $emptyPracticeSlots = static function (WP_Query $query): void {
        if ($query->get('post_type') === ESCTT_PRACTICE_SLOT_POST_TYPE) {
            $query->set('post__in', [0]);
        }
    };
    add_action('pre_get_posts', $emptyPracticeSlots);

    try {
        $html = esctt_render_practice_schedules_block();

        expect($html)->toContain('Aucun créneau publié pour le moment.');
    } finally {
        remove_action('pre_get_posts', $emptyPracticeSlots);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('the schedule comparison keeps every slot visible with profile status text', function () {
    schedules_load_wordpress();

    $locationId = schedules_create_location('Salle comparaison', '3 avenue du Club, Colombes');
    $jeuneSlotId = schedules_create_slot('Créneau jeune', 1, '18:00', '20:00', $locationId, 'jeune');
    $overlapSlotId = schedules_create_slot('Créneau chevauché', 1, '19:00', '21:00', $locationId, 'adulte-competition');
    $sharedSlotId = schedules_create_slot('Créneau partagé', 2, '20:00', '22:00', $locationId, 'adulte-loisir');

    try {
        $html = do_blocks('<!-- wp:esctt/practice-schedules /-->');

        expect($html)->toContain('Comparer les créneaux par profil')
            ->and($html)->toContain('name="esctt-practice-profile"')
            ->and($html)->toContain('value="jeune"')
            ->and($html)->toContain('value="adulte-loisir"')
            ->and($html)->toContain('value="adulte-competition"')
            ->and($html)->toContain('checked')
            ->and($html)->toContain('Grille hebdomadaire')
            ->and($html)->toContain('Axe horaire')
            ->and($html)->toContain('data-profile-slugs="jeune"')
            ->and($html)->toContain('data-profile-slugs="adulte-loisir"')
            ->and($html)->toContain('Adapté au profil Jeune')
            ->and($html)->toContain('Ce créneau n’est pas adapté au profil Jeune')
            ->and(substr_count($html, 'class="esctt-practice-slot"'))->toBeGreaterThanOrEqual(3)
            ->and(substr_count($html, 'class="esctt-practice-day"'))->toBe(7)
            ->and($html)->toContain('grid-row: 73 / span 8; grid-column: 1;')
            ->and($html)->toContain('grid-row: 77 / span 8; grid-column: 2;');
    } finally {
        wp_delete_post($jeuneSlotId, true);
        wp_delete_post($overlapSlotId, true);
        wp_delete_post($sharedSlotId, true);
        wp_delete_post($locationId, true);
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('autosave requests cannot save content fields', function () {
    if (! defined('DOING_AUTOSAVE')) {
        define('DOING_AUTOSAVE', true);
    }

    expect(esctt_can_save_content_post(0, 'nonce', 'action'))->toBeFalse();
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
