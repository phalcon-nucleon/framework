# E8 — Auth & sécurité

**Statut** : Terminé · **Dépend de** : E4, E7 · **Bloque** : —

## Objectif

Fournir l'authentification, la protection CSRF et la limitation de débit sur Phalcon 5, en s'appuyant sur le composant natif `Phalcon\Auth` quand il couvre le besoin, et corriger au passage les faiblesses de sécurité de l'implémentation 1.3.

## Périmètre

- `Auth/Manager.php` (305 lignes), `Auth/Authenticable.php`, `Interfaces/Auth/*`, `Foundation/Auth/User.php`, `Providers/Auth.php`, `Support/Facades/Auth.php`
- `Auth/Middleware/Authenticate.php`, `Auth/Middleware/ThrottleLogin.php`
- `Http/Middleware/Csrf.php`, `Http/Middleware/ThrottleRequest.php`, `Middleware/Throttle.php` (déplacés depuis E4)
- `Security/RateLimiter.php` (déplacé depuis E7)
- Tests : `tests/Test/Auth`, `tests/Test/Security`, `tests/Test/Middleware/ThrottleTest.php`, ainsi que les tests Csrf
- **Hors périmètre** : ACL (`Phalcon\Acl`). Elle reste utilisable telle quelle, et l'accès `acl` de `Phalcon\Auth` est documenté mais non encapsulé.

## État des lieux

### Ce que Phalcon 5 apporte

Depuis la 5.14, Phalcon a un composant `Phalcon\Auth`, également présent dans Phalcon 6. Il couvre l'essentiel de notre `Auth\Manager` :

- **Guards** : `session` (avec remember-me et HTTP Basic) et `token` (API, `Authorization: Bearer`). Plusieurs guards peuvent coexister, avec un guard par défaut.
- **Adapters** : `Model` (ORM), `Memory`, `Stream`.
- **Contrats du modèle utilisateur** : `Phalcon\Contracts\Auth\AuthUser` (`getAuthIdentifier()`, `getAuthPassword()`) et `AuthRemember` pour le remember-me.
- **Contrôle d'accès** : accès `auth`, `guest` et `acl`, restreints par `only()` / `except()`. Ils sont appliqués par des listeners fournis pour MVC (`Auth\Mvc\AuthDispatcherListener`), Micro et CLI.
- **Événements** : `auth:beforeLogin`, `afterLogin`, `beforeLogout`, `afterLogout`.
- **Construction** : `Auth\ManagerFactory::load()` à partir d'une config `guards` + `access`.

En revanche, **il ne fait pas de limitation de débit**. `RateLimiter` et les middlewares Throttle restent donc chez nous.

### Faiblesses de la 1.3 à corriger

