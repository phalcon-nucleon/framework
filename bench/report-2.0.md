# Nucleon 2.0: performance report

Nucleon 2.0 (PHP 8.3.35, Phalcon 5.22.1) against Nucleon 1.3 (PHP 7.3.33, Phalcon 3.4.5), on the same
application (`bench/app`, and its 1.3 version `bench/.legacy/app`).

## Method

- `bench/compare.sh`: 1.3 and 2.0 run at the same time in two containers, their processes alternating one by one,
  so that the noise of the machine affects both. Each iteration is a fresh PHP process with OPcache (file cache),
  as between PHP-FPM requests. 120 iterations after 10 warm-up ones, medians.
- Docker on macOS, container filesystem. The medians of two runs differ by up to 5 % (the noise margin of E0).
- Two configurations:
  - **deployed**: each version with its `optimize` (1.3: compiled loader and classes; 2.0: authoritative classmap,
    compiled configuration, routes and constants, OPcache preload script);
  - **default**: the default Composer autoloaders, nothing compiled.
- Scenarios: see `bench/README.md` (boot of the kernels, full HTTP / Micro / CLI requests, route middlewares,
  `ThrottleRequest`, first resolution of a service, Volt rendering, models on SQLite in memory, cache).

## Deployed (`--optimize`)

| Scenario | 1.3 | 2.0 | Time | Memory |
|---|---|---|---|---|
| boot-http | 169 µs | 178 µs | +6 % | −36 % |
| boot-cli | 314 µs | 233 µs | −26 % | −35 % |
| boot-micro | 165 µs | 181 µs | +9 % | −36 % |
| http | 240 µs | 315 µs | +31 % | −31 % |
| http-mw1 | 273 µs | 342 µs | +25 % | −30 % |
| http-mw3 | 282 µs | 346 µs | +23 % | −29 % |
| http-throttle | 435 µs | 441 µs | +2 % | −29 % |
| micro | 194 µs | 232 µs | +20 % | −33 % |
| cli | 443 µs | 316 µs | −29 % | −33 % |
| service | 4.1 µs | 6.5 µs | +2.4 µs | −36 % |
| view | 174 µs | 178 µs | +2 % | −29 % |
| view-nostat | 165 µs | 175 µs | +6 % | −29 % |
| model-load | 52 µs | 61 µs | +17 % | −34 % |
| model-find-first | 235 µs | 301 µs | +28 % | −30 % |
| model-find-100 | 731 µs | 967 µs | +32 % | −31 % |
| cache | 89 µs | 108 µs | +21 % | −33 % |
| cache-100 | 182 µs | 538 µs | +196 % | −31 % |

The default autoloaders run was made before the last change of E15 (see "An events listener"), which cost
about 20 µs on the HTTP scenarios: the HTTP rows of this table are pessimistic.

## Default autoloaders

| Scenario | 1.3 | 2.0 | Time | Memory |
|---|---|---|---|---|
| boot-http | 666 µs | 803 µs | +21 % | +6 % |
| boot-cli | 714 µs | 832 µs | +17 % | +5 % |
| boot-micro | 706 µs | 802 µs | +14 % | +3 % |
| http | 744 µs | 943 µs | +27 % | +8 % |
| http-mw1 | 849 µs | 1063 µs | +25 % | +7 % |
| http-mw3 | 853 µs | 1066 µs | +25 % | +7 % |
| http-throttle | 1059 µs | 1258 µs | +19 % | +3 % |
| micro | 732 µs | 843 µs | +15 % | +3 % |
| cli | 845 µs | 1006 µs | +19 % | +3 % |
| service | 4.5 µs | 6.8 µs | +2.3 µs | +6 % |
| view | 182 µs | 226 µs | +24 % | +5 % |
| view-nostat | 178 µs | 224 µs | +26 % | +5 % |
| model-load | 89 µs | 161 µs | +81 % | +2 % |
| model-find-first | 255 µs | 383 µs | +50 % | +1 % |
| model-find-100 | 783 µs | 1100 µs | +40 % | +1 % |
| cache | 93 µs | 142 µs | +53 % | +1 % |
| cache-100 | 195 µs | 615 µs | +215 % | −3 % |

## Analysis

**Memory**: −29 to −38 % on every scenario as deployed (1.3 compiled all its classes into one file).

**Console**: faster than 1.3 (−26 % to −29 %).

**HTTP, Micro, models, cache**: slower than 1.3. The budget of `CONVENTIONS.md` ("not slower than 1.3") is not met
on these scenarios. The causes were measured epic by epic, and come from Phalcon 5:

- request (E4): `Router::handle()` (+20 µs, index of the routes built on the first request), `Dispatcher::dispatch()`
  (+50 µs) and `Response::send()` (+6 µs) of Phalcon 5, without Nucleon; Nucleon itself adds nothing to `handle()`
  and is faster at boot;
- models (E10): the Phalcon 5 models (meta-data, `findFirst`);
- cache (E7): the `memory` adapter of Phalcon 5 serializes each value (`cache-100`: 100 set + get);
- Volt (E9): rendering on par with 1.3 once compiled (`view`).

These gaps were **accepted** on 6 October 2026 (`docs/upgrade-2.0/README.md`), with the figures measured then
(request +45 µs). The request now costs +75 µs. Measured commit by commit (E4, E7, E10, E12, HEAD):

1. **The measured application grew** with the epics (configuration of the cache, database and views, routes,
   kernels, list of preloaded classes): most of the difference. 1.3 measures a few µs more on it, 2.0 more because
   it reads its configuration and registers its services differently.
2. **The framework**, on the application of E4: the current code measures as E4 (boot 146 µs against 142 µs,
   request 284 µs against 281 µs), within the noise.

**An events listener**: during E15, the view of the actions (`view.implicit` false) was first put in the response
by a listener on `application:beforeSendResponse`. Measured: +20 µs on every request, with or without a view (as
soon as an application event has a listener, Phalcon builds an `Event` object for each application event). It
was replaced by a call in `handleIncoming()`; only the debug mode (`phalcon/debugbar`) keeps the listener.
**Lesson**: no listener on the application events in the request path of the framework.

**Decision** (7 October 2026): these gaps are accepted as the cost of Phalcon 5. The optimizations listed in the
decision of 6 October (pure PHP `memory` store, lazy reading of the model attributes, bypass of the dispatcher) stay
possible in 2.x.

## For reference: Laravel

Same container (PHP 8.3), one process per request, OPcache file cache, 200 iterations, medians. A minimal "Hello"
route; Laravel 13.35 deployed (`APP_ENV=production`, `APP_DEBUG=false`, `composer --no-dev --classmap-authoritative`,
`php artisan optimize`) and with an OPcache preload script of the 372 files a request includes.

| Request | Time | Requests/s (one core) | Memory |
|---|---|---|---|
| Nucleon 2.0 | 0.30 ms | ~3 330 | 481 KiB |
| Laravel 13, outside the `web` group | 1.28 ms | ~780 | 838 KiB |
| Laravel 13, `web` group (session, cookies, CSRF) | 3.0 ms | ~330 | 996 KiB |

Without the preload script, each Laravel process loads these files from the OPcache file cache (10 ms): under
PHP-FPM, the shared memory of OPcache makes it closer to the preloaded figure.
