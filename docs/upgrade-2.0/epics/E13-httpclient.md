# E13 — HttpClient v2

**Statut** : Terminé · **Dépend de** : E0 (E2 pour le provider et la Facade) · **Bloque** : —

## Objectif

Réécrire le client HTTP de Nucleon en gardant sa légèreté (aucune dépendance, 1 500 lignes au maximum), avec une API inspirée de `symfony/http-client`, des réglages sûrs par défaut et un transport de test pour les apps.

## Contexte

Mesure faite pour la décision du `README.md` (lignes de PHP installées, tests exclus) :

| Client | Paquets | Lignes |
|---|---|---|
| **Neutrino\HttpClient 1.3** | 0 | **2 150** |
| rmccue/requests | 1 | 8 157 |
| php-http/curl-client + nyholm/psr7 | 12 | 12 548 |
| symfony/http-client | 6 | 15 759 |
| guzzlehttp/guzzle | 8 | 35 649 |

Phalcon 5 n'a pas de client HTTP sortant (`Phalcon\Http\Client` a disparu). Aucun code du framework n'utilise le client depuis la suppression des Assets (E1), donc la réécriture ne touche que l'API publique.

## Périmètre

- `src/Neutrino/HttpClient/**` (18 fichiers, 2 150 lignes), remplacé entièrement
- Nouveaux : `Providers/HttpClient.php`, `Support/Facades/Http.php`
- Tests : `tests/Test/HttpClient` (14 fichiers de test), réécrits
- **Hors périmètre** :
  - adapter PSR-18 / PSR-7 (il imposerait `psr/http-client`, `psr/http-message` et une implémentation PSR-7) → paquet séparé éventuel après la 2.0 ;
  - HTTP/3 ;
  - cache HTTP.

## Constats sur la 1.3

| Constat | Où |
|---|---|
| **Bug** : `StreamContext::disableSsl()` met `verify_peer` à `true` : il ne désactive rien. | `Provider/StreamContext.php:56` |
| `Request` est mutable et à usage unique (la réponse est stockée dans la requête). On ne peut pas réutiliser une configuration commune (URL de base, en-têtes) sans la recopier. | `Request.php` |
| `Factory` dépend de `Di::getDefault()`. Le service `httpClient` est déclaré dans `Constants\Services`, mais aucun provider ne l'enregistre. | `Factory.php` |
| Pas de restriction des protocoles. Une URL `file://`, `gopher://` ou `dict://` venue d'un utilisateur ouvre une SSRF. | `Provider/Curl.php` |
| Les redirections conservent l'en-tête `Authorization`, même vers un autre hôte. | `Provider/*` |
| Les erreurs HTTP (4xx, 5xx) et réseau sont signalées par des codes à vérifier à la main (`isOk()`, `isFail()`, `getErrorCode()`). On peut facilement les ignorer. | `Response.php` |
| Le streaming passe par des événements Phalcon (`stream:start`, `progress`, `finish`) sur un gestionnaire d'événements dédié. | `Contract/Streaming/*` |

## Décisions

**API inspirée de Symfony, en plus léger :**

```php
$client = Http::withOptions(['base_uri' => 'https://api.example.com', 'auth_bearer' => $token]);

$response = $client->request('GET', '/users', ['query' => ['page' => 2], 'timeout' => 5]);
$response->getStatusCode();   // int
$response->getHeaders();      // array<string, list<string>>, lève une exception sur 3xx/4xx/5xx
$response->getContent();      // string, idem
$response->toArray();         // JSON décodé, idem
$response->getInfo('total_time');

foreach ($client->request('GET', '/export', ['buffer' => false])->chunks() as $chunk) { … }
```

- **Client** :
  - interface `HttpClientInterface` avec `request(string $method, string $url, array $options = []): ResponseInterface` et `withOptions(array $options): static` ;
  - le client est immuable : `withOptions()` renvoie une copie.
- **Options** (sous-ensemble des options Symfony, mêmes noms) :
  - `base_uri`, `query`, `headers` ;
  - `body` (chaîne, tableau encodé en formulaire, ressource ou itérable), `json` ;
  - `auth_basic`, `auth_bearer` ;
  - `timeout`, `max_duration`, `max_redirects` ;
  - `proxy`, `no_proxy` ;
  - `verify_peer`, `verify_host`, `cafile` ;
  - `http_version`, `buffer`, `on_progress`, `user_data`.
