# Déploiement WordPress indépendant sur OVH Pro — brief de décision

> Périmètre : hébergement Web OVH **Pro** (mutualisé), Bedrock + Sage + `esctt-content` dans un monorepo pnpm. La cible est le thème et le plugin uniquement ; l’application future est hors périmètre.
>
> Les liens ci-dessous sont les sources de référence propriétaires (OVH, GitHub, Roots, WordPress). Les capacités dépendant de l’offre, du datacenter ou du compte doivent être confirmées dans le Manager avant de traiter une recommandation comme garantie.

## Décision recommandée

Construire dans GitHub Actions un artefact versionné contenant le thème Sage compilé et le plugin compilé, puis le transférer en SFTP/SSH dans un répertoire de release sous le document-root WordPress. Ne pas publier `node_modules`, les sources de développement, `.git`, les secrets ou l’intégralité du monorepo. Conserver WordPress/Bedrock, `vendor`, la configuration d’environnement, les uploads et la base hors de cet artefact, sauf validation explicite de l’architecture effective.

Sur mutualisé, retenir **SFTP/SSH comme transport**, mais ne pas dépendre de `rsync`, d’un daemon, d’un conteneur ou d’une bascule atomique par symlink sans test sur le compte : les pages OVH de l’offre et d’accès indiquent l’accès SSH/SFTP, pas nécessairement ces primitives de déploiement. Une publication par répertoire versionné avec bascule d’un chemin configurable est souhaitable ; si le document-root ou la configuration ne permettent pas cette bascule, utiliser une fenêtre de synchronisation et vérifier le résultat.

## Findings

