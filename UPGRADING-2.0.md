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

## Models and database

```php
// config/database.php: unchanged, `adapter` by name or by class
'default'     => 'main',
'connections' => [
    'main'    => ['adapter' => 'mysql', 'config' => [...]],      // or Phalcon\Db\Adapter\Pdo\Mysql::class
    'reports' => ['adapter' => 'postgresql', 'config' => [...]],
],
```

- **`DatabaseStrategy` is removed.** `db` is the default connection, each connection is `db.<name>` (built on first use). A model uses another connection with `setConnectionService('db.reports')` (or `setReadConnectionService()` / `setWriteConnectionService()`); `Db::connection('reports')` returns it. The 1.3 `db` service with several connections forwarded to the default one: same behaviour.
- **Model description.** `primary()`, `column()`, `timestampable()`, `timestamps()`, `softDeletable()` and `softDelete()` are kept (typed). The description reaches Phalcon through `Neutrino\Model\MetaDataStrategy`, set on `modelsMetadata` by the `Model` or `ModelsMetaData` provider: register one of them. `Neutrino\Model` no longer has `metaData()` / `columnMap()` methods; a model that overrode them describes its columns with the methods or the new attributes:

```php
#[Timestamps]
#[SoftDelete]
class User extends Model
{
    #[Primary] public ?int $id = null;
    #[Column(Column::TYPE_VARCHAR)] public ?string $email = null;
    #[Column(Column::TYPE_VARCHAR, name: 'full_name', nullable: true)] public ?string $name = null;
}
```

- `timestamps()`: `updated_at` is nullable (null until the first update). `softDelete()`: the column defaults to `false`.
- `models.metadata.adapter` (`memory` by default, `apcu`, `stream`, `redis`, `libmemcached`): keep `memory` for Nucleon models, whose description costs less than a cache read. `model:cache` warms a shared cache (stream, redis, memcached) for introspected models.
- `Model::findFirst()` returns `null` (Phalcon 5), no longer `false`.

## Repositories

```php
// 1.3
protected $modelClass = User::class;

// 2.0
protected ?string $modelClass = User::class;
```

- **A string value is an equality**: `find(['name' => 'Ada'])` is `name = 'Ada'` (1.3: `LIKE`, where `%` and `_` were wildcards). Ask `LIKE` explicitly: `['name' => ['operator' => 'LIKE', 'value' => 'Ad%']]`. `null` is `IS NULL`.
- **Names are checked**: the keys of `$params` and `$order` must be attributes of the model (attribute names, mapped names for mapped columns), the operators one of `=`, `!=`, `<>`, `<`, `<=`, `>`, `>=`, `LIKE`, `NOT LIKE`, `IN`, `NOT IN`, `IS NULL`, `IS NOT NULL`, the directions `ASC` or `DESC`. Anything else throws an `InvalidArgumentException`: in 1.3 they were written as is in the PHQL (injection when they came from the request).
- Methods typed: `count(?array $params = null): int`, `find(array $params = [], ?array $order = null, ?int $limit = null, ?int $offset = null)`, `first(): ?ModelInterface`, `create/save/update/delete(ModelInterface|array $value, bool $withTransaction = true): bool`, `each(): Generator`. `TransactionException` extends `RuntimeException` (`Phalcon\Exception` no longer exists).
- `each()` and `Eachable::each()` stop on the first partial page (one query less) and honour `$end` exactly.
- `Support\Db::getQueries()` / `pretend()` return the statements as sent (placeholders, not the bound values), and restore the events manager of the connection.

## Migrations

```php
// Kernels\Cli\Kernel: the migration commands come with their provider (no longer declared by the CLI router)
protected array $providers = [
    // ...
    Neutrino\Providers\Database::class,
    Neutrino\Providers\Model::class,
    Neutrino\Database\Providers\MigrationsServicesProvider::class,
];

// config/migrations.php
'path'       => BASE_PATH . '/migrations',
'prefix'     => TimestampPrefix::class,   // default; DatePrefix::class
'storage'    => DatabaseStorage::class,   // default
'connection' => null,                     // connection of the `migrations` table, the default one otherwise
```

