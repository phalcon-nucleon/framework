#!/usr/bin/env bash
#
# Compares 2.x with 1.3 by running both at the same time, one scenario process at a time, alternating:
# machine-wide noise affects both versions equally. Both run on the container filesystem.
#
#   bench/compare.sh [--optimize] [--iterations=N] scenario...
#
#   --optimize   each version as deployed: 1.3 with its `optimize` (compiled loader and classes),
#                2.x with its own (authoritative classmap, caches, OPcache preload)
#
set -euo pipefail
cd "$(dirname "$0")/.."

optimize=0; iterations=120; scenarios=()
for arg in "$@"; do
    case "$arg" in
        --optimize) optimize=1 ;;
        --iterations=*) iterations="${arg#*=}" ;;
        *) scenarios+=("$arg") ;;
    esac
done
[ ${#scenarios[@]} -eq 0 ] && scenarios=(boot-http http service)

opts="-d opcache.enable_cli=1 -d opcache.file_cache=/tmp/oc -d opcache.validate_timestamps=0 -d error_reporting=24575"
legacy_prepare='mkdir -p /tmp/app /tmp/oc && cp -a /app/bench /tmp/app/'
current_prepare='mkdir -p /tmp/app /tmp/oc && cp -a /app/src /app/composer.json /app/bench /tmp/app/'
legacy_autoload=/tmp/app/bench/.legacy/vendor/autoload.php
current_opts=""
if [ $optimize -eq 1 ]; then
    legacy_prepare+=' && ln -s /tmp/app/bench/.legacy/vendor /tmp/app/bench/.legacy/app/vendor && (cd /tmp/app/bench/.legacy && composer dump-autoload -o -q) && php /tmp/app/bench/tools/legacy-optimize.php >/dev/null'
    legacy_autoload=/tmp/app/bench/tools/legacy-autoload.php
    current_prepare+=' && (cd /tmp/app/bench/.current && composer dump-autoload --no-dev --classmap-authoritative -q) && php /tmp/app/bench/tools/current-optimize.php >/dev/null'
    current_opts="-d opcache.preload=/tmp/app/bench/app/bootstrap/compile/preload.php -d opcache.preload_user=root"
else
    current_prepare+=' && (cd /tmp/app/bench/.current && composer dump-autoload --no-dev -q)'
fi

legacy=$(docker compose --profile legacy run -d --rm legacy sh -c "$legacy_prepare && touch /tmp/ready && sleep 7200")
current=$(docker compose run -d --rm php8 sh -c "$current_prepare && touch /tmp/ready && sleep 7200")
trap 'docker kill "$legacy" "$current" >/dev/null 2>&1' EXIT
until docker exec "$legacy" test -f /tmp/ready 2>/dev/null && docker exec "$current" test -f /tmp/ready 2>/dev/null; do sleep 1; done

measure() { # container php-options scenario autoload app
    docker exec "$1" php $opts $2 /tmp/app/bench/scenario.php "$3" "$4" "$5" \
        | sed -n 's/^@@BENCH@@{"time_ns":\([0-9]*\),"memory_peak":\([0-9]*\)}$/\1 \2/p'
}

tmp=$(mktemp -d)
printf '%-10s %14s %10s %14s %10s %9s %9s\n' scenario '1.3 (µs)' '1.3 (KiB)' '2.x (µs)' '2.x (KiB)' 'Δ time' 'Δ mem'
for s in "${scenarios[@]}"; do
    : > "$tmp/l"; : > "$tmp/c"
    for i in $(seq 1 $((iterations + 10))); do
        measure "$legacy" "" "$s" "$legacy_autoload" /tmp/app/bench/.legacy/app >> "$tmp/l"
        measure "$current" "$current_opts" "$s" /tmp/app/bench/.current/vendor/autoload.php /tmp/app/bench/app >> "$tmp/c"
    done
    median() { tail -n "$iterations" "$1" | sort -n -k"$2" | awk -v k="$2" '{a[NR]=$k} END {print a[int((NR+1)/2)]}'; }
    lt=$(median "$tmp/l" 1); lm=$(median "$tmp/l" 2); ct=$(median "$tmp/c" 1); cm=$(median "$tmp/c" 2)
    awk -v s="$s" -v lt="$lt" -v lm="$lm" -v ct="$ct" -v cm="$cm" 'BEGIN {
        printf "%-10s %14.1f %10.0f %14.1f %10.0f %+8.1f%% %+8.1f%%\n", s, lt/1000, lm/1024, ct/1000, cm/1024, (ct-lt)*100/lt, (cm-lm)*100/lm }'
done
rm -rf "$tmp"
