# E2 — Noyau

**Statut** : Rédigé · **Dépend de** : E1 · **Bloque** : E3, puis tous les epics fonctionnels

## Objectif

Faire démarrer les trois kernels (HTTP, CLI, Micro) sur Phalcon 5 avec le même modèle déclaratif qu'en 1.3 (providers chargés à la demande, middlewares, listeners, Facades), et outiller les IDE et PHPStan pour l'injection de dépendances.

## Périmètre

- `Foundation/Bootstrap.php`, `Foundation/Kernelize.php`, `Interfaces/Kernelable.php`, `Interfaces/Providable.php`
- Les trois `Foundation/*/Kernel.php`, **uniquement pour le boot**. `handle()` et le comportement propre à chaque kernel relèvent d'E4 (HTTP), E5 (Micro) et E6 (CLI).
- `Support/Provider.php`, `Support/SimpleProvider.php`, `Module.php`
- `Support/Facades/*`
- `Events/Listener.php`, `Constants/**`
- `Config/Loader.php`, `Dotconst.php`, `Dotconst/**`
- `Support/Traits/*`, `Support/DesignPatterns/*`, `Support/Fluent*`
- Tests : `tests/Test/{Config,Dotconst,Events,Facades,Design,Support}`, plus les tests de boot des kernels.
- **Hors périmètre** : les providers concrets de `Providers/*`. Chacun est porté avec son composant (E4 à E10).

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0)

| 1.3 | 5.22 | Concerne |
|---|---|---|
| `Phalcon\Di` | `Phalcon\Di\Di` | Kernelize, Facade, Test |
| `Phalcon\DiInterface` | `Phalcon\Di\DiInterface` | Facade, Module, InjectionAwareTrait, Test |
| `Di::setRaw($name, $service)` | `Di::setService(string $name, ServiceInterface $rawDefinition)` | Kernelize, Provider, SimpleProvider, Module |
| `new Di\Service($name, $definition, $shared)` | `new Di\Service($definition, bool $shared = false)` (plus de nom) | idem |
| `Phalcon\Config` | `Phalcon\Config\Config` | Bootstrap, Kernelable, Config\Loader |
| `Phalcon\Application` | `Phalcon\Application\AbstractApplication` | Kernelable, Test |
| `Phalcon\Mvc\User\Module` | supprimé, utiliser `Phalcon\Di\Injectable` | Module |
| `AbstractApplication::$modules` non typé | `protected array $modules` | Kernels HTTP et CLI : notre `protected $modules = []` non typé provoque une **erreur fatale** |
| `Console::$_arguments`, `$_options` | `$arguments`, `array $options` | Kernel CLI (`boot`, `isQuiet`, `isHelp`, `withStats`) |
| `Mvc\Application::handle()` | `handle(string $uri)` | `Bootstrap::run()` |
| `Mvc\Micro::handle($uri = null)` | `handle(string $uri)` | `Bootstrap::run()` |
| `Phalcon\Mvc\Collection` (ODM) | supprimé | `Constants/Events/Collection.php`, `CollectionManager.php` |
| `Injectable::__get()` | `__get(string $propertyName): mixed` | Provider, SimpleProvider, Listener (via Injectable) |
| `Phalcon\Version` (méthodes statiques, surcharge de `_getVersion()`) | `Phalcon\Support\Version`, méthodes d'instance (`get()`, `getId()`, `getPart()`), surcharge de `protected function getVersion(): array` | `Neutrino\Version` (utilisé par `tests/bootstrap.php`, l'aide CLI et le Debugger) |

## Décisions

