# Nucleon 2.0 — Migration vers Phalcon 5 / PHP 8.3+

Ce dossier pilote la refonte du framework, de la 1.3 (Phalcon 3, PHP 5.6 – 7.3) vers la 2.0.

- [`CONVENTIONS.md`](CONVENTIONS.md) : règles communes à tous les epics (code, dépendances, performances, critères d'acceptation).
- [`epics/`](epics) : un fichier par epic.
- [`tools/phalcon-api-audit.py`](tools/phalcon-api-audit.py) : liste les classes Phalcon utilisées par un module et celles qui n'existent plus en 5.x.

## Cibles

| | 1.3 | 2.0 |
|---|---|---|
| PHP | 5.6 – 7.3 | **≥ 8.3** |
| Phalcon | 3.0 – 3.4 (extension C) | **5.22+** (extension C). Job CI non bloquant sur **Phalcon 6** (implémentation PHP pur, en RC). |
| Tests | PHPUnit 5.6, Mockery 0.9 | PHPUnit 11, Mockery 1.6 |
| CI | Travis + Coveralls | GitHub Actions |

## Décisions prises

| Sujet | Décision | Epic |
|---|---|---|
| Version PHP | PHP ≥ 8.3 (8.1 et 8.2 ne reçoivent plus ou bientôt plus de correctifs de sécurité). | E0 |
| Phalcon 6 | Code écrit contre l'API Phalcon 5. Un job CI sur `phalcon/phalcon` 6 signale les incompatibilités sans bloquer les merges. | E0 |
| Optimizer / PhpPreloader | Supprimés. OPcache, `opcache.preload` et l'autoloader Composer optimisé couvrent le besoin. Plus de dépendance à `nikic/php-parser`. | E1 |
| HttpClient | Réécriture légère, sans dépendance, API inspirée de `symfony/http-client`. Notre client (2 150 lignes, 0 dépendance) est déjà plus léger que rmccue/requests (8 157), php-http + nyholm (12 548), symfony/http-client (15 759) et Guzzle (35 649). | E13 |
| Migrations | On garde les nôtres. `phalcon/migrations` est déclaratif (une classe par table, `morph()`) et n'a ni batch, ni `rollback`, ni `status`. | E11 |
| Process | On porte le nôtre. Phalcon 5 n'a pas de composant pour lancer des processus. | E14 |
| Facades | On les garde, avec un générateur d'IDE helpers (stubs PhpStorm et PHPStan) qui documente les Facades et l'injection de dépendances. | E2, E6 |
| Stockage des epics | Dans le dépôt, ce dossier. | — |
| Config non exportable | `config:cache` échoue avec un message explicite (fichier et clé). | E1 |
| Helpers `Support` | Les alias directs de fonctions natives sont supprimés. Les helpers qui apportent une valeur sont conservés et réimplémentés en natif. | E1 |
| Extension PHPStan | Paquet séparé `nucleon/phpstan`, après la 2.0. La 2.0 livre les stubs générés (`_ide_helper.php`, `.phpstorm.meta.php`). | E2 |
| Détection de Phalcon dans les tests | Par `class_exists`, pour que le job Phalcon 6 exécute vraiment les tests. Une absence de Phalcon fait échouer les tests. | E3 |
| `DatabaseStrategy` | Supprimé : `db` = connexion par défaut, `db.<nom>` pour les autres, `setConnectionService()` natif dans les modèles. | E10 |
| Sécurité du `Repository` | Listes blanches pour les colonnes, les opérateurs et le sens de tri (injection PHQL possible en 1.3). | E10 |
| Description des modèles | Attributs PHP 8 en plus de l'API par méthodes, lus par une `MetaDataStrategy` et mis en cache en production. | E10 |
| Filtre `string` du `Repository` | `=` par défaut. `LIKE` se demande explicitement via `operator`. | E10 |
| Documentation des tâches CLI | Attributs PHP 8 (`#[Description]`, `#[Option]`, `#[Argument]`). Docblocks lus en repli déprécié pendant la 2.x, supprimés en 3.0. | E6 |
| API du cache | PSR-16 (`Phalcon\Cache\CacheInterface`), sans couche de compatibilité avec l'ancienne API. | E7 |
| Stockage des migrations | `FileStorage` supprimé, seul `DatabaseStorage` reste. | E11 |
| Précompilation Volt | Nouvelle tâche `view:cache`, exécutée au déploiement. | E9 |
| Coloration syntaxique (debug) | `tempest/highlight` en `suggest` / `require-dev` (HTML et terminal, aucune dépendance), à la place d'`ark4ne/highlight`. | E12 |
| Requêtes HTTP parallèles | Pas dans la 2.0 (synchrone). L'API permet de les ajouter dans la 2.x sans changement cassant. | E13 |
| Config Rector de migration | Livrée avec la 2.0 (`resources/rector/upgrade-2.0.php`). | E15 |
| Maintenance de la 1.3 | Aucune. La 1.x est terminée, le tag `v1.3.2` reste disponible. | E15 |
| Conteneur | On reste sur `Phalcon\Di\Di` : `Phalcon\Container` n'implémente pas `Di\DiInterface`, qu'exige toute la pile MVC. | E2 |
| Config | `Neutrino\Config\Config extends Phalcon\Config\Config`, lectures directes du tableau interne : 7 à 9 fois plus rapide en lecture que la config de Phalcon 5, qui l'est jusqu'à 18 fois moins que celle de Phalcon 3. | E2 |
| Dotconst compilé | `const NAME = ...;` plutôt que `define()` (légèrement plus rapide), sauf `@php/env`. | E2 |

## Points à trancher (portés par les epics)

| Sujet | Question | Epic |
|---|---|---|
| Debug | Remplacement de notre barre de debug par `phalcon/debugbar` (en `suggest`, sans dépendance de production), à confirmer par l'étude E12-S1. On garde notre page d'erreur et `VarDump`. | E12 |
| Auth | Adoption de `Phalcon\Auth` (disponible depuis la 5.14), à confirmer par l'étude E8-S1 (remember-me haché et révoqué, coût de construction, présence dans Phalcon 6). Sinon, portage de notre `Manager` avec les corrections de sécurité. | E8 |
| Compatibilité des données chiffrées | Phalcon 5 déchiffre-t-il les données chiffrées par la 1.3 ? À vérifier par un test, avec une procédure de migration si ce n'est pas le cas. | E7 |
| Handlers Micro | Utiliser les handlers chargés à la demande de Phalcon (`Micro\Collection::setLazy`) plutôt que notre closure ? Décision sur mesures. | E5 |

## Epics

| # | Epic | Statut | Dépend de |
|---|---|---|---|
| E0 | [Socle & outillage](epics/E00-socle-outillage.md) | Terminé | — |
| E1 | [Nettoyage](epics/E01-nettoyage.md) | Terminé | E0 |
| E2 | [Noyau](epics/E02-noyau.md) | Terminé | E1 |
| E3 | [Outils de test publics](epics/E03-outils-de-test.md) | Rédigé | E2 |
| E4 | [HTTP](epics/E04-http.md) | Rédigé | E3 |
| E5 | [Micro](epics/E05-micro.md) | Rédigé | E3, E4-S4 |
| E6 | [CLI](epics/E06-cli.md) | Rédigé | E3 |
| E7 | [Services d'infrastructure](epics/E07-services-infrastructure.md) | Rédigé | E3 |
| E8 | [Auth & sécurité](epics/E08-auth-securite.md) | Rédigé | E4, E7 |
| E9 | [Vues & Volt](epics/E09-vues.md) | Rédigé | E4 |
| E10 | [Données](epics/E10-donnees.md) | Rédigé | E7 |
| E11 | [Migrations](epics/E11-migrations.md) | Rédigé | E6, E10 |
| E12 | [Erreurs & Debug](epics/E12-erreurs-debug.md) | Rédigé | E4 |
| E13 | [HttpClient v2](epics/E13-httpclient.md) | Rédigé | E0 |
| E14 | [Process](epics/E14-process.md) | Rédigé | E0 |
| E15 | [Release 2.0](epics/E15-release.md) | Rédigé | tous |

E4 à E7 peuvent avancer en parallèle une fois E3 terminé. E13 et E14 ne dépendent que d'E0.

## Processus de rédaction

1. **Conventions** : `CONVENTIONS.md` fixe le modèle d'epic et les critères d'acceptation communs. Tous les epics s'y réfèrent.
2. **Audit avant rédaction** : pour chaque epic, lire le module, lancer `tools/phalcon-api-audit.py` sur son répertoire et vérifier chaque signature dans les `phalcon/ide-stubs` 5.22.
3. **Rédaction progressive** : E0, E1 et E2 sont détaillés jusqu'aux stories. Les autres restent au niveau objectif, périmètre et décisions, et sont découpés en stories juste avant de les commencer.
4. **Validation** : chaque epic est relu et ses décisions tranchées avant de passer au statut « Prêt ».

Statuts : `À rédiger` → `Rédigé` → `Prêt` (décisions tranchées) → `En cours` → `Terminé`.

## Audit global de l'API Phalcon (5.22.0)

Sur 104 classes Phalcon référencées dans `src/`, 39 n'existent plus sous ce nom :

- **26 supprimées ou déplacées** : `Phalcon\Cache\Backend*` et `Frontend*`, `Crypt`, `Escaper`, `Security`, `Security\Random`, `Version`, `Loader`, `DiInterface`, `Db\AdapterInterface`, `Db\Pool`, `Logger\Adapter\File`, `Logger\AdapterInterface`, `Session\Adapter\Files`, `Session\Adapter\Memcache`, `Session\AdapterInterface`, `Mvc\User\Module`, `Mvc\Model\MessageInterface`, `Error`, `Exception`, `Test`, `Http\Client*`.
- **13 devenues un namespace** (la classe a été déplacée dedans) : `Phalcon\Application`, `Config`, `Di`, `Dispatcher`, `Filter`, `Logger`, `Db`, `Db\Adapter`, `Db\Adapter\Pdo`, `Logger\Adapter`, `Logger\Formatter`, `Mvc\View\Engine`, `Session\Adapter`.

Pour la liste détaillée avec les fichiers concernés, lancer `tools/phalcon-api-audit.py`. Au-delà des renommages, toutes les méthodes Phalcon sont désormais typées : chaque surcharge dans Nucleon doit reprendre exactement la signature parente.
