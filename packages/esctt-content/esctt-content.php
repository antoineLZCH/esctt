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

const ESCTT_LOCATION_POST_TYPE = 'esctt_location';
const ESCTT_PRACTICE_SLOT_POST_TYPE = 'esctt_practice_slot';
const ESCTT_PLAYER_PROFILE_TAXONOMY = 'esctt_player_profile';
const ESCTT_LOCATION_ADDRESS_META = '_esctt_location_address';
const ESCTT_PRACTICE_DAY_META = '_esctt_practice_day';
const ESCTT_PRACTICE_START_META = '_esctt_practice_start';
const ESCTT_PRACTICE_END_META = '_esctt_practice_end';
const ESCTT_PRACTICE_LOCATION_META = '_esctt_practice_location';

/**
 * @return array<int, string>
 */
function esctt_practice_days(): array
{
    return [
        1 => __('Lundi', 'esctt-content'),
        2 => __('Mardi', 'esctt-content'),
        3 => __('Mercredi', 'esctt-content'),
        4 => __('Jeudi', 'esctt-content'),
        5 => __('Vendredi', 'esctt-content'),
        6 => __('Samedi', 'esctt-content'),
        7 => __('Dimanche', 'esctt-content'),
    ];
}

/**
 * @return array<string, string>
 */
function esctt_player_profiles(): array
{
    return [
        'jeune' => __('Jeune', 'esctt-content'),
        'adulte-loisir' => __('Adulte loisir', 'esctt-content'),
        'adulte-competition' => __('Adulte compétition', 'esctt-content'),
    ];
}

function esctt_is_valid_day(string $day): bool
{
    return $day !== ''
        && (string) (int) $day === $day
        && array_key_exists((int) $day, esctt_practice_days());
}

function esctt_register_content_model(): void
{
    register_post_type(ESCTT_LOCATION_POST_TYPE, [
        'labels' => [
            'name' => __('Lieux', 'esctt-content'),
            'singular_name' => __('Lieu', 'esctt-content'),
            'add_new_item' => __('Ajouter un lieu', 'esctt-content'),
            'edit_item' => __('Modifier le lieu', 'esctt-content'),
            'menu_name' => __('Lieux', 'esctt-content'),
        ],
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'supports' => ['title'],
        'rewrite' => false,
        'query_var' => false,
    ]);

    register_post_type(ESCTT_PRACTICE_SLOT_POST_TYPE, [
        'labels' => [
            'name' => __('Créneaux', 'esctt-content'),
            'singular_name' => __('Créneau', 'esctt-content'),
            'add_new_item' => __('Ajouter un créneau', 'esctt-content'),
            'edit_item' => __('Modifier le créneau', 'esctt-content'),
            'menu_name' => __('Créneaux', 'esctt-content'),
        ],
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'supports' => ['title'],
        'taxonomies' => [ESCTT_PLAYER_PROFILE_TAXONOMY],
        'rewrite' => false,
        'query_var' => false,
    ]);

    register_taxonomy(ESCTT_PLAYER_PROFILE_TAXONOMY, [ESCTT_PRACTICE_SLOT_POST_TYPE], [
        'labels' => [
            'name' => __('Profils de joueur', 'esctt-content'),
            'singular_name' => __('Profil de joueur', 'esctt-content'),
            'menu_name' => __('Profils de joueur', 'esctt-content'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'rewrite' => false,
        'query_var' => false,
    ]);
}

function esctt_seed_player_profiles(): void
{
    foreach (esctt_player_profiles() as $slug => $name) {
        if (! term_exists($slug, ESCTT_PLAYER_PROFILE_TAXONOMY)) {
            wp_insert_term($name, ESCTT_PLAYER_PROFILE_TAXONOMY, ['slug' => $slug]);
        }
    }
}

function esctt_register_content_meta(): void
{
    register_post_meta(ESCTT_LOCATION_POST_TYPE, ESCTT_LOCATION_ADDRESS_META, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback' => 'esctt_content_meta_auth',
    ]);

    foreach ([ESCTT_PRACTICE_DAY_META, ESCTT_PRACTICE_START_META, ESCTT_PRACTICE_END_META] as $meta_key) {
        register_post_meta(ESCTT_PRACTICE_SLOT_POST_TYPE, $meta_key, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => 'esctt_content_meta_auth',
        ]);
    }

    register_post_meta(ESCTT_PRACTICE_SLOT_POST_TYPE, ESCTT_PRACTICE_LOCATION_META, [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => 'esctt_content_meta_auth',
    ]);
}