- **On reste sur `Phalcon\Di\Di`** et ses `FactoryDefault`. `Phalcon\Container` (nouveau dans 5.x) fait l'objet d'une étude limitée dans le temps (S3). On n'en change que si elle montre un gain clair sans casser la compatibilité avec `Mvc\Application`, qui attend un `DiInterface`.
- **`Bootstrap::run()` ne connaît plus la signature de `handle()`.** `Kernelable` reçoit une méthode `handleIncoming(): mixed` (nom indicatif) qui lit l'entrée courante : l'URI pour HTTP et Micro, `argv` pour la CLI. Elle appelle ensuite le `handle()` Phalcon. Chaque kernel l'implémente dans son epic (E4, E5, E6). E2 pose l'interface et une implémentation minimale.
- **Une seule logique d'enregistrement des providers.** `Kernelize::registerServices()` et `Module::registerServices()` dupliquent la même boucle. Elle est extraite dans un `ProviderRegistrar` interne.
- **Facades conservées.** `shouldReceive()` ne dépend plus de Mockery au chargement : on vérifie `class_exists(Mockery::class)` et on lève une exception explicite si Mockery est absent. `src/` ne dépend plus d'un paquet de `require-dev`.
- **IDE helpers.** Un générateur produit, à partir de l'application démarrée, la documentation des Facades et de l'injection de dépendances pour PhpStorm et PHPStan (S7). La tâche CLI qui l'expose est livrée dans E6.
- **Typage des providers.** `Provider::register()` est déclaré avec un type de retour réel dans les providers concrets (ex. `register(): \Phalcon\Mvc\Router`). Le générateur d'IDE helpers lit ce type par réflexion, sans instancier le service.
- **Dotconst conservé**, typé. On mesure la génération de `const X = ...;` (résolu à la compilation, mis en cache par OPcache) à la place de `define()`, quand la valeur est une expression constante. `@php/env` reste un `define()`, car `getenv()` est évalué à l'exécution.
- `Constants\Services`, `Constants\Env` et `Constants\Events\*` deviennent des constantes de classe typées (voir `CONVENTIONS.md`). Pas d'enum.

## Stories

### E2-S1 · Kernelize, Bootstrap et Kernelable
- Migration des classes DI et Config, `setService`, nouveau constructeur de `Service`.
- `protected array $modules` dans les kernels HTTP et CLI, et typage des autres propriétés déclaratives (`array $providers`, `array $middlewares`, `array $listeners`, `?string $dependencyInjection`, `?string $eventsManagerClass`, `array $errorHandlerLvl`).
- `Kernelable` typé, avec la nouvelle méthode `handleIncoming()`. `Bootstrap::run()` l'utilise.
- Le Kernel CLI utilise `$this->arguments` et `$this->options`.
- `Neutrino\Version` : n'étend plus la classe Phalcon. Classe autonome avec `Version::get(): string` statique (API conservée), numéro de version en constante. `tests/bootstrap.php` doit fonctionner dès cette story.
- Tests : `Bootstrap::make()` des trois stub kernels de `tests/.fake` ; ordre des étapes de boot ; événements `kernel:boot` et `kernel:terminate`.

### E2-S2 · Providers et Module
- `Provider`, `SimpleProvider` et `Providable` typés. Alias conservés.
- `ProviderRegistrar` partagé par `Kernelize` et `Module`.
- `Module` étend `Phalcon\Di\Injectable` et implémente `ModuleDefinitionInterface` avec les signatures 5.x (`registerAutoloaders(?DiInterface $container = null)`, `registerServices(DiInterface $container)`).
- Tests : service partagé ou non, alias, résolution à la demande (le provider n'est appelé qu'à la première résolution), enregistrement `'name' => Class::class`.

### E2-S3 · Étude `Phalcon\Container` (1 jour maximum)
- Compatibilité avec `Mvc\Application`, `Cli\Console` et `Mvc\Micro` (attendent-ils un `Di\DiInterface` ?), autowiring, coût mesuré avec le script de mesures d'E0.
- Livrable : une note dans cet epic avec la décision. Par défaut, on reste sur `Phalcon\Di\Di`.

### E2-S4 · Events et Constants
- `Listener` typé, signatures `Events\Manager::attach(string, $handler, int)`.
- `Constants\*` en constantes typées.
- Suppression de `Constants\Events\Collection` et `CollectionManager` (ODM supprimé de Phalcon).
- Vérifier chaque nom d'événement de `Constants\Events\*` avec la documentation et les stubs Phalcon 5. Les événements renommés ou supprimés sont listés pour `UPGRADING-2.0.md`.
- Revoir `Services::ASSETS` (Assets supprimé dans E1 : le service Phalcon existe toujours, on garde la constante) et `Services::HTTP_CLIENT` (conservé pour E13).