| Problème | Où |
|---|---|
| Le jeton remember-me est stocké **en clair** en base et comparé avec `===` (comparaison sensible au temps d'exécution). | `Auth\Manager::login()`, `retrieveUserByToken()` |
| `logout()` ne révoque pas le jeton remember-me en base : un cookie volé reste valide après déconnexion. | `Auth\Manager::logout()` |
| Le cookie remember-me a une durée de vie de 100 ans. | `Auth\Manager::login()` |
| `explode('|', $recaller)` sans contrôle : un cookie malformé produit un warning. | `Auth\Manager::user()` |
| `login()` exige `Foundation\Auth\User` au lieu de l'interface. | `Auth\Manager::login()` |
| La signature de limitation de débit est un `crc32` (32 bits, collisions possibles entre clients et routes). | `Middleware\Throttle::resolveRequestSignature()` |
| La fenêtre de limitation glisse : chaque `hit()` réécrit la durée de vie du compteur, donc un client qui insiste ne voit jamais son compteur expirer. Le lecture-incrément-écriture n'est pas atomique. | `Security\RateLimiter::hit()` |
| `RateLimiter` passe une durée de vie à `get()` et utilise `save()` / `exists()`, absents de l'API PSR-16 (E7). | `Security\RateLimiter` |
| Le CSRF est aussi vérifié sur GET (jeton passé dans l'URL, donc dans les logs et le `Referer`). Avec Phalcon 5, `checkToken()` détruit le jeton après validation par défaut (`destroyIfValid = true`), ce qui casse les formulaires ouverts dans plusieurs onglets et les appels AJAX successifs. | `Http\Middleware\Csrf` |

## Décisions

- **Adoption de `Phalcon\Auth`, confirmée par l'étude S1.** C'est la même logique que pour les migrations : quand Phalcon couvre nativement le besoin, on passe sur Phalcon. Notre `Auth\Manager` est supprimé. Nucleon garde une couche fine :
  - le provider `Auth`, qui construit le `Manager` via `ManagerFactory` depuis `config/auth.php` ;
  - la Facade `Auth` et les IDE helpers ;
  - le middleware de route `Authenticate`, appuyé sur `Auth::check()`, pour garder la syntaxe `'middleware' => Authenticate::class` dans les routes ;
  - le trait `Authenticable` et la classe `Foundation\Auth\User`, réécrits pour implémenter `AuthUser` et `AuthRemember`.

  **Si S1 montre un manque bloquant** (stockage du jeton remember-me non haché, performance de construction, absence dans Phalcon 6), on porte notre `Manager` en corrigeant toutes les faiblesses listées plus haut. L'API publique (`Auth::user()`, `check()`, `guest()`, `attempt()`, `login()`, `loginUsingId()`, `logout()`) reste la même dans les deux cas.
- **Identifiant de session** : `Phalcon\Auth` stocke la clé primaire de l'utilisateur, alors que la 1.3 stocke la valeur de `getAuthIdentifierName()` (l'email par défaut). Les sessions ouvertes avant la migration sont donc invalidées, et tous les utilisateurs devront se reconnecter. C'est documenté dans `UPGRADING-2.0.md`.
- **CSRF** :
  - vérification sur les seules méthodes non sûres (POST, PUT, PATCH, DELETE), avec le jeton lu dans l'en-tête `X-CSRF-Token` ou dans le champ `_csrf_token` du corps ;
  - GET, HEAD et OPTIONS ne sont plus vérifiés ;
  - jeton par session (`destroyIfValid = false`) par défaut, configurable (`security.csrf.rotate`).
- **Limitation de débit** :
  - `RateLimiter` réécrit sur le cache PSR-16 d'E7, avec une fenêtre fixe : le compteur et son expiration sont posés au premier hit et ne sont pas prolongés ensuite ;
  - incrément atomique via `increment()` de l'adapter de stockage quand il le permet (redis, apcu, libmemcached), sinon repli non atomique documenté ;
  - store de cache configurable (`security.throttle.store`), pour ne pas mêler les compteurs au cache applicatif ;
  - signature des requêtes en `xxh128` (`hash()`, PHP ≥ 8.1) au lieu de `crc32`.
- Les en-têtes `X-RateLimit-Limit`, `X-RateLimit-Remaining` et `Retry-After` sont conservés.

## Stories

### E8-S1 · Étude `Phalcon\Auth` (2 jours maximum)
- Vérifier sur Phalcon 5.22 et sur le job Phalcon 6 :
  1. stockage du jeton remember-me (haché ? lié au user agent ? révoqué au logout ?) ;
  2. coût de construction du `Manager` via `ManagerFactory`, qui doit rester à la demande ;
  3. compatibilité avec `Phalcon\Di\Di` sans autowiring (services à préenregistrer) ;
  4. intégration des listeners d'accès avec nos middlewares de route.
- Livrable : une note dans cet epic qui confirme l'adoption ou bascule sur le portage de notre `Manager`.

### E8-S2 · Provider, Facade et modèle utilisateur
- Provider `Auth` : `config/auth.php` au format `guards` + `access`. L'ancienne clé `auth.model` est convertie en guard `session` avec adapter `model`, et `session.id` en nom de clé de session.
- Facade `Auth` et IDE helpers (`@method static` vers `Phalcon\Auth\Manager`).
- `Authenticable` (trait) et `Foundation\Auth\User` implémentent `AuthUser` et `AuthRemember`.
- Tests : `attempt` réussi ou échoué, `login`, `loginById`, `logout`, `user()` via la session puis via le remember-me, cookie malformé, révocation du jeton au logout.

### E8-S3 · Middleware `Authenticate`
- Middleware de route typé (E4-S4), réponse 401 ou redirection configurable.
- Documentation de l'alternative native (accès `auth` + `Auth\Mvc\AuthDispatcherListener`) pour les apps qui préfèrent la déclaration par controller.

### E8-S4 · CSRF
- `Http\Middleware\Csrf` selon les décisions plus haut, sur `Encryption\Security` (E7-S5).
- Les helpers Volt `csrf_field()` et `csrf_token()` (E9) restent cohérents avec le nom du champ et de l'en-tête.
- Tests : POST avec jeton valide, invalide ou absent ; en-tête AJAX ; GET non vérifié ; plusieurs requêtes successives avec le même jeton.

### E8-S5 · `RateLimiter` et middlewares Throttle
- `RateLimiter` : `hit()`, `attempts()`, `tooManyAttempts()`, `retriesLeft()`, `availableIn()`, `resetAttempts()` et `clear()`, typés, sur le cache PSR-16, avec fenêtre fixe et incrément atomique.
- `Middleware\Throttle`, `Http\Middleware\ThrottleRequest` et `Auth\Middleware\ThrottleLogin` typés, avec la signature `xxh128`.
- Tests : dépassement de la limite, expiration de la fenêtre, en-têtes, store dédié, concurrence (deux processus qui incrémentent en parallèle sur redis).
- Mesure : surcoût d'une requête avec `ThrottleRequest` sur le store `memory` et sur `redis`.

## Changements cassants pour les apps

- `Neutrino\Auth\Manager` est remplacé par `Phalcon\Auth\Manager` (si S1 confirme). Les méthodes `user()`, `check()`, `guest()`, `attempt()`, `login()`, `logout()` et `loginUsingId()` restent disponibles via la Facade.
- Config `auth` au format `guards` / `access`. L'ancienne forme est convertie automatiquement.
- Les sessions ouvertes et les cookies remember-me de la 1.3 sont invalidés à la migration. Les utilisateurs doivent se reconnecter.
- Le modèle utilisateur implémente `AuthUser` (et `AuthRemember` pour le remember-me). Avec `Foundation\Auth\User`, il n'y a rien à faire.
- CSRF : GET n'est plus vérifié. Un jeton passé dans l'URL n'est plus lu.
- `RateLimiter` : fenêtre fixe au lieu d'une fenêtre qui glisse à chaque hit.

## Critères d'acceptation spécifiques

- Les suites `Auth`, `Security` et `Middleware` (Throttle et Csrf compris) sont activées et passent, y compris sur le job Phalcon 6.
- Chaque faiblesse listée dans l'état des lieux est couverte par un test qui échoue sur l'implémentation 1.3.
- Aucun service d'authentification n'est construit au boot (vérifié par test).

## Étude S1 · `Phalcon\Auth` (conclusion : adoption)

Lue dans le code de `Phalcon\Auth` (paquet PHP de Phalcon 6, même logique que la 5.22) et vérifiée par les tests.

1. **Jeton remember-me.** `Phalcon\Auth` génère le jeton (60 caractères hexadécimaux aléatoires) mais délègue son stockage au modèle (`AuthRemember::createRememberToken()` / `getRememberToken()`). Le hachage est donc à notre charge : le trait `Authenticable` stocke `sha256(jeton, user agent)`, compare avec `hash_equals()`, et révoque la colonne. Le guard lie le jeton au user agent (comparé au user agent courant), le révoque au logout (`RememberToken::delete()`), régénère l'id de session à la connexion et à la déconnexion. Cookie : `HttpOnly`, `Secure` par défaut, un an par défaut. Un cookie mal formé est ignoré (décodage JSON protégé). Le modèle adapter brûle un hachage quand l'utilisateur n'existe pas (temps constant).
2. **Coût.** Le `Manager` n'est construit qu'à la première utilisation du service `auth` : environ 75 µs la première fois dans un processus (classes Zephir initialisées à froid), 20 µs ensuite. Il construit aussi la session, la requête et les cookies, que le guard prend dans le conteneur : la session démarre avec l'authentification.
3. **`Phalcon\Di\Di` sans autowiring.** `ManagerFactory` reçoit le conteneur ; le guard `session` y lit `request`, `cookies` et `session` sous leur nom court (ou leur interface). Rien à préenregistrer de plus que nos providers.
4. **Middlewares.** Notre `Authenticate` appelle `check()` ; les listeners d'accès de Phalcon (`AuthDispatcherListener`) restent utilisables à côté, configurés par `auth.access`.
5. **Phalcon 6.** Présent. L'ORM de Phalcon 6 a besoin du paquet séparé `phalcon/phql` (ajouté au job CI).

Écart avec la décision initiale : l'identifiant reste celui de la 1.3 (`getAuthIdentifierName()`, `email` par défaut), utilisé comme `idColumn` du modèle adapter. Les sessions ouvertes avant la migration restent donc valides ; seuls les cookies remember-me de la 1.3 (format `id|jeton`) sont ignorés.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Étude | Fait | Adoption, voir plus haut. |
| S2 · Provider, Facade, modèle | Fait | Provider `Auth` : `ManagerFactory` sur `auth.guards` / `auth.access` ; forme 1.3 (`auth.model`, `session.id`) convertie en guard `web` avec le cookie `remember_me` ; `idColumn` déduit du modèle. Facade : méthodes du `Manager` plus `guest()`, `login()`, `loginUsingId()` (clé primaire lue dans les métadonnées et liée : `findFirst($id)` lirait une chaîne comme des conditions PHQL). `Authenticable` et `Foundation\Auth\User` implémentent `AuthUser` et `AuthRemember` ; `Neutrino\Auth\RememberToken`. `Neutrino\Auth\Manager` supprimé. Tests de bout en bout sur SQLite en mémoire (vraie table, vrai modèle) avec une session en mémoire. `Foundation\Auth\User` étend `Neutrino\Model`, porté en E10 : les tests utilisent un modèle Phalcon avec le trait. |
| S3 · `Authenticate` | Fait | 401, ou redirection (`[Authenticate::class => '/login']`). Le service `auth` n'est construit que sur les routes protégées (testé). |
| S4 · CSRF | Fait | POST, PUT, PATCH, DELETE ; en-tête `X-CSRF-Token`, champ `_csrf_token` (formulaire ou JSON) ; jeton gardé pour la session (`security.csrf.rotate`). Le middleware ne crée plus de jeton sur les requêtes sûres (la session ne démarre pas). `Csrf::token()` rend le jeton de la session : `getToken()` de Phalcon 5 en crée un nouveau à chaque requête, ce qui invaliderait les formulaires des autres onglets (testé). `TokenMismatchException` typée. |
| S5 · `RateLimiter` et Throttle | Fait | Fenêtre fixe (fin de fenêtre stockée, donc indépendante du respect du TTL par l'adapter : `memory` n'expire pas), clés hachées (`xxh128`, compatibles PSR-16), incrément atomique Redis / APCu / Memcached, store dédié. `RateLimiter::attempt()` décide sur le compte après l'incrément : sur un store atomique, des requêtes concurrentes ne dépassent pas la limite (20 processus lancés ensemble pour 5 tentatives restantes : exactement 5 acceptées, contre 18 en vérifiant avant de compter). Un compteur recréé par l'incrément (après `resetAttempts()` ou une éviction) reprend l'expiration de la fenêtre. Throttle : signature `xxh128`, en-têtes calculés avec le résultat de `attempt()` (moins d'allers-retours vers le store). Test de concurrence : 4 processus × 50 hits en parallèle sur Redis ajoutent exactement 200 au compteur. |