- **The 1.3 migrations run as they are** (a class named after the file, `up()` / `down()` untyped). `make:migration` now writes a file returning an anonymous class: no class name to keep unique.

```php
return new class extends Migration {
    protected ?string $connection = 'reports'; // optional: a db.<name> connection
    protected bool $withinTransaction = true;  // default

    public function up(Builder $schema): void { ... }
    public function down(Builder $schema): void { ... }
};
```

- **Transactions**: on PostgreSQL and SQLite, each migration and its log run in a transaction: a migration that fails leaves no half-applied change. MySQL commits each DDL statement: no effect there. `protected bool $withinTransaction = false;` opts a migration out. SQLite ignores `PRAGMA foreign_keys` in a transaction: there, `disableForeignKeyConstraints()` / `withoutForeignKeyConstraints()` defer the checks to the commit (`PRAGMA defer_foreign_keys`).
- **`FileStorage` is removed**: before upgrading, run your migrations with `DatabaseStorage` (or create the `migrations` table and insert the rows of `migrations/.migrations.dat`). Configuring it throws an explicit error.
- **`Schema\Dialect\*` and `Schema\DialectInterface` are replaced by `Schema\Grammar\{Mysql, Postgresql, Sqlite}`** (interface `Schema\Grammar`), chosen from the dialect of the connection. Only apps that extended a dialect are concerned: a grammar holds the column types and the statements Phalcon does not generate; the rest goes through the Phalcon adapter. `new Builder(?AdapterInterface $connection = null, ?Grammar $grammar = null)`.
- `migrate:rollback` rolls back **the whole last batch** (1.3: one migration of it). `--step=N` rolls back the last N migrations.
- New options: `--database=<name>` (connection of the migrations that do not declare theirs; `migrate:fresh` empties it, the connections the migrations declare and that of the `migrations` table), `--pretend` also on `migrate:rollback` and `migrate:reset`. `migrate:fresh` no longer accepts `--pretend` (it dropped the tables anyway).
- Schema: `renameColumn()` uses `RENAME COLUMN` (MySQL 8, MariaDB 10.5, SQLite 3.25); `getColumnType()` returns the Phalcon type (`Column::TYPE_*`); `decimal()` is `DECIMAL(10, 0)` by default and takes the precision as third argument; `enum()` is a `CHECK` constraint on PostgreSQL and SQLite; `uuid()`, `ipAddress()`, `macAddress()` use `UUID`, `INET`, `MACADDR` on PostgreSQL; `time()` and `timeTz()` take a precision. Indexes other than the primary key are created after the table. In `$schema->table()`, `rename()` runs after the other changes; `increments()` adds the column with its primary key, or only modifies a column that already is the primary key. SQLite cannot modify a column nor add or drop a primary or foreign key on an existing table: the command fails with a `CommandException`.
- The errors of a schema command are `Schema\Exception\CommandException` (command, table and cause).

## Authentication

The `auth` service is a `Phalcon\Auth\Manager` (also registered as `Phalcon\Auth\Manager::class`): `Neutrino\Auth\Manager` is removed.

```php
// 1.3: config/auth.php (and session.id)
'auth' => ['model' => App\Models\User::class],

// 2.0: still accepted, converted to the guard below
'auth' => [
    'guards' => [
        'web' => [
            'type'    => 'session',
            'default' => true,
            'adapter' => ['name' => 'model', 'options' => ['model' => App\Models\User::class]],
            'options' => ['name' => 'auth', 'rememberName' => 'remember_me', 'rememberTtl' => 1209600],
        ],
    ],
    'access' => [], // optional: Phalcon\Auth access classes
],
```