### E2-S5 · Config et Dotconst
- `Config\Loader` → `Phalcon\Config\Config`, typé. Le chargement depuis `bootstrap/compile/config.php` est conservé (format produit par E1-S3).
- `Dotconst`, `Loader`, `Compile`, `Helper` et les extensions typés. Mesure de `const` contre `define()` dans le fichier compilé ; on adopte `const` si c'est plus rapide.
- Tests existants de `tests/Test/Dotconst` migrés, plus les cas : référence imbriquée `@{...}`, `@php/env` avec valeur par défaut, fichier `.const.{env}.ini`.

### E2-S6 · Facades
- `Facade` typé : `getFacadeAccessor(): string`, `getFacadeRoot(): object`.
- Chargement de Mockery à la demande (voir Décisions).
- `swap()` et `clearResolvedInstances()` inchangés fonctionnellement.
- Chaque Facade concrète déclare son service par une constante de `Services`.
- Tests : appel statique, `swap()`, `shouldReceive()`, comportement sans Mockery.

### E2-S7 · Générateur d'IDE helpers
- Classe `Neutrino\Support\IdeHelper\Generator`. Elle reçoit un kernel démarré et parcourt `$di->getServices()`. Pour chaque service, elle détermine la classe :
  1. définition `'name' => Class::class` ;
  2. `SimpleProvider::$class` ;
  3. type de retour de `Provider::register()` ;
  4. sinon, résolution protégée par un `try/catch`, pour les services natifs de `FactoryDefault`.
- Fichiers produits, à ignorer dans le git des apps :
  - `_ide_helper.php` :
    - pour chaque Facade, une classe stub avec un `@method static` par méthode publique du service cible (signature et type de retour lus par réflexion) ;
    - un stub `Phalcon\Di\Injectable` avec un `@property-read` par service, pour `$this->cache`, `$this->router`, etc. dans les controllers, tâches et listeners.
  - `.phpstorm.meta.php` : `override()` de `DiInterface::get()`, `getShared()` et `Facade::getFacadeRoot()` vers la classe de chaque service.
- **PHPStan** : les stubs générés sont utilisables via `stubFiles`. **Décidé** : l'extension PHPStan (réflexion des Facades, type de retour dynamique de `DiInterface::get()`) sera un paquet séparé `nucleon/phpstan`, livré après la 2.0. Elle dépend de `phpstan/phpstan`, donc elle ne peut pas vivre dans `src/` (voir `CONVENTIONS.md`).
- Tests : génération sur `tests/.fake`. Le fichier produit est du PHP valide (`php -l`) et contient les services et Facades attendus.

### E2-S8 · Traits et design patterns
- `InjectionAwareTrait`, `Macroable`, `Singleton`, `Strategy` (+ traits) et `Fluent*` typés et alignés sur `Di\DiInterface`.
- Vérifier que `Strategy` reste compatible avec ses utilisateurs (`CacheStrategy`, `DatabaseStrategy`, `RateLimiter`), qui seront portés dans E7, E10 et E8.

## Changements cassants pour les apps

- Les kernels de l'app doivent typer leurs propriétés déclaratives (`protected array $providers = [...]`, etc.). `protected $modules` non typé provoque une erreur fatale.
- Un provider qui étend `Provider` doit typer `register()`. Ce n'est pas obligatoire, mais c'est nécessaire pour les IDE helpers.
- `Module::registerServices(DiInterface $container)` : nouvelle signature.
- `Constants\Events\Collection` et `CollectionManager` supprimés. Événements renommés : liste produite par S4.
- `Bootstrap::run()` : les apps qui surchargent `handle()` dans leur kernel doivent passer par `handleIncoming()`.

## Critères d'acceptation spécifiques

- Les trois stub kernels de `tests/.fake` démarrent (`make()` + `boot()` + `terminate()`) sur Phalcon 5.22 et sur le job Phalcon 6.
- Les suites `Config`, `Dotconst`, `Events`, `Facades`, `Design` et `Support` sont activées en CI et passent.
- Mesures : le boot de chaque kernel et la résolution d'un service partagé ne sont pas moins bons que ceux de la 1.3.
- Le générateur produit `_ide_helper.php` et `.phpstorm.meta.php` valides pour `tests/.fake`.
