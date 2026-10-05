# E9 — Vues & Volt

**Statut** : Rédigé · **Dépend de** : E4 (et E7 pour Escaper/Security, E8-S4 pour le nom du champ CSRF) · **Bloque** : —

## Objectif

Porter le service View et le moteur Volt sur Phalcon 5, avec les extensions Nucleon (CSRF, `route()`, fonctions `str_*`, filtres), et garder une compilation des templates rapide en production.

## Périmètre

- `Providers/View.php` (services `view`, `tag`, `assets`)
- `View/Engines/EngineRegister.php`, `View/Engines/Volt/VoltEngineRegister.php`
- `View/Engines/Volt/Compiler/*` :
  - classes de base `Extending`, `ExtensionExtend`, `FunctionExtend`, `FilterExtend` ;
  - extensions `CsrfExtension`, `PhpFunctionExtension`, `StrExtension` ;
  - fonction `RouteFunction` ;
  - filtres `MergeFilter`, `SliceFilter`, `SplitFilter`, `RoundFilter`.
- `Support/Facades/View.php`
- Tâche `view:clear` (côté logique, la commande est dans E6)
- Tests : `tests/Test/View` (8 fichiers de test)
- **Hors périmètre** :
  - la fonction Volt `dump()`, qui pointe vers `Debug\VarDump` → son sort est décidé dans E12, et E9 suit ;
  - les autres moteurs de template.

## Ruptures Phalcon 3 → 5 (vérifiées dans les stubs 5.22.0, sauf mention)

| 1.3 | 5.22 | Concerne |
|---|---|---|
| `Phalcon\DiInterface` | `Phalcon\Di\DiInterface` | Provider View |
| `Phalcon\Mvc\View\Engine` | `Phalcon\Mvc\View\Engine\AbstractEngine` | moteurs |
| `Volt\Compiler::addExtension()`, `addFilter()`, `addFunction()` | typés : `addFilter(string $name, $definition): static`, etc. | `VoltEngineRegister` |
| Options Volt `compiledPath`, `compiledSeparator`, `compileAlways` | Renommées en Phalcon 4 en `path`, `separator`, `always` d'après le guide de migration Phalcon 4. **À vérifier en S1** : les stubs ne documentent pas les noms acceptés. | `VoltEngineRegister` |
| `Phalcon\Tag` (instance via `$this->tag`) | `Phalcon\Tag` existe toujours (méthodes statiques, `hiddenField(): string`). `Phalcon\Html\TagFactory` est la nouvelle API. | `CsrfExtension`, provider View |
| `Phalcon\Escaper` | `Phalcon\Html\Escaper` | templates (via E7) |
| `Security::getSessionToken()`, `getToken()` | inchangés, typés `?string` | `CsrfExtension` |
| Instruction Volt `{% cache %}` | **À vérifier en S1** : le cache de sortie a disparu de `Phalcon\Cache` (E7), donc l'instruction Volt `cache` ne peut plus fonctionner comme avant. | templates des apps |

## Décisions

- **Le modèle d'extension Nucleon est conservé** : extensions, fonctions et filtres déclarés dans `config/view.php` (`extensions`, `functions`, `filters`). Les classes de base sont typées et deviennent des interfaces quand c'est possible (`VoltFunction::compile(string $resolvedArgs, array $exprArgs): string`, idem pour les filtres).
- **Doublons avec Volt** : chaque filtre et fonction Nucleon est comparé avec les filtres natifs de Volt 5. Les doublons sans valeur ajoutée sont supprimés. `slice` est un candidat, car Volt a un filtre natif `slice`.
- **CSRF** :
  - `csrf_field()` génère directement `<input type="hidden" name="_csrf_token" value="…">`, avec la valeur échappée par `Html\Escaper`, au lieu de passer par `Phalcon\Tag` ;
  - le nom du champ et la valeur du jeton suivent la config d'E8-S4.
- **`PhpFunctionExtension`** :
  - aujourd'hui, elle rend appelable dans les templates **n'importe quelle** fonction PHP (`function_exists`), y compris `system`, `exec` ou `unlink` ;
  - les templates sont écrits par les développeurs, mais une liste de refus par défaut limite les dégâts d'un template compromis ou généré : exécution de commandes, système de fichiers, `eval`-like, `ini_set`, `putenv` ;
  - la liste est configurable (`view.php_functions.deny` / `allow`).
- **Compilation** :
  - `always` (recompilation à chaque rendu) est actif quand `APP_ENV === Env::DEVELOPMENT` ou `APP_DEBUG`, comme en 1.3 ;
  - en production, `stat` est désactivable (`view.options.stat = false`) pour éviter le `stat()` de chaque template, et `view:clear` vide le répertoire compilé ;
  - **Décidé** : une tâche `view:cache` précompile tous les templates au déploiement. Elle évite le coût de compilation des premières requêtes et permet `stat = false` sans risque.
- Les services `tag` (`Phalcon\Tag`) et `assets` (`Phalcon\Assets\Manager`) restent enregistrés pour la compatibilité des templates. On ajoute `Html\TagFactory` sous le service `tagFactory`.

## Stories

### E9-S1 · Vérifications Volt 5
- Noms des options acceptés par `Volt::setOptions()` sur Phalcon 5.22 et sur le job Phalcon 6 (`path` / `compiledPath`, etc.).
- Comportement de l'instruction `{% cache %}` sans cache de sortie.
- Liste des filtres et fonctions natifs de Volt 5, comparée à ceux de Nucleon.
- Livrable : une note dans cet epic, qui met à jour les décisions sur les doublons.

### E9-S2 · Provider View et enregistrement des moteurs
- Provider `View` typé : `views_dir`, `partials_dir`, `layouts_dir`, moteurs (`EngineRegister::getRegisterClosure()` conservé).
- Services `tag`, `tagFactory` et `assets`. Facade `View` et IDE helpers.
- `VoltEngineRegister` : options Volt 5, extensions, fonctions, filtres, `dump()` (selon E12).
- Tests : rendu d'une vue avec layout et partial, vues implicites (E4-S1), moteur personnalisé.

### E9-S3 · Extensions, fonctions et filtres
- `CsrfExtension` (voir Décisions), `RouteFunction` (`route(name, params, query)` sur `Url::get()`), `StrExtension` (`str_*` vers `Support\Str`, filtres `slug`, `limit`, `words`, en cohérence avec la revue des helpers d'E1-S6).
- `PhpFunctionExtension` avec liste de refus configurable.
- Filtres `merge`, `split`, `round` (et `slice` selon S1).
- Tâche `view:cache` : compilation de tous les `*.volt` des répertoires de vues (commande enregistrée via E6-S3).
- Tests : code PHP compilé et rendu de chaque extension, fonction et filtre ; fonction refusée ; `csrf_field()` aligné sur le middleware Csrf d'E8.

### E9-S4 · Mesures
- Scénario ajouté au script d'E0 : rendu d'une page Volt (layout + 2 partials) sans recompilation, avec `stat` actif puis désactivé.

## Changements cassants pour les apps

- Options Volt renommées si S1 le confirme (`compiledPath` → `path`, etc.). L'ancienne forme est convertie si Volt 5 ne l'accepte plus.
- `PhpFunctionExtension` refuse par défaut les fonctions dangereuses.
- `{% cache %}` : selon S1.
- Filtres Nucleon supprimés au profit des filtres natifs : selon S1.

## Critères d'acceptation spécifiques

- La suite `View` est activée et passe, y compris sur le job Phalcon 6.
- Mesures : le rendu d'une page Volt n'est pas moins bon que celui de la 1.3.
