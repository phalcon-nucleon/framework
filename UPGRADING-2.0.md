# UPGRADING 1.3 > 2.0

Nucleon 2.0 requires **PHP ≥ 8.3** and **Phalcon ≥ 5.22**. This guide is completed as the upgrade progresses (see `docs/upgrade-2.0/`).

## Kernels

Kernel properties are typed: untyped redeclarations are a fatal error.

```php
// 1.3
protected $providers = [...];
protected $modules = [];

// 2.0
protected array $providers = [...];
protected array $middlewares = [...];
protected array $listeners = [...];
protected array $modules = [];
protected ?string $dependencyInjection = FactoryDefault::class;
protected ?string $eventsManagerClass = Manager::class;
protected array $errorHandlerLvl = [...];
```

Overridden methods follow the typed signatures of `Neutrino\Interfaces\Kernelable`: `registerRoutes(): void`, `boot(): void`, `terminate(): void`, `registerModules(array $modules, bool $merge = false): static`, `bootstrap(\Phalcon\Config\Config $config): void`.

`Bootstrap::run()` now calls `Kernelable::handleIncoming()`, which reads the current input (request URI or command line arguments) and calls the Phalcon `handle()`. A kernel that overrode `handle()` to change what is handled must override `handleIncoming()` instead. `Bootstrap` is `final`.

## Providers

```php
// 1.3
protected $name = Services::ROUTER;
protected $shared = true;
protected $aliases = [Router::class];
protected function register() { ... }

// 2.0
protected string $name = Services::ROUTER;
protected bool $shared = true;
protected array $aliases = [Router::class];
protected function register(): Router { ... } // the return type is optional, but the IDE helpers read it
```

`SimpleProvider`: `protected string $class` and `protected array $options`. A class implementing `Providable` directly declares `registering(): void`.

Phalcon binds closure service definitions to the container: a service defined with a `static function` or `static fn` fails ("Cannot bind an instance to a static closure").

## Modules

`Neutrino\Module` extends `Phalcon\Di\Injectable` (`Phalcon\Mvc\User\Module` no longer exists) and implements the Phalcon 5 signatures: `registerAutoloaders(?DiInterface $container = null): void`, `registerServices(DiInterface $container): void`, `initialise(DiInterface $container): void`. Its `$providers` is `protected array`.

## Listeners and middlewares

`protected array $listen` and `protected array $space` (typed). `Listener` implements `Phalcon\Events\EventsAwareInterface` itself, since `Phalcon\Di\Injectable` no longer carries an events manager.

## Event constants

Removed (the events no longer exist in Phalcon 5):

| Removed | Note |
|---|---|
| `Constants\Events\Collection`, `Constants\Events\CollectionManager`, `Events::COLLECTION`, `Events::COLLECTION_MANAGER` | The ODM was removed from Phalcon. |
| `Constants\Events\Volt`, `Events::VOLT` | The Volt compiler no longer fires events (use Volt extensions). |
| `Events\Model::NOT_SAVED`, `Events\Model::NOT_SAVE` | Not fired by Phalcon 5. |

Added: `Events::ROUTER`, `Events::DI`, `Events::KERNEL`, `Events\Router::*`, `Events\Di::*`, `Events\Db::CONNECTION_LOST`, `Events\Dispatch::{BEFORE_FORWARD, AFTER_BINDING, BEFORE_CALL_ACTION, AFTER_CALL_ACTION}`, `Events\Micro::{AFTER_BINDING, BEFORE_EXCEPTION}`, `Events\Model::{PREPARE_SAVE, VALIDATION}`, `Events\View::{BEFORE_COMPILE, AFTER_COMPILE}`.

All constants of `Neutrino\Constants` are typed (`public const string`).

## Facades

`getFacadeAccessor()` is typed: `protected static function getFacadeAccessor(): string` (or `object`). `swap()` and `shouldReceive()` now also replace a service the container had already resolved. `shouldReceive()` throws a `LogicException` when mockery/mockery is not installed.

## Support

