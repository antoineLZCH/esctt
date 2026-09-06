<?php

test('theme hooks provide the editor assets and WordPress defaults', function () {
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';

    $settings = apply_filters('block_editor_settings_all', []);

    expect($settings['styles'])->not->toBeEmpty()
        ->and(apply_filters('theme_file_path', 'fallback', 'theme.json'))->toEndWith('/build/assets/theme.json')
        ->and(apply_filters('theme_file_path', 'fallback', 'other.json'))->toBe('fallback')
        ->and(apply_filters('should_load_separate_core_block_assets', true))->toBeFalse()
        ->and(apply_filters('excerpt_more', '...'))->toContain('Continued');

    require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH . 'wp-admin/includes/screen.php';

    $GLOBALS['current_screen'] = null;
    ob_start();
    do_action('admin_head');
    $withoutBlockEditor = ob_get_clean();

    set_current_screen('post');
    get_current_screen()->is_block_editor(true);

    ob_start();
    do_action('admin_head');
    $withBlockEditor = ob_get_clean();

    ob_start();
    do_action('admin_head');
    ob_end_clean();

    $GLOBALS['current_screen'] = null;
    restore_error_handler();
    restore_exception_handler();

    expect($withoutBlockEditor)->toBe('')
        ->and($withBlockEditor)->toContain('/editor-')
        ->and(wp_script_is('wp-dom-ready'))->toBeTrue();
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('theme composers cover post and comment states', function () {
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';

    $originalQuery = $GLOBALS['wp_query'];
    $originalPost = $GLOBALS['post'] ?? null;
    $postId = wp_insert_post([
        'post_title' => 'Coverage post',
        'post_status' => 'publish',
        'post_type' => 'post',
    ]);
    $GLOBALS['post'] = get_post($postId);

    $setView = function (object $composer, string $name): void {
        $view = new class ($name) {
            public function __construct(private readonly string $name) {}

            public function name(): string
            {
                return $this->name;
            }
        };

        $property = new ReflectionProperty(\Roots\Acorn\View\Composer::class, 'view');
        $property->setValue($composer, $view);
    };

    $setQuery = function (string $flag = ''): \WP_Query {
        $query = new WP_Query();
        if ($flag !== '') {
            $query->{$flag} = true;
        }
        $GLOBALS['wp_query'] = $query;

        return $query;
    };

    try {
        $app = new \App\View\Composers\App();
        expect($app->siteName())->toBe(get_bloginfo('name', 'display'));

        $post = new \App\View\Composers\Post();
        $setView($post, 'partials.content');
        expect($post->title())->toBe('Coverage post');

        $setView($post, 'partials.page-header');
        $pageForPosts = wp_insert_post([
            'post_title' => 'Posts page',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
        update_option('page_for_posts', $pageForPosts);
        $setQuery('is_home');
        expect($post->title())->toBe('Posts page');

        update_option('page_for_posts', 0);
        expect($post->title())->toBe('Latest Posts');

        $setQuery('is_archive');
        expect($post->title())->not->toBe('');

        $search = $setQuery('is_search');
        $search->query_vars['s'] = 'coverage query';
        expect($post->title())->toContain('coverage query');

        $setQuery('is_404');
        expect($post->title())->toBe('Not Found');

        $setQuery();
        expect($post->title())->toBe('Coverage post')
            ->and($post->pagination())->toBeString();

        $comments = new \App\View\Composers\Comments();
        $commentCount = static function (): int {
            return 1;
        };
        add_filter('get_comments_number', $commentCount, 99);
        expect($comments->title())->toContain('One');
        remove_filter('get_comments_number', $commentCount, 99);

        $commentCount = static function (): int {
            return 2;
        };
        add_filter('get_comments_number', $commentCount, 99);
        expect($comments->title())->not->toContain('One');
        remove_filter('get_comments_number', $commentCount, 99);

        $query = $setQuery();
        expect($comments->responses())->toBeNull();
        $query->comments = [new WP_Comment((object) [
            'comment_ID' => 1,
            'comment_post_ID' => $postId,
            'comment_content' => 'Coverage comment',
            'comment_approved' => '1',
        ])];
        $query->comment_count = 1;
        $query->current_comment = -1;
        expect($comments->responses())->toBeString();

        $query = $setQuery('is_singular');
        $query->query_vars['cpage'] = 2;
        $query->max_num_comment_pages = 3;
        expect($comments->previous())->toBeString();
        $query->query_vars['cpage'] = 1;
        expect($comments->previous())->toBeNull();

        $query->query_vars['cpage'] = 1;
        $query->max_num_comment_pages = 2;
        expect($comments->next())->toBeString();
        $query->max_num_comment_pages = 1;
        expect($comments->next())->toBeNull();

        update_option('page_comments', true);
        $query->max_num_comment_pages = 2;
        expect($comments->paginated())->toBeTrue();
        $query->max_num_comment_pages = 1;
        expect($comments->paginated())->toBeFalse();

        $commentsOpen = static function (): bool {
            return false;
        };
        add_filter('comments_open', $commentsOpen, 99);
        $commentCount = static function (): int {
            return 1;
        };
        add_filter('get_comments_number', $commentCount, 99);
        expect($comments->closed())->toBeTrue();
        remove_filter('comments_open', $commentsOpen, 99);
        remove_filter('get_comments_number', $commentCount, 99);
        expect($comments->closed())->toBeFalse();
    } finally {
        $GLOBALS['wp_query'] = $originalQuery;
        $GLOBALS['post'] = $originalPost;
        wp_delete_post($postId, true);
        if (isset($pageForPosts)) {
            wp_delete_post($pageForPosts, true);
        }
    }
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);

test('theme bootstrap reports missing files', function () {
    putenv('APP_RUNNING_IN_CONSOLE=false');
    require_once dirname(__DIR__, 2) . '/web/wp/wp-load.php';

    $root = dirname(__DIR__, 4);
    $functions = $root . '/packages/theme/functions.php';
    $autoloaders = [
        $root . '/packages/theme/vendor/autoload.php',
        $root . '/apps/wordpress/vendor/autoload.php',
    ];
    $backups = [];
    $dieFilter = static function () {
        return static function ($message): never {
            throw new RuntimeException((string) $message);
        };
    };

    add_filter('wp_die_handler', $dieFilter, 99);

    try {
        foreach ($autoloaders as $autoloader) {
            if (file_exists($autoloader)) {
                $backup = $autoloader . '.coverage-backup';
                rename($autoloader, $backup);
                $backups[] = [$autoloader, $backup];
            }
        }

        $message = null;
        try {
            include $functions;
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
        }

        expect($message)->toContain('Error locating autoloader');
    } finally {
        remove_filter('wp_die_handler', $dieFilter, 99);
        foreach (array_reverse($backups) as [$autoloader, $backup]) {
            rename($backup, $autoloader);
        }
    }

    $stylesheetWasSet = isset($GLOBALS['wp_stylesheet_path']);
    $templateWasSet = isset($GLOBALS['wp_template_path']);
    $stylesheetPath = $stylesheetWasSet ? $GLOBALS['wp_stylesheet_path'] : null;
    $templatePath = $templateWasSet ? $GLOBALS['wp_template_path'] : null;
    $messages = [];
    $dieFilter = static function () use (&$messages) {
        return static function ($message) use (&$messages): void {
            $messages[] = (string) $message;
        };
    };

    $GLOBALS['wp_stylesheet_path'] = sys_get_temp_dir() . '/esctt-missing-theme';
    $GLOBALS['wp_template_path'] = $GLOBALS['wp_stylesheet_path'];
    add_filter('wp_die_handler', $dieFilter, 99);

    try {
        include $functions;
    } finally {
        remove_filter('wp_die_handler', $dieFilter, 99);
        restore_error_handler();
        restore_exception_handler();
        if ($stylesheetWasSet) {
            $GLOBALS['wp_stylesheet_path'] = $stylesheetPath;
        } else {
            unset($GLOBALS['wp_stylesheet_path']);
        }
        if ($templateWasSet) {
            $GLOBALS['wp_template_path'] = $templatePath;
        } else {
            unset($GLOBALS['wp_template_path']);
        }
    }

    expect($messages)->toHaveCount(2)
        ->and($messages[0])->toContain('Error locating');
})->skip(
    fn() => getenv('ESCTT_WORDPRESS_TESTS') !== '1',
    'Requires the CI WordPress installation.',
);
