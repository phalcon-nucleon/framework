<?php

declare(strict_types=1);

namespace Test\Cli;

use Fake\Kernels\Cli\Tasks\StubTask;
use Neutrino\Cli\Output\Helper;
use Neutrino\Foundation\Cli\Tasks\OptimizeTask;
use PHPUnit\Framework\TestCase;

final class TaskDocumentationTest extends TestCase
{
    public function testAttributes(): void
    {
        $this->assertSame([
            'description' => 'StubTask::mainAction',
            'arguments'   => ['abc : abc Arg', 'xyz : xyz Arg'],
            'options'     => ['-o1, --opt_1 : Option one', '-o2, --opt_2 : Option two'],
        ], Helper::getTaskInfos(StubTask::class, 'mainAction'));
    }

    public function testDocblockFallbackIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $infos = Helper::getTaskInfos(StubTask::class, 'legacyAction');
            Helper::getTaskInfos(StubTask::class, 'legacyAction');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([
            'description' => 'StubTask::legacyAction',
            'arguments'   => ['name : the name'],
            'options'     => ['-f, --force : force it'],
        ], $infos);
        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString(StubTask::class . '::legacyAction with a docblock is deprecated', $deprecations[0]);
    }

    public function testDocblockTextAsDescription(): void
    {
        $this->assertSame(['description' => 'StubTask::testAction'], @Helper::getTaskInfos(StubTask::class, 'testAction'));
    }

    public function testUnknownAction(): void
    {
        $this->assertSame(['__exception' => 'Methods ' . StubTask::class . '::nopeAction not found.'], Helper::getTaskInfos(StubTask::class, 'nopeAction'));
    }

    public function testDocumentationWithoutComments(): void
    {
        // OPcache strips the docblocks with save_comments=0: attributes remain, docblocks are lost.
        $autoload = var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true);
        $code = 'require ' . $autoload . '; echo json_encode(['
            . 'Neutrino\\Cli\\Output\\Helper::getTaskInfos(' . var_export(OptimizeTask::class, true) . ', "mainAction"),'
            . '@Neutrino\\Cli\\Output\\Helper::getTaskInfos(' . var_export(StubTask::class, true) . ', "legacyAction"),'
            . '(new ReflectionMethod(' . var_export(StubTask::class, true) . ', "legacyAction"))->getDocComment(),'
            . ']);';

        $opcache = extension_loaded('Zend OPcache') ? '' : ' -d zend_extension=opcache';
        exec(escapeshellarg(PHP_BINARY) . $opcache . ' -d opcache.enable_cli=1 -d opcache.save_comments=0 -r ' . escapeshellarg($code) . ' 2>&1', $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        [$optimize, $legacy, $docComment] = json_decode(implode('', $output), true);

        if ($docComment !== false) {
            $this->markTestSkipped('OPcache is not available: the comments cannot be stripped.');
        }
        $this->assertStringStartsWith('Runs all optimizations', $optimize['description']);
        $this->assertContains('-f, --force : Force optimization in debug mode.', $optimize['options']);
        $this->assertSame(['description' => ''], $legacy);
    }
}
