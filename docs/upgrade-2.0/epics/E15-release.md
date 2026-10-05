# E15 — Release 2.0

**Statut** : Rédigé · **Dépend de** : tous les epics · **Bloque** : —

## Objectif

Sortir Nucleon 2.0 avec la preuve que la performance est au moins égale à celle de la 1.3, un guide de migration complet et vérifié sur une vraie app, et un squelette `phalcon-nucleon/nucleon` prêt à l'emploi.

## Périmètre

- Dépôt `framework` :
  - `README.md`, `CLAUDE.md`, `composer.json` (alias de branche), `src/Neutrino/Version.php` ;
  - nouveaux `UPGRADING-2.0.md` et `CHANGELOG-2.0.md` ;
  - rapport de mesures `bench/report-2.0.md` ;
  - configuration Rector de migration pour les apps.
- Dépôt `phalcon-nucleon/nucleon` (squelette) : kernels, config, `public/index.php`, console, Docker, CI.
- Site `phalcon-nucleon.github.io` : documentation 2.0.
- **Hors périmètre** : nouvelles fonctionnalités. Tout ce qui n'est pas prêt est reporté en 2.x.

## Décisions

- **Cycle de sortie** : `v2.0.0-alpha.N` dès que E0 à E7 sont terminés (noyau utilisable), puis `beta.N` quand tous les epics sont terminés, puis `RC.N` après la validation sur une vraie app (S4), puis `v2.0.0`. Même logique de tags que la 1.3 (`v1.3.0-beta1`, `v1.3.0-RC2`).
- **Guide de migration consolidé** : `UPGRADING-2.0.md` est construit à partir des sections « Changements cassants pour les apps » de chaque epic, regroupées par thème (prérequis, kernels et providers, config, cache, session, auth, base de données, vues, CLI, tests, debug, HttpClient, Process). Chaque entrée a un exemple avant/après.
- **Migration assistée par Rector** : fournir `resources/rector/upgrade-2.0.php`, une configuration Rector utilisable par les apps qui ont Rector en dépendance de développement. Rien n'est ajouté aux dépendances de Nucleon. Elle automatise :
  - les renommages de classes (`Disptacher`, `Debug\Reflexion`, `Phalcon\Crypt`…, `Timeout`) ;
  - les renommages de méthodes (`Process::exec()` → `run()`…) ;
  - le typage des propriétés des kernels ;
  - les data providers statiques des `RoutesTestCase`.

  **Décidé** : cette configuration est livrée avec la 2.0. Elle réduit fortement le coût de migration des apps et ne repose que sur des règles standard de Rector (renommage de classes et de méthodes).
- **Pas de maintenance de la 1.3** (décidé) : la 1.3 n'a plus reçu de mise à jour depuis des années, et Phalcon 3 comme PHP ≤ 7.3 ne sont plus maintenus. Le `README.md` indique que la 1.x est terminée. Le tag `v1.3.2` reste disponible, sans branche de maintenance.
- **Phalcon 6** :
  - le statut du job CI Phalcon 6 au moment de la sortie est documenté dans le `README.md` (pris en charge, ou limites connues) ;
  - la prise en charge officielle de Phalcon 6 sera décidée à sa sortie en version stable, dans une 2.x mineure si aucun changement cassant n'est nécessaire.

## Stories

### E15-S1 · Mesures finales
- Exécution complète du script de mesures d'E0 sur la 2.0 (OPcache + preload d'E1) et comparaison avec `bench/baseline-1.3.json`. Scénarios :
  - boot des kernels ;
  - requête HTTP (avec et sans cache des routes) ;
  - requête Micro ;
  - tâche CLI ;
  - résolution de service ;
  - modèle (`findFirst`) ;
  - rendu Volt ;
  - `ThrottleRequest` ;
  - mémoire de pointe.
- Rapport `bench/report-2.0.md` : tableaux, environnement, méthode.
- **Bloquant** : toute régression au-delà de la marge de bruit mesurée dans E0 (5 %) doit être corrigée ou explicitement acceptée avec sa justification dans le rapport.

### E15-S2 · Documentation du dépôt
- `UPGRADING-2.0.md` (consolidation, voir Décisions) et `CHANGELOG-2.0.md` (Ajouts, Changements, Suppressions, Corrections de sécurité, avec un renvoi vers l'epic concerné).
- `README.md` :
  - prérequis (PHP ≥ 8.3, Phalcon ≥ 5.22) ;
  - badges GitHub Actions à la place de Travis et Coveralls ;
  - liste des fonctionnalités mise à jour (Optimizer et Assets supprimés, HttpClient et Process réécrits, IDE helpers) ;
  - mention de la fin de la 1.x (pas de maintenance).
- `CLAUDE.md` : la section « Runtime constraints » décrit désormais la 2.x. Le renvoi vers `docs/upgrade-2.0/` est conservé comme historique.
- `composer.json` : `"extra": {"branch-alias": {"dev-2.x": "2.0-dev"}}`. `Neutrino\Version` → 2.0.0.

### E15-S3 · Configuration Rector de migration
- `resources/rector/upgrade-2.0.php` avec les règles listées dans les Décisions.
- Testée sur l'app de S4 : le diff produit est relu et vérifié.

### E15-S4 · Validation sur une vraie app
- Migrer le squelette `phalcon-nucleon/nucleon`, puis une app réelle (à désigner : une app existante en 1.3), **uniquement** avec `UPGRADING-2.0.md` et la configuration Rector.
- Chaque difficulté non couverte par le guide est corrigée dans le guide (ou dans le framework) avant la RC.

### E15-S5 · Squelette `phalcon-nucleon/nucleon` 2.0
- Kernels HTTP, CLI et Micro typés. Providers à jour, dont `HttpClient`, migrations et l'enregistrement des tâches.
- Fichiers `config/*.php` aux nouveaux formats : `cache` (adapter/serializer), `log` (adapters), `session` (stores), `auth` (guards/access), `database` (connexions par nom), `view` (options Volt), `http_client`, `error`, `security` (csrf, throttle).
- `public/index.php` (`handleIncoming()`), console, `.gitignore` (`_ide_helper.php`, `.phpstorm.meta.php`, `bootstrap/compile/*`).
- Dockerfile (PHP 8.3 + Phalcon 5.22 + OPcache + preload), workflow CI d'exemple, `composer.json` avec `suggest`/`require-dev` (`tempest/highlight`, `phalcon/debugbar`).

### E15-S6 · Revue finale et publication
- Revue de sécurité de la branche `2.x` (CSRF, auth, `Repository`, HttpClient, Process, Volt), avec un focus sur les corrections annoncées dans les epics.
- Passage de la baseline PHPStan à vide, ou justification de chaque entrée restante.
- Tags `alpha`, `beta`, `RC`, `v2.0.0`. Fusion de `2.x` dans `master` à la sortie. Pas de branche de maintenance 1.3.
- Site de documentation mis à jour.

## Critères d'acceptation spécifiques

- `bench/report-2.0.md` publié, sans régression non justifiée.
- Le squelette et l'app de S4 tournent sur la 2.0 en ayant suivi uniquement le guide et la config Rector.
- Toutes les suites de tests sont activées en CI (plus aucune suite exclue) et passent sur la matrice Phalcon 5. Le statut du job Phalcon 6 est documenté.
- Baseline PHPStan vide ou justifiée.
