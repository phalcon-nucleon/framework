<?php

declare(strict_types=1);

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\Tasks\StubTask;
use Neutrino\Cli\Attribute\Description;
use Neutrino\Config\ConfigCompiler;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Http\RouteCompiler;
use Neutrino\Support\IdeHelper\Generator;
use Phalcon\Di\Di;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Test\Cli\CliTestCase;

/**
 * The framework commands, run through the console kernel of the fake app.
 */
final class FrameworkTasksTest extends CliTestCase
{
    private const string COMPILE_DIR = BASE_PATH . '/bootstrap/compile/';

    protected function tearDown(): void
    {
        foreach (glob(self::COMPILE_DIR . '*.php') ?: [] as $file) {
            unlink($file);
        }

        parent::tearDown();
    }

    public function testList(): void
    {
        $output = $this->runCommand('list');

        $this->assertStringContainsString('Available Commands :', $output);
        $this->assertMatchesRegularExpression('/^ list +List all commands available\.\s*$/m', $output);
        $this->assertMatchesRegularExpression('/^route$/m', $output);
        $this->assertMatchesRegularExpression('/^ route:cache +Cache the HTTP routes\.\s*$/m', $output);
        $this->assertStringContainsString('ide-helper', $output);
        $this->assertStringNotContainsString('migrate', $output);
    }

    public function testDefaultTaskListsTheCommands(): void
    {
        $this->assertStringContainsString('Available Commands :', $this->runCommand(''));
    }

