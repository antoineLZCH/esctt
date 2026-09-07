<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;
use WP_Block_Editor_Context;
use WP_Error;

const PAGE_BLOCK_CATALOG = [
    'esctt/hero',
    'esctt/sport-life',
    'core/paragraph',
    'core/heading',
    'core/image',
    'core/list',
    'core/buttons',
    'core/button',
    'core/group',
    'core/columns',
    'core/column',
    'core/separator',
];

/**
 * @return string[]
 */
function page_block_catalog(): array
{
    /** @var string[] $catalog */
    $catalog = apply_filters('esctt_page_block_catalog', PAGE_BLOCK_CATALOG);

    return $catalog;
}

function sport_life_helloasso_url(string $url): ?string
{
    $url = esc_url_raw(trim($url));
    $pattern = '~^https?://(?:[a-z0-9-]+\.)*helloasso\.com(?::[0-9]+)?(?:[/?#]|$)~i';

    return [null, $url][(int) preg_match($pattern, $url)];
}

function render_sport_life(array $attributes): string
{
    $helloAssoUrl = sport_life_helloasso_url((string) ($attributes['helloAssoUrl'] ?? ''));
    $cta = $helloAssoUrl === null ? '' : sprintf(
        '<p class="esctt-sport-life__cta"><a class="wp-element-button" href="%s" target="_blank" rel="noopener noreferrer">%s <span class="screen-reader-text">%s</span></a></p>',
        esc_url($helloAssoUrl),
        esc_html__('S’inscrire au tournoi des familles sur HelloAsso', 'esctt'),
        esc_html__('(ouvre dans une nouvelle fenêtre)', 'esctt'),
    );

    return sprintf(
        '<section class="esctt-sport-life" aria-labelledby="esctt-sport-life-title">
            <div class="esctt-sport-life__intro">
                <article class="esctt-sport-life__tournament">
                    <p class="esctt-sport-life__eyebrow">%s</p>
                    <h2 id="esctt-sport-life-title">%s</h2>
                    <p>%s</p>
                    <p>%s</p>
                    %s
                </article>
                <article class="esctt-sport-life__competitions" aria-labelledby="esctt-sport-life-competitions-title">
                    <p class="esctt-sport-life__eyebrow">%s</p>
                    <h2 id="esctt-sport-life-competitions-title">%s</h2>
                    <p>%s</p>
                </article>
            </div>
            <figure class="esctt-sport-life__jersey">
                <div class="esctt-sport-life__photos">
                    <img src="%s" alt="%s" width="900" height="1600" loading="lazy">
                    <img src="%s" alt="%s" width="900" height="1600" loading="lazy">
                </div>
                <figcaption>%s</figcaption>
            </figure>
        </section>',
        esc_html__('Tournoi interne', 'esctt'),
        esc_html__('Tournoi des familles', 'esctt'),
        esc_html__('Le tournoi des familles est le temps fort des tournois internes du club.', 'esctt'),
        esc_html__('Un rendez-vous pour partager le tennis de table en famille et entre membres.', 'esctt'),
        $cta,
        esc_html__('Pratique sportive', 'esctt'),
        esc_html__('Compétitions FFTT', 'esctt'),
        esc_html__('La compétition FFTT complète la vie du club. Cette présentation reste volontairement concise, sans résultats ni calendrier détaillé.', 'esctt'),
        esc_url(Vite::asset('resources/images/maillot-face.jpg')),
        esc_attr__('Photo du maillot de l’ES Colombienne vu de face', 'esctt'),
        esc_url(Vite::asset('resources/images/maillot-dos.jpg')),
        esc_attr__('Photo du maillot de l’ES Colombienne vu de dos', 'esctt'),
        esc_html__('Maillot actuel du club, photographié de face et de dos.', 'esctt'),
    );
}

