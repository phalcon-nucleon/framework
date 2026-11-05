# E1 — Nettoyage

**Statut** : Terminé · **Dépend de** : E0 · **Bloque** : E2

## Objectif

Supprimer ce que PHP 8.3, OPcache et Phalcon 5 rendent inutile, avant tout portage, pour réduire le volume de code à migrer.

## Périmètre

| Élément | Taille | Action |
|---|---|---|
| `src/Neutrino/Optimizer/` | 3 fichiers, 364 lignes | Supprimé |
| `src/Neutrino/PhpPreloader/` | 8 fichiers, 366 lignes | Supprimé |
| `src/Neutrino/Assets/` + `AssetsJsTask`, `AssetsSassTask` | 10 fichiers, ~590 lignes | Supprimés |
| `Config/ConfigPreloader.php`, `Config/ReturnConverter.php` | 150 lignes | Remplacés (voir S3) |
| `Foundation/Cli/Tasks/OptimizeTask.php` + `Optimize/compile.php` | 254 lignes | Réécrits (voir S2) |
| `Foundation/Cli/Tasks/RouteCacheTask.php` | 119 lignes | Allégé (voir S4) |
| Code de compatibilité PHP < 8 | ponctuel | Supprimé (voir S5) |
| `Support\Str`, `Arr`, `Obj`, `Func`, `Path` | ~1 500 lignes | Revus (voir S6) |
| Tests associés | `tests/Test/Optimizer` (3), `Preloader` (5), `Assets` (7) | Supprimés ou réécrits |

**Hors périmètre** : le portage Phalcon 5 des fichiers conservés (E2 et suivants).

## Ruptures Phalcon 3 → 5

- `Phalcon\Loader` (utilisé par `Optimizer`) n'existe plus (`Phalcon\Autoload\Loader`). Le module est supprimé, donc rien à porter.
- `RouteCacheTask` lit par réflexion des propriétés internes du router (`_defaultModule`, `_defaultParams`…). En Phalcon 5, elles n'ont plus de préfixe `_`. La correction est faite dans E4. Ici, on retire seulement la dépendance au preloader.

## Décisions

- **Optimizer et PhpPreloader** : OPcache (avec `opcache.validate_timestamps=0` en production), `opcache.preload` et `composer dump-autoload --classmap-authoritative --apcu` font mieux que la fusion de classes en un seul fichier. La traduction de l'autoloader Composer vers un autoloader Phalcon n'apporte plus rien avec une classmap faisant autorité et OPcache. `nikic/php-parser` disparaît des dépendances.
- **Assets** : la compilation JS (Closure Compiler) et Sass relève de l'outillage front (Vite, esbuild…). `Phalcon\Assets\Manager` reste disponible via le provider View.
- **Cache de config** : plus d'analyse du code source des fichiers de config. Le cache est un `var_export()` du tableau de config chargé, comme `config:cache` dans Laravel. Les expressions (`BASE_PATH . '/x'`) sont donc évaluées au moment du cache. C'est déjà le comportement attendu depuis la 1.3, qui interdit `__DIR__` et `__FILE__` dans les configs.
  - **Décidé** : une valeur non exportable (closure, objet) dans la config fait échouer `config:cache` avec un message explicite qui indique le fichier et la clé.
- **Helpers `Support`** (décidé) : on garde les helpers qui apportent une valeur que PHP n'a pas (notation pointée de `Arr::get` / `set` / `has` / `forget`, `Str::slug`, `snake`, `camel`, `studly`, aiguilles multiples dans `contains` / `startsWith`, `Path::findRelative`…) et on les réimplémente sur les fonctions natives de PHP 8. On supprime ce qui est déprécié ou n'est plus qu'un alias d'une fonction native.

## Stories