- `Str`: `length`, `lower`, `upper`, `substr`, `title`, `quickRandom` and `normalizePath` are removed. Use `mb_strlen`, `mb_strtolower`, `mb_strtoupper`, `mb_substr`, `mb_convert_case($value, MB_CASE_TITLE)`, `Str::random()` and `Path::normalize()`.
- `Arr::where()` is removed: use `array_filter($array, $callback, ARRAY_FILTER_USE_BOTH)`.
- `Str`, `Arr`, `Obj`, `Func` and `Path` are `final` and typed (`strict_types`).
- `Singleton` keeps one instance per subclass (1.3 shared a single instance between all subclasses).
- `InjectionAwareTrait`: the container is in `$container` (was `$_di`); resolved services are no longer written as dynamic properties.
- `Strategy`: `protected array $supported`, `protected ?string $default`; `uses(?string $use = null): object`.
- `Fluent` / `Fluentable`: typed signatures (`ArrayAccess`, `Iterator`, `JsonSerializable`).
- `Neutrino\Version` no longer extends `Phalcon\Version`: `Version::get()` and `Version::getId()` remain.

## Dotconst

Compiled constants (`dotconst:cache`) are written as `const NAME = ...;`, except values read at runtime (`@php/env`), still written with `define()`. `@php/env:NAME` without default gives `null` when the variable is not set, in the compiled file as with the ini files (the compiled file gave `false`). `@php/dir@suffix` keeps its suffix in the compiled file.

## Config

`Neutrino\Config\Loader` returns a `Neutrino\Config\Config`, which extends `Phalcon\Config\Config` (reads with the exact key bypass the Phalcon case-insensitive lookup). Type hints on `Phalcon\Config\Config` keep working. Two differences with Phalcon 5: `path()` returns the default value when the path goes through a scalar value (Phalcon throws an error), and writing a key with another case replaces the previous spelling (Phalcon keeps every spelling, returned by `toArray()`). `config:cache` evaluates the configuration: closures and objects are no longer allowed in `config/*.php` (enums are).

## HTTP

- `Foundation\Middleware\Disptacher` is renamed `Foundation\Middleware\Dispatcher` (no alias).
- Middleware hooks are declared `init|before|after|finish(Event $event, object $source, mixed $data = null)`, without return type: 1.3 middlewares (untyped parameters) keep working; only `false` stops the request.
- `Http\Controller::middleware(string $middlewareClass, mixed ...$params): Foundation\Middleware\Controller` is typed (controllers overriding it must follow). A route middleware that does not extend `Foundation\Middleware\Controller` throws an exception.
- `route:cache` fails on routes it cannot cache (converters, `beforeMatch()`, `match()`, objects in the paths): they were silently lost.
- `StatusCode::message()` returns `null` for an unknown code (it returned `''`). `BAD_UNAUTHORIZED`, `UPDATE_REQUIRED` and `BANDWIDTH_LIMIT_EXCEED` are deprecated for `UNAUTHORIZED`, `UPGRADE_REQUIRED` and `BANDWIDTH_LIMIT_EXCEEDED`.
- The HTTP router provider no longer calls `setUriSource()`: the kernel passes the request URI to `handle()`.

## Micro

- `Micro\Router` only exposes what it supports (see `Micro\RouterInterface`): `setDefault*()`, `setDefaults()`, `addPurge()`, `addTrace()`, `addConnect()`, `clear()`, `getModuleName()`, `handle()`, `getNamespaceName()`, `getMatches()` and `getRouteById()` are removed. `add()` returns the route; `getRouteByName()` returns `null` when the route does not exist.
- Handlers also accept `'Controller::action'` and `[Controller::class, 'action']`.
- `Micro\Middleware::bindOn()` returns a `Neutrino\Micro\MiddlewarePosition` (`Before`, `After`, `Finish`); the `ON_BEFORE`, `ON_AFTER` and `ON_FINISH` constants are removed.

```php
// 1.3
public function bindOn() { return self::ON_BEFORE; }

// 2.0
public function bindOn(): MiddlewarePosition { return MiddlewarePosition::Before; }
```

- Phalcon 5 passes route parameters as named arguments: a closure handler must declare them (`fn (string $id) => …`).

## CLI

- Document the tasks with attributes: docblocks are still read, but they are deprecated (removed in 3.0) and disappear with `opcache.save_comments=0`.

```php
// 1.3
/**
 * @description Import the users.
 * @option -f, --force: Overwrite.
 */
public function mainAction() {}

// 2.0
#[Description('Import the users.')]
#[Option('-f, --force', 'Overwrite.')]
public function mainAction() {}
```

