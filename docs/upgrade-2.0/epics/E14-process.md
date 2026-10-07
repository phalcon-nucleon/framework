# E14 — Process

**Statut** : Terminé · **Dépend de** : E0 · **Bloque** : E6-S5 (`server:run`), E12-S2 (test d'erreur fatale), E13-S2 (serveur de test local)

## Objectif

Porter `Neutrino\Process` (lancement et suivi de processus système via `proc_open`) sur PHP 8.3, corriger ses bugs de délai d'attente, et le compléter de ce qui manque pour un usage sûr : commande sans shell, code de sortie, variables d'environnement, entrée standard. On reste sans dépendance (pas de `symfony/process`).

Phalcon 5 n'a pas de composant pour lancer des processus (vérifié dans l'index de son API).

## Périmètre

- `src/Neutrino/Process/Process.php` (299 lignes), `Exception.php`, `Timeout.php`
- Utilisateurs dans le framework : `Foundation/Cli/Tasks/ServerTask.php` (E6), et dans les tests : `tests/Test/HttpClient/Provider/TraitWithLocalServer.php` (E13), `tests/Test/Cli/Tasks/ServerTaskTest.php` (E6). `Assets/SassCompiler.php` est supprimé par E1.
- Tests : `tests/Test/Process/ProcessTest.php`
- **Hors périmètre** :
  - pseudo-terminal (TTY/PTY) ;
  - pool de processus parallèles ;
  - Windows en CI (Phalcon y est rare). Le code garde `bypass_shell` pour Windows, sans garantie testée.

## Constats sur la 1.3

| Constat | Où |
|---|---|
| **Bug** : la condition de délai d'attente de `wait()` est inversée (`($start + $timeout) > microtime(true)`). La méthode rend la main dès le premier tour de boucle, tant que le délai **n'est pas** écoulé. | `Process::wait()` |
| **Bug** : `wait()` et `watch()` mélangent les unités : `$timeout` et `$step` en millisecondes, mais additionnés à `microtime(true)`, en secondes. | `Process::wait()`, `watch()` |
| **Bug** : `stop()` calcule `microtime(true) + ($timeout * 1000)` : un délai de 1 000 ms devient 1 000 000 s. Si le processus ignore SIGTERM, `stop()` ne rend jamais la main, et il n'y a pas de SIGKILL en dernier recours. | `Process::stop()` |
| Le code de sortie n'est pas récupérable. `proc_get_status()` ne le renvoie qu'au premier appel après la fin du processus, et il n'est pas conservé. | `Process::readStatus()` |
| La commande est uniquement une chaîne, passée au shell. Avec un argument venu de l'extérieur, c'est une injection de commande possible. Depuis PHP 7.4, `proc_open` accepte un tableau, exécuté sans shell. | `Process::__construct()` |
| Pas de variables d'environnement (`null` passé en dur), ni d'entrée standard. | `Process::start()` |
| `exec()` ne renvoie rien. Un échec (code ≠ 0) n'est visible que par la sortie d'erreur. | `Process::exec()` |

## Décisions

- **API inspirée de `symfony/process`, en plus léger**, avec la même démarche qu'E13 : des noms familiers, et seulement les fonctions utiles. Toutes les durées sont en **secondes** (`float`).

  ```php
  $process = new Process([PHP_BINARY, '-S', '127.0.0.1:8000'], cwd: $dir, env: ['APP_ENV' => 'test']);
  $process->start();
  $process->waitUntil(fn (string $out, string $err) => str_contains($err, 'started'), timeout: 5.0);
  // …
  $process->stop(timeout: 2.0); // SIGTERM puis SIGKILL après 2 s

  $code = (new Process(['git', 'status']))->run();   // bloquant, renvoie le code de sortie
  (new Process(['composer', 'dump-autoload']))->mustRun(); // lève ProcessFailedException si code ≠ 0
  ```

- **Commande** : `list<string>` (exécutée sans shell, recommandé) ou `string` (passée au shell, documentée comme dangereuse si elle contient des données externes).
- **Méthodes** :
  - lancement et attente : `start()`, `run(?callable $onOutput = null): int`, `mustRun()`, `wait(?float $timeout = null): int`, `waitUntil(callable $condition, ?float $timeout = null): bool`, `watch(callable $callback, ?float $timeout = null)` (conservé) ;
  - arrêt : `stop(float $timeout = 10.0, int $signal = 15): ?int`, avec la valeur numérique de SIGTERM et SIGKILL en repli, pour ne pas dépendre de l'extension `pcntl` ;
  - état : `isRunning()`, `isSuccessful()`, `getExitCode(): ?int`, `getPid(): ?int` ;
  - sorties : `getOutput()`, `getErrorOutput()`, `getIncrementalOutput()`, `getIncrementalErrorOutput()` ;
  - entrée : `setInput(string|resource)`.