function esctt_content_meta_auth($allowed, string $meta_key, int $post_id, int $user_id): bool
{
    return user_can($user_id, 'edit_post', $post_id);
}

function esctt_register_content_meta_boxes(): void
{
    add_meta_box(
        'esctt-location-details',
        __('Détails du lieu', 'esctt-content'),
        'esctt_render_location_meta_box',
        ESCTT_LOCATION_POST_TYPE,
        'normal',
        'default',
    );

    add_meta_box(
        'esctt-practice-slot-details',
        __('Détails du créneau', 'esctt-content'),
        'esctt_render_practice_slot_meta_box',
        ESCTT_PRACTICE_SLOT_POST_TYPE,
        'normal',
        'default',
    );
}

function esctt_render_location_meta_box(WP_Post $post): void
{
    wp_nonce_field('esctt_save_location', 'esctt_location_nonce');
    $address = (string) get_post_meta($post->ID, ESCTT_LOCATION_ADDRESS_META, true);
    ?>
    <p>
        <label for="esctt-location-address"><?php esc_html_e('Adresse', 'esctt-content'); ?></label>
    </p>
    <textarea id="esctt-location-address" name="esctt_location_address" rows="3" class="large-text" required><?php echo esc_textarea($address); ?></textarea>
    <?php
}

function esctt_render_practice_slot_meta_box(WP_Post $post): void
{
    wp_nonce_field('esctt_save_practice_slot', 'esctt_practice_slot_nonce');
    $day = (string) get_post_meta($post->ID, ESCTT_PRACTICE_DAY_META, true);
    $start = (string) get_post_meta($post->ID, ESCTT_PRACTICE_START_META, true);
    $end = (string) get_post_meta($post->ID, ESCTT_PRACTICE_END_META, true);
    $locationId = (int) get_post_meta($post->ID, ESCTT_PRACTICE_LOCATION_META, true);
    $locations = get_posts([
        'post_type' => ESCTT_LOCATION_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    ?>
    <p>
        <label for="esctt-practice-day"><?php esc_html_e('Jour', 'esctt-content'); ?></label>
        <select id="esctt-practice-day" name="esctt_practice_day" required>
            <option value=""><?php esc_html_e('Choisir un jour', 'esctt-content'); ?></option>
            <?php foreach (esctt_practice_days() as $value => $label) : ?>
                <option value="<?php echo esc_attr((string) $value); ?>" <?php selected($day, (string) $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="esctt-practice-start"><?php esc_html_e('Début', 'esctt-content'); ?></label>
        <input id="esctt-practice-start" type="time" name="esctt_practice_start" value="<?php echo esc_attr($start); ?>" step="60" required>
    </p>
    <p>
        <label for="esctt-practice-end"><?php esc_html_e('Fin', 'esctt-content'); ?></label>
        <input id="esctt-practice-end" type="time" name="esctt_practice_end" value="<?php echo esc_attr($end); ?>" step="60" required>
    </p>
    <p>
        <label for="esctt-practice-location"><?php esc_html_e('Lieu', 'esctt-content'); ?></label>
        <select id="esctt-practice-location" name="esctt_practice_location" required>
            <option value=""><?php esc_html_e('Choisir un lieu', 'esctt-content'); ?></option>
            <?php foreach ($locations as $location) : ?>
                <option value="<?php echo esc_attr((string) $location->ID); ?>" <?php selected($locationId, $location->ID); ?>><?php echo esc_html(get_the_title($location)); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p><?php esc_html_e('Sélectionnez au moins un profil de joueur dans la boîte Profils de joueur.', 'esctt-content'); ?></p>
    <?php
}

function esctt_can_save_content_post(int $post_id, string $nonce_name, string $nonce_action): bool
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return false;
    }

    if (wp_is_post_revision($post_id) || ! isset($_POST[$nonce_name]) || ! is_string($_POST[$nonce_name])) {
        return false;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST[$nonce_name]));

    return wp_verify_nonce($nonce, $nonce_action) !== false && current_user_can('edit_post', $post_id);
}

function esctt_save_location(int $post_id): void
{
    if (! esctt_can_save_content_post($post_id, 'esctt_location_nonce', 'esctt_save_location')) {
        return;
    }

    $address = isset($_POST['esctt_location_address']) && is_string($_POST['esctt_location_address'])
        ? sanitize_textarea_field(wp_unslash($_POST['esctt_location_address']))
        : '';

    if ($address === '') {
        delete_post_meta($post_id, ESCTT_LOCATION_ADDRESS_META);

        return;
    }

    update_post_meta($post_id, ESCTT_LOCATION_ADDRESS_META, $address);
}

