# E12 — Erreurs & Debug

**Statut** : Rédigé · **Dépend de** : E4 (et E7 pour le Logger) · **Bloque** : E9 (fonction Volt `dump()`), E11 (`--pretend` coloré)

## Objectif

Porter la gestion des erreurs sur PHP 8.3 / Phalcon 5 sans en perdre aucune (y compris les erreurs fatales), et réduire les outils de debug maison à ce que l'écosystème Phalcon ne fournit pas, sans ajouter de dépendance de production.

## Périmètre

| Partie | Fichiers | Lignes |
|---|---|---|
| Gestion des erreurs | `Error/Handler.php`, `Error/Error.php`, `Error/Helper.php` | ~650 |
| Sorties d'erreur | `Error/Writer/{Writable,Phplog,Logger,Flash,View,Json,Cli}.php` | ~420 |
| Debugger | `Debug/Debugger.php`, `DebugEventsManagerWrapper.php`, `DebugErrorLogger.php` | ~480 |
| Barre de debug | `Debug/DebugToolbar.php`, `Debug/resources/bar.html.php` | ~460 |
| Page d'erreur de debug | `Debug/resources/errors.html.php`, `Debug/helpers/functions.php` | ~455 |
| Utilitaires | `Debug/VarDump.php` (473), `Debug/Reflexion.php` (239) | ~710 |
| Middleware | `Foundation/Middleware/Debug.php` (déplacé depuis E4) | 120 |

- Dépendance concernée : `ark4ne/highlight` (2 228 lignes, dépend de `psr/simple-cache`, pas de version publiée : `dev-master` uniquement). Elle sert à colorer le code PHP et SQL de la page d'erreur et le SQL de `migrate --pretend` (E11). Elle est remplacée par `tempest/highlight` (voir Décisions).
- Tests : `tests/Test/Error` (8 fichiers de test), `tests/Test/Debug` (6).

## Constats

| Constat | Où |
|---|---|
| `Handler::register()` n'est appelé nulle part dans le framework. C'est l'app (probablement le squelette) qui doit le faire, et un oubli fait perdre toutes les sorties d'erreur. | `Error/Handler.php` |
| La fonction d'arrêt ne traite que `E_ERROR`. `E_PARSE`, `E_CORE_ERROR` et `E_COMPILE_ERROR` (erreurs fatales de compilation, d'include…) échappent aux sorties d'erreur. | `Handler::register()` |
| `Error\Writer\Logger` applique le formateur sur le logger lui-même. En Phalcon 5, le formateur appartient à chaque adapter (`Logger::getAdapter($name)->setFormatter()`), et les niveaux sont dans `Phalcon\Logger\Enum`. | `Error/Writer/Logger.php`, `Error/Helper::getLogType()` |
| `Debug\Reflexion` est un utilitaire générique de réflexion, utilisé hors du debug (`RouteCacheTask`, `StrExtension`, tests). Depuis PHP 8.1, `setAccessible()` n'est plus nécessaire. | `Debug/Reflexion.php` |
| La barre de debug et l'enregistrement des événements (`DebugEventsManagerWrapper`) doublonnent avec `phalcon/debugbar`, le paquet officiel : 11 panneaux (requête, SQL avec durées, cache, vues, route, session, config, logs, exceptions, versions, mesures), PHP ≥ 8.1, Phalcon 5 et 6, une seule dépendance (`matthiasmullie/minify`), refuse de démarrer en production. | `Debug/*` |
| `Foundation\Middleware\Debug` écrit un log pour chaque événement d'application et de dispatch. C'est un doublon de la barre de debug et du profilage d'événements. | `Foundation/Middleware/Debug.php` |
| L'en-tête de licence de `Error/Handler.php` est copié de l'incubator Phalcon. Le fichier est à réécrire. | `Error/Handler.php` |

## Décisions

- **Gestion des erreurs** :
  - `Bootstrap::make()` enregistre automatiquement `Handler` (désactivable par `error.register = false`) ;
  - la fonction d'arrêt traite `E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR` et `E_RECOVERABLE_ERROR` ;
  - `Handler` reçoit des `\Throwable` (et non plus seulement des `\Exception`) ;
  - `Error` devient un objet valeur `readonly`.
- **Les sorties d'erreur sont conservées** : `Phplog`, `Logger`, `Flash`, `View` (contrôleur ou vue d'erreur configurables), `Json` et `Cli`. Elles sont typées et portées sur Phalcon 5 (Logger : formateur par adapter, niveaux `Logger\Enum`).
- **Barre de debug : remplacée par `phalcon/debugbar`, si l'étude S1 confirme.**
  - Le paquet est mis en `suggest` (outil de développement) et non en `require` : aucune dépendance de production n'est ajoutée.
  - Le Debugger Nucleon se contente de l'enregistrer quand `APP_DEBUG` est vrai et que le paquet est installé.
  - Sont supprimés : `DebugToolbar`, `bar.html.php`, `DebugEventsManagerWrapper`, l'enregistrement des profileurs, et `Foundation\Middleware\Debug`.
