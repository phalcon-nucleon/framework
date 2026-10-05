# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`nucleon/framework` is the kernel of the Nucleon framework: a layer on top of the **Phalcon 3** C extension (namespace `Neutrino\`, PSR-4 from `src/Neutrino/`). It is a library, not an application. Apps are built from the separate `phalcon-nucleon/nucleon` skeleton.

## Ongoing: 2.0 upgrade

A migration to PHP ≥ 8.3 / Phalcon 5.22 is planned in `docs/upgrade-2.0/` (decisions, conventions, epics). Read `docs/upgrade-2.0/README.md` and `CONVENTIONS.md` before working on the `2.x` branch. The constraints below describe the current 1.3 code on `master`.

## Runtime constraints

- PHP **5.6 – 7.3** with `ext-phalcon ~3.0`. CI (`.travis.yml`) builds cphalcon v3.0.4 to v3.4.5 against PHP 5.6 to 7.3. The machine's default `php` may be much newer and have no Phalcon, so tests need a matching PHP + Phalcon 3 environment.
- Code must stay PHP 5.6 compatible: no scalar or return type hints, no nullable types, no `??=`, and so on. Variadics (`...$params`) are fine.
- PHPUnit 5.x (`PHPUnit_Framework_TestCase`, `setUp()`/`tearDown()` without `void` return types) and Mockery 0.9.

## Commands

```bash
composer install
vendor/bin/phpunit                                   # full suite (bootstrap & config from phpunit.xml)
vendor/bin/phpunit tests/Test/Cache                  # one directory
vendor/bin/phpunit tests/Test/Support/ArrTest.php    # one file
vendor/bin/phpunit --filter testMethodName           # one test
```

There is no linter or build step. `tests/bootstrap.php` loads the composer autoloader and calls `Neutrino\Dotconst::load()` on the fake app, which defines constants such as `BASE_PATH`, `APP_ENV` and `APP_DEBUG`. Framework code relies on these constants.

## Architecture

**Boot sequence.** `Foundation\Bootstrap::make($kernelClass)` instantiates a kernel, then calls `bootstrap($config)` → `registerServices` → `registerMiddlewares` → `registerListeners` → `registerRoutes` → `registerModules`. `run()` then calls `boot` → `handle` → `terminate`. The shared logic lives in the `Foundation\Kernelize` trait, used by the three kernels `Foundation\Http\Kernel` (extends `Phalcon\Mvc\Application`), `Foundation\Cli\Kernel` (extends `Phalcon\Cli\Console`) and `Foundation\Micro\Kernel` (extends `Phalcon\Mvc\Micro`). Apps configure a kernel declaratively through the `$providers`, `$middlewares`, `$listeners`, `$modules`, `$dependencyInjection`, `$eventsManagerClass` and `$errorHandlerLvl` properties.

**Providers (lazy DI).** Entries in `$providers` are one of:
- `'name' => Class::class`: registered directly as a shared service under both the name and the class.
- A `Support\Provider` subclass: sets `$name`, optional `$aliases` and `$shared`, and implements `register()`, which runs only when the service is first resolved.
- A `Support\SimpleProvider` subclass: sets `$class` and optional `$options` and is registered as a Phalcon service definition.

Service names are in `Constants\Services`. Built-in providers are in `src/Neutrino/Providers/`.

**Facades.** `Support\Facades\*` proxy static calls to DI services via `getFacadeAccessor()`. `Facade::setDependencyInjection()` is called during kernel bootstrap. In tests, `Facade::shouldReceive()` / `swap()` install Mockery mocks, and `Facade::clearResolvedInstances()` must run between tests.

**Events and middleware.** `Events\Listener` subclasses declare `$listen` (event → method) and/or `$space`, and are attached to the events manager by the kernel. Middlewares are listeners too (`Foundation\Middleware\*`, `Http\Middleware\*`), implementing `Interfaces\Middleware\{Init,Before,After,Finish}Interface`. Event names are in `Constants\Events\*`.

**Compiled / cached artifacts.** Several features read a precompiled file under `BASE_PATH/bootstrap/compile/` when it exists, and fall back to the source otherwise:
- Dotconst: `.const.ini` / `.const.{env}.ini`, with `@php/dir`, `@php/env`, `@php/const` and `@{ref}` extensions.
- Config cache.
- HTTP routes: `bootstrap/compile/http-routes.php` vs `routes/http.php`.

The CLI tasks in `Foundation\Cli\Tasks` (`optimize`, `config:cache`, `route:cache`, `dotconst:cache`, `clear-compiled`, …) generate these files. `Optimizer\Composer` converts the composer autoloader to a Phalcon loader. `PhpPreloader` uses nikic/php-parser to merge frequently used classes into one file, stripping `use` statements and converting `array()` to `[]`. When changing a feature that has a compiled form, keep both paths consistent.

## Tests

- `tests/Test/` mirrors `src/Neutrino/` (namespace `Test\`).
- `tests/.fake/nucleon.app/` is a fake Nucleon application (namespace `Fake\`) with stub kernels (`StubKernelHttp`, `StubKernelCli`, `StubKernelMicro`), routes, migrations and `.const*.ini` files.
- Most tests extend `Test\TestCase\TestCase`. It extends `Neutrino\Test\FuncTestCase`, boots `StubKernelHttp` for every test with an in-memory cache config, and cleans `tests/.data/` afterwards. To use a different kernel, override `kernelClassInstance()`.
- `Neutrino\Test\{TestCase,FuncTestCase,RoutesTestCase}` are part of the public API that apps use for their own tests, so changes to them affect downstream users.

## Versioning

Release notes are in `CHANGELOG-<major.minor>.md` and breaking-change migration notes are in `UPGRADING.md`. Update both when changing public behaviour. The framework version is in `src/Neutrino/Version.php`.
