# E3 — Outils de test publics

**Statut** : Terminé · **Dépend de** : E2 · **Bloque** : E4, E5, E6, E7

## Objectif

Porter sur PHPUnit 11 et Phalcon 5 les classes de test que les apps utilisent (`Neutrino\Test\*`) et l'application de test du framework (`tests/.fake`), pour que les epics fonctionnels puissent écrire et faire passer leurs tests.

## Périmètre

- `src/Neutrino/Test/TestCase.php` (212 lignes), `FuncTestCase.php` (304), `RoutesTestCase.php` (152), `Helpers/RoutesTrait.php` (90)
- `tests/.fake/nucleon.app/` : stub kernels, controllers, tâches, middlewares, providers, `StubRouteTestCase`
- `tests/Test/TestCase/*` : classes de base des tests du framework (`TestCase`, `TraitTestCase`, `UseCaches`…)
- **Hors périmètre** : les tests des modules (chaque epic migre les siens), `dispatchCli()` côté comportement (E6).

## Ruptures

| 1.3 | 2.0 | Concerne |
|---|---|---|
| `PHPUnit_Framework_TestCase` | `PHPUnit\Framework\TestCase` | TestCase |
| `\PHPUnit_Framework_ExpectationFailedException` | `PHPUnit\Framework\ExpectationFailedException` | FuncTestCase (8 occurrences) |
| `setUp()` / `tearDown()` / `setUpBeforeClass()` | avec `: void` | toutes les classes de base |
| `assertContains($string, $string)` | `assertStringContainsString()` | `assertResponseCode`, `assertResponseContentContains` |
| `@dataProvider` sur méthode d'instance | `#[DataProvider]` sur méthode **statique** | RoutesTestCase (`routesProvider`, `getApplicationRoutes`) |
| `Phalcon\Di`, `Phalcon\DiInterface`, `Phalcon\Config`, `Phalcon\Application` | `Phalcon\Di\Di`, `Phalcon\Di\DiInterface`, `Phalcon\Config\Config`, `Phalcon\Application\AbstractApplication` | TestCase, RoutesTestCase, RoutesTrait |
| `$app->handle()` sans argument (CLI), `handle($url)` (HTTP) | `handle(string $uri)` (HTTP), `handle(?array $arguments)` (CLI) | `dispatch()`, `dispatchCli()` |
| `Response::getHeaders()->get('Status')` | `Response::getStatusCode(): ?int` | `assertResponseCode` |

## Décisions