- **Page d'erreur de debug conservée.** Elle apporte la chaîne d'exceptions complète, les extraits de code colorés et les erreurs PHP survenues pendant la requête, ce que `Phalcon\Support\Debug` fait moins bien (ses ressources sont chargées depuis un CDN). Elle perd les panneaux « événements » et « profileurs », qui passent à la barre de debug.
- **`VarDump` conservé.** Ni PHP ni Phalcon ne fournissent d'équivalent, et `symfony/var-dumper` contredit la politique de dépendances. On le type et on prend en charge les énumérations et les propriétés `readonly`. La fonction Volt `dump()` (E9) continue de l'utiliser.
- **Coloration syntaxique : `tempest/highlight` en `suggest`, à la place d'`ark4ne/highlight`** (décidé). La coloration ne sert qu'en développement (page d'erreur de debug, `migrate --pretend`), donc une bibliothèque tierce est acceptable tant qu'elle reste hors des dépendances de production. Comparaison faite pour cette décision :

  | Bibliothèque | PHP | Dépendances | Lignes | Sortie HTML | Sortie terminal (ANSI) |
  |---|---|---|---|---|---|
  | `tempest/highlight` | `^8.3` jusqu'à la 2.12.x (maintenue), `^8.4` ensuite | aucune | ~12 500 | oui (classes CSS + thèmes fournis) | oui (`LightTerminalTheme`, thèmes personnalisables) |
  | `phiki/phiki` v2 | `^8.2` | `psr/simple-cache` | ~7 000 (+ 10 Mo de grammaires) | oui (thèmes VS Code) | non (supprimée en v2) |
  | `scrivo/highlight.php` | `>=5.4` | aucune | ~3 400 | oui (port de highlight.js 9) | non |
  | `ark4ne/highlight` | `>=5.6` | `psr/simple-cache` | ~2 200 | oui | oui, mais pas de version publiée |

  - `tempest/highlight` est la seule qui couvre les deux besoins : HTML pour la page d'erreur, terminal pour `--pretend`. Elle n'a aucune dépendance et prend en charge PHP et SQL (testé). La contrainte `^2.12` choisit automatiquement la 2.12.x sur PHP 8.3 et la dernière version sur PHP 8.4+.
  - Elle est déclarée en `suggest` et en `require-dev` (pour les tests du framework). Si elle est absente, le code est affiché sans coloration.
  - `ark4ne/highlight` est retiré des dépendances.
- **`Debug\Reflexion` → `Support\Reflection`**, réduit aux accès aux propriétés et méthodes non publiques (sans `setAccessible()`). C'est un changement de namespace documenté.

## Stories

### E12-S1 · Étude `phalcon/debugbar` (1 jour maximum)
- Installer `phalcon/debugbar` sur l'app `tests/.fake` (HTTP et Micro), sur Phalcon 5.22 et sur le job Phalcon 6.
- Vérifier les panneaux SQL (connexions `db.<nom>` d'E10), cache (stores multiples d'E7), vues, logs et exceptions, le coût par requête en mode debug, et l'absence totale d'effet quand `APP_DEBUG` est faux.
- Livrable : une note dans cet epic qui confirme le remplacement, ou liste ce qu'il faut garder de notre barre.

### E12-S2 · Gestion des erreurs
- `Handler` (enregistrement automatique, erreurs fatales, `\Throwable`), `Error` (objet valeur), `Helper` (formatage, correspondance des niveaux avec `Logger\Enum`).
- Tests : chaque type d'erreur et d'exception, erreur fatale simulée dans un sous-processus (via `Process`, E14, ou `proc_open`), `error_reporting` respecté.

### E12-S3 · Sorties d'erreur
- `Writable` typé. `Phplog`, `Logger` (formateur par adapter), `Flash`, `View` (contrôleur ou vue d'erreur, réponse 500), `Json` (Micro, détail seulement en debug) et `Cli`.
- Tests existants de `tests/Test/Error` migrés.

### E12-S4 · Debugger, page d'erreur et barre de debug
- `Debugger` : intégration de `phalcon/debugbar` si installé (selon S1), page d'erreur, collecte des erreurs PHP (`DebugErrorLogger`).
- Page d'erreur sans les panneaux événements et profileurs. Coloration HTML via `tempest/highlight` si présent (thème CSS embarqué dans la page), sinon texte brut.
- Helper partagé `Neutrino\Debug\Highlight` (HTML et terminal), utilisé par la page d'erreur et par `migrate --pretend` (E11), qui détecte la présence de `tempest/highlight`.
- Suppression de `DebugToolbar`, `bar.html.php`, `DebugEventsManagerWrapper` et `Foundation\Middleware\Debug` (si S1 confirme).
- Tests : page d'erreur et sortie terminal rendues avec et sans `tempest/highlight`, absence de debug quand `APP_DEBUG` est faux.

### E12-S5 · `VarDump` et `Support\Reflection`
- `VarDump::dump(mixed ...$vars)` typé : énumérations, propriétés `readonly`, objets récursifs, sortie HTML ou CLI.
- `Support\Reflection` qui remplace `Debug\Reflexion`. Mise à jour des utilisateurs : cache des routes (E4), `StrExtension` (E9), tests.

## Changements cassants pour les apps

- `Handler` est enregistré automatiquement. Les apps qui l'enregistraient elles-mêmes doivent retirer cet appel ou désactiver l'enregistrement automatique.
- La barre de debug Nucleon est remplacée par `phalcon/debugbar` (à installer en dépendance de développement) si S1 confirme. `Foundation\Middleware\Debug` est supprimé.
- `ark4ne/highlight` est retiré. Pour garder la coloration en développement : `composer require --dev tempest/highlight`.
- `Debug\Reflexion` → `Support\Reflection`.

## Critères d'acceptation spécifiques

- Les suites `Error` et `Debug` sont activées et passent, y compris sur le job Phalcon 6.
- Une erreur fatale de compilation (`E_COMPILE_ERROR`) est bien transmise aux sorties d'erreur.
- `composer show --tree` n'indique aucune dépendance de production nouvelle.
- Mesures : avec `APP_DEBUG` faux, une requête HTTP ne coûte pas plus cher qu'en 1.3 (aucun outil de debug chargé).
