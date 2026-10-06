# Release Note

## v2.0.0 (unreleased)

Nucleon 2.0 runs on PHP ≥ 8.3 and Phalcon ≥ 5.22. Migration notes: `UPGRADING-2.0.md`.

### Added
 - `optimize`: authoritative Composer classmap and OPcache preload script (`bootstrap/compile/preload.php`).
 - `Config\ConfigCompiler`: `config:cache` evaluates and validates the configuration.
 - `Support\IdeHelper\Generator`: `_ide_helper.php` (Facades `@method`, `@property-read` of the services on `Phalcon\Di\Injectable`) and `.phpstorm.meta.php` (`DiInterface::get()` / `getShared()` return types), generated from the booted application.
 - `Neutrino\Config\Config`: a `Phalcon\Config\Config` with reads 7 to 9 times faster.
 - `Kernelable::handleIncoming()`.
 - `FuncTestCase::dispatch()`: request headers and JSON body.
 - CLI: `ide-helper` and `dotconst:cache` commands; task documentation attributes (`Neutrino\Cli\Attribute`); `ProvidesTasks`; `NO_COLOR`.
 - Micro handlers `'Controller::action'` and `[Controller::class, 'action']`; `Micro\MiddlewarePosition`.
 - `StatusCode`: 102, 103, 421, 425 and 451; `UNAUTHORIZED`, `UPGRADE_REQUIRED`, `BANDWIDTH_LIMIT_EXCEEDED`.
 - Cache stores `rediscluster` and `weak`, serializer `msgpack`, custom adapters; `Providers\Cache::makeStore()`.
 - Logger: several adapters (`log.adapters`), `log.level`, `line` or `json` formatter.
 - Session: `noop` adapter and any `\SessionHandlerInterface`; session name per store.
 - Model attributes `#[Primary]`, `#[Column]`, `#[Timestamps]`, `#[SoftDelete]`; `Neutrino\Model\MetaDataStrategy`; `models.metadata.adapter`; `model:cache` command; `Db::connection()`; connection adapters by name (`mysql`, `postgresql`, `sqlite`).
 - Authentication on `Phalcon\Auth` (guards, access, `Auth::id()`, `validate()`, `guard()`); `Authenticate` redirection.
 - `view:cache` command (run by `optimize`); `tagFactory` service; `view.php_functions` (`allow` / `deny`).
 - `security.csrf.rotate`, `security.throttle.store`; CSRF token in a JSON body.
 - `app.crypt_signing`; `annotations.adapter` (`memory`, `apcu`, `stream`).
 - Event constants for the Phalcon 5 events: router, di, `db:connectionLost`, dispatcher binding and action calls, micro binding and exceptions, model `prepareSave` and `validation`, view compilation.

### Changed
 - Kernels, providers, modules, listeners, Facades, constants, Dotconst, `Support` helpers, traits and design patterns are typed (`strict_types`).
 - Dotconst compiles constants with `const` (faster than `define()`).
 - `Foundation\Middleware\Disptacher` renamed `Dispatcher`.
 - The `Str` helpers use the PHP 8 string functions; `Str::slug` is 6 times faster.
 - Cache: PSR-16 API (`Phalcon\Cache\CacheInterface`), stores configured with `adapter`, `serializer` and `options`.
 - Session: `Phalcon\Session\Manager` on an adapter; `sessionBag` takes a name.
 - Models described through a Phalcon meta-data strategy; `Repository`: a string is an equality, names and operators checked, typed API.
 - Volt 5 option names (1.3 names converted); `tag` is a `Phalcon\Html\TagFactory`; `csrf_field()` writes its escaped field; `PhpFunctionExtension` refuses dangerous functions; the Nucleon filters and functions compile variables as arguments.
 - `auth` is a `Phalcon\Auth\Manager`; `Auth::attempt()` returns a `bool`.
 - CSRF: only POST, PUT, PATCH and DELETE are checked, token in the `X-CSRF-Token` header or the body; the token stays valid for the session.
 - `RateLimiter`: fixed window, PSR-16 cache, atomic increment on Redis, APCu and Memcached; throttle signature `xxh128`.
 - `crypt`, `security`, `filter` and `escaper` are `Encryption\Crypt`, `Encryption\Security`, `Filter\Filter` and `Html\Escaper`; `crypt` signs by default.

### Fixed
 - `Facade::swap()` and `shouldReceive()` replace a service already resolved by the container.
 - `Singleton`: one instance per subclass.
 - Dotconst: `@php/dir@suffix` and unknown `@{reference}` values in the compiled file.
 - `FuncTestCase::dispatch()`: PATCH parameters in `$_POST`, DELETE parameters kept, superglobals restored.
 - `TestCase::checkExtension()`: the skip message was empty.
 - Route middlewares declared as `[Middleware::class => $parameter]`.
 - Micro: a Before middleware returning `false` stops the request (Phalcon 5 ignores the returned value).
 - `Micro\Router::add()` returns the route; the controller of a Micro route is built once per request.
 - CLI: `help <command>` and `help` alone; task options no longer break actions without parameters; a task run twice in a process reads its current options; `route:list` and `route:cache` no longer replace the console router; output blocks honour their padding.
 - `Repository`: column names, operators and sort directions were written unchecked in the PHQL (injection).
 - `Support\Db::getQueries()` left its listener attached on error and kept the events manager it created.
 - Remember-me: token stored hashed and bound to the user agent, revoked at logout, cookie limited to one year; malformed cookies are ignored.
 - The rate limiter released a client only when it stopped trying (sliding lifetime).
 - Session provider: the construction error kept the previous exception as its code.
 - `route:cache`: route names and hostnames are escaped; routes that cannot be cached are rejected instead of being lost.

### Removed
 - Assets (`Neutrino\Assets`, `assets:*` tasks), `Optimizer`, `PhpPreloader` (and `nikic/php-parser`), `ConfigPreloader`, `ReturnConverter`.
 - `Str::{length, lower, upper, substr, title, quickRandom, normalizePath}`, `Arr::where`.
 - Collection (ODM) and Volt event constants, `Model::NOT_SAVE(D)`.
 - `Micro\Router` methods that threw an exception, `Micro\Middleware::ON_*` constants.
 - Cache: output cache (`start`/`stop`), `queryKeys`, `save`/`exists`; backends `Memcache`, `Mongo`, `Database`, `Aerospike`, `Wincache`, `Xcache`; frontend `Output`.
 - `Database\DatabaseStrategy` (`db` is the default connection, the others `db.<name>`); `Neutrino\Model::metaData()` / `columnMap()`.
 - Volt `{% cache %}` (no output cache in Phalcon 5).
 - `Neutrino\Auth\Manager` (replaced by `Phalcon\Auth\Manager`); CSRF token read from the query string.
 - Logger adapters `Firelogger`, `Udplogger`, `Multiple`; session adapters `Files` (now `stream`) and `Memcache`.