- `Auth::user()`, `check()`, `guest()`, `attempt()`, `login()`, `loginUsingId()`, `logout()` remain on the Facade. `attempt()` returns a `bool` (1.3: the user or `null`): read the user with `Auth::user()`. `guest()`, `login()` and `loginUsingId()` are Facade methods: on the service, use `!$auth->check()` and `$auth->guard()->login($user)`. New: `Auth::id()`, `validate()`, `guard()`.
- The user model implements `Neutrino\Interfaces\Auth\Authenticable` (`Phalcon\Contracts\Auth\AuthUser` and `AuthRemember`). With `Foundation\Auth\User` or the `Neutrino\Auth\Authenticable` trait, nothing to do. The trait methods are typed; `getRememberToken()` / `setRememberToken()` are replaced by `getRememberToken(string $token)` and `createRememberToken(string $token, ?string $userAgent)` (contracts of `Phalcon\Auth`) and `forgetRememberToken()`.
- The session keeps the 1.3 identifier (`getAuthIdentifierName()`, `email` by default) under the same key (`session.id`): open sessions stay valid.
- **Remember-me**: the token is stored hashed (SHA-256), bound to the user agent, revoked at logout, and the cookie lasts one year by default (`rememberTtl`, 1.3: 100 years), `HttpOnly` and `Secure` (`rememberSecure`). The cookie format changes: the 1.3 remember-me cookies are ignored, these users log in again. The `remember_token` column holds 64 characters. One token per user, as in 1.3; for one token per device, implement `AuthRemember` with a table of tokens.
- `Authenticate` answers 401, or redirects: `'middleware' => [Authenticate::class => '/login']`.

## CSRF

`Http\Middleware\Csrf` checks POST, PUT, PATCH and DELETE only. The token is read from the `X-CSRF-Token` header or the `_csrf_token` field of the body (form, or JSON), no longer from the query string: GET and DELETE requests carrying the token in the URL must send it in the header. Render the token with `Csrf::token()`, not `$this->security->getToken()`: Phalcon 5 creates a new token at the first `getToken()` of each request, which would invalidate the forms open in other tabs. `Csrf::token()` reuses the token of the session, valid for the session (Phalcon 5 would also destroy it after each check); `security.csrf.rotate = true` renews it after each valid request. `Csrf::FIELD` and `Csrf::HEADER` hold the names.

## Rate limiting

`Security\RateLimiter` counts in a fixed window: the first hit opens the window for `$decaySeconds`, later hits no longer extend it (1.3: each hit restarted the lifetime, a client that kept trying was never released). The counters go to the cache store `security.throttle.store`, else the default store; the increment is atomic on Redis, APCu and Memcached.

| 1.3 | 2.0 |
|---|---|
| `new RateLimiter($name)` | `new RateLimiter(string $name = '', ?string $store = null)` |
| `tooManyAttempts($key, $max, $decay)` | `tooManyAttempts(string $key, int $max)` |
| `attempts($key, $decay)` | `attempts(string $key)` |
| `retriesLeft($key, $max, $decay)` | `retriesLeft(string $key, int $max)` |
| `availableIn($key, $decay)` | `availableIn(string $key)` |
| `hit($key, $decay)` | `hit(string $key, int $decaySeconds = 60)` |
| | `attempt(string $key, int $max, int $decaySeconds = 60): ?int`: counts and checks after the increment, safe under concurrency |

`Middleware\Throttle` subclasses declare `protected string $name`; the request signature is an `xxh128` hash (1.3: `crc32`), so the 1.3 counters are not read again.

## Views and Volt

```php
// config/view.php
'views_dir'     => BASE_PATH . '/resources/views/',
'compiled_path' => BASE_PATH . '/storage/views/',
'engines'       => ['.volt' => VoltEngineRegister::class],
'options'       => ['stat' => false],   // Volt options: path, separator, extension, always, stat
'extensions'    => [CsrfExtension::class, StrExtension::class, PhpFunctionExtension::class],
'filters'       => ['merge' => MergeFilter::class, 'split' => SplitFilter::class, 'round' => RoundFilter::class],
'functions'     => ['route' => RouteFunction::class],
'php_functions' => ['allow' => [], 'deny' => [...]], // optional, see below
```

