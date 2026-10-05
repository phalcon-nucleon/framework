# Release Note

## v2.0.0 (unreleased)

Nucleon 2.0 runs on PHP ≥ 8.3 and Phalcon ≥ 5.22. Migration notes: `UPGRADING-2.0.md`.

### Added
 - `optimize`: authoritative Composer classmap and OPcache preload script (`bootstrap/compile/preload.php`).
 - `Config\ConfigCompiler`: `config:cache` evaluates and validates the configuration.
 - `Support\IdeHelper\Generator`: `_ide_helper.php` (Facades `@method`, `@property-read` of the services on `Phalcon\Di\Injectable`) and `.phpstorm.meta.php` (`DiInterface::get()` / `getShared()` return types), generated from the booted application.
 - `Neutrino\Config\Config`: a `Phalcon\Config\Config` with reads 7 to 9 times faster.
 - `Kernelable::handleIncoming()`.
 - Event constants for the Phalcon 5 events: router, di, `db:connectionLost`, dispatcher binding and action calls, micro binding and exceptions, model `prepareSave` and `validation`, view compilation.

### Changed
 - Kernels, providers, modules, listeners, Facades, constants, Dotconst, `Support` helpers, traits and design patterns are typed (`strict_types`).
 - Dotconst compiles constants with `const` (faster than `define()`).
 - The `Str` helpers use the PHP 8 string functions; `Str::slug` is 6 times faster.

### Fixed
 - `Facade::swap()` and `shouldReceive()` replace a service already resolved by the container.
 - `Singleton`: one instance per subclass.
 - Dotconst: `@php/dir@suffix` and unknown `@{reference}` values in the compiled file.

### Removed
 - Assets (`Neutrino\Assets`, `assets:*` tasks), `Optimizer`, `PhpPreloader` (and `nikic/php-parser`), `ConfigPreloader`, `ReturnConverter`.
 - `Str::{length, lower, upper, substr, title, quickRandom, normalizePath}`, `Arr::where`.
 - Collection (ODM) and Volt event constants, `Model::NOT_SAVE(D)`.
