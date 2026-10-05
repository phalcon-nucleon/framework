# E4 — HTTP

**Statut** : Rédigé · **Dépend de** : E3 · **Bloque** : E8, E9, E12

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