function esctt_save_practice_slot(int $post_id): void
{
    if (! esctt_can_save_content_post($post_id, 'esctt_practice_slot_nonce', 'esctt_save_practice_slot')) {
        return;
    }

    $day = isset($_POST['esctt_practice_day']) && is_string($_POST['esctt_practice_day'])
        ? sanitize_text_field(wp_unslash($_POST['esctt_practice_day']))
        : '';
    $start = isset($_POST['esctt_practice_start']) && is_string($_POST['esctt_practice_start'])
        ? sanitize_text_field(wp_unslash($_POST['esctt_practice_start']))
        : '';
    $end = isset($_POST['esctt_practice_end']) && is_string($_POST['esctt_practice_end'])
        ? sanitize_text_field(wp_unslash($_POST['esctt_practice_end']))
        : '';
    $locationId = isset($_POST['esctt_practice_location']) && is_string($_POST['esctt_practice_location'])
        ? absint($_POST['esctt_practice_location'])
        : 0;

    if (esctt_is_valid_day($day)) {
        update_post_meta($post_id, ESCTT_PRACTICE_DAY_META, (string) (int) $day);
    } else {
        delete_post_meta($post_id, ESCTT_PRACTICE_DAY_META);
    }

    foreach ([ESCTT_PRACTICE_START_META => $start, ESCTT_PRACTICE_END_META => $end] as $meta_key => $time) {
        if (esctt_is_valid_time($time)) {
            update_post_meta($post_id, $meta_key, $time);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }

    if ($locationId > 0) {
        update_post_meta($post_id, ESCTT_PRACTICE_LOCATION_META, $locationId);
    } else {
        delete_post_meta($post_id, ESCTT_PRACTICE_LOCATION_META);
    }
}

function esctt_is_valid_time(string $time): bool
{
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time) === 1;
}

function esctt_location_is_valid(int $location_id): bool
{
    $location = get_post($location_id);

    return $location instanceof WP_Post
        && $location->post_type === ESCTT_LOCATION_POST_TYPE
        && $location->post_status === 'publish'
        && trim(get_the_title($location)) !== ''
        && trim((string) get_post_meta($location_id, ESCTT_LOCATION_ADDRESS_META, true)) !== '';
}

function esctt_practice_slot_is_valid(int $slot_id): bool
{
    $slot = get_post($slot_id);

    if (! $slot instanceof WP_Post || $slot->post_type !== ESCTT_PRACTICE_SLOT_POST_TYPE) {
        return false;
    }

    $day = (string) get_post_meta($slot_id, ESCTT_PRACTICE_DAY_META, true);
    $start = (string) get_post_meta($slot_id, ESCTT_PRACTICE_START_META, true);
    $end = (string) get_post_meta($slot_id, ESCTT_PRACTICE_END_META, true);
    $locationId = (int) get_post_meta($slot_id, ESCTT_PRACTICE_LOCATION_META, true);
    $profiles = wp_get_post_terms($slot_id, ESCTT_PLAYER_PROFILE_TAXONOMY, ['fields' => 'slugs']);

    if (is_wp_error($profiles)) {
        return false;
    }

    return trim(get_the_title($slot)) !== ''
        && esctt_is_valid_day($day)
        && esctt_is_valid_time($start)
        && esctt_is_valid_time($end)
        && esctt_location_is_valid($locationId)
        && count(array_intersect(array_keys(esctt_player_profiles()), $profiles)) > 0;
}

function esctt_demote_invalid_practice_slot(int $post_id): void
{
    static $updating = false;
    $post = get_post($post_id);

    if (! $post instanceof WP_Post
        || $post->post_type !== ESCTT_PRACTICE_SLOT_POST_TYPE
        || $updating
        || ! in_array($post->post_status, ['publish', 'future'], true)
        || esctt_practice_slot_is_valid($post->ID)) {
        return;
    }

    $updating = true;
    wp_update_post([
        'ID' => $post->ID,
        'post_status' => 'draft',
    ]);
    $updating = false;
}

/**
 * @return array<int, array<string, mixed>>
 */
