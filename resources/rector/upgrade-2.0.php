<?php

declare(strict_types=1);

/*
 * Rector configuration upgrading a Nucleon 1.3 application to 2.0 (see UPGRADING-2.0.md).
 *
 * With Rector as a development dependency of the application (`composer require --dev rector/rector`):
 *
 *   vendor/bin/rector process app config routes migrations tests --config=vendor/nucleon/framework/resources/rector/upgrade-2.0.php --dry-run
 *
 * Review the diff, then run it without --dry-run. It:
 * - renames the Nucleon and Phalcon 3 classes and methods that have a 2.0 / Phalcon 5 equivalent;
 * - types the properties and the methods that the 2.0 classes type (an untyped redeclaration is a fatal error),
 *   with the default value of the parent;
 * - makes the `routes()` of the RoutesTestCase static.
 *
 * The rest of the guide (configuration, cache API, HTTP client…) is done by hand. To also upgrade the syntax to
 * PHP 8.3 and the tests to the installed PHPUnit, add for example:
 *
 *   ->withPhpSets(php83: true)
 *   ->withComposerBased(phpunit: true)
 */

use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\VoidType;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\ValueObject\AddReturnTypeDeclaration;

require_once __DIR__ . '/StaticRoutesRector.php';
require_once __DIR__ . '/TypedPropertiesRector.php';

$kernels = ['Neutrino\Foundation\Http\Kernel', 'Neutrino\Foundation\Cli\Kernel', 'Neutrino\Foundation\Micro\Kernel'];

/*
 * The typed properties of the classes the applications extend: [class, property, type, default value of the parent
 * as PHP code, or null when it has none].
 */
$properties = [];

foreach ($kernels as $kernel) {
    foreach (['providers', 'middlewares', 'listeners', 'modules', 'errorHandlerLvl'] as $property) {
        $properties[] = [$kernel, $property, 'array', '[]'];
    }
    $properties[] = [$kernel, 'dependencyInjection', '?string', 'null'];
    $properties[] = [$kernel, 'eventsManagerClass', '?string', 'null'];
}

foreach (['Neutrino\Support\Provider', 'Neutrino\Support\SimpleProvider'] as $provider) {
    array_push(
        $properties,
        [$provider, 'name', 'string', null],
        [$provider, 'aliases', 'array', '[]'],
        [$provider, 'shared', 'bool', 'false'],
    );
}

array_push(
    $properties,
    ['Neutrino\Support\SimpleProvider', 'class', 'string', null],
    ['Neutrino\Support\SimpleProvider', 'options', 'array', '[]'],
    ['Neutrino\Events\Listener', 'listen', 'array', '[]'],
    ['Neutrino\Events\Listener', 'space', 'array', '[]'],
    ['Neutrino\Module', 'providers', 'array', '[]'],
    ['Neutrino\Repositories\Repository', 'modelClass', '?string', 'null'],
    ['Neutrino\Cli\Task', 'options', 'array', '[]'],
    ['Neutrino\Cli\Task', 'arguments', 'array', '[]'],
);

/*
 * The validators of Phalcon 3, under Phalcon\Filter\Validation in Phalcon 5.
 */
$validators = [];

foreach (['Alnum', 'Alpha', 'Between', 'Callback', 'Confirmation', 'CreditCard', 'Date', 'Digit', 'Email', 'ExclusionIn', 'File', 'Identical', 'InclusionIn', 'Numericality', 'PresenceOf', 'Regex', 'StringLength', 'Uniqueness', 'Url'] as $validator) {
    $validators['Phalcon\Validation\Validator\\' . $validator] = 'Phalcon\Filter\Validation\Validator\\' . $validator;
}

/*
 * The typed methods the applications override: [class, method, return type].
 */
$returns = [];

foreach ($kernels as $kernel) {
    foreach (['registerRoutes', 'registerServices', 'registerMiddlewares', 'registerListeners', 'boot', 'terminate', 'bootstrap'] as $method) {
        $returns[] = [$kernel, $method, new VoidType()];
    }
}

