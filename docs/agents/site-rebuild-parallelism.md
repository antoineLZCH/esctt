# Refonte du site : graphe d’exécution

Ce document s’applique à la spec [#16](https://github.com/antoineLZCH/esctt/issues/16) et à ses tickets [#17](https://github.com/antoineLZCH/esctt/issues/17) à [#37](https://github.com/antoineLZCH/esctt/issues/37).

## Règle d’exécution

Travaillez uniquement sur la **frontière** : un ticket ouvert, non assigné, dont tous les tickets indiqués dans `Blocked by` sont fermés.

1. Lire le corps complet et les commentaires du ticket ciblé.
2. Vérifier l’état et l’assignation de chacun de ses bloqueurs avec `gh issue view`.
3. Si tous les bloqueurs sont fermés, réclamer le ticket avec `gh issue edit <numéro> --add-assignee @me`.
4. Relire immédiatement le ticket : commencer seulement si l’assignation est confirmée et unique.
5. Après fermeture, recalculer la frontière ; les tickets devenus disponibles peuvent partir en parallèle.

Un agent traite un seul ticket à la fois. Les branches parallèles gardent la portée de leur ticket et se rebasent avant fusion. Les champs `Blocked by` des issues sont la source de vérité ; les vagues ci-dessous décrivent seulement les niveaux du graphe.

## Graphe des dépendances

Les arêtes transitives vers #37 sont omises pour préserver la lisibilité.

```mermaid
flowchart TD
    I17["#17 Socle WordPress"]
    I18["#18 Portes qualité"]
    I34["#34 Qualification OVH"]
    I19["#19 Cadre Gutenberg"]
    I33["#33 Releases vérifiées"]
    I20["#20 Identité et navigation"]
    I21["#21 Créneaux"]
    I23["#23 Tarifs"]
    I24["#24 Message important"]
    I25["#25 Vie sportive"]
    I26["#26 FAQ"]
    I27["#27 Partenaires"]
    I28["#28 Parcours d’inscription"]
    I31["#31 Création de pages"]
    I22["#22 Comparaison par profil"]
    I29["#29 HelloAsso"]
    I32["#32 Anciennes URL"]
    I30["#30 Contact et pages légales"]
    I35["#35 Déploiement et rollback"]
    I36["#36 Continuité d’exploitation"]
    I37["#37 Mise en ligne 1.0"]

    I17 --> I18
    I17 --> I34
    I18 --> I19
    I18 --> I33
    I19 --> I20
    I19 --> I21
    I19 --> I23
    I19 --> I24
    I19 --> I25
    I19 --> I26
    I19 --> I27
    I19 --> I28
    I19 --> I31
    I21 --> I22
    I28 --> I29
    I31 --> I32
    I19 --> I30
    I29 --> I30
    I22 --> I35
    I23 --> I35
    I29 --> I35
    I33 --> I35
    I34 --> I35
    I34 --> I36
    I35 --> I36
    I20 --> I37
    I24 --> I37
    I25 --> I37
    I26 --> I37
    I27 --> I37
    I30 --> I37
    I32 --> I37
    I36 --> I37
```

## Vagues maximales

| Vague | Tickets exécutables en parallèle une fois la vague précédente terminée |
| --- | --- |
| 1 | #17 |
| 2 | #18, #34 |
| 3 | #19, #33 |
| 4 | #20, #21, #23, #24, #25, #26, #27, #28, #31 |
| 5 | #22, #29, #32 |
| 6 | #30, #35 |
| 7 | #36 |
| 8 | #37 (`ready-for-human`) |

Le chemin critique probable est `#17 → #18 → #19 → #21/#28 → #22/#29 → #35 → #36 → #37`.