    public function testDefaultTaskSuggestsCommands(): void
    {
        $output = $this->runCommand('route:lsit');

        $this->assertStringContainsString('Command "route:lsit" not found.', $output);
        $this->assertStringContainsString('Did you mean', $output);
        $this->assertStringContainsString('route:list', $output);

        $this->output->out = '';
        $this->assertStringNotContainsString('Did you mean', $this->runCommand('zzzzzz'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function helpCommands(): iterable
    {
        yield 'help command' => ['help optimize'];
        yield 'command --help' => ['optimize --help'];
        yield 'command -h' => ['optimize -h'];
    }

    #[DataProvider('helpCommands')]
    public function testHelp(string $command): void
    {
        $output = $this->runCommand($command);

        $this->assertStringContainsString("Usage :\n\toptimize\n", $output);
        $this->assertStringContainsString("Description :\n\tRuns all optimizations", $output);
        $this->assertStringContainsString("Options :\n\t-f, --force : Force optimization in debug mode.\n", $output);
    }

    public function testHelpOfHelp(): void
    {
        $this->assertStringContainsString("Description :\n\tDisplay the help of a command.", $this->runCommand('help'));
    }

    public function testHelpOfAnUnknownCommand(): void
    {
        $this->assertStringContainsString('Command "nothing" not found.', $this->runCommand('help nothing'));
    }

    public function testHelpOfAnAttributeDocumentedTask(): void
    {
        $this->app->handle(['task' => StubTask::class, 'action' => 'main', '--help']);
        $output = $this->output->out;

        $this->assertStringContainsString("Description :\n\tStubTask::mainAction\n", $output);
        $this->assertStringContainsString("Arguments :\n\tabc : abc Arg\n\txyz : xyz Arg\n", $output);
        $this->assertStringContainsString("Options :\n\t-o1, --opt_1 : Option one\n\t-o2, --opt_2 : Option two\n", $output);
    }

    public function testCompileTasks(): void
    {
        $this->assertStringContainsString('Generating configuration cache          Success', $this->runCommand('config:cache'));
        $this->assertFileExists(BASE_PATH . ConfigCompiler::COMPILED_FILE);

        $this->assertStringContainsString('The configuration cache has been removed.', $this->runCommand('config:clear'));
        $this->assertFileDoesNotExist(BASE_PATH . ConfigCompiler::COMPILED_FILE);

        $this->assertStringContainsString('Generating dotconst cache               Success', $this->runCommand('dotconst:cache'));
        $this->assertFileExists(self::COMPILE_DIR . 'consts.php');

        $this->assertStringContainsString('Generating http-routes cache            Success', $this->runCommand('route:cache'));
        $this->assertStringContainsString("'/get-head'", (string) file_get_contents(BASE_PATH . RouteCompiler::COMPILED_FILE));

        file_put_contents(self::COMPILE_DIR . 'loader.php', '<?php');
        file_put_contents(self::COMPILE_DIR . 'preload.php', '<?php');
        $this->assertStringContainsString('The compiled files have been removed.', $this->runCommand('clear-compiled'));
        $this->assertFileDoesNotExist(self::COMPILE_DIR . 'loader.php');
        $this->assertFileDoesNotExist(self::COMPILE_DIR . 'preload.php');
    }

    public function testOptimize(): void
    {
        // The fake app runs in debug mode.
        $this->assertStringContainsString('Application is in debug mode.', $this->runCommand('optimize'));

        $this->output->out = '';
        $output = $this->runCommand('optimize --force --no-dump --no-preload');

        $this->assertStringContainsString('Generating configuration cache          Success', $output);
        $this->assertStringContainsString('Generating dotconst cache               Success', $output);
        $this->assertStringContainsString('Generating http-routes cache            Success', $output);
        $this->assertStringContainsString('Compiling views                         Success', $output);
        $this->assertStringNotContainsString('preload', $output);
    }

    public function testRouteList(): void
    {
        $output = $this->runCommand('route:list');

        $this->assertStringContainsString('NAMESPACE : Fake\\Kernels\\Http\\Controllers', $output);
        $this->assertMatchesRegularExpression('#\| GET\|HEAD +\| /get-head +\| StubController::indexAction +\| Neutrino\\\\Http\\\\Middleware\\\\Csrf \|#', $output);
        $this->assertStringContainsString('/u/{user}', $output);

        $this->output->out = '';
        $this->assertStringContainsString('/u/:int', $this->runCommand('route:list --no-substitution'));
    }

    public function testViewClear(): void
    {
        $dir = sys_get_temp_dir() . '/nucleon-views-' . bin2hex(random_bytes(4));
        mkdir($dir . '/sub', 0777, true);
        touch($dir . '/a.php');
        touch($dir . '/sub/b.php');
        $this->getDI()->getShared(Services::CONFIG)->merge(['view' => ['compiled_path' => $dir]]);

        $this->assertStringContainsString('Compiled views cleared!', $this->runCommand('view:clear'));
        $this->assertSame([], glob($dir . '/*'));
        $this->assertDirectoryExists($dir);
        rmdir($dir);
    }

    public function testViewCache(): void
    {
        $dir = sys_get_temp_dir() . '/nucleon-view-cache-' . bin2hex(random_bytes(4));
        mkdir($dir . '/views/users', 0777, true);
        mkdir($dir . '/views/layouts');
        mkdir($dir . '/compiled');
        file_put_contents($dir . '/views/index.volt', '{{ content() }}');
        file_put_contents($dir . '/views/layouts/base.volt', '<main>{% block body %}{% endblock %}</main>');
        // A layout relative to views_dir, as in a view rendered by the application.
        file_put_contents($dir . '/views/users/show.volt', "{% extends 'layouts/base.volt' %}{% block body %}{{ name|e }}{% endblock %}");
        file_put_contents($dir . '/views/users/notes.txt', 'not a template');
        $this->getDI()->getShared(Services::CONFIG)->merge(['view' => ['views_dir' => $dir . '/views/', 'compiled_path' => $dir . '/compiled/']]);

        try {
            $this->assertStringContainsString('Compiling views                         Success (3)', $this->runCommand('view:cache'));

            $compiled = glob($dir . '/compiled/*show.volt.php') ?: [];
            $this->assertCount(1, $compiled);
            $this->assertStringContainsString('<main><?= $this->escaper->html($name) ?></main>', (string) file_get_contents($compiled[0]));
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }

    public function testModelCache(): void
    {
        $dir = sys_get_temp_dir() . '/nucleon-model-cache-' . bin2hex(random_bytes(4));
        mkdir($dir . '/models/Sub', 0777, true);
        mkdir($dir . '/meta');
        file_put_contents($dir . '/models/Post.php', "<?php\nnamespace CacheTest\\Models;\nclass Post extends \\Neutrino\\Model { public function initialize() { parent::initialize(); \$this->setSource('posts'); \$this->primary('id', 0); } }\n");
        file_put_contents($dir . '/models/Sub/Tag.php', "<?php\nnamespace CacheTest\\Models\\Sub;\nfinal class Tag extends \\Neutrino\\Model { public function initialize() { parent::initialize(); \$this->primary('id', 0); \$this->column('name', 2); } }\n");
        file_put_contents($dir . '/models/Helper.php', "<?php\nnamespace CacheTest\\Models;\nfinal class Helper { }\n");
        foreach (['models/Post.php', 'models/Sub/Tag.php', 'models/Helper.php'] as $file) {
            require_once $dir . '/' . $file;
        }

        $di = $this->getDI();
        $di->getShared(Services::CONFIG)->merge(['models' => ['paths' => [$dir . '/models'], 'metadata' => ['adapter' => 'stream', 'options' => ['metaDataDir' => $dir . '/meta/']]]]);
        \Neutrino\Foundation\ProviderRegistrar::register($di, [\Neutrino\Providers\Model::class]);

        try {
            $this->assertStringContainsString('Caching models meta-data                Success (2)', $this->runCommand('model:cache'));
            $this->assertCount(4, glob($dir . '/meta/*.php') ?: [], 'Meta-data and column map of 2 models.');
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }

    public function testIdeHelper(): void
    {
        $dir = sys_get_temp_dir() . '/nucleon-ide-' . bin2hex(random_bytes(4));
        mkdir($dir);

        try {
            $output = $this->runCommand('ide-helper --kernel=Fake\\Kernels\\Http\\StubKernelHttp --output-dir=' . $dir);

            $this->assertStringContainsString('Generated ' . $dir . '/' . Generator::IDE_HELPER_FILE, $output);
            $this->assertStringContainsString('Generated ' . $dir . '/' . Generator::PHPSTORM_META_FILE, $output);

            $helper = (string) file_get_contents($dir . '/' . Generator::IDE_HELPER_FILE);
            // The HTTP kernel's router documents $this->router; the console output comes from the console kernel.
            $this->assertStringContainsString('@property-read \\Phalcon\\Mvc\\Router $router', $helper);
            $this->assertStringContainsString('@property-read \\Fake\\Kernels\\Cli\\Output\\StubOutput $output', $helper);
            // The console container is the default one again.
            $this->assertSame($this->app, Di::getDefault()?->getShared(Services::APP));

            unlink($dir . '/' . Generator::IDE_HELPER_FILE);
            unlink($dir . '/' . Generator::PHPSTORM_META_FILE);

            $this->output->out = '';
            $this->runCommand('ide-helper --no-meta --output-dir=' . $dir);
            $this->assertFileExists($dir . '/' . Generator::IDE_HELPER_FILE);
            $this->assertFileDoesNotExist($dir . '/' . Generator::PHPSTORM_META_FILE);
        } finally {
            array_map(unlink(...), glob($dir . '/{,.}*.php', GLOB_BRACE) ?: []);
            rmdir($dir);
        }
    }

    public function testFrameworkTasksAreDocumentedWithAttributes(): void
    {
        foreach (glob(dirname(__DIR__, 4) . '/src/Neutrino/Foundation/Cli/Tasks/*Task.php') ?: [] as $file) {
            $class = 'Neutrino\\Foundation\\Cli\\Tasks\\' . basename($file, '.php');

            $this->assertCount(1, (new ReflectionMethod($class, 'mainAction'))->getAttributes(Description::class), $class);
        }
    }
}
