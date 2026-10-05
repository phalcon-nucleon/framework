# E0 — Socle & outillage

**Statut** : Rédigé · **Dépend de** : — · **Bloque** : tous les autres epics

## Objectif

Disposer d'un environnement PHP 8.3+ / Phalcon 5.22 reproductible, d'une CI et d'outils de qualité, ainsi que des mesures de performance de la 1.3 qui serviront de référence pour toute la migration.

## Périmètre

- `composer.json`, `phpunit.xml`, `tests/bootstrap.php`
- Nouveaux fichiers : `docker/`, `.github/workflows/`, `phpstan.neon`, `rector.php`, `.php-cs-fixer.dist.php`, `bench/`
- Suppression de `.travis.yml` et de `tests/_ci/`
- **Hors périmètre** : faire passer les tests des modules. Chaque epic s'en charge pour son propre périmètre.

## Ruptures

- PHPUnit 5 → 11 :
  - `PHPUnit_Framework_TestCase` devient `PHPUnit\Framework\TestCase` ;
  - `setUp(): void` ;
  - `expectException` remplace les annotations `@expectedException` ;
  - les data providers deviennent `static` ;
  - les attributs `#[DataProvider]` / `#[Test]` remplacent les annotations ;
  - `syntaxCheck` et les `<filter>` disparaissent de `phpunit.xml` au profit de `<source>`.
- Mockery 0.9 → 1.6.
- Travis CI et Coveralls (`satooshi/php-coveralls`, paquet abandonné) sont remplacés.

## Décisions

- Branche de travail `2.x`. `master` reste sur la 1.3 jusqu'à la sortie.
- **Activation progressive des tests** : `phpunit.xml` déclare une suite par module (`tests/Test/<Module>`). La CI n'exécute que les suites listées comme migrées. Chaque epic ajoute les siennes à cette liste. On a ainsi une CI verte dès le départ, sans masquer de régression.
- **Mesures sans dépendance** : un script `bench/run.php`, basé sur `hrtime()` et `memory_get_peak_usage()`, compatible PHP 7.3 et 8.x. Le même script mesure la 1.3 et la 2.0, ce qui évite les écarts liés à des versions différentes de phpbench.
- Couverture de code avec PCOV, publiée en artefact CI. Pas de service tiers.

## Stories

### E0-S1 · Images Docker
- `docker/legacy` : PHP 7.3 + Phalcon 3.4.5, pour mesurer la 1.3 et la comparer avec la 2.0.
- `docker/php8` : PHP 8.3 / 8.4 / 8.5 (argument de build) + Phalcon 5.22 via PECL + PCOV + Composer.
- `docker compose` avec MySQL 8 et PostgreSQL 16, pour les tests de `Database` qui en ont besoin (à confirmer lors de l'audit d'E10 et E11).
- Commandes documentées dans `CLAUDE.md` : lancer la suite, une suite, un test.

### E0-S2 · `composer.json`
- `require` : `php: >=8.3`, `ext-phalcon: ^5.22`.
- Suppression de `nikic/php-parser` (son retrait effectif est fait dans E1).
- `ark4ne/highlight` retiré (décision d'E12). `tempest/highlight ^2.12` en `require-dev` et en `suggest`.
- `require-dev` : `phalcon/ide-stubs ^5.22`, `phpunit/phpunit ^11`, `mockery/mockery ^1.6`, `phpstan/phpstan`, `rector/rector`, `friendsofphp/php-cs-fixer`.
- Suppression de `minimum-stability: dev` et de `satooshi/php-coveralls`.
- Les entrées `replace` (`neutrino/dotconst`, `neutrino/optimizer`) sont revues : retirer `neutrino/optimizer`, puisque le module est supprimé dans E1.

### E0-S3 · PHPUnit 11
- `phpunit.xml` au format 11 : `<source>`, une suite par module, `APP_ENV=test`.
- `tests/bootstrap.php` inchangé sur le fond (Dotconst sur `tests/.fake/nucleon.app`).
- Règles Rector `PHPUnitSetList` appliquées à tout `tests/` en une passe mécanique, puis relues. La correction fonctionnelle des tests reste à la charge de chaque epic.

### E0-S4 · Qualité
- `phpstan.neon` : niveau `max`, stubs `phalcon/ide-stubs`, baseline générée sur l'existant et versionnée. La baseline doit diminuer à chaque epic.
- `rector.php` : jeux de règles PHP 8.3, sans application automatique en CI.
- `.php-cs-fixer.dist.php` : `@PER-CS2.0`, plus `declare_strict_types` sur les fichiers déjà migrés.

### E0-S5 · CI GitHub Actions
- Matrice principale : PHP 8.3 / 8.4 / 8.5 × Phalcon 5.22 (`shivammathur/setup-php`, extension `phalcon`), avec services MySQL et PostgreSQL.
- Étapes : `composer validate`, PHP-CS-Fixer en mode lecture seule, PHPStan, PHPUnit (suites migrées), couverture.
- **Job Phalcon 6** : PHP 8.3 sans l'extension, avec `composer require phalcon/phalcon` (dernière RC), `continue-on-error: true`. Son résultat est remonté dans le résumé du workflow.
- Suppression de `.travis.yml` et de `tests/_ci/`.

### E0-S6 · Mesures de référence de la 1.3
- `bench/` contient une application minimale avec un kernel HTTP, un kernel CLI et un kernel Micro, une route, un controller et une tâche.
- Le script `bench/run.php` mesure, en N itérations dans des processus séparés :
  1. le boot de chaque kernel ;
  2. une requête HTTP complète ;
  3. une requête Micro ;
  4. une tâche CLI ;
  5. la résolution d'un service partagé ;
  6. la mémoire de pointe.
- Exécution sur `docker/legacy` avec la 1.3 (tag `v1.3.x`), OPcache activé. Résultats versionnés dans `bench/baseline-1.3.json`.
- Le même script tourne sur `docker/php8` et compare avec la référence (écart en %). Utilisé par les epics qui touchent le chemin d'exécution d'une requête.

## Changements cassants pour les apps

- PHP ≥ 8.3 et Phalcon ≥ 5.22 obligatoires.

## Critères d'acceptation spécifiques

- `docker compose run php8 vendor/bin/phpunit` fonctionne (même avec 0 suite migrée).
- La CI est verte : style, PHPStan avec baseline, suites migrées. Le job Phalcon 6 s'exécute.
- `bench/baseline-1.3.json` est versionné et reproductible (écart inférieur à 5 % entre deux exécutions).
