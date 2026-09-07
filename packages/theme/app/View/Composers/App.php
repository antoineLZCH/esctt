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
     * Retrieve the planned public pages for the primary navigation.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function clubNavigation(): array
    {
        return [
            ['label' => __('Accueil', 'esctt'), 'url' => home_url('/')],
            ['label' => __('Horaires', 'esctt'), 'url' => home_url('/#horaires')],
            ['label' => __('Tarifs', 'esctt'), 'url' => home_url('/#tarifs')],
            ['label' => __('Inscriptions', 'esctt'), 'url' => home_url('/inscriptions/')],
            ['label' => __('FAQ', 'esctt'), 'url' => home_url('/faq/')],
            ['label' => __('Contact', 'esctt'), 'url' => home_url('/contact/')],
            ['label' => __('Mentions légales', 'esctt'), 'url' => home_url('/mentions-legales/')],
            ['label' => __('Confidentialité', 'esctt'), 'url' => home_url('/confidentialite/')],
        ];
    }

    /**
     * Retrieve the planned public pages for the footer navigation.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function footerNavigation(): array
    {
        return $this->clubNavigation();
    }
}
