# E11 — Migrations

**Statut** : Rédigé · **Dépend de** : E6 (enregistrement des tâches), E10 (modèles, connexions, `Support\Db`) · **Bloque** : —

## Objectif

Porter le système de migrations Nucleon (inspiré de Laravel : migrations incrémentales `up`/`down`, batches, `rollback`, `status`, schema builder) sur Phalcon 5. On simplifie au passage la couche de dialectes SQL, qui étend et enveloppe à la fois les dialectes Phalcon.

Pour rappel, `phalcon/migrations` a été écarté : il est déclaratif (une classe par table, `morph()`) et n'a ni batch, ni `rollback`, ni `status` (voir `README.md`).

## Périmètre

| Partie | Fichiers | Lignes |
|---|---|---|
| Schema builder | `Schema/Builder.php`, `Schema/Blueprint.php` (85 méthodes publiques), `Schema/Definition.php`, `Schema/Exception/*` | ~2 100 |
| Dialectes | `Schema/DialectInterface.php`, `Schema/DialectTrait.php`, `Schema/Dialect/*` (Mysql, Postgresql, Sqlite, Wrapper, Factory, traits) | ~1 900 |
| Migrator | `Migrations/Migrator.php`, `Migration.php`, `MigrationInterface.php`, `MigrationCreator.php`, stubs | ~750 |
| Stockage | `Migrations/Storage/*` (`DatabaseStorage`, `FileStorage`, `MigrationModel`, `MigrationRepository`) | ~490 |
| Préfixes | `Migrations/Prefix/*` (`DatePrefix`, `TimestampPrefix`) | ~90 |
| Tâches CLI | `Cli/Tasks/*` (`migrate`, `migrate:install`, `migrate:status`, `migrate:rollback`, `migrate:reset`, `migrate:refresh`, `migrate:fresh`, `make:migration`) | ~460 |
| Provider | `Providers/MigrationsServicesProvider.php` | 104 |

- Tests : `tests/Test/Database/{Cli,Migrations,Providers,Schema}` (25 fichiers de test), migrations de `tests/.fake/nucleon.app/migrations`.
- **Hors périmètre** :
  - les seeders ;
  - le verrouillage des migrations entre serveurs (`--isolated`) → après la 2.0.

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0)

| 1.3 | 5.22 | Concerne |
|---|---|---|
| `Db\Dialect\Mysql` / `Postgresql` / `Sqlite`, méthodes non typées | Méthodes typées (`createTable(string $tableName, string $schemaName, array $definition): string`, `limit(string $sqlQuery, $number): string`…). Plusieurs méthodes de `Db\Dialect` sont `final` (`escape`, `getColumnList`, `getSqlTable`, `getSqlExpression*`…). | `Schema\Dialect\*` (les classes Nucleon étendent les dialectes Phalcon **et** redéfinissent leurs méthodes via `WrapperTrait`) |
| `Db\DialectInterface`, `Db\ColumnInterface`, `Db\IndexInterface`, `Db\ReferenceInterface` | même nom, signatures typées | Dialectes, `Blueprint` |
| `Db\Column` : types de la 3.4 | Nouveaux types natifs : `TYPE_TINYINTEGER`, `TYPE_SMALLINTEGER`, `TYPE_MEDIUMINTEGER`, `TYPE_TIME`, `TYPE_TIMESTAMP`, `TYPE_ENUM`, `TYPE_UUID`, `TYPE_TINYTEXT`, `TYPE_MEDIUMTEXT`, `TYPE_LONGTEXT`, `TYPE_BIT`, `TYPE_BINARY`, `TYPE_VARBINARY`, et des types PostgreSQL (`TYPE_INET`, `TYPE_CIDR`, `TYPE_MACADDR`, intervalles, géométrie) | `DialectTrait` (les méthodes `type*()` contournent aujourd'hui l'absence de ces types) |
| `Phalcon\Di`, `Phalcon\DiInterface`, définitions DI par tableau `className` / `arguments` | `Phalcon\Di\Di`, `Di\DiInterface`. Les définitions par tableau existent toujours. | `MigrationsServicesProvider` |
| `Db\AdapterInterface` | `Db\Adapter\AdapterInterface` | `Blueprint`, `DatabaseStorage` |

