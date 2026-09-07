# Préservation des anciennes URL

Le plugin `esctt-content` possède le registre `esctt_redirect`. Chaque entrée
conserve un chemin source interne (`_esctt_redirect_source`) et l’identifiant de
la page publiée de destination (`_esctt_redirect_target`). La destination est
résolue à chaque requête : une nouvelle modification de slug ne crée donc pas
de chaîne de redirections.

Les chemins sont normalisés uniquement pour leur comparaison (slash initial,
slash final et doubles slashs). Les hôtes externes sont refusés. Les requêtes
GET et HEAD reçoivent une redirection 301 et leur query string est conservée.

## Inventaire vérifié

Vérifié le 7 septembre 2026 depuis `https://www.colombestennisdetable.fr/` et
par réponses HTTP, avec les destinations prévues par les pages natives du
nouveau site :

| Ancienne adresse utile | Observation vérifiée | Destination | Traitement |
| --- | --- | --- | --- |
| `/accueil/` | Ancien slug de la page d’accueil ; le site actuel répond 301 vers `/` | `/` | Entrée enregistrée automatiquement si la page d’accueil existe |
| `/politique-de-confidentialite/` | Page publiée actuelle, réponse 200 | `/confidentialite/` | Entrée enregistrée automatiquement si la page destination existe |
| `/inscriptions/` | Page publiée actuelle, réponse 200 | `/inscriptions/` | Même chemin, aucune entrée nécessaire |
| `/mentions-legales/` | Page publiée actuelle, réponse 200 | `/mentions-legales/` | Même chemin, aucune entrée nécessaire |
| `/mentions-legales` | Le canonical WordPress actuel répond 301 vers `/mentions-legales/` | `/mentions-legales/` | Canonical natif, aucune entrée nécessaire |

Les URL historiques suivantes sont bien présentes mais ne disposent pas d’une
destination éditoriale équivalente dans le nouveau site ; elles ne sont donc
ni migrées ni redirigées vers une page arbitraire :

- `/tournoi-des-familles-2022/`
- `/article-juin-2022/`
- `/article-sept-2020/`
- `/article-avril-2017/`

`/formation/tarifs/` et les liens avec fragment comme `/accueil/#contact`
apparaissent dans le contenu du site actuel, mais leur statut autonome n’est
pas suffisamment vérifié pour entrer dans le registre. Un fragment (`#contact`)
n’est par ailleurs jamais envoyé au serveur ; il ne peut pas être distingué
par une redirection HTTP.

## Limite de migration

Aucun contenu, fichier ou média Elementor n’est importé. Les entrées du
registre pointent vers des pages natives déjà présentes ou à créer dans le
nouveau site ; l’inventaire ci-dessus ne vaut pas migration du contenu
historique.
