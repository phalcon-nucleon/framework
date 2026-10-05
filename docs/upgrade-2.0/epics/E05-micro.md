# E5 — Micro

**Statut** : Terminé · **Dépend de** : E3 (et E4-S4 pour les middlewares de controller) · **Bloque** : —

## Objectif

Porter le kernel Micro et son router sur Phalcon 5, et simplifier `Micro\Router`, qui imite aujourd'hui toute l'API du router MVC en levant des exceptions sur la moitié des méthodes.

## Périmètre

- `Foundation/Micro/Kernel.php` (112 lignes)
- `Micro/Router.php` (452 lignes), `Micro/RouterInterface.php` (240 lignes), `Micro/Middleware.php`
- `Providers/Micro/Router.php`, `Support/Facades/Micro/Router.php`
- `tests/.fake/nucleon.app/app/Kernels/Micro/*`, `tests/Test/Micro`
- **Hors périmètre** : la sortie d'erreur JSON (`Error\Writer\Json`) → E12.

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0)

| 1.3 | 5.22 | Concerne |
|---|---|---|
| `Micro::handle($uri = null)` | `handle(string $uri)` | Kernel Micro (`handleIncoming`) |
| `Micro::before/after/finish($handler)` | typés, renvoient `static` | `registerMiddleware()` |
| `Micro::get/post/…(string $routePattern, $handler)` | renvoient `RouteInterface` | `Micro\Router::add*()` |
| `Micro::mount(CollectionInterface)` | typé `Phalcon\Mvc\Micro\CollectionInterface` | `Micro\Router::mount()` |
| `MiddlewareInterface::call(Micro $application)` | inchangé | `Micro\Middleware` |

## Décisions

- **`Micro\Router` allégé.**
  - Il n'implémente plus une copie de l'interface du router MVC. Les 9 méthodes `@deprecated` qui lèvent une exception sont supprimées : `setDefaultModule`, `setDefaultController`, `setDefaultAction`, `setDefaults`, `addPurge`, `addTrace`, `addConnect`, `clear`, `getModuleName`.
  - `Micro\RouterInterface` est réduit aux méthodes réellement prises en charge : `add`, `addGet`, `addPost`, `addPut`, `addPatch`, `addDelete`, `addOptions`, `addHead`, `notFound`, `mount`, `getRoutes`, `getRouteByName`, `wasMatched`, `getMatchedRoute`, `getParams`, plus les accès au controller et à l'action.
