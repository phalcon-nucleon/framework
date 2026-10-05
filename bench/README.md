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
| `micro` | full Micro request |
| `cli` | full CLI task |
| `service` | first resolution of a provider-registered shared service |

## Running

```bash
# 2.x (current code)
docker compose run --rm php8 php bench/run.php --compare=bench/baseline-1.3.json

# 1.3 baseline (PHP 7.3 + Phalcon 3.4)
docker compose --profile legacy run --rm -w /app/bench/.legacy legacy composer install
docker compose --profile legacy run --rm legacy \
    php bench/run.php --autoload=bench/.legacy/vendor/autoload.php --out=bench/baseline-1.3.json
```

Options: `--scenarios=http,micro`, `--iterations=200`, `--warmup=20`, `--out=FILE`, `--compare=FILE`.

`bench/app` is the measured application. It is written for the 1.3 API and is
ported along with the framework (E2), keeping the same behaviour so that
results stay comparable. `run.php` and `scenario.php` must stay PHP 7.3
compatible.

## Baseline 1.3

`baseline-1.3.json`: PHP 7.3.33, Phalcon 3.4.5, 200 iterations, Docker on macOS.
Two consecutive runs differ by less than 2.5 % on every scenario (median).
