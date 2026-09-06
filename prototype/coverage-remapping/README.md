# PROTOTYPE — remapping de couverture Astro/Vue/Adonis

Question traitée par [Vérifier la couverture des branches avec Astro et Vue](https://github.com/antoineLZCH/esctt/issues/11) : une branche propriétaire non couverte dans les sources originales `.astro`, `.vue` et `.ts` fait-elle réellement échouer un seuil CI à 100 % après transformation et remapping ?

## Rejouer

Prérequis : Node 24 et npm 11.

```sh
npm ci
npm run verify
```

`verify` attend l'échec des quatre commandes `*:fail`, puis la réussite des quatre commandes `*:pass`.

## Mutation vérifiée

Chaque fixture contient un ternaire `member ? … : …`. La variante `fail` n'exécute que `member: true`; la variante `pass` active aussi `member: false` avec `COVER_ALL=1`. Les seuils sont fixés séparément à 100 % pour lignes, branches, fonctions et instructions.

| Surface instrumentée | Plugin/runtime représentatif | `fail` | `pass` | Source originale dans le rapport brut |
|---|---|---:|---:|---|
| Astro `.astro`, frontmatter | `astro/config` + Vitest/V8 | 50 % branches, exit 1 | 100 %, exit 0 | `astro/src/components/Greeting.astro`, ligne 7 |
| Laravel–Inertia–Vue `<script setup>` | `laravel-vite-plugin` + plugin Vue + Vitest/V8 | 50 % branches, exit 1 | 100 %, exit 0 | `vue/laravel/src/Greeting.vue`, ligne 5 |
| Adonis–Inertia–Vue `<script setup>` | `@adonisjs/vite/client` + plugin Vue + Vitest/V8 | 50 % branches, exit 1 | 100 %, exit 0 | `vue/adonis/src/Greeting.vue`, ligne 5 |
| Serveur Adonis TypeScript | Japa + c8/V8 | 66,66 % branches, exit 1 | 100 %, exit 0 | `adonis/app/greeting.ts`, ligne 2 |

Les rapports sont capturés sous `reports/{failing,passing}/<surface>/coverage-final.json` et `coverage-summary.json`; les sorties complètes sont sous `reports/logs/`.

## Verdict et coûts révélés

- **Oui sur ces cas minimaux** : aucun des trois remappings n'efface le ternaire propriétaire; la branche absente apparaît à zéro dans le fichier source original et bloque le processus.
- `coverage.all: true` et un `include` explicite sont indispensables côté Vitest; c8 reçoit aussi un `--include` explicite. Sans ces listes propriétaires, un fichier jamais chargé pourrait disparaître silencieusement du dénominateur.
- Le rendu unitaire Astro repose ici sur `experimental_AstroContainer`, donc sur une API explicitement expérimentale à maintenir.
- c8 remappe bien le ternaire TypeScript, mais compte aussi deux branches synthétiques de chargement autour de la fonction : le seuil reste atteignable, au prix d'un rapport moins intuitif.
- Les deux essais Vue chargent les plugins Vite officiels des hôtes. Inertia n'ajoute pas de transformation SFC; son runtime n'est donc pas installé dans ce spike.

Ce prototype est une preuve ciblée, pas une garantie universelle pour toutes les syntaxes de template, macros ou transformations futures. Une CI réelle doit conserver les rapports bruts comme artefacts et auditer ses motifs `include`/`exclude` lors des mises à niveau majeures.
