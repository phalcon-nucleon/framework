# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`nucleon/framework` is the kernel of the Nucleon framework: a layer on top of **Phalcon 5** (namespace `Neutrino\`, PSR-4 from `src/Neutrino/`). It is a library, not an application. Apps are built from the separate `phalcon-nucleon/nucleon` skeleton.

The `2.x` branch is Nucleon 2.0 (`master` is the unmaintained 1.3, Phalcon 3). The 2.0 upgrade plan and its decisions are kept in `docs/upgrade-2.0/` (one file per epic, `CONVENTIONS.md`): read them before changing a design decision.

## Runtime constraints

- PHP **≥ 8.3** and `ext-phalcon` **≥ 5.22**. CI (GitHub Actions) runs PHP 8.3, 8.4 and 8.5 on Phalcon 5.22, plus a non-blocking job on Phalcon 6 (`phalcon/phalcon` package, pure PHP). The machine's `php` may have no Phalcon: run the tests in Docker.
- `declare(strict_types=1)`, full typing, `final` by default, PHPStan level `max` without baseline (see `docs/upgrade-2.0/CONVENTIONS.md`).
- No third-party dependency in `require`: only `ext-phalcon` and PHP extensions. Development tools (`phalcon/debugbar`, `tempest/highlight`) are suggested and optional at runtime.
- Phalcon binds closure service definitions to the container: never `static function` / `static fn` for a service definition.

## Commands

Development happens in Docker (`compose.yaml`: PHP 8.3 + Phalcon 5.22, MySQL, PostgreSQL, Redis):

```bash
docker compose run --rm php8 composer install
docker compose run --rm php8 php bin/phpunit-migrated            # every test suite (what CI runs)
docker compose run --rm php8 vendor/bin/phpunit --testsuite Cache  # one module suite (see phpunit.xml)
docker compose run --rm php8 vendor/bin/phpunit --filter testName
docker compose run --rm php8 vendor/bin/phpstan analyse --memory-limit=1G
docker compose run --rm php8 vendor/bin/php-cs-fixer fix --dry-run --diff
# benchmarks against 1.3: see bench/README.md (bench/compare.sh)
PHP_VERSION=8.4 docker compose build php8                          # other PHP version
```

`tests/bootstrap.php` loads the composer autoloader and calls `Neutrino\Dotconst::load()` on the fake app, which defines constants such as `BASE_PATH`, `APP_ENV` and `APP_DEBUG`. Framework code relies on these constants. CI uses the production `php.ini` (`zend.exception_ignore_args`, `output_buffering`): tests must not depend on the development defaults. `rector.php` holds the mechanical rules used during the upgrade (never applied in CI); `resources/rector/upgrade-2.0.php` is the configuration given to the apps.

## Architecture

**Boot sequence.** `Foundation\Bootstrap::make($kernelClass)` instantiates a kernel, calls `bootstrap($config)`, registers the error handler (outside the `test` environment), then `registerServices` → debug mode (`APP_DEBUG`, outside the console) → `registerMiddlewares` → `registerListeners` → `registerRoutes` → `registerModules`. `run()` then calls `boot` → `handleIncoming` (reads the request URI or the command line) → `terminate`. The shared logic lives in the `Foundation\Kernelize` trait, used by the three kernels `Foundation\Http\Kernel` (extends `Phalcon\Mvc\Application`), `Foundation\Cli\Kernel` (extends `Phalcon\Cli\Console`) and `Foundation\Micro\Kernel` (extends `Phalcon\Mvc\Micro`). Apps configure a kernel declaratively through the typed `$providers`, `$middlewares`, `$listeners`, `$modules`, `$dependencyInjection`, `$eventsManagerClass` and `$errorHandlerLvl` properties.

**Providers (lazy DI).** Entries in `$providers` are one of:
- `'name' => Class::class`: registered directly as a shared service under both the name and the class.
- A `Support\Provider` subclass: sets `$name`, optional `$aliases` and `$shared`, and implements `register()`, which runs only when the service is first resolved.
- A `Support\SimpleProvider` subclass: sets `$class` and optional `$options` and is registered as a Phalcon service definition.
- A class implementing `Interfaces\Providable` directly (`registering()`); `Cli\ProvidesTasks` also declares console commands.

Service names are in `Constants\Services`. Built-in providers are in `src/Neutrino/Providers/`.

**Facades.** `Support\Facades\*` proxy static calls to DI services via `getFacadeAccessor()`. `Facade::setDependencyInjection()` is called during kernel bootstrap. In tests, `Facade::shouldReceive()` / `swap()` install Mockery mocks or instances, and `Facade::clearResolvedInstances()` must run between tests. The IDE helpers (`ide-helper` command, `Support\IdeHelper\Generator`) list the Facades of `Generator::FACADES`.

**Events and middleware.** `Events\Listener` subclasses declare `$listen` (event → method) and/or `$space`, and are attached to the events manager by the kernel. Middlewares are listeners too (`Foundation\Middleware\*`, `Http\Middleware\*`), implementing `Interfaces\Middleware\{Init,Before,After,Finish}Interface`. Event names are in `Constants\Events\*`.

**Compiled / cached artifacts.** Several features read a precompiled file under `BASE_PATH/bootstrap/compile/` when it exists, and fall back to the source otherwise:
- Dotconst: `.const.ini` / `.const.{env}.ini`, with `@php/dir`, `@php/env`, `@php/const` and `@{ref}` extensions.
- Config cache (`config:cache`, validated by `Config\ConfigCompiler`).
- HTTP routes: `bootstrap/compile/http-routes.php` vs `routes/http.php`.
- Volt templates (`view:cache`), models meta-data (`model:cache`).

`optimize` runs them all, dumps an authoritative Composer classmap and writes the OPcache preload script `bootstrap/compile/preload.php`. When changing a feature that has a compiled form, keep both paths consistent.

## Tests

- `tests/Test/` mirrors `src/Neutrino/` (namespace `Test\`); each suite of `phpunit.xml` is listed in `tests/migrated-suites.txt`, run by `bin/phpunit-migrated`.
- `tests/.fake/nucleon.app/` is a fake Nucleon application (namespace `Fake\`) with stub kernels (`StubKernelHttp`, `StubKernelCli`, `StubKernelMicro`…), routes, migrations and `.const*.ini` files.
- Most tests extend `Test\TestCase\TestCase`. It extends `Neutrino\Test\FuncTestCase`, boots `StubKernelHttp` for every test with an in-memory cache config, and cleans its temporary directory afterwards. To use a different kernel, override `kernelClassInstance()`. The test config is shared between the tests of a class: remove what a test merges into it.
- Database tests run on SQLite, MySQL and PostgreSQL (skipped without a server or PDO driver); HTTP client tests run against local `php -S` servers.
- `Neutrino\Test\{TestCase,FuncTestCase,RoutesTestCase}` are part of the public API that apps use for their own tests, so changes to them affect downstream users.

## Versioning

Release notes are in `CHANGELOG-<major.minor>.md` and breaking-change migration notes in `UPGRADING-<major.minor>.md` (`UPGRADING-2.0.md`). Update both when changing public behaviour. The framework version is in `src/Neutrino/Version.php`.