1. **Accès OVH.** OVH documente l’accès SSH aux hébergements Web et les paramètres de connexion (hôte, identifiant, port et mot de passe/clé selon le cas) ; SFTP est le transport recommandé pour transférer des fichiers. L’accès et les commandes disponibles sont ceux d’un compte mutualisé, pas ceux d’un VPS administrateur. **Sources:** [OVH — Se connecter en SSH à un hébergement Web](https://help.ovhcloud.com/csm/fr-web-hosting-ssh-on-web-hosting?id=kb_article_view&sysparm_article=KB0052844), [OVH — Se connecter en FTP/SFTP](https://help.ovhcloud.com/csm/fr-web-hosting-ftp-sftp?id=kb_article_view&sysparm_article=KB0052839). **Support:** direct evidence. **Confidence:** medium (la page exacte et les limites d’offre doivent être relues dans le Manager).

2. **Limites à ne pas extrapoler.** La documentation OVH consultée pour SSH/SFTP ne constitue pas une promesse de `rsync`, de symlinks fonctionnels dans le document-root, de jobs cron suffisants pour un déploiement, ni de droits d’installation de paquets. **Recommandation:** faire un test non destructif (`command -v rsync`, création d’un symlink dans un répertoire de test, `readlink`, et observation depuis HTTP) et documenter le résultat ; à défaut, prévoir SFTP fichier-par-fichier et une publication non atomique. Cette conclusion est une **inférence**, pas une affirmation OVH.

3. **Sage doit être compilé avant transfert.** La documentation Sage décrit le build de production et le résultat destiné à être servi ; le thème ne doit donc pas être expédié comme seulement ses sources. Le pipeline doit installer les dépendances pnpm verrouillées, exécuter le build de production Sage, puis empaqueter le répertoire de distribution réellement attendu par le thème. **Source:** [Roots Sage — Installation](https://roots.github.io/sage/docs/installation/), [Roots Sage — Deployment](https://roots.github.io/sage/docs/9.x/installation/#deploying). **Support:** direct evidence for the build/deploy workflow; exact output directory is repository/version-dependent. **Confidence:** medium.

4. **Bedrock sépare le code et l’environnement.** Bedrock utilise Composer pour WordPress et ses dépendances et documente une structure où le document-root pointe vers `web/`; la configuration est fournie par l’environnement (`.env`/variables), et `web/app/uploads` contient le contenu téléversé. **Recommandation:** ne jamais mettre `.env` dans l’artefact ou le dépôt ; déterminer si OVH exécute Composer sur le serveur. Sinon, produire `vendor` et les dépendances nécessaires dans CI, en respectant exactement la structure Bedrock déjà installée. **Sources:** [Roots Bedrock — Installation](https://roots.io/bedrock/docs/installation/), [Roots Bedrock — Directory structure](https://roots.io/bedrock/docs/directory-structure/), [Roots Bedrock — Environment variables](https://roots.io/bedrock/docs/environment-variables/). **Support:** direct evidence plus recommendation. **Confidence:** high for Bedrock conventions, low for l’état actuel du serveur.

5. **Composition de l’artefact.** Inclure le thème Sage produit (assets compilés et PHP nécessaire), `esctt-content` dans sa forme installable, et tout fichier de manifeste/version utile au post-déploiement. Exclure tests, docs, caches, `node_modules`, `.git`, clés, `.env`, sauvegardes, uploads et le cœur WordPress si celui-ci est géré séparément. Le contenu exact doit être dérivé des scripts et de la version Sage/Bedrock du dépôt, non d’une liste générique. **Support:** researcher recommendation, fondée sur les structures Roots ci-dessus. **Confidence:** medium.

6. **Secrets et hôtes GitHub Actions.** Utiliser des secrets GitHub pour la clé privée et les paramètres sensibles ; ne jamais les écrire dans le dépôt, les logs ou une commande affichée. Utiliser un *environment* GitHub protégé (`production`) avec reviewers requis et secrets d’environnement, et restreindre le workflow aux branches/pathes prévus. Épingler la clé d’hôte OVH dans `known_hosts` après vérification indépendante de son empreinte ; ne pas désactiver la vérification SSH (`StrictHostKeyChecking=no`). Une clé de déploiement dédiée et à privilèges minimaux est préférable. **Sources:** [GitHub — Secrets](https://docs.github.com/en/actions/security-for-github-actions/security-guides/using-secrets-in-github-actions), [GitHub — Environments](https://docs.github.com/en/actions/deployment/targeting-different-environments/using-environments-for-deployment), [GitHub — Secure use reference](https://docs.github.com/en/actions/security-for-github-actions/security-guides/security-hardening-for-github-actions), [OpenSSH — ssh-keyscan](https://man.openbsd.org/ssh-keyscan). **Support:** direct evidence for GitHub controls; host-key verification procedure is recommendation. **Confidence:** high.

7. **Déclenchement indépendant.** Un workflow distinct peut être déclenché par `push` avec `paths` limités aux répertoires du thème, du plugin et aux fichiers de build pertinents ; il doit également exécuter les tests et construire depuis un checkout complet. La logique de chemins doit inclure les lockfiles et scripts dont la modification change l’artefact, sinon un changement de build peut être ignoré. **Sources:** [GitHub — Workflow syntax, `paths`](https://docs.github.com/en/actions/using-workflows/workflow-syntax-for-github-actions#onpushpull_requestpaths), [GitHub — Dependency caching](https://docs.github.com/en/actions/using-workflows/caching-dependencies-to-speed-up-workflows). **Support:** direct evidence plus inference appliquée au monorepo. **Confidence:** high.

8. **WordPress, uploads et base ne sont pas des fichiers de release.** WordPress documente que les médias sont stockés dans le répertoire d’uploads et que la base contient le contenu et les réglages ; les deux doivent donc rester persistants et être sauvegardés indépendamment du code. Les fichiers de configuration/secrets doivent également rester hors de l’artefact. **Sources:** [WordPress — Media Library file organization](https://wordpress.org/documentation/article/media-library-screen/), [WordPress — Database description](https://wordpress.org/documentation/article/wordpress-database-description/), [Roots Bedrock — Environment variables](https://roots.io/bedrock/docs/environment-variables/). **Support:** direct evidence plus deployment interpretation. **Confidence:** high.

9. **Rollback et vérification.** Conserver plusieurs releases immuables (si l’espace le permet), le commit/numéro de build et un manifeste de fichiers ; rollbacker le code vers le release précédent sans restaurer automatiquement la base. Sauvegarder la base via l’outil OVH disponible ou export SQL, et les uploads via SFTP/outil OVH ; tester la restauration avant de considérer la sauvegarde utile. Après publication, vérifier HTTP/HTTPS, une page WordPress, les assets compilés, l’activation/chargement du plugin, les permissions et les logs PHP. **Sources:** [OVH — Sauvegarder et restaurer une base de données](https://help.ovhcloud.com/csm/fr-web-hosting-database-backup?id=kb_article_view&sysparm_article=KB0052837), [WordPress — Backing up your WordPress site](https://wordpress.org/documentation/article/wordpress-backups/). **Support:** sources directes pour le besoin de sauvegarde ; stratégie release/health-check = recommendation. **Confidence:** medium.

## Unknowns à vérifier sur le compte OVH (bloquants avant implémentation)

- Nom d’hôte SSH/SFTP, port, utilisateur, méthode de clé, quota et chemin exact du document-root ; confirmer que l’offre **Pro** active l’accès attendu.
- Version PHP, extensions, limites de mémoire/temps, capacité à lancer Composer, et possibilité réelle d’exécuter des commandes SSH non interactives.
- `rsync` présent et utilisable, support des symlinks et comportement du serveur HTTP avec symlink ; ne pas le supposer.
- Document-root actuel : `web/` Bedrock ou autre ; emplacement de `wp`, `vendor`, `.env`, `uploads`, plugins et thème actif. Confirmer les permissions et propriétaire.
- Possibilité de conserver plusieurs releases et de changer le document-root ou un symlink sans interruption ; sinon choisir la synchronisation avec fenêtre et maintenance.
- Méthode d’export/restauration DB et de sauvegarde/restauration uploads, rétention, taille et temps de restauration ; identifier si une modification de schéma/migration est nécessaire.
- Empreinte de clé hôte OVH obtenue par un canal indépendant ; rotation et procédure d’urgence.
- URL de smoke test, cache/CDN, mode maintenance et logs accessibles après déploiement.

## Contradictions

Aucune contradiction retenue dans les sources officielles ci-dessus. Le point `rsync`/symlink n’est pas contradictoire : il est simplement non établi par les pages d’accès OVH ; il doit rester une hypothèse à tester.

## Missing evidence

Les sources officielles ne suffisent pas à établir la présence de `rsync`, les droits de symlink, le chemin exact du document-root, les versions installées, ni l’aptitude de ce compte à exécuter Composer. Aucun de ces points ne doit être codé comme une certitude avant inspection SSH/Manager.

## Sources kept

- [OVH SSH](https://help.ovhcloud.com/csm/fr-web-hosting-ssh-on-web-hosting?id=kb_article_view&sysparm_article=KB0052844) — accès et paramètres SSH.
- [OVH FTP/SFTP](https://help.ovhcloud.com/csm/fr-web-hosting-ftp-sftp?id=kb_article_view&sysparm_article=KB0052839) — transfert SFTP.
- [Roots Bedrock](https://roots.io/bedrock/docs/installation/) et [structure](https://roots.io/bedrock/docs/directory-structure/) — conventions de déploiement.
- [Roots Sage](https://roots.github.io/sage/docs/installation/) — installation/build du thème.
- [GitHub Actions secrets](https://docs.github.com/en/actions/security-for-github-actions/security-guides/using-secrets-in-github-actions) et [environments](https://docs.github.com/en/actions/deployment/targeting-different-environments/using-environments-for-deployment) — protection du déploiement.
- [WordPress backups](https://wordpress.org/documentation/article/wordpress-backups/) — sauvegarde code, médias et base.

## Sources rejected/deprioritized

Aucune source secondaire utilisée. Les pages marketing OVH et tutoriels/blogs ont été écartés pour les contraintes techniques ; la validation finale doit utiliser le contenu actuel du Manager et les pages d’aide OVH correspondantes.

## Next steps

1. Faire l’inventaire non destructif SSH/Manager listé ci-dessus.
2. Construire localement/CI un artefact Sage + plugin et vérifier son contenu contre le document-root réel.
3. Tester sur un environnement de préproduction : transfert, smoke test, sauvegarde, puis rollback de code.
