# E4 — HTTP

**Statut** : Terminé · **Dépend de** : E3 · **Bloque** : E8, E9, E12

## Objectif

Servir une requête HTTP complète sur Phalcon 5 : kernel, routage (y compris le cache des routes), dispatch, controllers et middlewares, avec des performances au moins égales à la 1.3.

## Périmètre

- `Foundation/Http/Kernel.php` : `handle`, `registerRoutes`, `boot`
- `Providers/Http/Router.php`, `Providers/Http/Dispatcher.php`, `Providers/Url.php`, `Providers/Cookies.php`
- `Foundation/Middleware/Application.php`, `Controller.php`, `Disptacher.php`
- `Interfaces/Middleware/*`
- `Http/Controller.php` : middlewares déclarés sur les routes
- `Http/Middleware/Ajax.php`
- `Http/Standards/StatusCode.php` (808 lignes), `Method.php`
- `Foundation/Cli/Tasks/RouteCacheTask.php` : la logique de compilation. La tâche CLI elle-même est portée dans E6.
- Facades `Router`, `Request`, `Response`, `Url`
- Tests : `tests/Test/{Http,Middleware}`, ainsi que les tests de routage et du cache des routes.
- **Hors périmètre** :
  - `Http/Middleware/Csrf.php`, `ThrottleRequest.php` et `Middleware/Throttle.php` → **E8**. Ils dépendent de Security, de la session et de `RateLimiter`, eux-mêmes portés dans E7 et E8.
  - `Foundation/Middleware/Debug.php` → **E12**.
  - Vues → E9.

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0)

| 1.3 | 5.22 | Concerne |
|---|---|---|
| `Mvc\Application::handle()` sans argument, URI lue par le router | `handle(string $uri): ResponseInterface\|bool` | Kernel HTTP (`handleIncoming`) |
| `Router::handle($uri = null)` | `handle(string $uri): void` | RoutesTrait, HelperTask |
| Propriétés internes du router `_defaultModule`, `_defaultNamespace`, `_defaultController`, `_defaultAction`, `_defaultParams`, `_removeExtraSlashes`, `_notFoundPaths` | sans le préfixe `_` et typées. Les valeurs par défaut sont lisibles via `getDefaults(): array`. | `RouteCacheTask::compile()` |
| `useImplicitView($bool)` | `useImplicitView(bool): static` | Kernel HTTP |
| `Response::setStatusCode($code, $message = null)` | `setStatusCode(int $code, ?string $message = null)` | middlewares |
| `Dispatcher::getHandlerClass()`, `wasForwarded()`, `isFinished()` | même nom, typés (`AbstractDispatcher`) | `Foundation\Middleware\Controller` |
| `Phalcon\Dispatcher` (type des paramètres) | `Phalcon\Dispatcher\AbstractDispatcher` | middlewares |
| `Url::setBaseUri`, `setStaticBaseUri` | inchangés, typés `string` | provider Url |

## Décisions

- **URI passée explicitement.** `Kernel::handleIncoming()` (interface posée dans E2) lit `$_SERVER['REQUEST_URI']`, retire la chaîne de requête et appelle `handle($uri)`. `Router::setUriSource()` n'est plus nécessaire et disparaît du provider.
- **Cache des routes plus sûr.**
  - Les valeurs par défaut sont lues via `getDefaults()`. On n'utilise plus la réflexion que pour `removeExtraSlashes` et `notFoundPaths`, qui n'ont pas de getter.
  - Toutes les valeurs sont échappées par `var_export`. Aujourd'hui, le nom et le hostname d'une route sont concaténés sans échappement.
  - `route:cache` **échoue** si une route ne peut pas être mise en cache : handler en closure, convertisseurs (`convert()`), `beforeMatch()` ou `match()`. Aujourd'hui, ces éléments sont perdus sans avertissement.
  - Le fichier est écrit de façon atomique (fichier temporaire puis `rename`).
