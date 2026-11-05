<?php

declare(strict_types=1);

namespace Test\Dotconst;

use LogicException;
use Neutrino\Dotconst;
use Neutrino\Dotconst\Compile;
use Neutrino\Dotconst\Exception\CycleNestedConstException;
use Neutrino\Dotconst\Exception\InvalidFileException;
use Neutrino\Dotconst\Exception\RuntimeException;
use Neutrino\Dotconst\Extensions\Extension;
use Neutrino\Dotconst\Helper;
use Neutrino\Dotconst\Loader;
use Neutrino\Support\Path;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class DotconstTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/nucleon-dotconst-' . bin2hex(random_bytes(6));
        mkdir($this->dir . '/bootstrap/compile', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->dir);
    }

    /**
     * @return iterable<array{array<string, mixed>, array<string, mixed>}>
     */
    public static function dataDynamize(): iterable
    {
        $s = DIRECTORY_SEPARATOR;

        yield [['max_int' => PHP_INT_MAX], ['max_int' => '@php/const:PHP_INT_MAX']];
        yield [['max_int' => PHP_INT_MAX], ['max_int' => '@php/const:PHP_INT_MAX@']];
        yield [['separator' => $s], ['separator' => '@php/const:DIRECTORY_SEPARATOR']];
        yield [['separator' => $s], ['separator' => '@php/const:DIRECTORY_SEPARATOR@']];
        yield [['separator' => $s . '.testing'], ['separator' => '@php/const:DIRECTORY_SEPARATOR@.testing']];
        yield [['directory' => 'directory'], ['directory' => '@php/dir']];
        yield [['directory' => 'directory'], ['directory' => '@php/dir@']];
        yield [['directory' => 'directory' . $s . 'testing'], ['directory' => '@php/dir@/testing']];
        yield [['directory' => 'directory' . $s . 'testing'], ['directory' => '@php/dir:/testing@']];
        yield [['directory' => 'directory' . $s . 'testing' . $s . 'sub'], ['directory' => '@php/dir:/testing@/sub']];
        yield [['env' => null], ['env' => '@php/env:some_env_value']];
        yield [['env' => 'test'], ['env' => '@php/env:some_env_value:test']];
        yield [['A' => 'a', 'B' => 'a/b', 'C' => 'a/b/c'], ['A' => 'a', 'B' => '@{a}/b', 'C' => '@{B}/c']];
        yield [['A' => 1, 'B' => 1], ['A' => 1, 'B' => '@{a}']];
        yield [['B' => 'unknown/b'], ['B' => '@{unknown}/b']];
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $config
     */
    #[DataProvider('dataDynamize')]
    public function testDynamize(array $expected, array $config): void
    {
        $method = new ReflectionMethod(Loader::class, 'dynamize');

        self::assertSame($expected, $method->invoke(null, $config, 'directory'));
    }

    public function testFromFilesNoFile(): void
    {
        self::assertSame([], Loader::fromFiles('no file'));
    }

    public function testFromFilesInvalidFile(): void
    {
        file_put_contents($this->dir . '/.const.ini', "a = \"unterminated\n[");

        $this->expectException(InvalidFileException::class);

        Loader::fromFiles($this->dir);
    }

    public function testFromFilesReadsSectionsReferencesAndEnvironmentFile(): void
    {
        $this->writeIni('.const.ini', <<<'INI'
            base_path = @php/dir
            storage_path = @php/dir:/storage
            cache_path = @{storage_path}/cache
            [APP]
            env = @php/env:NUCLEON_DOTCONST_UNSET:staging
            name = nucleon
            [DB]
            port = 3306
            INI);
        $this->writeIni('.const.staging.ini', <<<'INI'
            [DB]
            port = 3307
            host = db.staging
            INI);

        self::assertSame([
            'BASE_PATH'    => $this->dir,
            'STORAGE_PATH' => $this->dir . '/storage',
            'CACHE_PATH'   => $this->dir . '/storage/cache',
            'APP_ENV'      => 'staging',
            'APP_NAME'     => 'nucleon',
            'DB_PORT'      => 3307,
            'DB_HOST'      => 'db.staging',
        ], Loader::fromFiles($this->dir));
    }

    public function testEnvironmentFileSelectedByAReference(): void
    {
        $this->writeIni('.const.ini', "default_env = local\n[APP]\nenv = @{default_env}\nname = base\n");
        $this->writeIni('.const.local.ini', "[APP]\nname = local\n");

        $this->assertSame(['DEFAULT_ENV' => 'local', 'APP_ENV' => 'local', 'APP_NAME' => 'local'], Loader::fromFiles($this->dir));
    }

    public function testCompileDefinesTheSameConstantsAsTheIniFiles(): void
    {
        $this->writeIni('.const.ini', <<<'INI'
            base_path = @php/dir
            public_path = @php/dir:/public
            nested = @{public_path}
            nested_sub = @{nested}/assets
            literal_ref = @{unknown}/x
            max = @php/const:PHP_INT_MAX
            sep = @php/const:DIRECTORY_SEPARATOR@tmp
            [APP]
            env = @php/env:NUCLEON_DOTCONST_ENV:production
            debug = false
            [TEST]
            int = 123
            float = 1.5
            str = abc
            arr[v1] = v1
            INI);

        $file = Compile::compile($this->dir, $this->dir . '/bootstrap/compile');

        self::assertSame($this->dir . '/bootstrap/compile/consts.php', $file);

        $content = (string) file_get_contents($file);
        self::assertStringContainsString("const BASE_PATH = __DIR__ . '/../..';", $content);
        self::assertStringContainsString("define('APP_ENV', (getenv('NUCLEON_DOTCONST_ENV') === false ? 'production' : getenv('NUCLEON_DOTCONST_ENV')));", $content);

        $expected = Loader::fromFiles($this->dir);
        $compiled = $this->constantsOf($file);

        self::assertEqualsCanonicalizing(array_keys($expected), array_keys($compiled));

        foreach ($expected as $const => $value) {
            $actual = $compiled[$const];
            if (is_string($actual) && str_contains($actual, '/..')) {
                $actual = Path::normalize($actual);
            }

            self::assertSame($value, $actual, $const);
        }
    }

    public function testCompiledEnvironmentValueIsReadAtRuntime(): void
    {
        $this->writeIni('.const.ini', "[APP]\nenv = @php/env:NUCLEON_DOTCONST_ENV:production\nname = @php/env:NUCLEON_DOTCONST_NAME\n");

        $file = Compile::compile($this->dir, $this->dir . '/bootstrap/compile');

        self::assertSame(['APP_ENV' => 'production', 'APP_NAME' => null], $this->constantsOf($file));
        self::assertSame(['APP_ENV' => 'local', 'APP_NAME' => 'n'], $this->constantsOf($file, ['NUCLEON_DOTCONST_ENV' => 'local', 'NUCLEON_DOTCONST_NAME' => 'n']));
    }

    public function testLoadFromCompiledFile(): void
    {
        file_put_contents($this->dir . '/bootstrap/compile/consts.php', "<?php\nconst NUCLEON_DOTCONST_COMPILED_" . strtoupper(bin2hex(random_bytes(4))) . " = true;\n");

        self::assertTrue(Loader::fromCompile($this->dir . '/bootstrap/compile'));
        self::assertFalse(Loader::fromCompile($this->dir));
    }

    public function testLoadRejectsAlreadyDefinedConstants(): void
    {
        $name = 'NUCLEON_DOTCONST_' . strtoupper(bin2hex(random_bytes(4)));
        $this->writeIni('.const.ini', "$name = 1\n");

        Dotconst::load($this->dir);
        self::assertSame(1, constant($name));

        $this->expectException(RuntimeException::class);

        Dotconst::load($this->dir);
    }

    public function testExtensionRequiresAnIdentifier(): void
    {
        $this->expectException(LogicException::class);

        new class extends Extension {
            public function parse(string $value, string $basePath): mixed
            {
                return $value;
            }

            public function compile(string $value, string $basePath, string $compilePath): string
            {
                return var_export($value, true);
            }
        };
    }

    public function testNestedConstSort(): void
    {
        $given = [
            'A' => ['require' => 'C'],
            'B' => ['require' => null],
            'C' => ['require' => 'D'],
            'H' => ['require' => 'F'],
            'D' => ['require' => '_E_'],
            'F' => ['require' => 'A'],
            'G' => ['require' => 'A'],
            'I' => ['require' => null],
        ];

        self::assertSame(['B', 'I', 'D', 'C', 'A', 'F', 'G', 'H'], array_keys(Helper::nestedConstSort($given)));
    }

    public function testCyclicNestedConstSort(): void
    {
        $this->expectException(CycleNestedConstException::class);

        Helper::nestedConstSort([
            'A' => ['require' => 'B'],
            'B' => ['require' => 'C'],
            'C' => ['require' => 'A'],
        ]);
    }

    private function writeIni(string $name, string $content): void
    {
        file_put_contents($this->dir . '/' . $name, $content);
    }

    /**
     * Includes a compiled file in a separate process and returns the constants it defines.
     *
     * @param array<string, string> $env
     *
     * @return array<string, mixed>
     */
    private function constantsOf(string $file, array $env = []): array
    {
        $code = 'require ' . var_export($file, true) . '; echo serialize(get_defined_constants(true)["user"] ?? []);';
        $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env + ['PATH' => (string) getenv('PATH')]);
        self::assertIsResource($process);

        $output = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $errors . $output);

        $constants = unserialize($output);
        self::assertIsArray($constants);

        /** @var array<string, mixed> $constants */
        return $constants;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff((array) scandir($dir), ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