function page_structure_error(string $content): ?WP_Error
{
    $blocks = parse_blocks($content);
    $hero = $blocks[0] ?? [];

    if (($hero['blockName'] ?? null) !== 'esctt/hero') {
        return new WP_Error(
            'esctt_hero_required',
            __('Every published page must start with a Hero block.', 'esctt'),
        );
    }

    $lock = $hero['attrs']['lock'] ?? [];

    if (($lock['move'] ?? false) !== true || ($lock['remove'] ?? false) !== true) {
        return new WP_Error(
            'esctt_hero_locked',
            __('The Hero block cannot be moved or removed.', 'esctt'),
        );
    }

    $heroCount = 0;
    $h1HeadingCount = 0;
    $allowedBlocks = page_block_catalog();
    $walk = null;
    $walk = function (array $nestedBlocks) use (&$walk, &$heroCount, &$h1HeadingCount, $allowedBlocks): ?WP_Error {
        foreach ($nestedBlocks as $block) {
            $name = $block['blockName'] ?? null;

            if (! in_array($name, $allowedBlocks, true)) {
                return new WP_Error(
                    'esctt_block_not_allowed',
                    sprintf(__('The block "%s" is not allowed on pages.', 'esctt'), (string) $name),
                );
            }

            if ('esctt/hero' === $name) {
                $heroCount++;
            }

            if ('core/heading' === $name && 1 === (int) ($block['attrs']['level'] ?? 0)) {
                $h1HeadingCount++;
            }

            $error = $walk($block['innerBlocks'] ?? []);

            if (null !== $error) {
                return $error;
            }
        }

        return null;
    };

    $error = $walk($blocks);

    if (null !== $error) {
        return $error;
    }

    if (1 !== $heroCount) {
        return new WP_Error(
            'esctt_hero_unique',
            __('A published page must contain exactly one Hero block.', 'esctt'),
        );
    }

    if ($h1HeadingCount > 0 || 1 !== (int) preg_match_all('/<h1\\b/i', do_blocks($content))) {
        return new WP_Error(
            'esctt_h1_unique',
            __('The Hero must be the only block that renders an H1.', 'esctt'),
        );
    }

    return null;
}

add_filter('register_post_type_args', function (array $args, string $postType): array {
    if ('page' !== $postType) {
        return $args;
    }

    $args['template'] = [
        [
            'esctt/hero',
            [
                'lock' => [
                    'move' => true,
                    'remove' => true,
                ],
            ],
        ],
    ];
    $args['template_lock'] = false;

    return $args;
}, 10, 2);

add_filter('allowed_block_types_all', function (bool|array $allowed, WP_Block_Editor_Context $context): bool|array {
    if ('page' !== $context->post?->post_type) {
        return $allowed;
    }

    return page_block_catalog();
}, 10, 2);

add_action('init', function (): void {
    register_block_type('esctt/hero', [
        'api_version' => '3',
        'attributes' => [
            'title' => [
                'type' => 'string',
                'default' => '',
            ],
            'compact' => [
                'type' => 'boolean',
                'default' => false,
            ],
        ],
        'render_callback' => function (array $attributes): string {
            $class = ! empty($attributes['compact'])
                ? 'esctt-hero esctt-hero--compact'
                : 'esctt-hero';

            return sprintf(
                '<section class="%s"><h1>%s</h1></section>',
                esc_attr($class),
                wp_kses((string) ($attributes['title'] ?? ''), [
                    'a' => [
                        'href' => true,
                        'rel' => true,
                        'target' => true,
                    ],
                    'br' => [],
                    'em' => [],
                    'strong' => [],
                ]),
            );
        },
        'supports' => [
            'html' => false,
            'multiple' => false,
            'reusable' => false,
        ],
    ]);

    register_block_type('esctt/sport-life', [
        'api_version' => '3',
        'attributes' => [
            'helloAssoUrl' => [
                'type' => 'string',
                'default' => '',
            ],
        ],
        'render_callback' => __NAMESPACE__ . '\\render_sport_life',
        'supports' => [
            'html' => false,
            'multiple' => false,
            'reusable' => false,
        ],
    ]);
});

add_filter('rest_pre_insert_page', function (object $post) {
    if (! in_array($post->post_status, ['publish', 'future'], true)) {
        return $post;
    }

    return page_structure_error((string) $post->post_content) ?? $post;
});

add_filter('wp_insert_post_empty_content', function (bool $maybeEmpty, array $postarr): bool {
    if ('page' !== ($postarr['post_type'] ?? '')) {
        return $maybeEmpty;
    }

    if (! in_array($postarr['post_status'] ?? '', ['publish', 'future'], true)) {
        return $maybeEmpty;
    }

    if (null !== page_structure_error(wp_unslash((string) ($postarr['post_content'] ?? '')))) {
        return true;
    }

    return $maybeEmpty;
}, 10, 2);

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_action('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    wp_enqueue_script('wp-blocks');
    wp_enqueue_script('wp-block-editor');
    wp_enqueue_script('wp-components');
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-i18n');

    if (! Vite::isRunningHot()) {
        $dependencies = json_decode(Vite::content('editor.deps.json'));

        foreach ($dependencies as $dependency) {
            wp_enqueue_script($dependency);
        }
    }
    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Disable on-demand block asset loading.
 *
 * @link https://core.trac.wordpress.org/ticket/61965
 */
add_filter('should_load_separate_core_block_assets', '__return_false');

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'esctt'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'esctt'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'esctt'),
        'id' => 'sidebar-footer',
    ] + $config);
});
