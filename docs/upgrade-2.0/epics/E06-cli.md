# E6 — CLI

**Statut** : Rédigé · **Dépend de** : E3 · **Bloque** : E11

## Objectif

Porter la console Nucleon sur Phalcon 5 : kernel CLI, router, `Task`, sortie et questions, tâches du framework. Rendre la documentation des tâches (aide, liste) indépendante des docblocks, et exposer les générateurs livrés par E1, E2 et E4.

## Périmètre

- `Foundation/Cli/Kernel.php` : `handle`, gestion de l'aide, options globales, statistiques
- `Cli/Task.php` (267 lignes), `Cli/Router.php`
- `Cli/Output/*` (Writer, Decorate, Helper, Table, Block, Group, QuestionHelper), `Cli/Question/*`
- `Providers/Cli/{Router,Dispatcher,Output}.php`, `Constants/Services/Cli.php`
- `Foundation/Cli/Tasks/*` : `default`, `list`, `help`, `optimize`, `clear-compiled`, `config:cache`, `config:clear`, `dotconst:cache`, `route:cache`, `route:list`, `view:clear`, `server:run`, et la nouvelle tâche `ide-helper`
- `tests/Test/Cli` (16 fichiers de test), stubs CLI de `tests/.fake`
- **Hors périmètre** :
  - la logique métier des tâches livrée par d'autres epics : compilation de la config et preload (E1), générateur d'IDE helpers (E2), compilation des routes (E4) ;
  - les tâches de migration (`migrate*`, `make:migration`) → E11.

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0)

| 1.3 | 5.22 | Concerne |
|---|---|---|
| `Console::$_arguments`, `$_options` | `$arguments`, `array $options` | Kernel CLI (`handle`, `getArguments`, `isQuiet`, `isHelp`, `withStats`, `boot`) |
| `Console::handle(array $arguments = null)` | `handle(?array $arguments = null)` | Kernel CLI |
| `Console::setArgument(array $arguments = null, $str = true, $shift = true)` | `setArgument(?array $arguments = null, bool $str = true, bool $shift = true): static` | Kernel CLI, `FuncTestCase::dispatchCli()` |
| `Cli\Router::add($pattern, $paths = null)` | `add(string $pattern, $paths = null): RouteInterface` | `Cli\Router::addTask()` |
| `Cli\Router::setDefaultTask($task)` | `setDefaultTask(string): static` | provider Router |
| `Cli\Dispatcher::setTaskSuffix()`, `getOptions()`, `getParams()` | même nom, typés | provider Dispatcher, `Task` |
| `Phalcon\Config` | `Phalcon\Config\Config` | `Task` |

## Décisions

- **Documentation des tâches par attributs.**
  - Aujourd'hui, `help` et `list` lisent les docblocks des actions (`@description`, `@option`, `@argument`) par réflexion. Avec `opcache.save_comments=0`, réglage courant en production pour la performance, ces docblocks disparaissent et l'aide est vide.
  - En 2.0, les tâches se documentent avec des attributs PHP 8 : `#[Description('…')]`, `#[Option('-m, --memory', '…')]`, `#[Argument('name', '…')]` dans `Neutrino\Cli\Attribute`.
  - **Décidé** : la lecture des docblocks est gardée comme repli déprécié pendant la 2.x, avec un avertissement de dépréciation. Elle sera supprimée en 3.0.
- **Enregistrement des tâches par module.**
  - Le provider `Cli\Router` déclare en dur les tâches de migration, ce qui couple la console au module Database.
  - On ajoute un moyen pour un provider de déclarer ses tâches : une interface `ProvidesTasks` lue par le provider Router, ou une liste de tâches dans la config CLI. Le choix est fait en S3.
  - E11 l'utilise pour les tâches `migrate*`.
- **`dotconst:cache` devient une commande.** La tâche existe mais n'est aujourd'hui accessible que via `optimize`.
- **Couleurs** :
  - `Decorate` utilise `stream_isatty(STDOUT)` au lieu de `posix_isatty`, qui exige l'extension posix ;
  - la variable d'environnement standard `NO_COLOR` est prise en compte ;
  - les options `--colors` et `--no-colors` restent prioritaires.
