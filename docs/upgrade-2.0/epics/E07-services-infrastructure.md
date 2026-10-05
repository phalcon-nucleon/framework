# E7 — Services d'infrastructure

**Statut** : Rédigé · **Dépend de** : E3 · **Bloque** : E8, E10

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
