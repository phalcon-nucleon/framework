# E10 — Données

**Statut** : Rédigé · **Dépend de** : E7 · **Bloque** : E11

## Objectif

Porter la couche de données sur Phalcon 5 en gardant ce qui fait la performance de Nucleon : des modèles décrits sans introspection de la base. On supprime au passage la stratégie multi-connexions, que Phalcon couvre nativement, et on corrige l'injection PHQL possible dans `Repository`.

## Périmètre

- `Model.php` (274 lignes), `Support/Model/Eachable.php`
- `Providers/Database.php`, `Providers/Model.php`, `Providers/ModelManager.php`, `Providers/ModelsMetaData.php`, `Providers/ModelTransactionManager.php`
- `Database/DatabaseStrategy.php` (831 lignes)
- `Repositories/Repository.php` (410 lignes), `Interfaces/Repositories/RepositoryInterface.php`, `Repositories/Exceptions/*`
- `Support/Db.php` (capture des requêtes, mode `pretend`)
- Tests : `tests/Test/{Models,Repositories}`, `tests/Test/Database/DatabaseStrategyTest.php`, `tests/Test/Providers` (providers de base de données et de modèles)
- **Hors périmètre** :
  - les migrations, le schéma et les dialectes → E11 ;
  - `Database/Providers/MigrationsServicesProvider.php` → E11.

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0)