Suites `Auth` et `Security` activées, `CsrfTest` et `ThrottleTest` réintégrés dans `Http` et `Middleware`. 644 tests verts sur Phalcon 5.22 et 6 (avec `phalcon/phql`). Baseline PHPStan : 52 entrées en moins.

Faiblesses de la 1.3 couvertes par un test qui échoue sur la 1.3 : jeton haché (`testRememberMe`), révocation au logout (`testLogoutRevokesTheRememberToken`), durée du cookie (`testRememberMe`), cookie mal formé (`testMalformedRememberCookie`), interface plutôt que `Foundation\Auth\User` (le modèle des tests n'en hérite pas), signature (`hash('xxh128')`, `testRoutesAreCountedSeparately`), fenêtre fixe (`testFixedWindow`), API PSR-16 (`RateLimiterTest`), CSRF sur GET et jeton détruit (`testSafeMethodsAreNotChecked`, `testTokenStaysValidForSuccessiveRequests`).

### Mesures

`bench/compare.sh --optimize`, 150 itérations, requête complète avec `ThrottleRequest` (store `memory`) :

| Scénario | 1.3 | 2.0 |
|---|---|---|
| `http` | 232 µs | 295 µs |
| `http-throttle` | 430 µs | 471 µs |
| Surcoût du throttle | +198 µs | **+176 µs** |

Sur Redis (2.x seul, autoloader simple) : environ +480 µs de plus que sur `memory`, surtout la connexion à Redis ouverte par chaque processus du bench ; en PHP-FPM, `persistent => true` la réutilise.

