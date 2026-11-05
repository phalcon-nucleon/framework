# E2 — Noyau

**Statut** : Terminé · **Dépend de** : E1 · **Bloque** : E3, puis tous les epics fonctionnels

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

### E2-S9 · Config rapide
- `Neutrino\Config\Config extends Phalcon\Config\Config` : lecture directe du tableau interne pour une clé exacte (`__get`, `offsetGet`, `get`, `has`, `isset`, `path`), repli sur Phalcon sinon (casse différente, clé absente, `cast`). Les niveaux imbriqués sont de la même classe. Reste un `Phalcon\Config\Config`.
- `Config\Loader` la renvoie.
- Tests : lectures, repli insensible à la casse, valeurs nulles et absentes, écriture, `merge()`, `toArray()`. Mesure A/B.

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

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Kernelize, Bootstrap, Kernelable | Fait | Propriétés déclaratives typées dans les trois kernels, `Kernelable` typé avec `handleIncoming()` (HTTP et Micro : chemin de `REQUEST_URI` sans la query string ; CLI : `handle()` sur les arguments déjà posés). `Bootstrap` `final`, `make()` générique (`@template`). Kernel CLI sur `$this->arguments` / `$this->options`, sans constructeur. `Neutrino\Version` autonome (`MAJOR`, `MINOR`, `PATCH`, `STABILITY`). Les providers concrets de `Providers/*` ont seulement reçu le typage de leurs propriétés, pour pouvoir être chargés. |
| S2 · Providers et Module | Fait | `Foundation\ProviderRegistrar` (interne) partagé par `Kernelize` et `Module`. Il injecte le conteneur dans le provider avant `registering()` (plus de dépendance au conteneur par défaut). `Provider::register()` reste sans type de retour dans la classe de base, pour que chaque provider déclare le sien. `SimpleProvider::getClass()` pour le générateur d'IDE helpers. |
| S3 · Étude `Phalcon\Container` | Fait | Voir « Décision S3 » ci-dessous : on reste sur `Phalcon\Di\Di`. |
| S4 · Events et Constants | Fait | Noms vérifiés dans le binaire `phalcon.so` 5.22.1 et par exécution réelle (modèles, Volt) : voir le tableau ci-dessous. `Listener` implémente `EventsAwareInterface` lui-même (`Injectable` n'a plus d'events manager en Phalcon 5). Constantes en `public const string`. `Services::ASSETS` et `Services::HTTP_CLIENT` conservés. |
| S5 · Config et Dotconst | Fait | `Config\Loader` sur `Neutrino\Config\Config` (S9) et `ConfigCompiler::COMPILED_FILE`. Dotconst typé ; `Helper::normalizePath` supprimé (doublon exact de `Path::normalize`). `const` adopté dans le fichier compilé (mesure ci-dessous). Corrections : `@php/dir@suffixe` perdait son suffixe une fois compilé, `@php/env` sans défaut valait `false` compilé contre `null` lu, une référence `@{inconnue}` produisait du PHP invalide. `Loader::loadRaw()` ne résout plus tout le fichier pour trouver `APP_ENV`. Tests : 26, dont la parité fichier compilé / fichiers ini dans un processus séparé. |
| S6 · Facades | Fait | `getFacadeAccessor(): string\|object` (l'accès par objet existait et est testé). Mockery chargé à la demande, `LogicException` explicite sans Mockery (testé dans un processus sans Mockery). **Correction** : Phalcon 5 garde en cache les instances partagées déjà résolues, `swap()` et `shouldReceive()` ne remplaçaient donc pas un service déjà utilisé ; le service est désormais retiré du conteneur avant d'être remplacé. |
| S7 · IDE helpers | Fait | `Support\IdeHelper\Generator` (+ `SignatureRenderer`). Classe d'un service trouvée sans le construire : définition par nom de classe (ou `className`), type de retour de `Provider::register()` (lu sur la closure du provider), type de retour d'une closure, objet ; sinon résolution protégée. `_ide_helper.php` (Facades, `@property-read` sur `Phalcon\Di\Injectable`) et `.phpstorm.meta.php` (`get()` / `getShared()` de `DiInterface` et `Di`). Fichiers valides (`php -l`) sur `tests/.fake`. La tâche CLI reste dans E6. |
| S8 · Traits et design patterns | Fait | **Correction** : `Singleton` partageait une seule instance entre toutes ses sous-classes. `InjectionAwareTrait` ne crée plus de propriétés dynamiques (dépréciées en PHP 8.2) et stocke le conteneur dans `$container`. `Strategy` typé (`uses(?string): object`, erreur explicite sans adaptateur par défaut). `CacheStrategy`, `DatabaseStrategy` et `RateLimiter` restent à porter (E7, E10, E8) ; `CacheStrategy` ne se charge de toute façon pas en Phalcon 5 (`Phalcon\Cache\BackendInterface` n'existe plus). |
| S9 · Config rapide | Fait | Ajoutée après les mesures (voir ci-dessous). `path()` cherche d'abord le chemin entier comme une seule clé (comme Phalcon), puis parcourt les niveaux (repli insensible à la casse) ; il renvoie la valeur par défaut quand le chemin traverse une valeur scalaire, là où Phalcon 5 lève une erreur (`Call to a member function has() on string`). Réécrire une clé avec une autre casse remplace l'ancienne orthographe : Phalcon garde toutes les orthographes dans son tableau interne (et dans `toArray()`, et `remove()` n'efface que la dernière), ce qui ferait lire une valeur périmée par l'accès direct. Tests : 7, dont des comparaisons avec `Phalcon\Config\Config`. |

Suites ajoutées à `tests/migrated-suites.txt` : `Design`, `Dotconst`, `Events`, `Facades`, `Foundation` (nouvelle : boot des cinq stub kernels, ordre des étapes, événements `kernel:*`, `run()`, providers, module, version), `Support`. 211 tests, verts sur Phalcon 5.22.1 et sur Phalcon 6 (exécution locale du job). Baseline PHPStan : 2 240 → 1 922 erreurs, plus aucune dans le périmètre. Les tests du périmètre n'utilisent plus `Test\TestCase\TestCase` (porté par E3).

Outillage : `phpstan/constants.php` déclare à PHPStan les constantes d'app (`BASE_PATH`, `APP_ENV`, `APP_DEBUG`, typées par `dynamicConstantNames`), `phpstan/TraitUsage.php` fait analyser les traits fournis aux apps.

### Décision S3 : `Phalcon\Container`

`Phalcon\Container\Container` n'implémente pas `Phalcon\Di\DiInterface`. Or `Mvc\Application`, `Cli\Console`, `Mvc\Micro`, `Di\Injectable` (controllers, tâches, listeners), les dispatchers et les vues attendent un `DiInterface`. C'est le conteneur de la pile ADR (`Phalcon\ADR`, `Phalcon\Auth`), pas un remplaçant du `Di` de la pile MVC. L'adopter imposerait un adaptateur `DiInterface`, donc une couche de plus sur chaque résolution. **On reste sur `Phalcon\Di\Di`** ; pas de mesure, l'incompatibilité tranche.

### Événements Phalcon 5 (S4)

| Changement | Événements |
|---|---|
| Supprimés | `collection:*`, `collectionManager:*` (ODM supprimé) ; `volt:*` (le compilateur Volt n'a plus d'events manager) ; `model:notSaved`, `model:notSave` (absents du binaire, non déclenchés quand `beforeSave` annule) |
| Ajoutés | `router:*` (6), `di:beforeServiceResolve`, `di:afterServiceResolve`, `db:connectionLost`, `dispatch:{beforeForward, afterBinding, beforeCallAction, afterCallAction}`, `micro:{afterBinding, beforeException}`, `model:{prepareSave, validation}`, `view:{beforeCompile, afterCompile}`, espace `kernel` |
| Inchangés | les autres (`application:*`, `console:*`, `dispatch:*`, `micro:*`, `db:*`, `loader:*`, `acl:*`, `view:*`, `modelsManager:afterInitialize`) |

### Dotconst : `const` contre `define()` (S5)

Fichier de 60 constantes (chemins `__DIR__`, entiers, références), inclusion seule, OPcache avec cache fichier, 400 processus alternés : `const` 55,1 µs, `define()` 56,3 µs (médianes). L'écart est faible mais pas défavorable : `const` est adopté. `@php/env` reste en `define()` (lu à l'exécution).

### Mesures (critère « pas moins bon que la 1.3 »)

La première mesure était faussée par deux biais, corrigés dans `bench/` :
- l'autoloader de développement du dépôt (dépendances de dev, 28 fichiers chargés au démarrage) : la 2.x est désormais mesurée avec `bench/.current`, une installation `--no-dev` comme celle d'une app ;
- le montage Docker de macOS, qui rend chaque inclusion de fichier 2 à 3 fois plus lente et masque tout le reste : les mesures se font sur une copie dans le conteneur, et la baseline 1.3 a été régénérée ainsi (avec l'app 1.3 figée dans `bench/.legacy/app`).

Boot et résolution d'un service (médianes, 200 itérations, deux passes à ±0,5 %) :

| Scénario | 1.3 | 2.0 (Composer simple) | 2.0 + classmap optimisée | 2.0 + classmap + preload |
|---|---|---|---|---|
| boot-http | 461 µs | 522 µs (+13 %) | 487 µs (+5,5 %) | 204 µs (−56 %) |
| boot-cli | 519 µs | 577 µs (+11 %) | 540 µs (+3,9 %) | 249 µs (−52 %) |
| boot-micro | 513 µs | 570 µs (+11 %) | 530 µs (+3,4 %) | 267 µs (−48 %) |
| service | 4,3 µs | 10,0 µs | 10,0 µs | 9,8 µs |
| mémoire | 432–500 Kio | +1,6 à +4,9 % | | +2,8 % |

La mémoire suit le surcoût de PHP 8.3 à vide (+6 %). Le preload a été mesuré sur les seuls espaces de noms déjà portés (le générateur s'arrête sur une erreur fatale de compilation dans un module non porté, ici `HttpClient`).

Origine de l'écart, mesurée sur Phalcon seul :

| Primitive | Phalcon 3.4 / PHP 7.3 | Phalcon 5.22 / PHP 8.3 |
|---|---|---|
| `new Config([...])` | 4,9 µs | 12,6 µs |
| Accès `$config->app->base_uri` | 0,10 µs | 0,78 à 1,8 µs |
| Première résolution d'un service (closure) | 0,69 µs | 1,12 µs |
| Events manager créé et un `fire()` | 0,8 µs | 6,2 µs |

`Phalcon\Config\Config` (une `Support\Collection` insensible à la casse) est jusqu'à 18 fois plus lent en lecture que celui de Phalcon 3 : c'est l'essentiel de l'écart sur `service` (le provider `Url` lit la config). D'où **S9**, `Neutrino\Config\Config` (PHP 8.3, OPcache, ns par opération) :

| Opération | `Phalcon\Config\Config` | `Neutrino\Config\Config` |
|---|---|---|
| `$config->app->base_uri` | 706 | 93 |
| `$config['app']['base_uri']` | 703 | 150 |
| `isset($config->app->x)` | 961 | 131 |
| `path()` sur 4 niveaux | 3 048 | 522 |
| construction (config de test) | 3 690 | 5 266 |

Mesure A/B alternée dans le même conteneur (300 itérations, même code à la classe de config près) :

| Scénario | Config Phalcon | Config Neutrino |
|---|---|---|
| service, Composer simple | 10,2 µs | 7,7 µs (−25 %) |
| boot-http, Composer simple | 621 µs | 640 µs (+3 % : un fichier de classe de plus à charger) |
| service, classmap + preload | 9,4 µs | 7,1 µs (−24 %) |
| boot-http, classmap + preload | 207 µs | 210 µs (+1,5 %, construction de la config) |

Le reste de l'écart sur `service` (≈ +3,5 µs contre la 1.3) vient de la résolution d'une closure par le `Di` de Phalcon 5 et du code du provider `Url` (E4).
