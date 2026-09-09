# Recherche — articulation avec la future application du club

_Date de consultation des sources : 6 septembre 2026 (UTC)._

_Question traitée : [ticket #13](https://github.com/antoineLZCH/esctt/issues/13)._

## Périmètre et méthode

Le ticket demande une comparaison de **WordPress sans Elementor**, **Astro statique avec API éditoriale restreinte**, **Laravel–Inertia–Vue** et **Adonis–Inertia–Vue** selon six axes : frontières, données/authentification, runtimes/déploiements, réemploi, couplage et maintenance. Les quatre variantes sont comparées, mais ce rapport ne définit ni les fonctionnalités, ni les données, ni les rôles, ni le fournisseur d’identité, ni le schéma ou l’hébergement de la future application.

Les domaines cités par le ticket — gestion des équipes, informations HelloAsso et vie du club — servent uniquement de repères de responsabilité. Le rapport distingue ce qui peut rester une donnée éditoriale du site (par exemple horaires, tarifs ou lien HelloAsso) de ce qui appartiendrait à l’application future (par exemple les données opérationnelles d’équipes ou de vie du club), sans créer leur modèle. Il n’infère ni accès en écriture à HelloAsso ni synchronisation entre les deux applications.

Les décisions de projet utilisées comme contraintes sont les commentaires de résolution primaires des tickets [#2](https://github.com/antoineLZCH/esctt/issues/2#issuecomment-5559761180), [#3](https://github.com/antoineLZCH/esctt/issues/3#issuecomment-5560363960), [#4](https://github.com/antoineLZCH/esctt/issues/4#issuecomment-5560402501), [#5](https://github.com/antoineLZCH/esctt/issues/5#issuecomment-5560471633), [#9](https://github.com/antoineLZCH/esctt/issues/9#issuecomment-5560474825), [#10](https://github.com/antoineLZCH/esctt/issues/10#issuecomment-5560508610) et [#11](https://github.com/antoineLZCH/esctt/issues/11#issuecomment-5560580546). Les capacités techniques ci-dessous viennent des documentations officielles des projets. Une **observation** est un fait documenté ; une **inférence** est explicitement signalée comme telle.

## Ce qui est établi avant la comparaison

- Le périmètre actuel du site couvre l’information publique, le parcours d’inscription et le widget/lien HelloAsso ; aucune fonction de la future application n’est incluse dans cette refonte ([résolution #2](https://github.com/antoineLZCH/esctt/issues/2#issuecomment-5559761180)). C’est une frontière de projet, pas une conception de la future application.
- Les horaires, lieux et tarifs sont des données éditoriales structurées ; les administrateurs doivent pouvoir modifier le contenu sans déploiement ([résolution #3](https://github.com/antoineLZCH/esctt/issues/3#issuecomment-5560363960)).
- Le plafond de 250 € par an et la routine de 1–2 h par mois incluent l’infrastructure, les sauvegardes et la supervision ; sauvegardes hors VPS, restaurations testées, accès nominatifs/MFA et continuité par un second administrateur restent exigés ([résolution #4](https://github.com/antoineLZCH/esctt/issues/4#issuecomment-5560402501)).
- Les tests et seuils de qualité sont communs aux stacks ; le spike du ticket #11 montre une mesure de branches sur les cas minimaux Astro, Vue et Adonis, mais ne prouve pas une architecture de la future application ([résolutions #5](https://github.com/antoineLZCH/esctt/issues/5#issuecomment-5560471633) et [#11](https://github.com/antoineLZCH/esctt/issues/11#issuecomment-5560580546)).

Les sources techniques ne permettent pas de convertir honnêtement ces contraintes en euros ou en heures sans connaître la charge de la future application, le trafic, les services d’hébergement et le niveau de disponibilité. Le « coût réel » est donc comparé par surfaces à exploiter et à maintenir, sans devis ni verdict de compatibilité budgétaire. Cette limite est également conservée par la résolution du ticket #9 ([source projet](https://github.com/antoineLZCH/esctt/issues/9#issuecomment-5560474825)).

## Comparaison par variante

### 1. WordPress sans Elementor

**Effet de l’absence d’Elementor.** Ce choix retire la surface de constructeur et ses extensions de la chaîne à maintenir ; il ne retire ni le runtime PHP/DB/HTTP, ni l’API REST, ni les thèmes et extensions encore retenus. Il réduit donc une famille de compatibilités, sans transformer WordPress en API métier de la future application.

- **Frontières et données — fait puis inférence.** L’API REST WordPress permet à des applications séparées de recevoir les données du site au format JSON ([S1]). La frontière possible est donc un contenu éditorial publié consommé par HTTP ; dépendre de tables, de champs ou d’extensions internes serait une inférence de couplage plus forte, non une capacité requise par le ticket. La propriété des données de la future application reste indéterminée. Les horaires, tarifs et lien HelloAsso peuvent rester des contenus du site ; les données opérationnelles d’équipes ou de vie du club resteraient à attribuer à l’application future par un contrat ultérieur.
- **Données et authentification — fait puis limite.** Pour un utilisateur déjà authentifié, l’API documente l’authentification par cookie et nonce ; les *Application Passwords* sont des identifiants d’intégration révocables transmis via HTTPS et ne remplacent pas la connexion interactive à `wp-admin` ([S2], [S3]). Ces mécanismes ne définissent pas une identité commune entre deux applications : fournisseur d’identité, comptes et partage éventuel sont à décider ultérieurement.
- **Runtimes et déploiements — fait puis conséquence.** Les exigences WordPress reposent sur un serveur PHP, une base de données et un serveur HTTP avec HTTPS ([S4]). Dans la topologie distincte, le socle minimal est donc le runtime WordPress et le runtime avec déploiement propres à la future application si elle est dynamique ; un frontend futur statique remplacerait ce second runtime par son build et son hébergement. La lecture de l’API ne fusionne pas ces processus ([S1]).
- **Réemploi.** Le réemploi directement établi est l’interface de contenu REST ([S1]). Le thème et les extensions WordPress sont des éléments du site ; leur réemploi dans une application d’une autre pile est une inférence conditionnelle, pas un actif portable garanti.
- **Couplage.** Une dépendance limitée au contrat de contenu et à sa fraîcheur permet des déploiements séparés (inférence à partir de l’API HTTP [S1]). Une dépendance aux types de champs ou extensions augmente le couplage de version et de publication.
- **Maintenance.** Le site ajoute les mises à jour du cœur, du thème et des extensions à la chaîne de maintenance. WordPress documente l’application automatique des mises à jour mineures/de sécurité du cœur et l’activation séparée des mises à jour automatiques des extensions et thèmes ([S5], [S6]) ; il reste à qualifier les compatibilités, sauvegarder et superviser. Avec une application distincte, ces opérations ne disparaissent pas : elles s’additionnent à celles de cette application.

**Compromis observé :** contrat éditorial réutilisable et séparation de déploiement, contre maintien d’un runtime CMS en parallèle et dépendance potentielle à ses extensions. Ce constat ne choisit ni l’emplacement des données métier ni l’authentification future.

### 2. Astro statique avec API éditoriale restreinte

**Portée de l’API restreinte.** Pour cette comparaison, une surface en lecture seule limitée aux contenus publiés réduit la surface de modification et le couplage métier, mais ne permet pas au site statique de gérer ou synchroniser les équipes, la vie du club ou les informations HelloAsso. Le build doit disposer d’un identifiant à privilèges minimaux, conservé côté build et jamais exposé au navigateur ([S8], [S10]). Une API indisponible bloque ou retarde la publication selon le pipeline retenu ; une publication réussie peut rester obsolète jusqu’au build suivant. Ces conséquences sont évaluées sans fixer le protocole ou le pipeline.

- **Frontières et données — fait puis périmètre.** Astro documente un build dont la sortie est placée par défaut dans `dist/`, ainsi que la récupération de données distantes pendant la construction ([S7], [S8]). L’API éditoriale restreinte est donc la frontière d’entrée de cette variante, telle que nommée par le ticket ; son schéma, ses droits et son propriétaire ne sont pas définis ici. Les données d’équipes ou de vie du club ne deviennent pas pour autant éditoriales ; les exposer au site supposerait un contrat séparé. Le lien HelloAsso peut rester un contenu publié, sans supposer une lecture de données HelloAsso.
- **Données et authentification — limite technique.** Un artefact statique ne fournit pas de session serveur ou de service d’identité pour la future application. Astro documente le rendu à la demande comme un autre mode nécessitant un adaptateur ([S9]) et distingue les variables publiques accessibles au client des variables privées ([S10]). Une authentification éventuelle relève donc de l’API, de l’application ou d’un fournisseur séparé ; aucun secret de build ne doit devenir une donnée du navigateur. Ce sont des limites de la variante, pas une proposition de protocole.
- **Runtimes et déploiements — fait puis comptage.** Le frontend statique nécessite un build et un hébergement de fichiers ; l’API éditoriale est une surface de service séparée. Dans la topologie distincte, le socle minimal est donc build + hébergement statique + API éditoriale + runtime et déploiement propres à la future application si elle est dynamique. Il n’y a pas de processus serveur Astro dans le strict mode statique ; activer le rendu à la demande changerait l’option étudiée ([S7], [S9]). Si l’API est fournie par WordPress, le runtime WordPress s’ajoute ; si elle est fournie autrement, le runtime correspondant s’ajoute. La future application conserve son propre déploiement.
- **Réemploi.** Astro documente l’intégration de composants Vue ([S11]). Des composants de présentation ou un client de contenu pourraient donc être réutilisés si la future application adopte les mêmes contrats ; aucun service serveur ou domaine métier n’est fourni par l’artefact statique.
- **Couplage.** Le runtime du frontend reste découplé de celui de la future application, mais le build dépend du contrat éditorial, de sa disponibilité et de sa politique de republication (inférence à partir de [S7] et [S8]). Le couplage d’exploitation se déplace vers la chaîne API → build → hébergement.
- **Maintenance.** Le frontend évite l’exploitation d’un processus applicatif permanent, mais conserve les dépendances de build, l’hébergement statique et la chaîne de publication ; l’API éditoriale doit être sauvegardée, sécurisée, supervisée et maintenue. Si le CMS est WordPress, sa maintenance s’ajoute également. Le gain ou la perte en heures et en euros ne peut pas être quantifié sans le pipeline et l’API retenus.

**Compromis observé :** isolation du processus public et déploiement de fichiers, contre une chaîne de publication supplémentaire et l’absence de socle serveur réutilisable. Passer en rendu à la demande pour obtenir ce socle ne serait plus la variante statique comparée.

### 3. Laravel–Inertia–Vue

- **Frontières et données — fait puis scénario.** Inertia conserve dans le framework serveur le routage, les contrôleurs, l’authentification, l’autorisation et la récupération des données, et rend des composants de page JavaScript côté client ([S12]). Le site peut donc rester un client du backend Laravel sans que le protocole Inertia impose une API publique dédiée. Une future application pourrait être un autre client ou un service séparé, mais ses contrats et la propriété des données ne sont pas définis. Dans le scénario partagé, des composants d’affichage ou des services éditoriaux pourraient être réemployés ; les données d’équipes, de vie du club et les éventuelles informations HelloAsso restent à séparer par responsabilité, sans que le backend commun les fusionne automatiquement.
- **Données et authentification — capacités optionnelles.** Laravel Sanctum documente l’authentification d’une SPA par cookie/session sur le même domaine de premier niveau (des sous-domaines sont possibles), ainsi que l’émission de jetons API ([S15]). Laravel Passport documente un serveur OAuth2 complet ([S16]). Ces capacités rendent plusieurs articulations possibles, mais ne choisissent ni un SSO, ni des comptes communs, ni une autorité d’identité.
- **Runtimes et déploiements — fait puis scénario.** L’adaptateur serveur Inertia s’installe dans le framework serveur ([S13]) et le guide de déploiement Laravel documente le serveur de production, l’optimisation et une route de santé ([S14]). Le site demande donc un runtime applicatif Laravel. Un backend Laravel commun correspond à un runtime et un déploiement backend partagés pour les deux responsabilités ; des backends séparés correspondent à deux runtimes et déploiements backend. Dans les deux cas, les données, sauvegardes et contrôles propres à la future application restent à exploiter.
- **Réemploi.** Un backend commun rend potentiellement réutilisables des règles et services PHP ; une séparation de déploiement peut aussi partager des bibliothèques versionnées. Le réemploi est conditionnel au périmètre réel et ne permet pas d’inférer une économie de maintenance.
- **Couplage.** Même backend : couplage élevé des versions, migrations, autorisations et incidents. Backends séparés : couplage ramené aux API ou paquets explicitement partagés (inférences à partir de [S12]–[S16]). Inertia ne tranche pas entre ces deux topologies.
- **Maintenance.** Laravel publie une politique de support de 18 mois pour les corrections fonctionnelles et de 2 ans pour les correctifs de sécurité ([S17]). Cette information aide à planifier les montées de version ; elle ne chiffre pas leur effort. Un backend commun évite éventuellement de maintenir deux runtimes Laravel, mais agrandit le rayon d’impact d’une mise à jour ; des applications séparées conservent le coût d’exploitation de chacune.

**Compromis observé :** possibilité conditionnelle de réemploi serveur et de backend commun, contre une coordination plus forte si le runtime est partagé. L’existence de Sanctum ou Passport ne constitue pas une décision d’identité.

### 4. Adonis–Inertia–Vue

- **Frontières et données — fait puis scénario.** La documentation Adonis présente Inertia comme un pont vers React/Vue : routes et contrôleurs restent server-first et rendent des pages Inertia au lieu de renvoyer directement HTML ou JSON ([S18]). Comme pour Laravel, un backend commun ou des déploiements séparés sont techniquement des scénarios ; ni les données métier ni leurs contrats ne sont définis par cette recherche. Un partage éventuel peut concerner la présentation ou des services éditoriaux, mais ne doit pas confondre les responsabilités des équipes, de la vie du club ou des informations HelloAsso.
- **Données et authentification — capacités documentées.** Adonis documente un guard de session fondé sur une session et un cookie ([S21]) et un guard de jetons opaques pour authentifier des requêtes d’API ([S22]). La page d’introduction distingue aussi les guards session, access-token, basic, custom et social ([S20]). Ces mécanismes ne définissent pas un SSO inter-applications ni un fournisseur d’identité ; le choix et la maintenance d’une fédération éventuelle restent ouverts.
- **Runtimes et déploiements — fait puis scénario.** Le déploiement Adonis compile l’application avec `node ace build`, copie les actifs Vite dans la sortie et exécute ensuite le serveur Node de production ([S19]). Le site a donc un runtime Node et une chaîne de build. Un serveur Adonis commun correspond à un runtime et un déploiement backend partagés pour les deux responsabilités ; des serveurs séparés correspondent à deux runtimes et déploiements backend. Le partage réduit les processus, au prix du même partage de disponibilité, sécurité et versions que dans le scénario Laravel.
- **Réemploi.** Le typage et les modules TypeScript, ainsi que les composants Vue, peuvent être partagés si les deux produits adoptent ces contrats et versions. Cela reste une possibilité conditionnelle ; partager des modèles internes ferait aussi partager leurs changements.
- **Couplage.** Backend commun : couplage de runtime, de versions et de livraison. Backends séparés : couplage limité aux contrats ou paquets publiés. Inertia ne décide pas du niveau de séparation.
- **Maintenance.** Les guides Adonis documentent des protections SSR telles que CSRF, CSP et HSTS ([S23]) et la construction/les processus de production ([S19]). Il faut maintenir Node, npm, Adonis, Inertia, Vue, les dépendances et le code applicatif ; les guides consultés ne donnent pas un horizon de support comparable à la politique Laravel. Cette absence de calendrier dans les sources consultées est une incertitude à vérifier, pas la preuve qu’Adonis serait non maintenu. Le coût d’une mutualisation ou de deux déploiements reste dépendant du périmètre réel.

**Compromis observé :** possibilité conditionnelle de partage TypeScript et de runtime Node, contre la coordination et le rayon d’impact d’un backend commun ; les mécanismes d’authentification documentés ne suffisent pas à décider une identité partagée.

## Lecture transversale des six axes

| Axe | WordPress sans Elementor | Astro statique + API éditoriale restreinte | Laravel–Inertia–Vue | Adonis–Inertia–Vue |
|---|---|---|---|---|
| **Frontières** | CMS et site public ; application séparée ou consommatrice de l’API | Artefact public ; API éditoriale distincte | Contrôleurs/backend serveur et pages Vue | Contrôleurs/backend serveur et pages Vue |
| **Données / authentification** | API REST et identifiants d’intégration WordPress ; pas d’identité inter-app établie | Pas d’identité dans l’artefact ; API/app/fournisseur à déterminer | Sessions/cookies, jetons, OAuth2 disponibles comme briques optionnelles | Sessions et jetons documentés ; fédération à déterminer |
| **Runtimes / déploiements** | Runtime PHP/DB/HTTP du CMS + surface future séparée | Build + hébergement statique + API ; pas de runtime Astro permanent en statique | Runtime serveur ; backend commun possible, sinon surface future séparée | Runtime Node/build ; backend commun possible, sinon surface future séparée |
| **Réemploi** | Surtout contenu et contrat REST | Présentation Vue/client de contenu, sous conditions | Services PHP et composants Vue, sous conditions | Modules TypeScript et composants Vue, sous conditions |
| **Couplage** | Contrat éditorial ; fort si internes/extensions | Contrat et fraîcheur de publication ; runtime isolé | API faible à backend commun fort | API faible à backend commun fort |
| **Maintenance** | CMS, thème/extensions, données et application future | Dépendances/build/hébergement/API et éventuellement CMS | Framework, Composer/npm, code et éventuel backend partagé ; support documenté | Node/npm, framework, code et éventuel backend partagé ; horizon de support à vérifier |

Les formulations « faible », « fort » et « sous conditions » sont des **inférences comparatives** à partir des frontières et capacités documentées ; ce ne sont ni des mesures de performance ni des estimations de charge.

## Coût d’une seconde application : distinguer les topologies

1. **À côté de WordPress.** Même si l’application ne lit que le contenu publié, le runtime CMS, sa base, ses sauvegardes, ses mises à jour et sa supervision restent à exploiter ; l’application ajoute son runtime éventuel, son code métier, ses données, son authentification, ses sauvegardes et son support. Réutiliser l’API évite de recopier le contenu et une partie de l’édition, pas les responsabilités d’exécution ni les coûts propres à la seconde application. Cette conclusion est une inférence fondée sur l’API et les exigences WordPress ([S1], [S4]–[S6]).
2. **À côté d’Astro statique.** Le frontend n’ajoute pas de processus serveur Astro, mais il ajoute un build, une publication et un hébergement de fichiers ; l’API éditoriale doit être exploitée en parallèle, avec ses identifiants à privilèges minimaux, ses sauvegardes et sa supervision. La future application ajoute son propre code, ses données, son authentification et son support. Si l’API est fournie par WordPress, la première topologie s’applique aussi ; le nombre exact dépend de l’architecture retenue ([S7]–[S10]).
3. **Même backend Laravel ou Adonis, responsabilités séparées.** Un socle partagé économise potentiellement un runtime backend, un déploiement et une partie de la supervision ; il n’élimine ni le code de la future application, ni ses données, son authentification, ses sauvegardes et son support. Il partage aussi les versions, les incidents, la disponibilité, les secrets et les contrôles de sécurité. Séparer les modules logiquement ne supprime pas ce couplage de runtime. Deux backends séparés restaurent l’indépendance mais ne donnent plus l’économie de processus. Ces compromis sont déduits des architectures Inertia et des guides de déploiement ([S12]–[S19]), sans concevoir les modules de la future application.
4. **Ce qui ne peut pas être chiffré ici.** Le prix de l’hébergement, du stockage externe, de la supervision, des sauvegardes, du trafic et du temps de reprise dépend de fournisseurs et de niveaux de service non arrêtés. Aucun montant ou nombre d’heures n’est donc présenté comme fait ; la contrainte budgétaire et la charge mensuelle restent les critères à confronter à une architecture ultérieure ([résolution #4](https://github.com/antoineLZCH/esctt/issues/4#issuecomment-5560402501)).

## Questions laissées ouvertes, sans préjuger du choix

- périmètre exact, clients et données de la future application ;
- contenu éditorial réellement commun, fraîcheur attendue et contrat de lecture ;
- comptes, propriété de l’identité, MFA, SSO éventuel et exigences de sécurité ;
- séparation ou mutualisation des runtimes, déploiements, sauvegardes et alertes ;
- fournisseur d’hébergement, trafic, objectifs de disponibilité et compétences de reprise.

Ces inconnues empêchent de sélectionner une variante sur la seule articulation. Elles constituent les entrées d’une décision future, pas des éléments à concevoir dans ce rapport.

## Sources primaires consultées

### Projet

- [S0 — ticket #13](https://github.com/antoineLZCH/esctt/issues/13).
- [S0a — résolution #2](https://github.com/antoineLZCH/esctt/issues/2#issuecomment-5559761180), [S0b — résolution #3](https://github.com/antoineLZCH/esctt/issues/3#issuecomment-5560363960), [S0c — résolution #4](https://github.com/antoineLZCH/esctt/issues/4#issuecomment-5560402501), [S0d — résolution #5](https://github.com/antoineLZCH/esctt/issues/5#issuecomment-5560471633).
- [S0e — résolution #9](https://github.com/antoineLZCH/esctt/issues/9#issuecomment-5560474825), [S0f — résolution #10](https://github.com/antoineLZCH/esctt/issues/10#issuecomment-5560508610), [S0g — résolution #11](https://github.com/antoineLZCH/esctt/issues/11#issuecomment-5560580546).

### Documentation éditeur / projet officiel

- [S1 — WordPress REST API Handbook](https://developer.wordpress.org/rest-api/).
- [S2 — WordPress REST API: Authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/).
- [S3 — WordPress Application Passwords](https://developer.wordpress.org/advanced-administration/security/application-passwords/).
- [S4 — WordPress Requirements](https://wordpress.org/about/requirements/).
- [S5 — WordPress: Updating WordPress](https://wordpress.org/documentation/article/updating-wordpress/).
- [S6 — WordPress: Plugin and themes auto-updates](https://wordpress.org/documentation/article/plugins-themes-auto-updates/).
- [S7 — Astro: Deploy your Astro Site](https://docs.astro.build/en/guides/deploy/).
- [S8 — Astro: Data fetching](https://docs.astro.build/en/guides/data-fetching/).
- [S9 — Astro: On-demand rendering](https://docs.astro.build/en/guides/on-demand-rendering/).
- [S10 — Astro: Environment variables](https://docs.astro.build/en/guides/environment-variables/).
- [S11 — Astro: Vue integration](https://docs.astro.build/en/guides/integrations-guide/vue/).
- [S12 — Inertia v3: How it works](https://inertiajs.com/docs/v3/core-concepts/how-it-works).
- [S13 — Inertia v3: Server-side setup](https://inertiajs.com/docs/v3/installation/server-side-setup).
- [S14 — Laravel 13.x: Deployment](https://laravel.com/framework/docs/13.x/deployment).
- [S15 — Laravel 13.x: Sanctum](https://laravel.com/framework/docs/13.x/sanctum).
- [S16 — Laravel 13.x: Passport](https://laravel.com/framework/docs/13.x/passport).
- [S17 — Laravel 13.x: Release notes](https://laravel.com/framework/docs/13.x/releases).
- [S18 — AdonisJS: Inertia](https://docs.adonisjs.com/guides/frontend/inertia).
- [S19 — AdonisJS: Deployment](https://docs.adonisjs.com/deployment).
- [S20 — AdonisJS: Authentication introduction](https://docs.adonisjs.com/guides/auth/introduction).
- [S21 — AdonisJS: Session guard](https://docs.adonisjs.com/guides/auth/session-guard).
- [S22 — AdonisJS: Access tokens guard](https://docs.adonisjs.com/guides/auth/access-tokens-guard).
- [S23 — AdonisJS: Securing SSR apps](https://docs.adonisjs.com/guides/security/securing-ssr-applications).

Toutes les sources techniques ci-dessus sont des documentations officielles et ont été vérifiées le 6 septembre 2026. Les comparatifs, blogs, benchmarks non reproductibles et extensions non officielles n’ont pas servi à établir les faits.