## Décisions

- **On porte nos migrations** (décision du `README.md`). L'API des migrations des apps (`up(Builder $schema)` / `down(Builder $schema)`, `Blueprint`) est conservée.
- **Dialectes recomposés.**
  - Aujourd'hui, `Schema\Dialect\Mysql` **étend** `Phalcon\Db\Dialect\Mysql` **et** utilise `WrapperTrait` (437 lignes), qui redéfinit toutes les méthodes DDL pour les déléguer à un dialecte enveloppé. Avec les signatures typées et les méthodes `final` de Phalcon 5, ce double mécanisme devient coûteux et fragile.
  - En 2.0, chaque dialecte Nucleon est une **grammaire** autonome (`Schema\Grammar\Mysql`, `Postgresql`, `Sqlite`). Elle n'étend plus Phalcon et ne contient que ce que Phalcon ne fait pas :
    - correspondance des types `Blueprint` vers les types `Db\Column` ;
    - activation et désactivation des clés étrangères ;
    - `renameTable` ;
    - SQL spécifique (enum PostgreSQL, etc.).
  - Le DDL standard (créer, modifier, supprimer table, colonne, index, clé étrangère) passe par les méthodes de l'adapter Phalcon de la connexion, qui utilisent son propre dialecte.
  - `WrapperTrait`, `Wrapper` et `Factory` disparaissent. Une grammaire est choisie par `getDialectType()` de la connexion.
- **Types natifs** : les méthodes `type*()` s'appuient sur les nouveaux types `Db\Column` de Phalcon 5 quand ils existent (`TYPE_TINYINTEGER`, `TYPE_ENUM`, `TYPE_UUID`, `TYPE_TIME`, `TYPE_TIMESTAMP`, textes, `TYPE_INET`, `TYPE_MACADDR`…). Le code de contournement correspondant est supprimé.
- **Migrations anonymes** : un fichier de migration peut retourner une classe anonyme (`return new class extends Migration { … };`). Il n'y a plus de collision de noms de classes entre migrations, et le nom de classe n'a plus à être déduit du nom de fichier (`Str::studly()` + `deletePrefix()`). Les migrations nommées de la 1.3 restent prises en charge. `make:migration` génère des migrations anonymes.
- **Connexion par migration** : propriété `?string $connection` sur `Migration` et option `--database=<nom>` sur les tâches, qui s'appuient sur les connexions `db.<nom>` d'E10. La table de suivi reste sur la connexion par défaut, sauf option contraire.
- **Transactions** : propriété `bool $withinTransaction = true` sur `Migration`. Les migrations sont exécutées dans une transaction quand le SGBD prend en charge le DDL transactionnel (PostgreSQL, SQLite). Sur MySQL, les instructions DDL valident implicitement la transaction : c'est documenté, et la propriété y est sans effet.
- **Stockage** :
  - `DatabaseStorage` est conservé (table `migrations` : `id`, `migration`, `batch`), sur `Neutrino\Model` (E10).
  - **Décidé** : `FileStorage` est supprimé. Un état de migrations stocké dans un fichier local diverge dès qu'il y a plusieurs serveurs ou que le déploiement remplace le répertoire.
- **`--pretend`** : conservé, s'appuie sur `Support\Db::pretend()` (E10-S5). Le SQL est coloré via le helper `Debug\Highlight` d'E12 (`tempest/highlight`, dépendance de développement facultative). À défaut, il est affiché sans coloration.
- **Tâches** : enregistrées par le provider de migrations via le mécanisme d'E6-S3 (plus de déclaration en dur dans le router CLI). Elles sont documentées avec les attributs d'E6-S4.