- **Détection de Phalcon.** `TestCase::checkExtension('phalcon')` passe tous les tests en « skipped » quand l'extension n'est pas chargée. Avec Phalcon 6 (PHP pur, sans extension), le job CI Phalcon 6 serait vert sans avoir rien exécuté. On teste désormais la présence de Phalcon par `class_exists(\Phalcon\Support\Version::class)`, et l'absence de Phalcon **échoue** au lieu d'être ignorée. `checkExtension()` reste disponible pour les autres extensions.
- **Data providers statiques** (imposé par PHPUnit 11). `RoutesTestCase::routes()` devient `protected static function routes(): array`, et `formatDataRoute()` devient statique. C'est un changement cassant pour les apps, documenté avec un exemple avant/après.
- **Kernel démarré à chaque test** : on garde ce comportement (isolation forte), et on mesure son coût. Si démarrer le kernel à chaque test rend la suite trop lente, une option de réutilisation par classe sera étudiée plus tard. Hors périmètre de la 2.0.
- **Simulation des requêtes** (`dispatch()`) : on corrige les incohérences actuelles. Aujourd'hui, les paramètres d'une requête PATCH sont mis dans `$_GET`, et ceux d'une requête DELETE sont ignorés. Les paramètres vont désormais dans `$_GET` pour GET, HEAD et DELETE, et dans `$_POST` pour POST, PUT et PATCH. On ajoute les en-têtes (`$_SERVER['HTTP_*']`) et une option de corps JSON.
- Les assertions personnalisées passent par `PHPUnit\Framework\Assert` (messages d'échec), sans lever d'exception à la main avant un `assertEquals` redondant.

## Stories

### E3-S1 · `Neutrino\Test\TestCase`
- PHPUnit 11 : classe parente, `: void`, signature de `setDI(DiInterface $di)`.
- Détection de Phalcon décrite plus haut.
- `kernelClassInstance()` : `protected static function kernelClassInstance(): string` (class-string d'un `Kernelable`).
- `tearDown()` : `Mockery::close()` seulement si Mockery est installé (comme pour les Facades dans E2), `Facade::clearResolvedInstances()`, `terminate()`, `$di->reset()`.
- `getConfig()` / `setConfig()` typés, avec `Phalcon\Config\Config`.

### E3-S2 · `FuncTestCase`
- Assertions typées : `assertController(string)`, `assertAction(string)`, `assertHeader(array)`, `assertResponseCode(int)`, `assertDispatchIsForwarded()`, `assertRedirectTo(string)`, `assertResponseContentContains(string)`.
- `assertResponseCode()` s'appuie sur `Response::getStatusCode()`.
- `dispatch(string $url, string $method = 'GET', array $params = [], array $headers = [], ?array $json = null): string` renvoie la sortie (remplace le paramètre `&$output`).
- Restauration de `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, `$_REQUEST` et `$_FILES` après chaque appel.
- `mockService(string $service, string|object $class, bool $shared)` typé.
- `dispatchCli(string $cli)` : adapté à `handle(?array)`. Son comportement complet est validé dans E6.

### E3-S3 · `RoutesTestCase` et `RoutesTrait`
- Providers statiques avec attributs `#[DataProvider]`. `getApplicationRoutes()` statique, construit sur `Phalcon\Di\Di`.
- `assertRoute()` typé. On garde la vérification « toutes les routes de l'app sont testées » (`testRoutesTested`).
- `RoutesTrait::assertRoute()` passe l'URI complète à `Router::handle(string)`.

### E3-S4 · Application de test `tests/.fake`
- Stub kernels (HTTP, HTTP vide, CLI, CLI vide, Micro) avec les propriétés typées exigées par E2.
- Controllers, tâches, middlewares, listeners et providers stubs typés.
- `StubRouteTestCase` adapté aux providers statiques. Il sert de test de non-régression de `RoutesTestCase`.

### E3-S5 · Classes de base des tests du framework
- `Test\TestCase\TestCase` : le répertoire temporaire `tests/.data/` est remplacé par un répertoire unique par processus (`sys_get_temp_dir()/nucleon-tests-<pid>`). Les suites peuvent ainsi tourner en parallèle et un test interrompu ne laisse pas de fichiers derrière lui.
- `TraitTestCase`, `UseCaches`, `TestListenable`, `TestListenize` : PHPUnit 11 et types. La config de cache mémoire de `TraitTestCase` est adaptée au format de cache d'E7. En attendant E7, on utilise un adapter mémoire minimal.

### E3-S6 · Tests de ces outils
- Tests dédiés pour `FuncTestCase::dispatch()` (paramètres par méthode, en-têtes, JSON), chaque assertion (succès et échec), `RoutesTestCase` (via `StubRouteTestCase`) et la détection de Phalcon.

## Changements cassants pour les apps

- PHPUnit 11 obligatoire : `setUp(): void`, etc.
- `RoutesTestCase::routes()` et `formatDataRoute()` deviennent statiques.
- `FuncTestCase::dispatch()` renvoie la sortie au lieu de la remplir par référence. Les paramètres PATCH passent de `$_GET` à `$_POST`.
- `assertResponseCode()` attend un `int`.

## Critères d'acceptation spécifiques

- Le job Phalcon 6 exécute réellement les tests (aucun « skipped » dû à la détection de Phalcon).
- `StubRouteTestCase` passe.
- Les suites `TestCase` et les tests des outils de test sont activés en CI.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · `Neutrino\Test\TestCase` | Fait | PHPUnit 11, typé. `$app` est un `Kernelable&InjectionAwareInterface`. Détection de Phalcon par `class_exists(Phalcon\Support\Version::class)`, échec et non « skipped » ; en pratique, sans Phalcon la classe ne se charge même pas (elle implémente des interfaces Phalcon), ce qui fait aussi échouer le test. `checkExtension()` corrigé (le message de saut était toujours vide). `tearDown()` : `Mockery::close()` seulement si Mockery est chargé, `Di::reset()`. La config par défaut est un `Neutrino\Config\Config`. |
| S2 · `FuncTestCase` | Fait | `dispatch(string $url, string $method = 'GET', array $params = [], array $headers = [], ?array $json = null): string`. Paramètres dans `$_GET` (GET, HEAD, DELETE) ou `$_POST` (POST, PUT, PATCH), query string de l'URL dans `$_GET`, en-têtes dans `$_SERVER['HTTP_*']` (`CONTENT_TYPE`, `CONTENT_LENGTH` sans préfixe), corps JSON injecté dans le service `request` (`Request::$rawBody`, lu par `getJsonRawBody()`). Les superglobales sont restaurées, le tampon de sortie aussi en cas d'exception. Accepte un kernel HTTP ou Micro. La réponse remplace le service `response` en le retirant d'abord du conteneur (cache des instances partagées, voir E2-S6). Assertions par `Assert` avec message, `assertResponseCode(int)` sur `getStatusCode()`, `assertController()` accepte aussi un dispatcher CLI (nom de tâche). `mockService()` : `$shared` vaut `true` par défaut. `dispatchCli()` exige un kernel CLI ; son comportement relève d'E6. |
| S3 · `RoutesTestCase` et `RoutesTrait` | Fait | `routes()`, `formatDataRoute()`, `routesProvider()` et `getApplicationRoutes()` statiques ; attributs `#[DataProvider]` et `#[Depends]`. Clés des jeux de données stables (index au lieu d'un suffixe aléatoire). `getApplicationRoutes()` remet le conteneur par défaut à zéro après lecture. `assertRoute()` restaure `REQUEST_METHOD` et compare les paramètres de route d'un seul `assertEquals`. |
| S4 · Application `tests/.fake` | Fait | Kernels typés dès E2. `StubController` : actions typées, `dataAction()` renvoie méthode, `$_GET`, `$_POST`, en-têtes et JSON. `StubRouteTestCase` statique, il enregistre les appels dans `$assertedRoutes`. Les classes qui étendent des classes encore non portées (`Neutrino\Http\Controller`, `Cli\Task`, `Cli\Output\Writer`, `StubCache` sur l'ancien cache) restent à typer avec leurs epics (E4, E6, E7). |
| S5 · Classes de base du framework | Fait | Répertoire temporaire par processus (`sys_get_temp_dir()/nucleon-tests-<pid>/`), vidé après chaque test. `Test\TestCase\TestCase` appelle la config de `TraitTestCase` par un alias (sa propre méthode `setUpBeforeClass()` masquait celle du trait). La config de cache de `TraitTestCase` et `UseCaches` reste au format 1.3 : les stores ne sont qu'enregistrés, jamais construits ; E7 la remplace. |
| S6 · Tests des outils | Fait | Suite `Assert` : `FuncTestCaseTest` (paramètres par méthode, query string, en-têtes, JSON, restauration des superglobales, sortie, exception et tampon, `mockService`, chaque assertion en succès et en échec, `checkExtension`, détection de Phalcon dans un processus sans extension), `RoutesTraitTest`, `RoutesTestCaseTest`, et `AppRoutesTest`, un vrai `RoutesTestCase` sur `routes/http.php` de l'app de test (toutes les routes testées, `testRoutesTested` passe sans test incomplet). |

Suite ajoutée à `tests/migrated-suites.txt` : `Assert` (58 tests). 269 tests au total, verts sur Phalcon 5.22.1 et Phalcon 6 (aucun test ignoré). Baseline PHPStan : 1 922 → 1 795 erreurs, aucune dans le périmètre.

Coût du kernel démarré à chaque test : la suite `Assert` (58 tests, un boot du kernel HTTP et souvent une requête par test) tourne en environ 35 ms, soit moins d'une milliseconde par test. Pas de réutilisation par classe à prévoir.

Constat pour E4 : dans Phalcon, `:controller` et `:action` ne sont associés au contrôleur et à l'action que si les `paths` les déclarent (`'controller' => 1`). La route `/back/:controller/:action` de l'app de test ne le fait pas, que ce soit en 1.3 ou en 2.0.
