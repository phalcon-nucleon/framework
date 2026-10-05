<?php

declare(strict_types=1);

/*
 * 2.0 `optimize` on the bench application: dotconst, config and routes caches, and an OPcache preload
 * script (bench/app/bootstrap/compile/preload.php). Expects bench/.current dumped with --classmap-authoritative.
 *
 * The preload list is limited to the modules ported so far: PreloadGenerator stops on a compile error of an
 * unported module. Extend PORTED as the epics port modules (E6: replace this script by the optimize task).
 */

use Neutrino\Config\ConfigCompiler;
use Neutrino\Dotconst;
use Neutrino\Foundation\Http\RouteCompiler;
use Neutrino\Support\Facades\Facade;

const PORTED = '/^Neutrino\\\\(Foundation\\\\(Bootstrap|Kernelize|ProviderRegistrar|Http\\\\|Micro\\\\|Middleware\\\\(Application|Controller|Dispatcher))'
    . '|Support\\\\(Provider|SimpleProvider|Facades|Path|Arr|Str|AtomicFile)|Constants|Dotconst|Config\\\\(Config|Loader)|Events|Interfaces'
    . '|Providers\\\\(Url|Cookies|Http|Cli|Micro)|Error\\\\Handler|Http\\\\|Micro\\\\|Version|Module)/';

$root = dirname(__DIR__);
$app = $root . '/app';

require $root . '/.current/vendor/autoload.php';

spl_autoload_register(static function (string $class) use ($app): void {
    if (str_starts_with($class, 'Bench\\')) {
        require $app . '/app/' . str_replace('\\', '/', substr($class, 6)) . '.php';
    }
});

Dotconst\Compile::compile($app, $app . '/bootstrap/compile');
Dotconst::load($app);
ConfigCompiler::compile($app);

$di = new Phalcon\Di\FactoryDefault();
$router = new Phalcon\Mvc\Router(false);
$di->setShared('router', $router);
Facade::setDependencyInjection($di);
require $app . '/routes/http.php';
RouteCompiler::write($router, $app);

$classes = array_values(array_filter(
    array_keys(require $root . '/.current/vendor/composer/autoload_classmap.php'),
    static fn(string $class): bool => preg_match(PORTED, $class) === 1,
));
foreach (glob($app . '/app/*/*.php') ?: [] as $file) {
    $classes[] = 'Bench\\' . str_replace('/', '\\', substr($file, strlen($app . '/app/'), -4));
}

$preload = $app . '/bootstrap/compile/preload.php';
file_put_contents($preload, "<?php\n\nrequire " . var_export($root . '/.current/vendor/autoload.php', true) . ";\n"
    . 'spl_autoload_register(static function (string $c): void { if (str_starts_with($c, \'Bench\\\\\')) { require '
    . var_export($app . '/app/', true) . " . str_replace('\\\\', '/', substr(\$c, 6)) . '.php'; } });\n"
    . 'foreach (' . var_export($classes, true) . ' as $c) { class_exists($c) || interface_exists($c) || trait_exists($c); }' . "\n");

echo count($classes), " classes preloaded in $preload\n";
