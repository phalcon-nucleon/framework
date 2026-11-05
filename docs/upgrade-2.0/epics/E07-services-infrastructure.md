# E7 — Services d'infrastructure

**Statut** : Terminé · **Dépend de** : E3 · **Bloque** : E8, E10

## Objectif

Porter sur Phalcon 5 les services transverses (cache, logger, session, flash, chiffrement, sécurité, filtre, échappement, annotations) en gardant la configuration par stores multiples et le chargement à la demande. C'est l'epic où l'API Phalcon change le plus : Cache, Logger et Session ont été entièrement réécrits depuis Phalcon 3.

## Périmètre

- `Cache/CacheStrategy.php`, `Providers/Cache.php`, `Support/Facades/Cache.php`
- `Providers/Logger.php`, `Support/Facades/Log.php`
- `Providers/Session.php`, `Support/Facades/Session.php`
- `Providers/Flash.php`, `Providers/FlashSession.php`, `Support/Facades/Flash.php`
- `Providers/Crypt.php`, `Providers/Security.php`, `Providers/Filter.php`, `Providers/Escaper.php`, `Providers/Annotations.php`
- Tests : `tests/Test/Cache`, `tests/Test/Providers` (11 fichiers de test), `tests/Test/TestCase/UseCaches.php`
- **Hors périmètre** :
  - `Security/RateLimiter.php` → E8 (il doit s'adapter à la nouvelle API de cache, voir Ruptures) ;
  - les services de base de données et de modèles → E10 ;
  - le service View → E9.

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0)

### Cache

