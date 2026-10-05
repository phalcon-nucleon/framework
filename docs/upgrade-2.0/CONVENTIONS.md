# Conventions Nucleon 2.0

Règles communes à tous les epics. Un epic peut y déroger s'il le justifie explicitement.

## Code

- `declare(strict_types=1);` dans tous les fichiers.
- Typage complet : paramètres, retours, propriétés. `mixed` seulement quand la valeur est réellement libre.
- Les surcharges de méthodes Phalcon reprennent exactement la signature des `phalcon/ide-stubs` 5.x.
- `final` par défaut sur les classes qui ne sont pas des points d'extension documentés.
- Promotion des paramètres du constructeur et `readonly` pour les objets valeur.
- **Enums** : seulement pour les ensembles fermés de valeurs internes, jamais comparées à des chaînes venues de l'extérieur. Les noms de services et d'événements, et `Env` (comparé à la constante `APP_ENV` issue du `.const.ini`), restent des **constantes de classe typées** (`const string CACHE = 'cache';`), car ils servent de clés ou de valeurs de chaîne (`$this->{Services::CACHE}`, `APP_ENV === Env::TEST`). Un enum imposerait `->value` partout.
- Plus de code de compatibilité PHP < 8.3 ni de polyfills.
- Ne pas réimplémenter ce que PHP 8.3 ou Phalcon 5 fournissent (`str_contains`, `array_is_list`, `Phalcon\Support\Helper\*`, etc.), sauf si l'API Nucleon apporte une vraie valeur et reste rétrocompatible.

## Dépendances

- **Zéro dépendance tierce en `require` par défaut.** Seules `ext-phalcon` et les extensions PHP sont acceptées sans discussion.
- Toute dépendance de production s'ajoute avec une justification dans l'epic : besoin, taille (lignes et nombre de paquets installés) et alternatives écartées.
- Les dépendances de développement (PHPUnit, PHPStan, Rector, etc.) sont libres.
- Aucun code de `src/` ne doit dépendre d'un paquet de `require-dev` (aujourd'hui, `Support\Facades\Facade` utilise Mockery).

## Qualité

- PHPStan au niveau fixé par E0 (cible : `max`, avec une baseline temporaire autorisée pendant la migration).
- PHP-CS-Fixer avec le jeu de règles `@PER-CS2.0`.
- Rector sert aux migrations mécaniques. Son résultat est relu, jamais commité tel quel.

## Performances

- Les mesures de référence de la 1.3 sont faites dans E0 (phpbench) :
  - boot d'un kernel HTTP, CLI et Micro ;
  - une requête HTTP complète (route → controller → réponse) ;
  - une requête Micro ;
  - résolution d'un service partagé ;
  - mémoire de pointe.
- **Budget** : la 2.0 ne doit pas être plus lente ni plus gourmande en mémoire que la 1.3 sur ces scénarios.
- Tout epic qui touche le chemin d'exécution d'une requête (boot, DI, routage, dispatch, middlewares, vues, modèles) joint la comparaison phpbench à sa PR.
- Le principe du chargement à la demande est conservé : aucun service n'est instancié tant qu'il n'est pas utilisé.

## Modèle d'epic

Chaque fichier `epics/Exx-*.md` suit cette structure :

1. **Objectif** : une phrase.
2. **Périmètre** : fichiers de `src/` et `tests/` concernés, et ce qui est hors périmètre.
3. **Ruptures Phalcon 3 → 5** : issues de l'audit (`tools/phalcon-api-audit.py` + stubs).
4. **Décisions** : ce qui est supprimé, remplacé ou réécrit, et pourquoi. Les questions ouvertes sont marquées **À trancher**.
5. **Changements cassants pour les apps** : à reporter dans `UPGRADING-2.0.md`.
6. **Stories** : environ 1 à 2 jours chacune, avec leurs critères propres.
7. **Critères d'acceptation** : ceux de la section suivante, plus les critères spécifiques à l'epic.
8. **Dépendances**.

## Critères d'acceptation communs

- Les tests du périmètre passent sur la matrice CI Phalcon 5 (PHP 8.3, 8.4, 8.5).
- PHPStan et PHP-CS-Fixer passent sur le périmètre.
- `strict_types` et le typage complet sont en place sur le périmètre.
- Pas de régression phpbench si l'epic touche le chemin d'exécution d'une requête.
- Les changements cassants sont reportés dans `UPGRADING-2.0.md` et `CHANGELOG-2.0.md`.
- Les échecs du job Phalcon 6 sont analysés : corrigés, ou consignés comme limite connue de Phalcon 6.