- **Handlers `Controller::action`** : on garde la syntaxe de route vers un controller avec middlewares de controller. **À trancher** pendant S2 : s'appuyer sur les handlers chargés à la demande de Phalcon (`Micro\Collection::setLazy(true)`) plutôt que sur notre closure maison, si les mesures montrent un gain et si les middlewares de controller restent pris en charge.
- **Position des middlewares Micro : enum.** `Micro\Middleware::bindOn()` renvoie aujourd'hui une chaîne (`'before'`, `'after'`, `'finish'`). C'est un ensemble fermé de valeurs internes, jamais comparé à une chaîne venue de l'extérieur. Il devient l'enum `Neutrino\Micro\MiddlewarePosition` (`Before`, `After`, `Finish`), conformément à `CONVENTIONS.md`. Un `bindOn()` invalide devient impossible au lieu de lever une `RuntimeException`.
- Le kernel Micro garde `$eventsManagerClass = null` par défaut (pas de gestionnaire d'événements, pour la performance). Les événements de controller (`micro:beforeExecuteRoute` / `afterExecuteRoute`) que `Micro\Router` déclenche lui-même restent pris en charge.

## Stories

### E5-S1 · Kernel Micro
- Propriétés typées. `handleIncoming()` appelle `handle($uri)` avec l'URI lue comme pour le kernel HTTP (même code, partagé).
- `registerModules()` reste un no-op final, avec la signature de `Kernelable`.
- `registerMiddlewares()` utilise `MiddlewarePosition` (`match`).
- Tests : boot, requête sur `get.test.abc`, route introuvable, middlewares before/after/finish.

### E5-S2 · `Micro\Router`
- Interface réduite, méthodes typées, suppression des méthodes qui lèvent une exception.
- Handlers `Controller::action` et `[Controller::class, 'action']` avec middlewares de controller, en réutilisant l'infrastructure d'E4-S4.
- Étude du chargement à la demande via `Micro\Collection` (décision à trancher ci-dessus), avec mesures.
- Tests existants de `tests/Test/Micro` migrés, plus un test du message d'erreur quand l'action n'existe pas.

### E5-S3 · Middlewares Micro
- `Micro\Middleware` : `bindOn(): MiddlewarePosition`, `call(Micro $application): bool`.
- Tests : arrêt du traitement quand `call()` renvoie `false` en position Before.

### E5-S4 · Provider et Facade
- `Providers\Micro\Router` (`SimpleProvider`, partagé) et `Support\Facades\Micro\Router` typés et couverts par les IDE helpers d'E2.

## Changements cassants pour les apps

- `Micro\Router` ne propose plus les méthodes qui levaient une exception (voir la liste plus haut).
- `Micro\Middleware::bindOn()` renvoie `MiddlewarePosition` au lieu d'une chaîne. Les constantes `ON_BEFORE`, `ON_AFTER` et `ON_FINISH` sont supprimées.

## Critères d'acceptation spécifiques

- La suite `Micro` est activée et passe, y compris sur le job Phalcon 6.
- Mesures : la requête Micro n'est pas moins bonne que celle de la 1.3.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Kernel Micro | Fait | Propriétés typées (E2), `handleIncoming()` partagé avec le kernel HTTP (`Kernelize::incomingUri()`), `registerModules()` no-op final. `registerMiddlewares()` par `match` sur `MiddlewarePosition`. Tests : requête, route introuvable (exception Micro faute de `notFound()`), middlewares Before / After / Finish. |
| S2 · `Micro\Router` | Fait | Interface réduite aux méthodes prises en charge, méthodes typées, plus d'exceptions. `add()` passe par `Micro::map()` + `via()` et renvoie la route (elle renvoyait `null`). Handlers : closure, `'Controller::action'`, `[Controller::class, 'action']`, `['controller' => …, 'action' => …, 'middlewares' => […]]` ; les middlewares de controller acceptent `Classe`, `Classe => [paramètres]` et `Classe => paramètre`, avec la même règle que les middlewares de route HTTP (clé entière : la valeur est la classe). Le controller n'est construit qu'une fois par requête. Erreurs explicites : action inexistante, handler invalide, middleware qui n'étend pas `Foundation\Middleware\Controller`. `getRouteByName()` renvoie `null` au lieu de `false`. |
| S3 · Middlewares Micro | Fait | Enum `Neutrino\Micro\MiddlewarePosition`, `bindOn(): MiddlewarePosition`. **Constat** : Phalcon 5 ignore la valeur renvoyée par un objet middleware ; seul `Micro::stop()` arrête la requête. Le kernel enveloppe donc les middlewares Before : `call()` qui renvoie `false` appelle `stop()` (testé : ni le handler, ni After, ni Finish ne s'exécutent). |
| S4 · Provider et Facade | Fait | Provider `SimpleProvider` (classe vue par les IDE helpers, testé), docblock de la Facade réécrit sur l'API réelle (il décrivait le router MVC). |

Suite `Micro` activée (28 tests), verte sur Phalcon 5.22 et 6. 344 tests au total. Baseline PHPStan : 1 735 → 1 700.

### Décision S2 : handlers chargés à la demande (`Micro\Collection::setLazy`)

Mesure (20 routes vers un controller, enregistrement et première requête, à froid, classes Nucleon déjà chargées comme avec le preload) : closure Nucleon 261 µs, Collection paresseuse 240 µs, soit environ 1 µs par route déclarée. La Collection ne prend pas en charge les middlewares de controller. **On garde la closure** ; une app peut toujours monter une `Collection` (`Router::mount()`, testé) pour les routes sans middleware.

### Comportements de Phalcon 5 à connaître

- Micro lie les closures de route à l'application (`$this` est le kernel).
- Les paramètres de route sont passés en **arguments nommés** : une closure `fn () => …` sur `/users/{id}` échoue (« Unknown named parameter $id ») ; elle doit déclarer `$id` (ou `...$args`).

### Mesures (`bench/compare.sh`, 100 itérations)

| Scénario | Autoloader simple | Tel que déployé (`optimize`) |
|---|---|---|
| micro (requête) | +11,0 % temps, +1,8 % mémoire | **+4,9 %** temps (+9 µs), **−35 %** mémoire |
| boot-micro | +7,5 %, +1,8 % | **−9,9 %**, **−38 %** |
