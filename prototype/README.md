# Prototype jetable — Accueil ES Colombienne

Question : quelle présentation des horaires reste utile sur mobile, en conservant tous les créneaux visibles ? La hiérarchie de page et la matrice tarifaire sont communes.

## Ouvrir

Depuis ce worktree : `python3 -m http.server 8087 --directory prototype`, puis http://localhost:8087/. Le fichier index.html s'ouvre aussi directement.

- A : grille hebdomadaire à axe horaire, retenue par défaut sur desktop.
- B : blocs par jour, empilés sans défilement horizontal, retenus par défaut sur mobile.
- C : semaine en lignes, jour à gauche et créneaux à droite, conservée pour comparaison.

Sans paramètre, le prototype bascule automatiquement entre A et B à 700 px. `?variant=A|B|C` force une variante. Le sélecteur flottant conserve le profil et change ce paramètre ; les flèches du clavier changent de variante hors contrôles et zone de défilement.

## Statut

Prototype de discussion validé pour clore la décision, sans choix de stack ou direction artistique définitive. La palette de travail reprend les teintes du maillot. La photo humaine du hero et celle du tournoi restent à fournir. Les boutons d'inscription mènent à une explication, pas à une adhésion active.

Hiérarchie retenue : navigation, bandeau d'information important, hero, courte présentation, horaires avec lieux, tarifs, vie du club, FAQ, partenaires, invitation à s'inscrire et pied de page. Le tournoi des familles est prioritaire parmi les tournois. Le maillot est présenté uniquement avec les photos réelles face et dos ; l'essai 3D est rejeté. Le bandeau et sa date sont explicitement des exemples. Un menu d'évitement donne accès au contenu, aux horaires et aux tarifs.

Les horaires sont fictifs sauf lundi 20 h–22 h en jeu libre pour adultes loisirs et compétiteurs. Lieux A/B, montants et catégories tarifaires : démonstration uniquement. Le créneau après minuit teste explicitement l'affichage du jour suivant. Pas de placement des créneaux qui se chevauchent dans ce prototype.

## Vérifications

`PLAYWRIGHT_MODULE=/chemin/vers/playwright/index.mjs node prototype/check.mjs`

Vérifie les trois variantes à 320, 375 et 1280 px : absence de débordement global, conservation des 8 créneaux, 4 adaptés aux jeunes, absence de débordement vertical interne et images chargées. Vérifie aussi la bascule automatique A/B, l'ordre navigation–bandeau–hero, le menu d'évitement, les cinq questions fréquentes, les photos du maillot et le statut fictif de la date. `?check=1` exécute les assertions de profil et d'horaire après minuit dans le navigateur.

Ne constitue pas une validation d'accessibilité : clavier complet, lecteur d'écran, contrastes et utilisabilité humaine restent à évaluer. Le détecteur Impeccable a tourné en mode dégradé (parseurs indisponibles), avec deux alertes sur les bordures d'accent. Bordures conservées dans ce brouillon ; la sélection est aussi indiquée par un texte explicite.

## Sources

Décision validée : https://github.com/antoineLZCH/esctt/issues/7

Assets copiés des fichiers fournis par le porteur : les deux SVG, les photos et le visuel graphique du maillot, et la capture `reference-accueil.png`. La capture n'autorise pas la reprise de ses données comme informations réelles.

## Essai du maillot en 3D

Ouvrir http://localhost:8087/maillot-3d.html avec le même serveur (ne pas ouvrir ce fichier directement en file:// : le chargement des textures WebGL demande une origine HTTP).

Modèle paramétrique simplifié en WebGL natif, sans dépendance. Rotation par glissement, boutons et curseur accessible au clavier ; pas d'animation automatique. Les deux photos réelles restent disponibles si WebGL est indisponible. Elles sont conservées dans assets/maillot-face.jpg et assets/maillot-dos.jpg.

Le volume, la coupe, les manches, les empiècements et les marquages sont approximatifs. L'écusson reprend le SVG fourni, recoloré pour ce visuel ; le grand ESCTT est recomposé en texte, pas repris d'un fichier d'impression. Ce n'est pas une reconstruction des photos ni un asset de production. Le but est d'évaluer l'intérêt de l'interaction, pas d'approuver la fidélité d'un modèle.

Vérification : `PLAYWRIGHT_MODULE=/chemin/vers/playwright/index.mjs node prototype/check-maillot.mjs` (serveur actif). Chargement, commandes et absence de débordement vérifiés à 375/1280 px, ainsi que le repli sans WebGL. Détecteur graphique en mode dégradé sans alerte ; accessibilité complète et performances sur vrai téléphone non certifiées.

Aucun de ces fichiers ne doit être fusionné comme code de production.