- Les tâches du framework restent dans `Foundation\Cli\Tasks` et sont `final`.

## Stories

### E6-S1 · Kernel CLI
- `handle(?array $arguments = null)`, `handleIncoming()` (lit `$_SERVER['argv']`), propriétés `arguments` et `options`.
- Redirection vers `HelperTask` quand `-h` / `--help` est présent, revalidée avec le format d'arguments de Phalcon 5.
- Options globales `-q` / `--quiet`, `-s` / `--stats`, `--colors`, `--no-colors`.
- Tests : `dispatchCli()` (E3) sur `StubTask` avec arguments, options, aide et statistiques.

### E6-S2 · `Task`, `Router`, Output et Questions
- `Task` typé : `getArg()`, `getOption()`, `hasOption(string ...$options)`, `callTask()`, sorties (`line`, `info`, `notice`, `warn`, `error`, `question`, `table`, `block`), questions (`prompt`, `confirm`, `choices`, `ask`).
- `Cli\Router::addTask(string $command, string $class, ?string $action = null, array $params = [])` avec la syntaxe `{param}`.
- `Writer`, `Decorate` (couleurs, voir Décisions), `Table`, `Block`, `Group`, `QuestionHelper` et `Question*` typés. L'entrée de `QuestionHelper` reste injectable pour les tests.
- Tests existants de `tests/Test/Cli` migrés.

### E6-S3 · Providers CLI et enregistrement des tâches
- Providers `Router`, `Dispatcher` et `Output` typés (`register(): …`).
- Mécanisme d'enregistrement des tâches par provider (voir Décisions). Les tâches du framework l'utilisent aussi.
- `Constants\Services\Cli` en constantes typées.

### E6-S4 · Attributs de documentation
- `Neutrino\Cli\Attribute\{Description, Option, Argument}`. `Output\Helper::getTaskInfos()` lit les attributs, puis les docblocks en repli déprécié.
- Toutes les tâches du framework sont annotées avec ces attributs.
- `list` et `help` sont testés avec `opcache.save_comments=0`, via un job CI ou un test qui supprime les docblocks.

### E6-S5 · Tâches du framework
- `default`, `list`, `help` (y compris `describeRoutePattern`), `clear-compiled`, `config:cache`, `config:clear`, `dotconst:cache` (nouvelle commande), `optimize` et `route:cache` (s'appuient sur E1 et E4), `route:list` (lecture du router HTTP via les getters de Phalcon 5), `view:clear`, `server:run`.
- `server:run` : validation de l'hôte avec `filter_var(…, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)` (la branche PHP < 7 est supprimée par E1-S5).

### E6-S6 · Tâche `ide-helper`
- Commande `ide-helper` qui appelle le générateur d'E2-S7 sur le kernel HTTP de l'app, et en option sur les kernels CLI et Micro, pour couvrir tous les services.
- Options : `--output-dir`, `--no-meta` (pas de `.phpstorm.meta.php`).
- Tests sur `tests/.fake`.

## Changements cassants pour les apps

- La documentation des tâches passe par des attributs. Les docblocks restent lus en repli déprécié pendant la 2.x (suppression en 3.0).
- Les tâches de l'app qui lisent `$this->_arguments` ou `$this->_options` du kernel doivent utiliser les accesseurs.
- `Decorate` ne dépend plus de l'extension posix. `NO_COLOR` désactive les couleurs.

## Critères d'acceptation spécifiques

- La suite `Cli` est activée et passe, y compris sur le job Phalcon 6.
- `list` et `help` affichent descriptions, options et arguments avec `opcache.save_comments=0`.
- `ide-helper` produit des fichiers valides pour `tests/.fake`.
- Mesures : le boot du kernel CLI et l'exécution d'une tâche simple ne sont pas moins bons que ceux de la 1.3.