- Volt options: the 1.3 names `compiledPath`, `compiledSeparator`, `compiledExtension` and `compileAlways` are converted to `path`, `separator`, `extension` and `always` (Phalcon 5 deprecates the old names).
- `{% cache %}` no longer compiles (the output cache is gone from Phalcon 5): remove it, or cache the data with the `cache` service.
- The `tag` service is a `Phalcon\Html\TagFactory` (also `tagFactory`), as in the Phalcon 5 `FactoryDefault`: Volt compiles `link_to()`, `form()`… on it. `Phalcon\Tag` stays resolvable by its class. `assets` is built with the `TagFactory`.
- `csrf_field()` writes the field itself (`<input type="hidden" name="_csrf_token" value="…">`, escaped), with the session token of `Csrf::token()`.
- `PhpFunctionExtension` refuses the functions of `PhpFunctionExtension::DENY` (commands, files, `ini_set`, `putenv`, callbacks such as `call_user_func` or `array_map`…). `view.php_functions.allow` allows some of them again, `view.php_functions.deny` replaces the list.
- `SliceFilter` (`array_slice(offset, length)`) differs from the native Volt `slice(start, end)` (inclusive end, strings too): without `'slice' => SliceFilter::class` in `filters`, `slice` is the Volt one.
- Custom extensions, functions and filters: `ExtensionExtend::compileFunction(string $name, string $arguments, ?array $funcArguments)` (and `compileFilter()`, `resolveExpression(array)`, `compileStatement(array)`, now optional), `FunctionExtend::compileFunction(string $resolvedArgs, ?array $exprArgs)`, `FilterExtend::compileFilter(string $resolvedArgs, ?array $exprArgs)`. `$exprArgs` is `null` when the filter has no parentheses. `EngineRegister::register(ViewBaseInterface $view, DiInterface $di)`.
- New `view:cache` command (run by `optimize`): compiles every template, so that the production can set `stat` to `false`.

## Errors and debug