| 1.3 | 5.22 |
|---|---|
| `Cache\Backend\*` + `Cache\Frontend\*` (backend + frontend) | `Phalcon\Cache\Cache` sur un adapter (`Cache\AdapterFactory`) et un serializer (`Storage\SerializerFactory`) |
| `BackendInterface` : `get($key, $lifetime)`, `save($key, $content, $lifetime, $stopBuffer)`, `delete`, `exists($key, $lifetime)`, `queryKeys($prefix)`, `start`/`stop` (cache de sortie), `isFresh`, `getLastKey`… | `CacheInterface` (dans l'esprit de PSR-16) : `get(string $key, $default)`, `set(string $key, $value, $ttl)`, `delete`, `has`, `clear`, `getMultiple`, `setMultiple`, `deleteMultiple`. Plus de cache de sortie. |
| Backends : Aerospike, Apc, Database, Libmemcached, File, Memcache, Memory, Mongo, Redis, Wincache, Xcache | Adapters : `apcu`, `libmemcached`, `memory`, `redis`, `rediscluster`, `stream`, `weak` |
| Frontends : Data, Json, File, Base64, Output, Igbinary, None | Serializers : `php`, `json`, `base64`, `igbinary`, `msgpack`, `none` |

### Logger

| 1.3 | 5.22 |
|---|---|
| `Logger\Adapter\File($path, $options)` utilisé directement comme logger | `Phalcon\Logger\Logger(string $name, array $adapters)` avec plusieurs adapters possibles |
| Adapters : File, Firelogger, Stream, Syslog, Udplogger | Adapters : `Stream`, `Syslog`, `Noop` |
| `Logger\AdapterInterface` | `Logger\Adapter\AdapterInterface`, `Logger\LoggerInterface` |

### Session

| 1.3 | 5.22 |
|---|---|
| L'adapter est la session (`new Adapter\Files($options)`, `->start()`) | `Session\Manager` + adapter (`setAdapter(\SessionHandlerInterface)`) |
| Adapters : Files, Libmemcached, Memcache, Redis (+ Aerospike, Database, HandlerSocket, Mongo de l'incubator) | Adapters : `Stream`, `Redis` et `Libmemcached` (construits avec `Storage\AdapterFactory`), `Noop` |
| `new Session\Bag($name)` | `new Session\Bag(ManagerInterface $session, string $name)` |
| `Session\AdapterInterface` | `Session\ManagerInterface` |

### Autres services

| 1.3 | 5.22 |
|---|---|
| `Phalcon\Crypt($cipher)` | `Phalcon\Encryption\Crypt(string $cipher, bool $useSigning = true, ?PadFactory)` |
| `Phalcon\Security` | `Phalcon\Encryption\Security(?Session\ManagerInterface, ?Http\RequestInterface)` |
| `Phalcon\Filter` | `Phalcon\Filter\Filter`, construit par `Filter\FilterFactory::newInstance()` |
| `Phalcon\Escaper` | `Phalcon\Html\Escaper` |
| `Flash\Direct()`, `Flash\Session()` | `Flash\Direct` / `Flash\Session` (`?EscaperInterface $escaper, ?Session\ManagerInterface $session`) |
| `Annotations\Adapter\Memory` | inchangé, plus `Apcu` et `Stream` |

## Décisions

- **Stratégie de cache conservée** : un service `cache` qui délègue au store par défaut, et des services `cache.<store>` créés à la demande.
  - `CacheStrategy` implémente `Phalcon\Cache\CacheInterface` (PSR-16 Phalcon) au lieu de `BackendInterface`. On garde `uses(string $store)` pour cibler un store.
  - **Décidé : pas de couche de compatibilité avec l'ancienne API** (`save`, `exists`, `queryKeys`, `start`/`stop`). C'est une version majeure, et l'API PSR-16 est le standard. La correspondance est documentée dans `UPGRADING-2.0.md`.
- **Nouveau format de config du cache** : `cache.stores.<nom> = ['adapter' => 'redis', 'serializer' => 'php', 'options' => […]]`. Une classe complète est acceptée comme adapter personnalisé (doit implémenter `Phalcon\Storage\Adapter\AdapterInterface`). Correspondance avec la 1.3 :
  - `File` → `stream`, `Memory` → `memory`, `Apc` → `apcu`, `Libmemcached` → `libmemcached`, `Redis` → `redis` ;
  - `Memcache`, `Mongo`, `Database`, `Aerospike`, `Wincache` et `Xcache` sont supprimés ;
  - frontends → serializers : `Data` → `php`, `Json` → `json`, `Igbinary` → `igbinary`, `Base64` → `base64`, `None` → `none`. `Output` est supprimé.
  - Les clés `driver`, `backend` et `frontend` de la 1.3 sont refusées avec un message qui indique le nouveau format.
- **Logger** :
  - `log.adapters` décrit une liste d'adapters (`stream` avec `path`, `syslog` avec `name`, `noop`, ou une classe personnalisée) ;
  - l'ancienne forme à un seul adapter (`log.adapter`) est acceptée et convertie ;
  - Firelogger et Udplogger sont supprimés ;
  - l'adapter PSR-3 est hors périmètre.
- **Session** :
  - stores multiples conservés (`session.default` + `session.stores`) ;
  - adapters `stream` (remplace `Files`), `redis`, `libmemcached`, `noop`, ou une classe implémentant `\SessionHandlerInterface`, ce qui couvre les besoins Database ou Mongo de l'incubator ;
  - la session reste démarrée à la première résolution du service ;
  - le service `sessionBag` devient une fabrique qui reçoit le nom du bag (`$di->get(Services::SESSION_BAG, ['name'])`).
- **Chiffrement** :
  - `app.cipher` et `app.key` sont conservés ;
  - nouvelle option `app.crypt_signing` (défaut `true`, valeur par défaut de Phalcon 5) ;
  - **à vérifier en S5** : la compatibilité des données chiffrées avec la 1.3 (cookies chiffrés, valeurs stockées). Si Phalcon 5 ne peut pas déchiffrer un message de Phalcon 3, `UPGRADING-2.0.md` le signale et indique la procédure (désactiver la signature le temps d'une rotation, ou rechiffrer).
- **Security sans démarrage de session inutile** : on vérifie en S5 que le hachage de mot de passe via `security` ne démarre pas la session. Si c'est le cas, la session est injectée de façon différée.
- **Annotations** : l'adapter est configurable (`annotations.adapter` : `memory` par défaut, `apcu` ou `stream` recommandés en production).

## Stories

### E7-S1 · Cache
- Provider : création des stores avec `Cache\AdapterFactory` et `Storage\SerializerFactory` (une seule instance de chaque fabrique, partagée).
- `CacheStrategy` : `CacheInterface` + `uses()`, typé.
- Validation de config et messages d'erreur pour les anciennes clés.
- Facade `Cache` et IDE helpers (méthodes PSR-16).
- Tests : chaque adapter disponible en CI (`memory`, `stream`, `apcu` si l'extension est présente, `redis` via service CI), store par défaut, `uses()`, TTL, multi-clés, adapter personnalisé.
- `tests/Test/TestCase/UseCaches.php` et la config de cache de `TraitTestCase` (E3-S5) passent au nouveau format.

### E7-S2 · Logger
- Provider `Logger` basé sur `Phalcon\Logger\Logger` + adapters, avec la conversion de `log.adapter`.
- Formatter configurable (`line` par défaut, `json`).
- Facade `Log`.
- Tests : `stream` (fichier temporaire), `syslog` (construction seulement), plusieurs adapters, adapter personnalisé, config invalide.
- Note pour E12 : les sorties d'erreur `Error\Writer\Logger` utilisent ce service.

### E7-S3 · Session
- Provider `Session` : `Session\Manager` + adapter, stores multiples. Correction au passage d'un bug : `new \RuntimeException($msg, $e)` passe l'exception comme code. On utilise `new \RuntimeException($msg, 0, $e)`.
- Service `sessionBag` en fabrique.
- Facade `Session`.
- Tests : `stream` et `noop`, `redis` via service CI, handler personnalisé, store inexistant.

### E7-S4 · Flash
- `Flash` (non partagé, `setImplicitFlush(false)`) et `FlashSession` (partagé) construits avec l'escaper et la session du conteneur.
- Facade `Flash`.
- Tests : messages directs et en session.

### E7-S5 · Chiffrement et sécurité
- `Crypt` : `Encryption\Crypt` avec `app.cipher`, `app.key` et `app.crypt_signing`.
- Test de compatibilité : déchiffrer avec Phalcon 5 une valeur chiffrée par la 1.3 (jeu de données généré avec `docker/legacy` d'E0). Résultat et procédure dans `UPGRADING-2.0.md`.
- `Security` : `Encryption\Security`, injection différée de la session si nécessaire (voir Décisions).
- Tests : hachage et vérification de mot de passe sans démarrage de session, génération et vérification de jeton CSRF (préalable au middleware Csrf d'E8).

### E7-S6 · Filter, Escaper, Annotations
- `Filter` via `FilterFactory::newInstance()` (fabrique, plus `SimpleProvider`).
- `Escaper` → `Html\Escaper`.
- `Annotations` : adapter configurable.
- Alias de classes mis à jour (`aliases`), pour que la résolution par nom de classe (`$di->get(\Phalcon\Html\Escaper::class)`) fonctionne.

## Changements cassants pour les apps

- **Cache** :
  - nouveau format de config (`adapter`, `serializer`) ;
  - API PSR-16 (`set`/`has` au lieu de `save`/`exists`) ;
  - plus de cache de sortie (`start`/`stop`) ;
  - plus de durée de vie passée à `get()` ;
  - backends supprimés : voir la correspondance plus haut.
- **Logger** : `log.adapters`. Firelogger et Udplogger supprimés.
- **Session** :
  - `Files` → `stream` ;
  - adapters de l'incubator remplacés par un `\SessionHandlerInterface` ;
  - `sessionBag` demande un nom.
- **Classes renommées** : `Phalcon\Crypt`, `Security`, `Escaper` et `Filter` deviennent `Encryption\Crypt`, `Encryption\Security`, `Html\Escaper` et `Filter\Filter` (alias DI mis à jour).
- **Données chiffrées** : selon le résultat d'E7-S5.

## Critères d'acceptation spécifiques

- Les suites `Cache` et `Providers` (hors providers de base de données et de vues) sont activées et passent, y compris sur le job Phalcon 6. Les adapters qui dépendent d'une extension absente sur Phalcon 6 sont ignorés explicitement.
- Aucun service n'est instancié au boot : test qui démarre le kernel et vérifie qu'aucun store de cache, la session et le logger ne sont résolus.
- Mesures : la résolution du service `cache` et un `get`/`set` sur le store `memory` ne sont pas moins bons que ceux de la 1.3.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Cache | Fait | `CacheStrategy` : `Phalcon\Cache\CacheInterface` + `uses()`, `final`, appels inconnus transmis au store (`getAdapter()`). Le provider lit les noms des stores au boot et ne construit rien ; chaque `cache.<store>` est un `Phalcon\Cache\Cache` construit à la première utilisation (`Providers\Cache::makeStore()`, public). Les adapters Phalcon sont construits directement, sans `Cache\AdapterFactory` (plus coûteuse que l'adapter lui-même) ; une seule `SerializerFactory` par provider. Noms d'adapters insensibles à la casse (`Memory`, `Redis` de la 1.3 passent) ; `File` et `Apc` donnent le nouveau nom dans l'erreur. Tests : `memory`, `stream` (3 serializers), `apcu`, `redis` (service CI), TTL, multi-clés, adapter personnalisé, anciennes clés, registration sans construction. |
| S2 · Logger | Fait | `Phalcon\Logger\Logger` sur `log.adapters` (`stream`, `syslog`, `noop`, classe), `log.level`, formatter global ou par adapter (`line`, `json`, classe, ou tableau `format`/`date_format`). Forme 1.3 à un adapter convertie (sans `log.adapter`, un fichier sur `log.path`, comme en 1.3) ; `options` n'est plus obligatoire. Alias `Phalcon\Logger\Logger`. Les niveaux sont lus sur `Logger\Enum` (les constantes de `Logger` n'existent pas en Phalcon 6). |
| S3 · Session | Fait | `Session\Manager` + adapter (`stream`, `redis`, `libmemcached`, `noop`, `\SessionHandlerInterface`), stores multiples ou store unique, noms de classe 1.3 `Phalcon\Session\Adapter\Redis` et `Libmemcached` acceptés (construits avec leur fabrique), `name` par store, démarrage à la première résolution. Alias `Phalcon\Session\Manager`. `sessionBag` : fabrique qui reçoit le nom. Bug de la 1.3 corrigé (exception précédente passée comme code). Le démarrage réel est testé dans un processus séparé (PHPUnit a déjà envoyé sa sortie). |
| S4 · Flash | Fait | `Direct` (non partagé, `setImplicitFlush(false)`) et `Session` (partagé) reçoivent l'escaper du conteneur ; `flashSession` ne lit la session qu'au premier message (testé). |
| S5 · Chiffrement et sécurité | Fait | `Encryption\Crypt` avec `app.cipher` (défaut `aes-256-cfb`), `app.key`, `app.crypt_signing` (défaut `true`). Compatibilité vérifiée sur des valeurs chiffrées par Phalcon 3.4 (`tests/Test/Providers/fixtures/crypt-1.3.php`, généré avec l'image `legacy`) : lisibles avec la signature désactivée, refusées (« Hash does not match ») sinon. La 1.3 ne signait pas : procédure dans `UPGRADING-2.0.md`. `Encryption\Security` en `SimpleProvider` : Phalcon lit la session dans le conteneur seulement pour les jetons CSRF, le hachage ne la démarre pas (testé). Jeton CSRF généré et vérifié (processus séparé). |
| S6 · Filter, Escaper, Annotations | Fait | `FilterFactory::newInstance()`, `Html\Escaper`, annotations `memory`/`apcu`/`stream`/classe (alias `Annotations\Adapter\AdapterInterface`, la classe concrète dépendant de la config). Les closures de service déclarent leur type de retour : les IDE helpers documentent `cache`, `cache.<store>`, `session` et `sessionBag` sans les construire (testé). |

Suites `Cache` et `Providers` activées (`ProvideDatabaseTest` sorti dans la suite `ProvidersDatabase`, E10). Les anciens tests de providers sont remplacés : `AllProvidersTest` couvrait aussi Auth, les modèles et les vues, que leurs epics (E8, E9, E10) testeront. Le provider de cache factice de l'app de test, inutilisé, est supprimé. 577 tests au total, verts sur Phalcon 5.22 et sur Phalcon 6 (APCu et Redis ignorés explicitement quand l'extension manque). Baseline PHPStan : 60 entrées en moins ; `RateLimiter` (E8) a deux entrées nouvelles, car il appelle encore `save()`/`exists()`.

### Comportements de Phalcon 5 à connaître

- **Interpolation du logger** : le contexte utilise les délimiteurs du format (`%name%`), plus `{name}` comme en Phalcon 3.
- **Session en CLI** : `Manager::start()` renvoie `false` sans erreur si des en-têtes sont déjà partis, et `get()`/`set()` ne font alors rien.
- **Clés de cache** : `{}()/\@:` sont refusés (PSR-16). E8 doit en tenir compte pour les clés du `RateLimiter`.
- **Phalcon 6** : `getMultiple()` est typé `: mixed` (signature reprise par `CacheStrategy`).

### Mesures (`bench/compare.sh --optimize`, 200 itérations)

| Scénario | 1.3 | 2.0 | Δ temps | Δ mémoire |
|---|---|---|---|---|
| `cache` : résolution du service + 1 set + 1 get sur `memory` | 86 µs | 112 µs | +26 µs (+30 %) | −35 % |
| `cache-100` : résolution + 100 set + get | 179 µs | 547 µs | +368 µs (+205 %) | −33 % |

**Critère non atteint, à cause de Phalcon 5.** Mesuré sans Nucleon : le premier objet de chaque classe Zephir coûte 25 à 35 µs (`SerializerFactory`, adapter `Memory`), puis un `set` + `get` sur `Phalcon\Cache\Cache` coûte environ 4,5 µs contre 0,9 µs avec le backend `Memory` de Phalcon 3 ; l'adapter seul, sans sérialisation, en coûte encore 2,7 µs. La couche Nucleon (`CacheStrategy`, Facade) ajoute environ 0,1 µs par appel (mesuré : 0,15 à 0,35 µs par set + get). Sur un store réseau (Redis, Memcached), l'aller-retour (plusieurs dizaines de µs) domine cet écart.

**Accepté pour l'instant** (6 octobre 2026). Piste si le besoin apparaît : un store `memory` en PHP pur (tableau, TTL, copie par sérialisation) qui serait plus rapide que la 1.3, au prix d'un adapter maison qui n'est pas un adapter Phalcon (`getAdapter()` ne renverrait plus un `Phalcon\Cache\Adapter\Memory`).