- **Exceptions** : `ProcessFailedException` (code ≠ 0, avec la commande et les sorties) et `ProcessTimedOutException` (remplace `Timeout`), sous `Neutrino\Process\Exception`.
- **Renommages** : `exec()` → `run()`, `getError()` → `getErrorOutput()`, `pid()` → `getPid()`. Les anciens noms ne sont pas conservés (version majeure). La correspondance est dans `UPGRADING-2.0.md`.
- Les sorties restent mises en mémoire tampon dans `php://temp` (débordement sur disque au-delà de 1 Mo), ce qui évite les blocages de pipes pleins.
- Taille cible : 400 lignes au maximum.

## Stories

### E14-S1 · Cœur
- Constructeur (commande tableau ou chaîne, `cwd`, `env`, `timeout` global), `start()`, `isRunning()`, mémorisation du code de sortie au premier statut « terminé », `getPid()`, `getExitCode()`, `isSuccessful()`.
- Gestion des erreurs de `proc_open` (programme introuvable, répertoire invalide) en `Exception` avec l'erreur PHP d'origine en exception précédente.

### E14-S2 · Attente, arrêt et délais
- `wait()`, `waitUntil()`, `watch()` avec délais en secondes corrects, `ProcessTimedOutException`.
- `stop()` : SIGTERM, attente, puis SIGKILL. Valeur de retour = code de sortie.
- `run()` et `mustRun()`.
- Tests de non-régression des trois bugs de la 1.3 : `wait()` avec délai sur un processus de 2 s (rend la main après la fin du processus ou après le délai, pas avant), `stop()` sur un processus qui ignore SIGTERM (`trap '' TERM`), unités de `watch()`.

### E14-S3 · Entrées et sorties
- `setInput()` (chaîne ou flux), `getOutput()`, `getErrorOutput()`, sorties incrémentales, callback de `run()` et `watch()` appelé à chaque nouvelle sortie.
- Tests : grosse sortie (> 1 Mo) sans blocage, entrée standard lue par le processus enfant, commande tableau avec arguments contenant espaces et métacaractères shell (aucune interprétation).

### E14-S4 · Utilisateurs du framework
- `ServerTask` (E6) et le serveur de test local des tests HttpClient (E13) passent à la commande tableau et à `waitUntil()`, à la place des `sleep()` fixes.

## Changements cassants pour les apps

- `exec()` → `run()` (renvoie le code de sortie), `getError()` → `getErrorOutput()`, `pid()` → `getPid()`, `Timeout` → `ProcessTimedOutException`.
- Délais en secondes (`float`) au lieu de millisecondes.
- `stop()` envoie SIGKILL si le processus ne s'est pas arrêté dans le délai.

## Critères d'acceptation spécifiques

- La suite `Process` est activée et passe sur Linux (CI) et macOS (poste de développement), y compris sur le job Phalcon 6.
- Les trois bugs de la 1.3 sont couverts par des tests qui échouent sur la 1.3.
- Taille du module ≤ 400 lignes. Aucune dépendance ni extension requise en plus de `proc_open`.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Cœur | Fait | Commande en liste (sans shell) ou en chaîne (shell), `cwd`, `env` (ajouté à celui de PHP, `null` retire une variable), `timeout` de `run()`. Code de sortie mémorisé au premier statut « terminé » (128 + signal pour un processus tué). Erreur de `proc_open` (programme introuvable, répertoire invalide) en `ProcessException`, avec l'erreur PHP en exception précédente. |
| S2 · Attente, arrêt et délais | Fait | Durées en secondes. `wait()`, `waitUntil()` (sur toutes les sorties reçues), `watch()` renvoient le code de sortie ou lèvent `ProcessTimedOutException` sans arrêter le processus ; `run()` l'arrête à son délai. `stop()` : signal, attente, puis SIGKILL (valeurs numériques, sans `pcntl`). Tests des trois bugs de la 1.3 : `wait()` avec délai, unités de `watch()`, `stop()` sur un processus qui ignore SIGTERM (`trap '' TERM`). Un processus encore en cours est arrêté à la destruction de son objet. |
| S3 · Entrées et sorties | Fait | Entrée (chaîne ou flux) passée par un flux temporaire ; sorties écrites par le processus dans des fichiers temporaires ouverts en ajout, lus par le parent avec ses propres descripteurs. Constat : en partageant le descripteur (la 1.3 donnait le même flux `php://temp` à l'enfant), chaque `fseek()` du parent déplaçait aussi la position d'écriture de l'enfant, qui écrasait des données (3 Mo écrits, 2,8 Mo lus). Tests : 3 Mo sur chaque sortie, entrée lue par l'enfant, arguments avec espaces et métacaractères shell non interprétés, sorties incrémentales. |
| S4 · Utilisateurs du framework | Fait | `ServerTask` lance `php -S` en commande liste, sans `close()`. Les serveurs de test d'E13 (`LocalServer`) passent par `Process` et `waitUntil()` sur leur message de démarrage, au lieu de sonder le port. |

Taille : 294 lignes de code hors commentaires (502 avec la documentation), contrôlées par `SizeTest`. Suite `Process` activée (18 tests), verte sur Linux (Docker), macOS (PHP 8.5) et Phalcon 6. La baseline PHPStan, vide, est supprimée : tout le code est analysé au niveau `max`. 1 318 tests verts.
