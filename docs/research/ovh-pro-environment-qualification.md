# Qualification de l’environnement OVH Pro

> Qualification non destructive de l’hébergement Web OVH Pro utilisé par le site existant. Exécutée le 2026-09-06T22:03:16Z depuis le worktree de l’issue #34.
>
> Aucun fichier de production n’a été modifié. Les probes ont utilisé des répertoires temporaires hors de `www`, puis les ont supprimés.

## Décision de déploiement

La méthode utilisable pour la suite est **SSH par clé + `rsync`**, avec un artefact construit en CI :

1. Construire dans GitHub Actions le thème Sage, le plugin `esctt-content` et les dépendances nécessaires au runtime. Le serveur ne fournit ni Composer ni WP-CLI.
2. Transférer l’artefact par `rsync` sur SSH vers une release temporaire hors de la racine active `www`.
3. Vérifier le manifeste, les sommes et les permissions avant publication.
4. Publier uniquement les composants possédés par le dépôt dans `www/wp-content/themes/` et `www/wp-content/plugins/`, avec une fenêtre de maintenance tant que le comportement HTTP d’un symlink n’est pas validé.
5. Ne jamais transférer `wp-config.php`, les uploads, la base de données, le cœur WordPress ou un fichier `.env`.

`rsync` est maintenant prouvé fonctionnel sur ce compte. Les symlinks et le renommage de répertoires fonctionnent dans un répertoire temporaire du même espace, mais leur résolution par le serveur HTTP n’a pas été testée. Une bascule par symlink reste donc une optimisation à qualifier avant de la rendre obligatoire. La stratégie atomique et le rollback coordonné relèvent de l’issue #35.

## Résultats vérifiés

### Accès et identité

| Élément | Résultat |
| --- | --- |
| Hôte SSH | `ssh.cluster107.hosting.ovh.net` |
| Port | `22` |
| Authentification par clé | Fonctionnelle (`BatchMode`, commande distante non destructive) |
| Répertoire courant de session | `/home/colombes-antoinelzch` |
| Chemin de home OVH observé | `/homez.412/colombes` |
| Compte privilégié | Non : UID observé `39079`, aucun accès root tenté |
| Shell | `/bin/ovh_ssh` |

L’empreinte de clé d’hôte a été validée avant son ajout au `known_hosts` local. Elle n’est pas recopiée dans le dépôt.

### Système, PHP et quotas

- Système observé : Linux x86_64, noyau `6.18.42-ovh-vps-grsec-zfs+`.
- `.ovhconfig` indique `environment=production`, `app.engine=php`, `app.engine.version=8.4` et `container.image=stable64`.
- PHP CLI : `/usr/local/php8.4/bin/php`, version `8.4.22`.
- Extensions utiles observées : `curl`, `dom`, `fileinfo`, `gd`, `imagick`, `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `ssh2`, `zip`, `zlib`, OPcache.
- `df -h` sur le home : système de fichiers réseau de `16T`, `9,3T` utilisés, `6,3T` disponibles, `60%` utilisés.
- La commande `quota` est absente. Ces chiffres sont donc une capacité du système de fichiers, pas un quota individuel OVH vérifié.

### Chemins et permissions

- `www` est la racine WordPress observée : le cœur WordPress, `wp-config.php` et `wp-content/` y sont présents.
- `www` : propriétaire `colombes-antoinelzch:users`, mode `755`, lecture/traversée/écriture effectives pour le compte SSH.
- `www/wp-content` : propriétaire `colombes-antoinelzch:users`, mode `755`.
- `www/wp-config.php` : propriétaire `colombes-antoinelzch:users`, mode `604`. Son contenu n’a pas été lu ni modifié.
- `wp-content/` contient notamment `plugins/`, `themes/`, `uploads/`, `upgrade/` et `upgrade-temp-backup/`. Les uploads et les données existantes restent hors du périmètre de l’artefact.
- Les listings affichent des ACL (`+`), mais `getfacl` n’est pas installé ; le détail des ACL n’est pas qualifié.
- Le document-root HTTP exact et le comportement HTTP d’un symlink restent à confirmer par un smoke test public contrôlé.

### Commandes disponibles

| Commande | Résultat distant |
| --- | --- |
| `php` | `/usr/local/php8.4/bin/php` |
| `wp` | absente |
| `composer` | absente |
| `rsync` | `/usr/bin/rsync`, version `3.1.3`, protocole 31 |
| `sftp` | `/usr/bin/sftp` |
| `scp` | `/usr/bin/scp` |
| `tar`, `gzip`, `zip`, `unzip` | disponibles |
| `mv`, `ln`, `readlink`, `chmod`, `mkdir` | disponibles |
| `quota` | absente |

### Transfert et primitives de publication

- Un fichier de probe sans donnée sensible a été transféré par `rsync` vers un répertoire temporaire du home.
- La somme SHA-256 locale et distante était identique.
- Le répertoire temporaire de transfert a été supprimé et vérifié absent.
- Dans un répertoire temporaire, les opérations suivantes ont réussi : création et lecture d’un symlink, remplacement du symlink, renommage d’un répertoire et nettoyage.
- Ces probes n’ont pas écrit dans `www` ni dans `wp-content`.

## Limites restantes

- Le quota individuel OVH n’est pas disponible via la commande `quota`.
- WP-CLI et Composer ne peuvent pas être exécutés sur le serveur dans l’état qualifié.
- La résolution HTTP d’un symlink n’est pas établie.
- Aucun smoke test public, changement de document-root, sauvegarde/restauration de base ou déploiement n’a été effectué dans cette qualification.
- Des fichiers `temp-write-test-*` préexistants ont été observés dans `wp-content/`; ils n’ont pas été touchés.

## Règles de sécurité du rapport

- Aucun mot de passe, clé privée, valeur d’environnement, contenu de `wp-config.php`, contenu de base ou média n’est enregistré.
- Les fichiers `.env` et `.env.local` sont exclus de toute opération ; seuls des fichiers d’exemple ou de test seraient admissibles dans le dépôt.
- Les artefacts de qualification temporaires sont hors dépôt et ont été supprimés.

## Sources et suite

- [Brief de décision OVH](./ovh-pro-wordpress-deployment.md)
- [Issue #34 — Qualifier l’environnement OVH Pro](https://github.com/antoineLZCH/esctt/issues/34)
- [OVH — Utiliser SSH sur un hébergement Web](https://docs.ovhcloud.com/en/guides/web-cloud/web-hosting/ssh-on-webhosting)
- [OVH — Connexion à l’espace FTP/SFTP](https://docs.ovhcloud.com/en/guides/web-cloud/web-hosting/ftp-connection)

La prochaine étape est l’issue #35 : définir et tester la publication coordonnée du thème et du plugin, avec sauvegarde, smoke tests et rollback.