array_push(
    $returns,
    ['Neutrino\Support\Facades\Facade', 'getFacadeAccessor', new StringType()],
    ['Neutrino\Micro\Middleware', 'bindOn', new ObjectType('Neutrino\Micro\MiddlewarePosition')],
    ['Neutrino\Module', 'registerAutoloaders', new VoidType()],
    ['Neutrino\Module', 'registerServices', new VoidType()],
    ['Neutrino\Module', 'initialise', new VoidType()],
    ['Neutrino\Test\TestCase', 'kernelClassInstance', new StringType()],
    ['Neutrino\Test\TestCase', 'setUp', new VoidType()],
    ['Neutrino\Test\TestCase', 'tearDown', new VoidType()],
    ['Neutrino\Test\TestCase', 'setUpBeforeClass', new VoidType()],
    ['Neutrino\Test\TestCase', 'tearDownAfterClass', new VoidType()],
    ['Neutrino\Test\RoutesTestCase', 'routes', new ArrayType(new MixedType(), new MixedType())],
);

return RectorConfig::configure()
    ->withConfiguredRule(RenameClassRector::class, [
        // Nucleon
        'Neutrino\Foundation\Middleware\Disptacher'      => 'Neutrino\Foundation\Middleware\Dispatcher',
        'Neutrino\Debug\Reflexion'                       => 'Neutrino\Support\Reflection',
        'Neutrino\Process\Exception'                     => 'Neutrino\Process\Exception\ProcessException',
        'Neutrino\Process\Timeout'                       => 'Neutrino\Process\Exception\ProcessTimedOutException',
        // Phalcon 3 > 5
        'Phalcon\Di'                                     => 'Phalcon\Di\Di',
        'Phalcon\DiInterface'                            => 'Phalcon\Di\DiInterface',
        'Phalcon\Config'                                 => 'Phalcon\Config\Config',
        'Phalcon\Crypt'                                  => 'Phalcon\Encryption\Crypt',
        'Phalcon\Security'                               => 'Phalcon\Encryption\Security',
        'Phalcon\Security\Random'                        => 'Phalcon\Encryption\Security\Random',
        'Phalcon\Filter'                                 => 'Phalcon\Filter\Filter',
        'Phalcon\Escaper'                                => 'Phalcon\Html\Escaper',
        'Phalcon\Logger'                                 => 'Phalcon\Logger\Logger',
        'Phalcon\Logger\Adapter\File'                    => 'Phalcon\Logger\Adapter\Stream',
        'Phalcon\Version'                                => 'Phalcon\Support\Version',
        'Phalcon\Debug'                                  => 'Phalcon\Support\Debug',
        'Phalcon\Registry'                               => 'Phalcon\Support\Registry',
        'Phalcon\Loader'                                 => 'Phalcon\Autoload\Loader',
        'Phalcon\Db'                                     => 'Phalcon\Db\Enum',
        'Phalcon\Db\AdapterInterface'                    => 'Phalcon\Db\Adapter\AdapterInterface',
        'Phalcon\Validation'                             => 'Phalcon\Filter\Validation',
        'Phalcon\Validation\Message'                     => 'Phalcon\Messages\Message',
        'Phalcon\Mvc\Model\Message'                      => 'Phalcon\Messages\Message',
        'Phalcon\Mvc\User\Component'                     => 'Phalcon\Di\Injectable',
        'Phalcon\Mvc\User\Plugin'                        => 'Phalcon\Di\Injectable',
        'Phalcon\Mvc\User\Module'                        => 'Phalcon\Di\Injectable',
        'Phalcon\Session\Adapter\Files'                  => 'Phalcon\Session\Adapter\Stream',
        'Phalcon\Annotations\Adapter\Files'              => 'Phalcon\Annotations\Adapter\Stream',
        'Phalcon\Mvc\Model\MetaData\Files'               => 'Phalcon\Mvc\Model\MetaData\Stream',
        // No base exception in Phalcon 5: each component has its own.
        'Phalcon\Exception'                              => 'Exception',
    ] + $validators)
    ->withConfiguredRule(RenameMethodRector::class, [
        new MethodCallRename('Neutrino\Process\Process', 'exec', 'run'),
        new MethodCallRename('Neutrino\Process\Process', 'getError', 'getErrorOutput'),
        new MethodCallRename('Neutrino\Process\Process', 'pid', 'getPid'),
        new MethodCallRename('Neutrino\Error\Error', 'isFateful', 'isFatal'),
    ])
    ->withConfiguredRule(Nucleon\Rector\TypedPropertiesRector::class, $properties)
    ->withConfiguredRule(AddReturnTypeDeclarationRector::class, array_map(
        static fn(array $return): AddReturnTypeDeclaration => new AddReturnTypeDeclaration(...$return),
        $returns,
    ))
    ->withRules([Nucleon\Rector\StaticRoutesRector::class])
    // Removes the imports of the renamed classes, without importing the other names.
    ->withImportNames(importNames: false, removeUnusedImports: true);