function esctt_published_practice_slots(): array
{
    $slots = [];

    foreach (get_posts([
        'post_type' => ESCTT_PRACTICE_SLOT_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC',
    ]) as $post) {
        if (! esctt_practice_slot_is_valid($post->ID)) {
            continue;
        }

        $locationId = (int) get_post_meta($post->ID, ESCTT_PRACTICE_LOCATION_META, true);
        $profileTerms = wp_get_post_terms($post->ID, ESCTT_PLAYER_PROFILE_TAXONOMY);
        $profileNames = is_wp_error($profileTerms) ? [] : wp_list_pluck($profileTerms, 'name');
        $day = (int) get_post_meta($post->ID, ESCTT_PRACTICE_DAY_META, true);
        $location = get_post($locationId);

        $slots[] = [
            'day' => $day,
            'day_label' => esctt_practice_days()[$day],
            'start' => (string) get_post_meta($post->ID, ESCTT_PRACTICE_START_META, true),
            'end' => (string) get_post_meta($post->ID, ESCTT_PRACTICE_END_META, true),
            'title' => get_the_title($post),
            'location_name' => get_the_title($location),
            'location_address' => (string) get_post_meta($locationId, ESCTT_LOCATION_ADDRESS_META, true),
            'profiles' => $profileNames,
        ];
    }

    usort($slots, static function (array $left, array $right): int {
        return [$left['day'], $left['start'], $left['title']] <=> [$right['day'], $right['start'], $right['title']];
    });

    return $slots;
}

function esctt_render_practice_schedules_block(): string
{
    $groups = [];

    foreach (esctt_published_practice_slots() as $slot) {
        $groups[$slot['day']][] = $slot;
    }

    $output = '<section class="esctt-practice-schedules" aria-labelledby="esctt-practice-schedules-title">';
    $output .= '<h2 id="esctt-practice-schedules-title">' . esc_html__('Horaires d’entraînement', 'esctt-content') . '</h2>';

    if ($groups === []) {
        return $output . '<p>' . esc_html__('Aucun créneau publié pour le moment.', 'esctt-content') . '</p></section>';
    }

    foreach ($groups as $daySlots) {
        $output .= '<section class="esctt-practice-day">';
        $output .= '<h3>' . esc_html($daySlots[0]['day_label']) . '</h3><ul>';

        foreach ($daySlots as $slot) {
            $profiles = implode(', ', array_map(static fn(string $profile): string => esc_html($profile), $slot['profiles']));
            $output .= '<li class="esctt-practice-slot">';
            $output .= '<time datetime="' . esc_attr($slot['start']) . '">' . esc_html($slot['start']) . '</time>';
            $output .= ' – <time datetime="' . esc_attr($slot['end']) . '">' . esc_html($slot['end']) . '</time>';
            $output .= ' <strong>' . esc_html($slot['title']) . '</strong>';
            $output .= '<span class="esctt-practice-profiles">' . esc_html($profiles) . '</span>';
            $output .= '<span class="esctt-practice-location"><strong>' . esc_html($slot['location_name']) . '</strong>: ' . nl2br(esc_html($slot['location_address'])) . '</span>';
            $output .= '</li>';
        }

        $output .= '</ul></section>';
    }

    return $output . '</section>';
}

add_filter('esctt_page_block_catalog', static function (array $catalog): array {
    $catalog[] = 'esctt/practice-schedules';

    return array_values(array_unique($catalog));
});

add_action('init', 'esctt_register_content_model', 5);
add_action('init', 'esctt_register_content_meta', 6);
add_action('init', 'esctt_seed_player_profiles', 20);
add_action('add_meta_boxes', 'esctt_register_content_meta_boxes');
add_action('save_post_' . ESCTT_LOCATION_POST_TYPE, 'esctt_save_location');
add_action('save_post_' . ESCTT_PRACTICE_SLOT_POST_TYPE, 'esctt_save_practice_slot');
add_action('wp_after_insert_post', 'esctt_demote_invalid_practice_slot', 10, 1);
add_action('init', static function (): void {
    register_block_type('esctt/practice-schedules', [
        'api_version' => '3',
        'title' => __('Horaires de pratique', 'esctt-content'),
        'description' => __('Affiche les créneaux publiés regroupés par jour.', 'esctt-content'),
        'category' => 'design',
        'icon' => 'calendar-alt',
        'render_callback' => 'esctt_render_practice_schedules_block',
        'supports' => [
            'html' => false,
            'multiple' => false,
            'reusable' => false,
        ],
    ]);
});
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

// @codeCoverageIgnoreStart
if (function_exists('add_action')) {
    add_action('init', 'esctt_register_pricing_fields', 6);
}
// @codeCoverageIgnoreEnd