- A provider declares its commands by implementing `Neutrino\Cli\ProvidesTasks` (`public static function tasks(): array`). The migration commands are no longer registered by the console router: declare the migrations provider in the console kernel (E11).
- Task actions receive the route parameters only (Phalcon 5 would also pass the options as named arguments); read the options with `getOption()` / `hasOption()`.
- Tasks read the kernel arguments and options through `getArguments()`, `isQuiet()`, etc. (`$_arguments` and `$_options` no longer exist). `Task::$options`, `$arguments` are typed arrays; `getArg()`, `getOption()`, `hasOption(string ...)` are typed.
- `Decorate` no longer needs the posix extension; `NO_COLOR` disables the colors (`--colors` still forces them).
- `Writer::write(string $message, bool $newline): void` and the other output methods are typed: an output subclass overriding `write()` must follow.
- The `help` route is `help( .*)*`.

## Cache

The `cache` service implements `Phalcon\Cache\CacheInterface` (PSR-16 style). There is no compatibility layer with the 1.3 API.

| 1.3 | 2.0 |
|---|---|
| `save($key, $content, $lifetime)` | `set($key, $value, $ttl = null)` (`int` seconds or `DateInterval`; `0` or less deletes the item) |
| `get($key, $lifetime)` | `get($key, $default = null)`: the lifetime is set when writing |
| `exists($key)` | `has($key)` |
| `delete($key)` | `delete($key)` |
| `queryKeys($prefix)` | removed. `Cache::uses()->getAdapter()->getKeys($prefix)` reads the keys of a Phalcon adapter |
| `start()` / `stop()` (output cache) | removed |
| | `clear()`, `getMultiple()`, `setMultiple()`, `deleteMultiple()` |

Keys follow PSR-16: `{}()/\@:` are invalid (`Phalcon\Cache\Exception\InvalidArgumentException`).

The stores are configured with an adapter and a serializer:

```php
// 1.3
'stores' => [
    'file' => ['driver' => 'File', 'adapter' => 'Data', 'options' => ['cacheDir' => BASE_PATH . '/storage/cache/']],
],

// 2.0
'stores' => [
    'file'  => ['adapter' => 'stream', 'serializer' => 'php', 'options' => ['storageDir' => BASE_PATH . '/storage/cache/']],
    'redis' => ['adapter' => 'redis', 'options' => ['host' => '127.0.0.1', 'port' => 6379, 'lifetime' => 3600]],
],
```

- Adapters (1.3 backends): `File` → `stream` (`cacheDir` → `storageDir`), `Memory` → `memory`, `Apc` → `apcu`, `Libmemcached` → `libmemcached`, `Redis` → `redis`; new: `rediscluster`, `weak`. `Memcache`, `Mongo`, `Database`, `Aerospike`, `Wincache` and `Xcache` are removed. A custom adapter is a class implementing `Phalcon\Cache\Adapter\AdapterInterface`, built with `new $class(SerializerFactory $factory, array $options)`.
- Serializers (1.3 frontends): `Data` → `php` (default), `Json` → `json`, `Igbinary` → `igbinary`, `Base64` → `base64`, `None` → `none`; new: `msgpack`. `Output` is removed.
- A store with the 1.3 keys `driver`, `backend` or `frontend` is rejected with an explicit message.
- `cache.<store>` services are `Phalcon\Cache\Cache` instances; `Cache::uses($store)` returns one.

## Logger

The `logger` service is a `Phalcon\Logger\Logger`, which writes to several adapters:

```php
// 1.3
'log' => ['adapter' => 'File', 'path' => BASE_PATH . '/storage/logs/app.log', 'options' => []],

// 2.0
'log' => [
    'level'     => 'info',                    // optional
    'formatter' => 'line',                    // line (default), json, a class, or ['formatter' => 'line', 'format' => …, 'date_format' => …]
    'adapters'  => [
        'main'   => ['adapter' => 'stream', 'path' => BASE_PATH . '/storage/logs/app.log'],
        'syslog' => ['adapter' => 'syslog', 'name' => 'app', 'formatter' => 'json'],
    ],
],
```

- The 1.3 single adapter form (`log.adapter`, `log.path` or `log.name`, `log.options`) still works: `File` (or no `log.adapter`) and `Stream` become `stream`, `Syslog` stays `syslog`. `options` is no longer required.
- `Firelogger`, `Udplogger` and `Multiple` are removed (several adapters replace `Multiple`).
- Context placeholders use the format delimiters: `%name%`, no longer `{name}`. In a line format, `%type%` becomes `%level%`.
- The log methods take `(string $message, array $context = [])`; `log($level, $message, $context)`.