### E1-S1 · Suppression d'Optimizer, PhpPreloader et Assets
- Supprimer les répertoires, les tâches `AssetsJsTask` et `AssetsSassTask`, leur déclaration dans `Providers/Cli/Router.php` et les tests associés.
- Retirer `nikic/php-parser` de `composer.json`.
- `grep` de contrôle : plus aucune référence à `PhpPreloader`, `PhpParser`, `Optimizer\` ou `Neutrino\Assets`.

### E1-S2 · Nouvelle tâche `optimize`
- Enchaîne `config:cache`, `route:cache` et `dotconst:cache`, comme aujourd'hui (`$compileTasks`).
- Génère `bootstrap/compile/preload.php`, un script pour `opcache.preload`. Il charge la classmap Composer filtrée : classes Nucleon du chemin d'exécution d'une requête, classes de l'app sous `app/`, et une liste configurable.
- Affiche les réglages OPcache recommandés et lance `composer dump-autoload --classmap-authoritative` (option `--apcu`).
- `clear-compiled` supprime aussi `preload.php`.
- Retour du script de mesures d'E0 : comparer « optimize 1.3 » et « optimize 2.0 ».

### E1-S3 · Cache de config sans analyse de code
- `ConfigCacheTask` : `Config\Loader::raw()` → contrôle des valeurs exportables → écriture de `bootstrap/compile/config.php` (`<?php return [...];`), en écriture atomique (fichier temporaire puis `rename`).
- Suppression de `ConfigPreloader` et `ReturnConverter`. `Config\Loader::fromCompile()` est inchangé.
- Tests : config avec constantes, tableaux imbriqués, valeur non exportable (erreur).

### E1-S4 · `route:cache` sans pretty-print
- Retirer le passage par le preloader. Le code PHP généré est écrit tel quel.
- Le reste (lecture des propriétés internes du router) est porté dans E4.

### E1-S5 · Suppression du code de compatibilité PHP < 8
- `Dotconst/Helper.php:70` (`PHP_VERSION_ID < 70000`).
- `Foundation/Cli/Tasks/ServerTask.php:54-55` (validation de l'hôte).
- `Foundation/Cli/Kernel.php:100` (contournement PHP 5.6 sur `setArgument`).
- `Support/Str.php:520-526` (cascade `random_bytes` / sodium / openssl / mcrypt) → `random_bytes()` seul.
- Recherche de contrôle : `PHP_VERSION`, `version_compare`, `function_exists` sur des fonctions natives de PHP 8.

### E1-S6 · Revue des helpers `Support`
- Inventaire de chaque méthode publique de `Str`, `Arr`, `Obj`, `Func` et `Path`. Pour chacune : conservée (réimplémentée en natif), dépréciée ou supprimée. Le tableau est reporté dans `UPGRADING-2.0.md`.
- Suppressions déjà identifiées :
  - `Str::normalizePath` (déprécié depuis la 1.3, remplacé par `Path::normalize`) ;
  - `Str::quickRandom` (aléatoire non cryptographique, remplacé par `Str::random`).
- Les méthodes conservées sont typées et s'appuient sur les fonctions natives (`str_contains`, `str_starts_with`, `str_ends_with`, `array_is_list`, `mb_*`).
- Comparer avec `Phalcon\Support\Helper\Str\*` et `Arr\*` : quand Phalcon fait la même chose, déléguer si c'est plus rapide (mesure à l'appui), sinon garder l'implémentation native.

## Changements cassants pour les apps

- Suppression des tâches `assets:js` et `assets:sass` et du namespace `Neutrino\Assets`.
- `optimize` ne génère plus `bootstrap/compile/loader.php` ni le fichier de classes compilées. Il génère `preload.php`, à déclarer dans `opcache.preload`.
- `config:cache` évalue la config : pas de closure ni d'objet dans `config/*.php`.
- Helpers supprimés : voir le tableau produit par S6.

## Critères d'acceptation spécifiques

- `composer why nikic/php-parser` ne renvoie rien.
- `optimize` puis `clear-compiled` fonctionnent sur l'app `tests/.fake`.
- Les mesures d'une requête HTTP avec `optimize` + preload ne sont pas moins bonnes que celles de la 1.3 avec son `optimize`.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Suppressions | Fait | `Optimizer`, `PhpPreloader`, `Assets`, les tâches `assets:*`, `ConfigPreloader`, `ReturnConverter` et leurs tests (Optimizer, Preloader, Assets, ancien `OptimizeTaskTest`) supprimés. Plus aucune référence dans `src/` ni `tests/`. |
| S2 · `optimize` | Fait | `Foundation\Optimize\PreloadGenerator` (+ `PreloadResult`) : classes candidates tirées de la classmap Composer (namespaces `Neutrino\` hors CLI, debug, migrations et tests, plus les classes sous `app/`), chacune chargée une fois à la génération ; celles qui échouent sont écartées avec leur raison. Une erreur fatale de compilation PHP (signature incompatible) ne peut pas être interceptée : elle arrête la génération, jamais le serveur. Configurable via `config/optimize.php` (`preload.namespaces`, `paths`, `excludes`). `OptimizeTask` : `composer dump-autoload --classmap-authoritative` (`--apcu`, `--no-dev`, `--composer=`, `--no-dump`), caches config/dotconst/routes, script de preload, réglages OPcache recommandés. `clear-compiled` supprime aussi `preload.php` (et les fichiers de la 1.x). Tests : 5, dont l'exécution réelle du script généré. Les tests de la tâche elle-même relèvent d'E6. |
| S3 · Cache de config | Fait | `Config\ConfigCompiler` (`compile()`, `clear()`) : `var_export` de la config évaluée, écriture atomique (`Support\AtomicFile`, qui invalide aussi OPcache). Les enums sont acceptés. Une closure ou un objet lève `UncacheableConfigException` (fichier, clé, type). Tests : 7. |
| S4 · `route:cache` | Fait | Plus de passage par le preloader. Le reste (propriétés du router Phalcon 5, échappement, écriture atomique) relève d'E4-S3. |
| S5 · Compatibilité PHP < 8 | Fait | `Dotconst\Helper` (tri stable maison), `ServerTask` (validation d'hôte, désormais `FILTER_VALIDATE_DOMAIN` + `FILTER_FLAG_HOSTNAME`), `Foundation\Cli\Kernel` (contournement PHP 5.6), `Str::random` (cascade de générateurs). |
| S6 · Helpers `Support` | Fait | `Str`, `Arr`, `Obj`, `Func` et `Path` réécrits (`strict_types`, typage complet, fonctions natives PHP 8, PHPStan niveau max sans baseline). Suite `Helpers` (81 tests) séparée de `Support`, dont le reste relève d'E2. |

Suites ajoutées à `tests/migrated-suites.txt` : `Config`, `Helpers`, `Optimize` (93 tests). Baseline PHPStan : 2 666 → 2 240 erreurs.

### Revue des helpers (pour `UPGRADING-2.0.md`)

| Classe | Supprimé (remplacement) | Conservé | Changements de comportement |
|---|---|---|---|
| `Str` | `length` (`mb_strlen`), `lower` (`mb_strtolower`), `upper` (`mb_strtoupper`), `substr` (`mb_substr`), `title` (`mb_convert_case($v, MB_CASE_TITLE)`), `quickRandom` (`Str::random`), `normalizePath` (`Path::normalize`) | `ascii`, `camel`, `contains`, `endsWith`, `finish`, `is`, `levenshtein`, `limit`, `words`, `parseCallback`, `random`, `replaceFirst`, `replaceLast`, `slug`, `snake`, `startsWith`, `studly`, `capitalize`, `ucfirst` | Paramètres typés (`string`). `replaceFirst`/`replaceLast` avec une recherche vide renvoient la chaîne inchangée. |
| `Arr` | `where` (`array_filter($array, $callback, ARRAY_FILTER_USE_BOTH)`) | toutes les autres méthodes | `pluck` lève `InvalidArgumentException` si une clé n'est pas scalaire. `isAssoc` s'appuie sur `array_is_list`. |
| `Obj`, `Func`, `Path` | — | toutes | `Func::tap` accepte tout `callable` (et non plus seulement une `Closure`). |

Les fonctions Volt `str_length`, `str_lower`, `str_upper`, `str_substr` et `str_title` (via `StrExtension`) disparaissent avec ces méthodes : E9 le prend en compte (filtres Volt natifs `length`, `lower`, `upper`, `slice`, `capitalize`).

### Comparaison avec `Phalcon\Support\Helper` (PHP 8.3, OPcache, ns par appel)

| Helper | Nucleon | Phalcon |
|---|---|---|
| `startsWith` | 176 | 387 |
| `endsWith` | 158 | 371 |
| `camel` | 105 | 239 |
| `snake` | 148 | 223 |
| `slug` | 1 758 (11 275 avant optimisation de `Str::ascii`) | 2 537 |
| `random` | 507 | 2 708 |
| `Arr::get` (clé simple) | 232 | 234 |

Les helpers Nucleon restent plus rapides (les helpers Phalcon sont des objets invocables instanciés à chaque appel) : on ne délègue pas. La mesure a révélé que `Str::slug` était 4 fois plus lent que Phalcon : `Str::ascii` enchaînait environ 150 `str_replace`, remplacés par une table unique et un seul `strtr`.