- **Middlewares** : le modèle de la 1.3 est conservé (middlewares globaux attachés aux événements `application:*` et `dispatch:*`, middlewares de controller filtrés par `only()` / `except()`, middlewares déclarés dans les `paths` d'une route).
  - La classe mal orthographiée `Foundation\Middleware\Disptacher` est renommée `Dispatcher`, sans alias (version majeure).
  - Les méthodes des interfaces de middleware sont typées : `before(Event $event, object $source, mixed $data = null): bool`, etc.
- **`Http\Standards`** :
  - `StatusCode` et `Method` restent des **constantes typées**, car elles sont passées à des API Phalcon qui attendent un `int` ou une `string` (voir `CONVENTIONS.md`).
  - `StatusCode::message(int): ?string` est conservé.
  - Le fichier passe de 808 lignes à environ 150 : un tableau de libellés, et plus d'en-tête de licence copié de l'incubator Phalcon.
- `Ajax` s'appuie sur `Request::isAjax()` plutôt que sur `$_SERVER` directement, ce qui le rend testable via `FuncTestCase::dispatch()` avec en-têtes.

## Stories

### E4-S1 · Kernel HTTP
- `handleIncoming()`, `registerRoutes()` (fichier compilé ou `routes/http.php`), `boot()` avec `useImplicitView((bool) $config->view->implicit ?? false)`.
- Tests : requête complète sur `tests/.fake` (route → controller → réponse), route introuvable, réponse déjà envoyée.

### E4-S2 · Providers Router, Dispatcher, Url, Cookies
- `register()` typés (exploités par les IDE helpers d'E2) : `register(): \Phalcon\Mvc\Router`, etc.
- Router sans routes par défaut (`new Router(false)`), sans `setUriSource`.
- Le Dispatcher reçoit le gestionnaire d'événements partagé.
- Url : `app.base_uri` et `app.static_base_uri` (repli sur `base_uri`).
- Facades `Router`, `Request`, `Response` et `Url` vérifiées avec les IDE helpers.

### E4-S3 · Cache des routes
- Réécriture de `compile()` selon les décisions plus haut. La tâche CLI appelle cette logique (portée dans E6).
- Tests aller-retour : charger `routes/http.php`, compiler, recharger le fichier compilé. On compare chaque route (méthodes HTTP, hostname, nom, pattern, pattern compilé, paths) et les valeurs par défaut du router.
- Tests d'échec : closure, convertisseur, `beforeMatch`.
- Mesure : requête HTTP avec et sans cache des routes, comparée à la 1.3.

### E4-S4 · Infrastructure des middlewares
- `Foundation\Middleware\Application`, `Controller` et `Dispatcher` (renommé), plus les interfaces `Init`, `Before`, `After` et `Finish`, typés.
- Vérification des noms d'événements utilisés (`application:boot`, `beforeHandleRequest`, `afterHandleRequest`, `beforeSendResponse`, `dispatch:beforeDispatchLoop`, `beforeDispatch`, `beforeExecuteRoute`, `afterExecuteRoute`, `afterDispatch`, `afterDispatchLoop`) avec Phalcon 5, en cohérence avec E2-S4.
- Un middleware qui renvoie `false` arrête bien le dispatch sur Phalcon 5 (comportement des événements annulables).
- Tests existants de `tests/Test/Middleware` (hors Throttle) migrés.

### E4-S5 · Controller et middlewares de route
- `Http\Controller::routeMiddleware()` et `middleware()` typés. Formats acceptés dans `paths['middleware']` : une classe, une liste de classes, `[Classe => paramètres]`.
- Mesure : coût d'un controller avec 0, 1 et 3 middlewares de route. Les middlewares sont instanciés et attachés à chaque requête : on vérifie que c'est sans régression par rapport à la 1.3.
- Middleware `Ajax`.

### E4-S6 · `Http\Standards`
- `StatusCode` (constantes `int` typées, tableau de libellés, `message()`) et `Method` (constantes `string` typées).
- Comparaison avec les libellés par défaut de `Phalcon\Http\Response` pour éviter les divergences.

## Changements cassants pour les apps

- `Foundation\Middleware\Disptacher` → `Foundation\Middleware\Dispatcher`.
- Signatures typées des méthodes `init`, `before`, `after` et `finish` des middlewares.
- `route:cache` refuse les routes avec closure, convertisseur ou `beforeMatch` (elles étaient perdues sans avertissement en 1.3).
- Les apps qui surchargent `handle()` dans leur kernel HTTP passent par `handleIncoming()` (voir E2).

## Critères d'acceptation spécifiques

- Les suites `Http` et `Middleware` (hors Throttle) sont activées et passent, y compris sur le job Phalcon 6.
- Mesures : la requête HTTP complète, avec et sans cache des routes, n'est pas moins bonne que celle de la 1.3.
- Le test aller-retour du cache des routes passe sur toutes les routes de `tests/.fake`.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Kernel HTTP | Fait | `handleIncoming()` (posé dans E2), `registerRoutes()` sur `RouteCompiler::COMPILED_FILE` ou `routes/http.php`, `boot()` avec `view.implicit`. Tests : requête complète, route introuvable (exception du dispatcher, faute de `notFound()`), `Bootstrap::run()` avec query string, réponse déjà envoyée (pas d'envoi en double), fichier compilé ou fichier de routes. Le test du cycle de vie des listeners (retiré d'E2 faute de pile HTTP) est rétabli. |
| S2 · Providers | Fait | `register()` typés (`Router`, `Dispatcher`, `Url`), lus par les IDE helpers (testé). Router sans routes par défaut ni `setUriSource()`. Url : repli sur `/` sans `app.base_uri`, et `static_base_uri` sur `base_uri`. |
| S3 · Cache des routes | Fait | `Foundation\Http\RouteCompiler` (`compile()`, `write()` atomique, `clear()`), appelé par `RouteCacheTask` (tâche portée dans E6). Défauts via `getDefaults()`, `removeExtraSlashes` et `notFound` par réflexion, hostname du groupe repris. Échec explicite (`UncacheableRouteException`) pour un convertisseur, un `beforeMatch` (de la route ou du groupe : Phalcon le copie sur la route), un `match()` ou un objet dans les paths. Tests aller-retour sur toutes les routes de `tests/.fake` et sur un router complet (défauts, `removeExtraSlashes`, `notFound`, nom avec apostrophe, hostname, groupe, placeholders) ; fichier compilé valide (`php -l`) et rien d'écrit en cas d'échec. Voir « `dumpDispatcher()` » ci-dessous. |
| S4 · Infrastructure des middlewares | Fait | `Application`, `Controller`, `Dispatcher` (renommé, sans alias) et interfaces typés. Noms d'événements vérifiés dans E2-S4. Un middleware qui renvoie `false` sur `dispatch:beforeDispatch` arrête bien la requête (testé). Voir « Hooks des apps » pour les signatures. |
| S5 · Controller et middlewares de route | Fait | `routeMiddleware()` et `middleware()` typés ; formats `Classe`, `[Classe, …]`, `[Classe => paramètres]` et `[Classe => paramètre]` testés. **Correction** : `[Classe => paramètre]` (paramètre seul) n'était pas accepté (le test portait sur le tableau des middlewares au lieu du paramètre). Une classe qui n'est pas un middleware de controller lève une exception explicite. `Ajax` lit l'en-tête par `Request::getHeader()` (testable avec `dispatch()`), sans casse : `Request::isAjax()` de Phalcon 5 compare `XMLHttpRequest` strictement, ce qui aurait rejeté les clients acceptés en 1.3. `only()` / `except()` s'additionnent comme en 1.3 (`[]` remet à zéro). |
| S6 · `Http\Standards` | Fait | `StatusCode` : 176 lignes au lieu de 808, constantes `int`, tableau `MESSAGES`, `message(int): ?string` (`null` au lieu de `''` pour un code inconnu). Ajout de 102, 103, 421, 425, 451 et des noms corrects `UNAUTHORIZED`, `UPGRADE_REQUIRED`, `BANDWIDTH_LIMIT_EXCEEDED` (les anciens restent, dépréciés). Comparaison avec Phalcon : sept libellés diffèrent, tous obsolètes côté Phalcon (« Request Time-out », 425 « Unordered Collection »…) ; le test fige cette liste. `Method` : constantes `string`. |

Suites activées : `Http` (32 tests, hors `CsrfTest` → E8) et `Middleware` (14 tests, hors Debug → E12 et Throttle → E8), vertes sur Phalcon 5.22 et 6. 315 tests au total. Baseline PHPStan : 1 795 → 1 735, rien dans le périmètre.

### Hooks des apps

Les interfaces de middleware typent les paramètres (`Event $event, object $source, mixed $data = null`) mais **pas le retour** : une app peut ne rien renvoyer (seul `false` a un effet), et ses middlewares de la 1.3 restent compatibles sans modification (paramètres non typés = plus larges). Même principe pour `Http\Controller::onConstruct()`. Règle ajoutée à `CONVENTIONS.md`.

### `Router::dumpDispatcher()` (Phalcon 5.22)

Phalcon 5.22 sait sérialiser le router (`buildDispatcherDump()`, `dumpDispatcher()`, `loadDispatcherFromArray()`), y compris les index, et refuse les closures. Évalué et écarté : le rechargement est plus lent que de rejouer les `add()` (205 µs contre 152 µs pour 50 routes, première requête comprise) et il perd les défauts du router, `removeExtraSlashes()` et `notFound()`. Disponible aussi dans Phalcon 6.

### Mesures

Mesurées avec `bench/compare.sh` (1.3 et 2.x alternés, 120 itérations, médianes).

Telles que déployées (`optimize` de chaque version) :

| Scénario | 1.3 | 2.0 | Temps | Mémoire |
|---|---|---|---|---|
| boot-http | 162 µs | 145 µs | −11 % | −38 % |
| http (requête complète) | 236 µs | 281 µs | +19 % | −34 % |
| http-mw1 (1 middleware de route) | 272 µs | 310 µs | +14 % | −32 % |
| http-mw3 (3 middlewares de route) | 276 µs | 316 µs | +15 % | −32 % |
| service | 4,4 µs | 7,2 µs | +2,8 µs | −38 % |

Autoloaders Composer simples, sans cache : boot +14 %, requête +20 %, middlewares de route +20 %, service +3 µs.

Le surcoût de la requête vient de Phalcon 5, mesuré sans Nucleon (une requête MVC à froid, Phalcon 3.4 contre 5.22) : `Router::handle()` +20 µs (index des routes construit à la première requête), `Dispatcher::dispatch()` +50 µs, `Response::send()` +6 µs, soit +92 µs sur `handle()` de bout en bout. Avec Nucleon, l'écart sur `handle()` est de +71 µs : Nucleon n'ajoute rien, et gagne au boot. Le coût d'un middleware de route est le même qu'en 1.3 (+30 à +40 µs pour le premier).

**À trancher** : le critère « pas moins bon que la 1.3 » n'est pas atteint sur la requête complète (+19 % en production), à cause de Phalcon 5. Le boot et la mémoire sont meilleurs.