- **The error handler is registered by `Foundation\Bootstrap::make()`** (`Neutrino\Error\Handler`), outside the `test` environment. Remove your own `Handler::register()` call (it does nothing when already registered), or set `'error' => ['register' => false]` in the config to register it yourself. The fatal errors (`E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR`, `E_RECOVERABLE_ERROR`) now reach the writers; 1.3 reported `E_ERROR` only.
- `Handler::handleException()` takes any `\Throwable`.
- **`Error\Error` is a `readonly` value object**: `$error->type`, `->message`, `->file`, `->line`, `->code`, `->exception`, `->isException`, `->isError`, `->typeStr` and `->logLvl` are public properties. Array access (`$error['message']`) and the changes are removed: update the error views that read `$error[...]`. `isFateful()` is renamed `isFatal()`. `Error::fromError()` takes `int` and `string` arguments; the code of an exception can be a string (`PDOException`).
- `Error\Helper::getLogType()` returns a `Phalcon\Logger\Enum` level.
- Writers (`$errorHandlerLvl`) implement `handle(Error $error): void`. `Flash` no longer shows the fatal errors (they are on the error page: a flash message output before it sent the headers, and the page lost its 500 status). `View` answers 500 also without a `view` service, and when the error page fails too (error controller or view that throws): then with the default message, the failure going to the PHP log. `Logger`: `error.formatter` (same values as `log.formatter`: `formatter`, `format`, `date_format`; the 1.3 `date` key is no longer read, and a formatter class is built without arguments) formats the errors only, the adapters keep their formatter; Phalcon 5 writes the line breaks of a message as `\x0A`, so an error is on one line of the log.
- **The Nucleon debug bar is replaced by [`phalcon/debugbar`](https://github.com/phalcon/debugbar)**: `composer require --dev phalcon/debugbar`. When installed and `APP_DEBUG` is true, `Debug\Debugger` boots it on the HTTP kernel and gives the application's events manager to the services resolved afterwards (connections, cache stores, views) and its adapter to the `logger`, so its panels show the queries, the cache operations, the views and the logs. `debug.bar` holds its options (`['enabled' => false]` disables it). The Micro kernel has no bar (`phalcon/debugbar` needs a `Phalcon\Mvc\Application`). `DebugToolbar`, `DebugEventsManagerWrapper`, `Debugger::registerProfiler()` / `getGlobalEventsManager()` and `Foundation\Middleware\Debug` are removed.
- The debug error page no longer loads anything from a CDN; it lost its events and profilers tabs (now in the bar).
- **`ark4ne/highlight` is removed**: `composer require --dev tempest/highlight` to keep the highlighted code on the error page and in `migrate --pretend` (`Debug\Highlight`). Without it, the code is shown plain.
- **`Debug\Reflexion` is renamed `Support\Reflection`**: `get()`, `set()`, `invoke()` (arguments by value), `properties()`, `property()`, `method()`. `invokeArgs()`, `getReflectionClass()`, `getReflectionProperty()`, `getReflectionMethod()`, `getReflectionProperties()` and `getReflectionMethods()` are removed (use PHP's `ReflectionClass`).
- `VarDump::dump()` writes text in the console; `VarDump::html()` and `VarDump::text()` return the dump. Enums, `readonly` and uninitialized properties are shown. A recursive array is cut after 64 levels.

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

## HTTP client

`Neutrino\HttpClient` is rewritten, with an API inspired by `symfony/http-client` (same option names). The 1.3 `Request`, `Provider\Curl`, `Provider\StreamContext`, `Factory`, `Response`, `Header`, `Uri`, parsers and streaming events are removed.

```php
// Kernels: the `httpClient` service (and the `Http` Facade)
protected array $providers = [
    // ...
    Neutrino\Providers\HttpClient::class,
];

// config/http_client.php: default options of the requests
'transport' => 'curl',                          // optional: curl (default with ext-curl), stream, or a Transport class
'base_uri'  => 'https://api.example.com',
'timeout'   => 10,

// 2.0
$response = Http::request('GET', '/users', ['query' => ['page' => 2], 'auth_bearer' => $token]);
$response->getStatusCode();
$response->toArray();
```

| 1.3 | 2.0 |
|---|---|
| `(new Curl())->get($uri, $params)->send()` | `$client->request('GET', $uri, ['query' => $params])` |
| `->post($uri, $params)` / with `['json' => true]` | `request('POST', $uri, ['body' => $params])` / `['json' => $params]` |
| `setHeaders([...])`, `setHeader($name, $value)` | `['headers' => [...]]` |
| `setProxy($host, $port, $access)` | `['proxy' => 'http://access@host:port']`, `no_proxy` |
| `setTimeout($s)`, `setConnectTimeout($s)` | `['timeout' => $s]` (idle; 0 for none, `default_socket_timeout` by default), `['max_duration' => $s]` (total) |
| `disableSsl()` | `['verify_peer' => false, 'verify_host' => false]` |
| `setCookies([...])` | `['headers' => ['cookie' => 'a=1; b=2']]` |
| `$response->getCode()`, `getBody()`, `getHeader()` | `getStatusCode()`, `getContent()`, `getHeaders()` |
| `isOk()`, `isFail()`, `getError()` | exceptions (below), or `getStatusCode()` with `$throw = false` |
| `parse(Json::class)` / `parse(JsonArray::class)` | `toArray()` |
| `parse(Xml::class)` | `simplexml_load_string($response->getContent())` |
| streaming events (`stream:start`, `progress`, `finish`) | `['buffer' => false]` + `foreach ($response->chunks() as $chunk)`, `on_progress` |

- **HTTP errors throw**: `getHeaders()`, `getContent()`, `toArray()` and `chunks()` throw `RedirectionException`, `ClientException` or `ServerException` on a 3xx, 4xx or 5xx status (`$throw = false` returns them). Network errors throw `TransportException`. All implement `Exception\HttpClientExceptionInterface`.
- **Safe defaults**: TLS checked (1.3 `StreamContext::disableSsl()` did not disable anything); only `http` and `https` URLs, also on redirections; `Authorization`, `Cookie` and `Proxy-Authorization` are not sent to another origin on a redirection; 20 redirections at most.
- The request is sent when the response is first read; a response never read is sent when it is destroyed (its errors are ignored).
- `body` given as a resource or an iterable is read in memory before sending.
- Tests: `new HttpClient([], new MockTransport([new MockResponse('...'), MockResponse::json([...], 201), MockResponse::error('...')]))`, or a callback building the response from the `Request`; `getRequests()` returns the requests sent. With the Facade: `Http::swap(new HttpClient([], $transport))`.

## Removed

- `Neutrino\Assets` and the `assets:js` / `assets:sass` tasks.
- `Neutrino\Optimizer`, `Neutrino\PhpPreloader`, `Config\ConfigPreloader`, `Config\ReturnConverter`: `optimize` now dumps an authoritative Composer classmap and generates `bootstrap/compile/preload.php`, to declare in `opcache.preload`.