| 1.3 | 5.22 | Concerne |
|---|---|---|
| `Phalcon\Db\AdapterInterface` | `Phalcon\Db\Adapter\AdapterInterface`, entièrement typée, avec de nouvelles méthodes | `DatabaseStrategy` (qui l'implémente par délégation, méthode par méthode) |
| `Phalcon\Db\Adapter` | `Phalcon\Db\Adapter\AbstractAdapter` | `Support\Db` |
| `Db\Adapter\Pdo\*($descriptor)` | `Pdo\Mysql`, `Postgresql`, `Sqlite` (`__construct(array $descriptor)`), plus `Db\Adapter\PdoFactory` | `Providers\Database` |
| `Di\Service($name, …)` + `setRaw()` | `Di\Service($definition, $shared)` + `setService()` | `Providers\Database`, `Providers\Model` |
| `MetaData` : 14 clés `MODELS_*` | 16 constantes, dont `MODELS_COLUMN_MAP` et `MODELS_REVERSE_COLUMN_MAP` | `Model` |
| Description d'un modèle par une méthode `metaData()` sur le modèle | Point d'extension officiel : `MetaData\Strategy\StrategyInterface` (`getMetaData()`, `getColumnMaps()`), plus les stratégies `Introspection` et `Annotations` fournies | `Model` |
| Adapters de métadonnées : `Memory` (+ Apc, Files…) | `Memory`, `Apcu`, `Libmemcached`, `Redis`, `Stream` | `Providers\ModelsMetaData` |
| `Phalcon\Mvc\Model\MessageInterface` | `Phalcon\Messages\MessageInterface` | `Repository` |
| `Phalcon\Exception` | `Phalcon\Exception` disparu, exceptions propres à chaque composant | `Repository` |

## Décisions

- **Description des modèles par une stratégie de métadonnées officielle.**
  - La 1.3 décrit chaque modèle dans `initialize()` (`primary()`, `column()`, `timestamps()`, `softDelete()`…) et expose le résultat via une méthode `metaData()` que Phalcon lit si elle existe.
  - En 2.0, une classe `Neutrino\Model\MetaDataStrategy` implémente `StrategyInterface` et fournit ces métadonnées à Phalcon. On ne dépend plus d'une méthode spéciale, qui reste à vérifier dans Phalcon 5 (S1).
  - L'API de description reste la même pour les apps.
- **Attributs PHP 8 pour la description (décidé)**, en plus de l'API par méthodes : `#[Primary]`, `#[Column(type: Column::TYPE_VARCHAR, nullable: true)]`, `#[Timestamps]`, `#[SoftDelete]` sur les propriétés du modèle.
  - Avantages : la description est déclarative, lisible par les IDE et par PHPStan, et colle aux propriétés typées.
  - Coût : la lecture des attributs passe par la réflexion. Elle n'est donc acceptable que si les métadonnées sont mises en cache.
  - Les deux API coexistent. Les attributs sont lus par `MetaDataStrategy`, puis mis en cache par l'adapter de métadonnées (`apcu` ou `stream` en production). Les mesures de S6 valident le coût.
- **Adapter de métadonnées configurable** : `models.metadata.adapter` = `memory` par défaut (développement), `apcu` ou `stream` recommandés en production, avec une tâche `model:cache` qui préchauffe le cache.
- **Suppression de `DatabaseStrategy`.**
  - Ses 831 lignes réimplémentent toute l'interface d'adapter de base de données en déléguant chaque appel à la connexion par défaut. Avec l'interface typée et élargie de Phalcon 5, il faudrait réécrire chaque signature, pour un gain nul.
  - Remplacement :
    - le service `db` est la connexion par défaut (comme aujourd'hui quand il n'y a qu'une connexion) ;
    - chaque connexion est exposée en `db.<nom>`, créée à la demande ;
    - les modèles choisissent la leur avec les méthodes natives `setConnectionService()`, `setReadConnectionService()` et `setWriteConnectionService()` ;
    - un helper `Db::connection(?string $name)` donne accès à une connexion par son nom.
- **`Repository` sécurisé.**
  - `paramsToCriteria()` insère aujourd'hui les noms de colonnes (clés de `$params`), l'opérateur et le sens de tri **tels quels** dans le PHQL. Si une app passe des données utilisateur comme clés ou comme ordre de tri (tri depuis la query string, par exemple), il y a injection PHQL.
  - En 2.0 :
    - les colonnes sont validées contre les attributs du modèle (métadonnées) ;
    - les opérateurs sont validés contre une liste blanche (`=`, `!=`, `<>`, `<`, `<=`, `>`, `>=`, `LIKE`, `NOT LIKE`, `IN`, `NOT IN`, `IS NULL`, `IS NOT NULL`) ;
    - le sens de tri est limité à `ASC` / `DESC` ;
    - toute valeur invalide lève une exception.
  - **Décidé** : une valeur `string` produit `=` (en 1.3, elle produisait un `LIKE` qui interprète `%` et `_` comme des jokers). `LIKE` se demande explicitement via `['operator' => 'LIKE', 'value' => …]`.
- **`Eachable` et `Repository::each()`** conservés (générateurs), typés. La pagination par décalage (`offset`) est conservée. La pagination par clé (*keyset*) est hors périmètre.
- **`Support\Db::getQueries()` et `pretend()`** conservés : ils servent au mode `--pretend` des migrations (E11). Ils sont portés sur `AbstractAdapter` et ne remplacent plus définitivement le gestionnaire d'événements de la connexion.

## Stories

### E10-S1 · Vérification du mécanisme de métadonnées
- Tester sur Phalcon 5.22 et sur le job Phalcon 6 si une méthode `metaData()` du modèle est toujours lue. Quel que soit le résultat, on passe par `StrategyInterface` (décision ci-dessus). Le test sert à savoir si la 1.3 et la 2.0 peuvent coexister pendant la migration d'une app.
- Correspondance des clés `MODELS_*` de la 1.3 avec les 16 constantes de la 5.22, y compris `MODELS_COLUMN_MAP` et `MODELS_REVERSE_COLUMN_MAP`.

### E10-S2 · `Model` et `MetaDataStrategy`
- `Neutrino\Model` typé. `primary()`, `column()`, `timestampable()`, `timestamps()`, `softDeletable()` et `softDelete()` conservés. Comportements `Timestampable` et `SoftDelete` de Phalcon 5.
- `MetaDataStrategy` : métadonnées et `columnMap` fournis à Phalcon depuis la description du modèle.
- Attributs `Neutrino\Model\Attribute\{Primary, Column, Timestamps, SoftDelete}` lus par `MetaDataStrategy`, mesurés avec l'adapter `memory` et avec `apcu`.
- Tests : description complète (types, bind types, numériques, nullables, valeurs par défaut, auto insert/update, identité, colonnes mappées), aucune requête d'introspection envoyée à la base (vérifié par un listener `db:beforeQuery`).

### E10-S3 · Providers de base de données et de modèles
- `Providers\Database` : connexions `db.<nom>` à la demande, `db` = connexion par défaut, adapter donné par son nom (`mysql`, `postgresql`, `sqlite` via `PdoFactory`) ou par sa classe.
- `Providers\Model`, `ModelManager`, `ModelsMetaData` (adapter configurable + `MetaDataStrategy`) et `ModelTransactionManager` sur `setService()`.
- Suppression de `DatabaseStrategy` et de son test. Ajout du helper `Db::connection()`.
- Tests : une et plusieurs connexions, connexion par modèle (`setConnectionService`), connexions lecture/écriture séparées.

### E10-S4 · `Repository`
- `RepositoryInterface` et `Repository` typés, messages via `Phalcon\Messages\MessageInterface`.
- `paramsToCriteria()` sécurisé (listes blanches, voir Décisions).
- Transactions via `Transaction\Manager` (service `transactionManager`), exception `TransactionException` conservée.
- Tests d'injection : clé de colonne, opérateur et sens de tri malveillants rejetés.

### E10-S5 · `Eachable` et `Support\Db`
- `Eachable::each()` et `Repository::each()` typés (`iterable` / `Generator`).
- `Support\Db::getQueries()` et `pretend()` : listener attaché puis détaché proprement. Le gestionnaire d'événements d'origine de la connexion est restauré.

### E10-S6 · Mesures
- Scénarios ajoutés au script d'E0 :
  - premier chargement d'un modèle (initialisation des métadonnées) ;
  - `findFirst` par clé primaire ;
  - `find` de 100 lignes sur SQLite en mémoire.
- Comparaison avec la 1.3 (adapter `memory`) et en 2.0 (`memory`, `apcu`, avec et sans attributs).

## Changements cassants pour les apps

- `DatabaseStrategy` supprimé. `db` est la connexion par défaut, les autres connexions sont `db.<nom>`, et les modèles choisissent la leur avec `setConnectionService()`.
- Config de connexion : `adapter` par nom (`mysql`, `postgresql`, `sqlite`) ou par classe Phalcon 5 (`Phalcon\Db\Adapter\Pdo\Mysql`).
- `Repository` rejette les colonnes inconnues, les opérateurs hors liste blanche et les sens de tri autres que `ASC` / `DESC`. Une valeur `string` produit `=` au lieu de `LIKE`.
- Les modèles qui surchargent `metaData()` ou `columnMap()` à la main doivent passer par l'API de description ou par les attributs.

## Critères d'acceptation spécifiques

- Les suites `Models` et `Repositories`, ainsi que les tests des providers de données, sont activées et passent, y compris sur le job Phalcon 6.
- Aucun modèle Nucleon ne déclenche de requête d'introspection (`DESCRIBE`, `information_schema`, `PRAGMA`).
- Mesures : le chargement d'un modèle et `findFirst` ne sont pas moins bons que ceux de la 1.3.
