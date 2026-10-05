# Benchmarks

Measures the cost of a request for each kind of kernel, to check that Nucleon 2.x
is not slower nor more memory hungry than 1.3 (see `docs/upgrade-2.0/CONVENTIONS.md`).

Each iteration runs `scenario.php` in a fresh PHP process, with OPcache enabled
and a file cache, so that compiled code is reused between processes as it would
be between PHP-FPM requests. Scenarios are interleaved to spread machine-wide
noise evenly.

| Scenario | Measures |
|---|---|
| `boot-http`, `boot-cli`, `boot-micro` | Dotconst + config + `Bootstrap::make()` + `boot()` |
| `http` | full HTTP request: route → controller → response sent |
| `http-mw1`, `http-mw3` | same request, with 1 and 3 route middlewares |
| `micro` | full Micro request |
| `cli` | full CLI task |
| `service` | first resolution of a provider-registered shared service |

## Running

Measure on the container filesystem: on macOS, the Docker bind mount makes
each file inclusion 2 to 3 times slower and hides everything else.

```bash
# 2.x: the framework installed without dev dependencies, as an application installs it
docker compose run --rm -w /app/bench/.current php8 composer install --no-dev
docker compose run --rm php8 sh -c 'mkdir -p /tmp/app && cp -a /app/src /app/composer.json /app/bench /tmp/app/ \
    && cd /tmp/app && php bench/run.php --autoload=bench/.current/vendor/autoload.php --compare=bench/baseline-1.3.json'

# 1.3 baseline (PHP 7.3 + Phalcon 3.4), with the 1.3 application frozen in bench/.legacy/app
docker compose --profile legacy run --rm -w /app/bench/.legacy legacy composer install
docker compose --profile legacy run --rm legacy sh -c 'mkdir -p /tmp/app && cp -a /app/bench /tmp/app/ && cd /tmp/app \
    && php bench/run.php --autoload=bench/.legacy/vendor/autoload.php --app=bench/.legacy/app --out=/tmp/app/baseline.json \
    && cat /tmp/app/baseline.json' > bench/baseline-1.3.json
```

Options: `--scenarios=http,micro`, `--iterations=200`, `--warmup=20`, `--app=DIR`, `--out=FILE`, `--compare=FILE`.

`bench/app` is the measured application, ported along with the framework.
`bench/.legacy/app` is the same application with the 1.3 API. `run.php` and
`scenario.php` must stay PHP 7.3 compatible.

The `http`, `micro` and `cli` scenarios run on 2.x once their kernels are
ported (E4, E5, E6). Until then, use `--scenarios=boot-http,boot-cli,boot-micro,service`.

## Comparing with 1.3 on a noisy machine

`bench/compare.sh` runs 1.3 and 2.x at the same time (two containers) and alternates their
processes one by one, so that machine-wide noise affects both equally. Prefer it to
`--compare` when the machine is loaded.

```bash
bench/compare.sh http http-mw1 service            # default Composer autoloaders
bench/compare.sh --optimize http service          # each version as deployed (its `optimize`)
```

With `--optimize`, 1.3 runs with its compiled loader and classes (`bench/tools/legacy-optimize.php`),
and 2.x with an authoritative classmap, its caches and an OPcache preload script
(`bench/tools/current-optimize.php`, limited to the modules ported so far).

## Baseline 1.3

`baseline-1.3.json`: PHP 7.3.33, Phalcon 3.4.5, 200 iterations, Docker on macOS,
container filesystem, default Composer autoloader. Two consecutive runs differ
by less than 2.5 % on every scenario (median).
