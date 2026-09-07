<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Registration extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var string[]
     */
    protected static $views = [
        'partials.content-inscriptions',
    ];

    /**
     * Return the ordered registration guidance.
     *
     * @return array<int, array{title: string, description: string}>
     */
    public function steps(): array
    {
        return [
            [
                'title' => __('Choisir un créneau', 'esctt'),
                'description' => __('Consultez les horaires, le lieu et le profil de joueur adaptés à votre pratique.', 'esctt'),
            ],
            [
                'title' => __('Vérifier votre situation', 'esctt'),
                'description' => __('Identifiez votre catégorie tarifaire et le montant applicable à votre situation.', 'esctt'),
            ],
            [
                'title' => __('Préparer les informations utiles', 'esctt'),
                'description' => __('Lisez les informations de santé ci-dessous et préparez les éléments demandés par le service d’adhésion externe. Ne transmettez aucun document au site du club.', 'esctt'),
            ],
            [
                'title' => __('Finaliser l’adhésion', 'esctt'),
                'description' => __('Après ces étapes, utilisez le lien vers le service externe d’adhésion pour terminer votre démarche.', 'esctt'),
            ],
        ];
    }

    /**
     * Return the essential PPS information for the structured HTML view.
     *
     * @return array<int, array{heading: string, text: string}>
     */
    public function preventionHealth(): array
    {
        return [
            [
                'heading' => __('18 à 64 ans', 'esctt'),
                'text' => __('Le PPS est un parcours en ligne, gratuit et sécurisé, accessible depuis l’Espace Licencié. Il permet de valider une licence avec pratique sportive au début de chaque saison.', 'esctt'),
            ],
            [
                'heading' => __('65 ans et plus', 'esctt'),
                'text' => __('Un certificat médical reste obligatoire à chaque changement de catégorie. Entre deux changements, si la pratique est continue, le PPS peut être utilisé.', 'esctt'),
            ],
            [
                'heading' => __('Si une situation nécessite un avis médical', 'esctt'),
                'text' => __('Si vous identifiez un symptôme ou une situation signalée pendant le PPS, consultez un médecin et fournissez un certificat médical avant d’obtenir une licence avec pratique sportive.', 'esctt'),
            ],
            [
                'heading' => __('Accéder au PPS', 'esctt'),
                'text' => __('Un compte actif sur SPID Ma Licence est nécessaire. Utilisez « Activer son compte » ou « Créer son compte » depuis la page de connexion de l’Espace Licencié.', 'esctt'),
            ],
        ];
    }

    /**
     * Render the required Hero before the registration sections.
     */
    public function hero(): string
    {
        $blocks = parse_blocks((string) get_the_content());

        return render_block($blocks[0]);
    }

    /**
     * Render the remaining editor blocks after the registration sections.
     */
    public function editorContent(): string
    {
        $blocks = parse_blocks((string) get_the_content());
        return implode('', array_map(
            static fn (array $block): string => render_block($block),
            array_slice($blocks, 1),
        ));
    }

    /**
     * Return the Admin-managed season documents.
     *
     * @return array<int, array{title: string, url: string, season: string}>
     */
    public function documents(): array
    {
        return esctt_registration_documents();
    }

    /**
     * Return the site's document policy for the public page.
     */
    public function documentsPolicy(): string
    {
        return esctt_membership_documents_policy();
    }
}