- **Réponse** :
  - `getStatusCode()`, `getHeaders(bool $throw = true)`, `getContent(bool $throw = true)`, `toArray(bool $throw = true)`, `getInfo(?string $type = null)`, `chunks(): \Generator` ;
  - exceptions typées : `TransportException`, `RedirectionException`, `ClientException`, `ServerException`, toutes sous `HttpClientExceptionInterface`.
- **Transports** :
  - `CurlTransport` par défaut, `StreamTransport` en repli si `ext-curl` est absent, derrière une interface `Transport` ;
  - `MockTransport` + `MockResponse` pour les tests des apps, l'équivalent de `MockHttpClient` de Symfony.
- **Sécurité par défaut** :
  - vérification TLS activée ;
  - protocoles limités à `http` et `https` (`CURLOPT_PROTOCOLS` / `CURLOPT_REDIR_PROTOCOLS`, contrôle équivalent côté stream) ;
  - `Authorization` et les cookies sont retirés lors d'une redirection vers un autre hôte ;
  - `max_redirects` vaut 20 par défaut.
- **Parsers** :
  - `toArray()` décode le JSON ;
  - les parsers XML (`Xml`, `XmlArray`) sont supprimés : `simplexml_load_string($response->getContent())` suffit.
- **Requêtes parallèles** :
  - **Décidé** : pas d'exécution parallèle (`curl_multi`) dans la 2.0, qui est synchrone. L'API (réponses chargées au premier accès) permet de l'ajouter dans la 2.x sans changement cassant.
- **Intégration** : un provider `HttpClient` enregistre le service `httpClient` avec les options par défaut de `config/http_client.php`. La Facade `Http` est couverte par les IDE helpers d'E2.

## Stories

### E13-S1 · Contrats, options et réponse
- `HttpClientInterface`, `ResponseInterface`, `Transport`, résolution des options (fusion des valeurs par défaut, `base_uri` relative, `query`, `json`, `body`, `auth_*`) et validation (option inconnue = exception).
- Hiérarchie d'exceptions.
- Tests unitaires de la résolution des options (sans réseau).

### E13-S2 · `CurlTransport`
- Requête synchrone, en-têtes, corps, redirections (avec nettoyage des en-têtes sensibles entre hôtes), délais, proxy, TLS, restriction de protocoles, `on_progress`, `buffer: false` + `chunks()`.
- Tests contre un serveur de test local (`php -S` lancé par la suite de tests, avec des routes de test : statuts, redirections, lenteur, flux, JSON).

### E13-S3 · `StreamTransport`
- Même comportement que `CurlTransport` via `stream_context_create` et `fopen`, avec les mêmes contrôles.
- Les tests de S2 sont joués sur les deux transports (data provider).

### E13-S4 · `MockTransport`, provider et Facade
- `MockTransport` (suite de `MockResponse` ou callback) et `MockResponse` (statut, en-têtes, corps, morceaux, erreur réseau simulée). Assertions sur les requêtes envoyées.
- `Providers\HttpClient`, Facade `Http`, config `http_client`.
- Exemple de test d'app dans la documentation.

### E13-S5 · Taille et performances
- Contrôle en CI : `src/Neutrino/HttpClient` ne dépasse pas 1 500 lignes (hors commentaires).
- Mesures : 100 requêtes GET locales en 1.3 et en 2.0 (curl et stream).

## Changements cassants pour les apps

- Nouvelle API : `Request` / `Response` 1.3 → `HttpClient::request()` / `ResponseInterface`. Une table de correspondance est fournie :
  - `get($uri, $params)` → `request('GET', $uri, ['query' => $params])` ;
  - `isOk()` → `getStatusCode() < 300` ;
  - `parse(Json::class)` → `toArray()` ;
  - `disableSsl()` → `['verify_peer' => false, 'verify_host' => false]` ;
  - etc.