## Session

The `session` service is a `Phalcon\Session\Manager` (also registered as `Phalcon\Session\Manager::class`), on the adapter of the default store. It is still started on its first use.

```php
// 1.3
'stores' => ['files' => ['adapter' => 'Files', 'options' => ['uniqueId' => 'app']]],

// 2.0
'stores' => ['files' => ['adapter' => 'stream', 'name' => 'APPSESSID', 'options' => ['savePath' => BASE_PATH . '/storage/sessions', 'uniqueId' => 'app']]],
```

- Adapters: `Files` → `stream`, `Redis` → `redis`, `Libmemcached` → `libmemcached`, plus `noop`. `Memcache` is removed. The incubator adapters (Database, Mongo, Aerospike, HandlerSocket) are replaced by a class implementing `\SessionHandlerInterface`, built with `new $class($options)`.
- `sessionBag` needs a name: `$di->get(Services::SESSION_BAG, ['cart'])`.
- The Manager reads and writes only once the session is started; `destroy()` takes no argument.

## Encryption, security, filter, escaper, annotations

| 1.3 service class (and DI alias) | 2.0 |
|---|---|
| `Phalcon\Crypt` | `Phalcon\Encryption\Crypt` |
| `Phalcon\Security` | `Phalcon\Encryption\Security` |
| `Phalcon\Filter` | `Phalcon\Filter\Filter` |
| `Phalcon\Escaper` | `Phalcon\Html\Escaper` |
| `Phalcon\Annotations\Adapter\Memory` | `Phalcon\Annotations\Adapter\AdapterInterface` (alias) |

**Encrypted data.** `crypt` signs its messages by default (`app.crypt_signing`, default `true`, as in Phalcon 5). Nucleon 1.3 did not sign: data it encrypted (encrypted cookies, stored values) fails to decrypt with "Hash does not match" once signing is on. Phalcon 5 reads it with signing off (tested on values encrypted by Phalcon 3.4, `aes-256-cfb`, `aes-256-cbc` and `aes-128-ctr`). To migrate:

1. deploy with `'crypt_signing' => false` in `config/app.php`: 1.3 data stays readable;
2. re-encrypt what is stored (or wait for the encrypted cookies to expire);
3. remove `crypt_signing` to sign again. Data written during step 1 is unsigned: re-encrypt it too.

`security` reads the session only for CSRF tokens: hashing a password does not start the session.

`annotations.adapter` selects the annotations adapter: `memory` (default), `apcu` or `stream` (recommended in production), with `annotations.options`.

`flash` and `flashSession` use the container's `escaper`; `flashSession` reads the session when a message is stored or output.

## Tests (`Neutrino\Test`)

PHPUnit 11 is required: `setUp(): void`, `tearDown(): void`, `setUpBeforeClass(): void`, attributes instead of annotations.

```php
// 1.3
protected static function kernelClassInstance() { return HttpKernel::class; }

protected function routes()
{
    return [$this->formatDataRoute('/', 'GET', true, 'Index', 'index')];
}

// 2.0
protected static function kernelClassInstance(): string { return HttpKernel::class; }

protected static function routes(): array
{
    return [static::formatDataRoute('/', 'GET', true, 'Index', 'index')];
}
```

- `RoutesTestCase::routes()`, `formatDataRoute()`, `routesProvider()` and `getApplicationRoutes()` are static (PHPUnit 11 data providers). `routes/http.php` is required from a static method: use the `Router` Facade there, not `$this`.
- `FuncTestCase::dispatch(string $url, string $method = 'GET', array $params = [], array $headers = [], ?array $json = null): string` returns the output (it was filled by reference). PATCH parameters go to `$_POST` (they went to `$_GET`), DELETE parameters to `$_GET` (they were dropped). The superglobals are restored after the call.
- `assertResponseCode()` expects an `int` and compares it with `Response::getStatusCode()`.
- `mockService(string $service, string|object $class, bool $shared = true)`.
- A test case fails (instead of being skipped) when Phalcon is not available.

## Removed

- `Neutrino\Assets` and the `assets:js` / `assets:sass` tasks.
- `Neutrino\Optimizer`, `Neutrino\PhpPreloader`, `Config\ConfigPreloader`, `Config\ReturnConverter`: `optimize` now dumps an authoritative Composer classmap and generates `bootstrap/compile/preload.php`, to declare in `opcache.preload`.