## Stories

### E11-S1 · Grammaires et types
- `Schema\Grammar\{Mysql,Postgresql,Sqlite}` + interface `Grammar`.
- Correspondance de tous les types `Blueprint` vers `Db\Column` 5.x, avec suppression des contournements.
- Suppression de `WrapperTrait`, `Wrapper`, `Factory`, `DialectTrait` et `DialectInterface` (remplacés par les grammaires).
- Tests de `tests/Test/Database/Schema/Dialect` réécrits par grammaire : SQL généré pour chaque type et chaque commande, sur les trois SGBD (services CI MySQL et PostgreSQL d'E0, SQLite en mémoire).

### E11-S2 · `Builder` et `Blueprint`
- `Builder` typé : `hasTable`, `hasColumn(s)`, `getColumnType`, `getColumnListing`, `create`, `table`, `drop`, `dropIfExists`, `dropAllTables`, `rename`, `execute`, `enable/disableForeignKeyConstraints`.
- `Blueprint` typé (85 méthodes). Commandes construites sur `Db\Column`, `Db\Index` et `Db\Reference` 5.x, et exécutées via l'adapter de la connexion. `Support\Fluent` est conservé pour les définitions de colonnes.
- Tests : `BlueprintTest` et `BuilderTest` migrés, et exécution réelle sur les trois SGBD (création, modification, renommage, suppression, index, clés étrangères).

### E11-S3 · Migrator et migrations
- `Migrator` typé : `run`, `runPending`, `rollback` (par `--step` ou dernier batch), `reset`, `status`, avec `--pretend`.
- Résolution des migrations anonymes et nommées, connexion par migration, transactions.
- `MigrationCreator` : stubs anonymes (`blank`, `create`, `update`).
- Préfixes `DatePrefix` et `TimestampPrefix` typés.
- Tests : `MigratorTest` et `MigrationCreatorTest` migrés, plus migrations anonymes, migration sur une connexion secondaire, rollback d'une migration en échec dans une transaction (PostgreSQL et SQLite).

### E11-S4 · Stockage
- `StorageInterface` et `DatabaseStorage` typés, `MigrationModel` sur l'API de description d'E10.
- Suppression de `FileStorage` et de ses tests.
- Tests de `tests/Test/Database/Migrations/Storage` migrés.

### E11-S5 · Provider et tâches CLI
- `MigrationsServicesProvider` sur `Phalcon\Di\Di`, sans `Di::getDefault()`. Il enregistre aussi les tâches via E6-S3.
- Tâches `migrate`, `migrate:install`, `migrate:status`, `migrate:rollback`, `migrate:reset`, `migrate:refresh`, `migrate:fresh` et `make:migration`, typées et documentées par attributs, avec les options `--database`, `--step`, `--pretend`, `--path` et `--force` (confirmation en production).
- Tests de `tests/Test/Database/Cli` migrés.

## Changements cassants pour les apps

- `Schema\Dialect\*` (classes et interface) remplacés par `Schema\Grammar\*`. Seules les apps qui avaient étendu un dialecte sont concernées.
- `FileStorage` supprimé : passer sur `DatabaseStorage` avant la migration vers la 2.0.
- Migrations exécutées dans une transaction par défaut sur PostgreSQL et SQLite (désactivable par migration).
- Les tâches de migration ne sont disponibles que si le provider de migrations est déclaré dans le kernel CLI de l'app.

## Critères d'acceptation spécifiques

- Les suites `Database/Cli`, `Database/Migrations`, `Database/Providers` et `Database/Schema` sont activées et passent sur MySQL 8, PostgreSQL 16 et SQLite, y compris sur le job Phalcon 6.
- Les migrations de `tests/.fake` (nommées, 1.3) et une migration anonyme s'exécutent, reviennent en arrière et s'affichent correctement dans `migrate:status`.
- `migrate --pretend` affiche le SQL sans rien exécuter (vérifié par un listener `db:beforeQuery`).