- Les erreurs HTTP lèvent des exceptions par défaut (`$throw = false` pour l'ancien comportement).
- Parsers XML supprimés. Événements de streaming remplacés par `on_progress` et `chunks()`.
- Les URLs autres que `http` et `https` sont refusées.

## Critères d'acceptation spécifiques

- La suite `HttpClient` est activée et passe sur les deux transports, y compris sur le job Phalcon 6.
- `composer.json` ne gagne aucune dépendance.
- Taille du module ≤ 1 500 lignes (hors commentaires).
- Les cas de sécurité (protocole `file://`, redirection vers un autre hôte avec `Authorization`, certificat invalide) sont couverts par des tests.

## Avancement

| Story | État | Notes |
|---|---|---|
| S1 · Contrats, options et réponse | Fait | `HttpClientInterface`, `ResponseInterface`, `Transport` (un échange : il produit la tête puis les morceaux du corps ; arrêter le générateur interrompt l'échange). `Options` : valeurs par défaut, fusion (`headers` et `query` fusionnés), option inconnue refusée, résolution d'URL RFC 3986 (exemples de la RFC en test), `query`, `json`, `body` (chaîne, formulaire, ressource et itérable lus en mémoire), `auth_*`, pas d'injection d'en-tête. `Request` et `Head` en objets valeurs. `Response` : envoi au premier accès (ou à la destruction d'une réponse jamais lue, erreurs ignorées), redirections suivies par le client (303, et 301/302 sur un POST, deviennent GET ; 307/308 gardent méthode et corps), `max_duration` sur toute la chaîne, `on_progress`, `user_data`, `getInfo()`. Exceptions `TransportException`, `RedirectionException`, `ClientException`, `ServerException`, `DecodingException`, `InvalidArgumentException` sous `HttpClientExceptionInterface`. |
| S2 · `CurlTransport` | Fait | `curl_multi` pour lire le corps au fil de l'eau, `CURLOPT_PROTOCOLS` limité à http(s), pas de `FOLLOWLOCATION` (le client suit), délai d'inactivité par `LOW_SPEED_*`, proxy (et aucun proxy d'environnement sinon), TLS, HTTP/1.0, 1.1, 2. Pas de `curl_close()` (déprécié en PHP 8.5). |
| S3 · `StreamTransport` | Fait | Wrapper `http` avec `follow_location` désactivé, `ignore_errors`, `connection: close`, délai d'inactivité et durée maximale à la lecture, proxy (`request_fulluri`, authentification), contrôles TLS. Mêmes tests que cURL (`TransportTest`, data provider) contre deux serveurs `php -S` (deux origines) et un serveur TLS au certificat autosigné généré par le test : GET, corps JSON/formulaire/brut, HEAD, statuts, redirections, en-têtes sensibles non transmis à une autre origine, `file://` refusé (requête et redirection), délai, durée maximale, connexion refusée, flux reçu progressivement, gros corps, HTTP/1.0, proxy et `no_proxy`, certificat invalide refusé puis accepté par `verify_*` ou `cafile`. |
| S4 · `MockTransport`, provider et Facade | Fait | `MockTransport` (liste de `MockResponse` ou callback, `getRequests()`), `MockResponse` (corps en morceaux, `json()`, `error()`). `Providers\HttpClient` (`httpClient`, alias `HttpClientInterface` et `HttpClient`, config `http_client` avec `transport`), Facade `Http` (ajoutée aux IDE helpers). Exemple de test d'app dans `UPGRADING-2.0.md`, joué par `ProviderTest`. |
| S5 · Taille et performances | Fait | `SizeTest` (suite `HttpClient`, donc en CI) : 942 lignes de code hors commentaires et lignes vides (limite 1 500). Mesures (`bench/tools/http-client.php`, 100 GET sur un serveur local, médiane de 7 séries) : 2.x curl 69 µs et stream 62 µs par requête (PHP 8.3), 1.3 curl 61 µs et stream 50 µs (PHP 7.3). Sur le même PHP 8.3, appels bruts : `curl_exec` 55 µs, `curl_multi` 63 µs, `file_get_contents` 50 µs ; le client ajoute environ 10 µs (résolution des options, générateur), plus 8 µs pour `curl_multi` côté cURL, prix de la lecture en flux et de l'exécution parallèle future. Négligeable devant la latence d'un appel réel. |

Suite `HttpClient` activée (113 tests, environ 4 s : délais et flux réels). 1 294 tests verts sur Phalcon 5.22 ; suite `HttpClient` verte sur Phalcon 6. PHPStan : l'exclusion de `src/Neutrino/HttpClient` est retirée, plus aucun fichier exclu. Aucune dépendance ajoutée.
