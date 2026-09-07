<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var string[]
     */
    protected static $views = [
        '*',
    ];

    /**
     * Retrieve the site name.
     */
    public function siteName(): string
    {
        return get_bloginfo('name', 'display');
    }

    /**
     * Retrieve the active important message.
     *
     * @return array{message: string, detail_url: string, detail_label: ?string}|null
     */
    public function importantMessage(): ?array
    {
        return esctt_get_active_important_message_data();
    }
}
