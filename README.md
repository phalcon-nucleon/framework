<p align="center"><a href="https://phalcon-nucleon.github.io/" target="_blank"><img width="100" src="https://phalcon-nucleon.github.io/img/nucleon.svg"></a></p>

Nucleon : Phalcon extended framework. (Kernel)
==============================================
[![CI](https://github.com/phalcon-nucleon/framework/actions/workflows/ci.yml/badge.svg?branch=2.x)](https://github.com/phalcon-nucleon/framework/actions/workflows/ci.yml)

> **Note:** This repository contains the core code of the Nucleon framework. If you want to build an application using Nucleon, visit the main [Nucleon repository](https://github.com/phalcon-nucleon/nucleon).

## Requirements

- PHP ≥ 8.3
- Phalcon ≥ 5.22 (the `phalcon` extension)

Phalcon 6 (pure PHP implementation, still a release candidate) is tested by a non-blocking CI job: the whole test suite passes on it. Its official support will be decided once it is stable.

Upgrading from 1.3: [`UPGRADING-2.0.md`](UPGRADING-2.0.md), with a Rector configuration for the mechanical part (`resources/rector/upgrade-2.0.php`). Release notes: [`CHANGELOG-2.0.md`](CHANGELOG-2.0.md).

**Nucleon 1.x is no longer maintained** (Phalcon 3 and PHP ≤ 7.3 are not either). The `v1.3.2` tag stays available.

## About

- Kernels (HTTP, Micro, CLI) with lazy providers: services are built when first used.
- Facades, with generated IDE helpers (`php quark ide-helper`).
- Middlewares: global, per route or per controller; CSRF, throttling (rate limiter on the cache), authentication.
- Models described by attributes or methods, read by a Phalcon meta-data strategy; repositories with checked criteria.
- Cache strategy over several PSR-16 stores; logger on several adapters; sessions on several stores.
- Authentication on `phalcon/auth` (guards, remember-me).
- Migrations: schema builder for MySQL, PostgreSQL and SQLite, batches, rollback, transactions.
- Console: commands documented by attributes, output helpers, questions.
- Volt extensions and functions, `view:cache`.
- HTTP client (no dependency, cURL or streams, mock transport for the tests) and processes.
- Error handler with a debug error page; integration of `phalcon/debugbar` (suggested).
- Production: `php quark optimize` (authoritative classmap, OPcache preload script, configuration, routes, constants and views compiled).
- Dotconst: constants of the `.const.ini` files, compiled.

No third-party dependency is required: `phalcon/debugbar` and `tempest/highlight` are suggested for development.

## Resource
- [Phalcon](https://phalcon.io): Phalcon framework
- [Nucleon icon](http://www.flaticon.com/free-icon/atom_170849). re-colorized by nucleon.
