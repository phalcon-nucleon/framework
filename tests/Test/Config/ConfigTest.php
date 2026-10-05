<?php

declare(strict_types=1);

namespace Test\Config;

use Neutrino\Config\Config;
use Phalcon\Config\Config as PhalconConfig;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config([
            'app'   => ['base_uri' => '/', 'Static_Uri' => '/s/', 'debug' => false, 'nothing' => null],
            'cache' => ['stores' => ['file' => ['ttl' => 60]]],
            7       => 'seven',
        ]);
    }

    public function testIsAPhalconConfigWithNestedNeutrinoConfigs(): void
    {
        $this->assertInstanceOf(PhalconConfig::class, $this->config);
        $this->assertInstanceOf(Config::class, $this->config->app);
        $this->assertInstanceOf(Config::class, $this->config->cache->stores->file);
    }

    public function testReads(): void
    {
        $config = $this->config;

        $this->assertSame('/', $config->app->base_uri);
        $this->assertSame('/', $config['app']['base_uri']);
        $this->assertSame('/', $config->get('app')->get('base_uri'));
        $this->assertSame(60, $config->path('cache.stores.file.ttl'));
        $this->assertSame(60, $config->path('cache/stores/file/ttl', null, '/'));
        $this->assertSame('seven', $config[7]);
        $this->assertFalse($config->app->debug);
        $this->assertSame('60', $config->cache->stores->file->get('ttl', null, 'string'));
    }

    public function testCaseInsensitiveReadsFallBackOnPhalcon(): void
    {
        $config = $this->config;

        $this->assertSame('/', $config->APP->Base_Uri);
        $this->assertSame('/s/', $config->app->static_uri);
        $this->assertSame('/s/', $config['app']['STATIC_URI']);
        $this->assertSame('/s/', $config->path('App.static_uri'));
        $this->assertTrue(isset($config->App->static_uri));
        $this->assertTrue($config->has('CACHE'));
    }

    public function testMissingAndNullValues(): void
    {
        $config = $this->config;

        $this->assertNull($config->app->unknown);
        $this->assertNull($config->app->nothing);
        $this->assertFalse(isset($config->app->unknown));
        // Same as Phalcon: isset() is true for a key holding null.
        $phalcon = new PhalconConfig(['app' => ['nothing' => null]]);
        $this->assertSame(isset($phalcon->app->nothing), isset($config->app->nothing));
        $this->assertTrue($config->app->has('nothing'));
        $this->assertSame('default', $config->path('app.unknown', 'default'));
        $this->assertSame('default', $config->path('app.base_uri.deeper', 'default'));
        $this->assertSame('default', $config->get('unknown', 'default'));
    }

    public function testKeyContainingTheDelimiter(): void
    {
        $data = ['mail.host' => 'x', 'Smtp.Port' => 25, 'mail' => ['host' => 'nested']];
        $config = new Config($data);
        $phalcon = new PhalconConfig($data);

        foreach (['mail.host', 'smtp.port', 'mail/host'] as $path) {
            $this->assertSame($phalcon->path($path, 'def'), $config->path($path, 'def'), $path);
        }
        $this->assertSame('x', $config->path('mail.host'));
        $this->assertSame(25, $config->path('smtp.port'));
    }

    public function testWritingAKeyWithAnotherCase(): void
    {
        $config = new Config(['app' => ['debug' => false]]);
        $phalcon = new PhalconConfig(['app' => ['debug' => false]]);

        foreach ([$config, $phalcon] as $c) {
            $c->app->DEBUG = true;
        }
        $this->assertSame($phalcon->app->debug, $config->app->debug);
        $this->assertTrue($config->app->debug);
        $this->assertTrue($config->path('app.debug'));
        $this->assertTrue($config->app->get('debug'));
        $this->assertTrue($config['app']['debug']);

        $config->app['Debug'] = 2;
        $config->app->set('dEbug', 3);
        $this->assertSame(3, $config->app->debug);
        $this->assertSame(['dEbug' => 3], $config->app->toArray());

        $merged = $config->merge(['APP' => ['DEBUG' => 4]]);
        $this->assertSame(4, $merged->path('app.debug'));
        $this->assertSame(4, $merged->app->debug);

        $config->app->remove('debug');
        $this->assertFalse($config->app->has('debug'));
        $this->assertSame([], $config->app->toArray());
    }

    public function testWritesMergeAndExport(): void
    {
        $config = $this->config;
        $config->app->base_uri = '/new/';
        $config['extra'] = ['a' => 1];

        $merged = $config->merge(['app' => ['added' => true]]);

        $this->assertSame('/new/', $config->app->base_uri);
        $this->assertInstanceOf(Config::class, $config->extra);
        $this->assertInstanceOf(Config::class, $merged->app);
        $this->assertTrue($merged->path('app.added'));
        $this->assertSame(['ttl' => 60], $config->cache->stores->file->toArray());
    }
}
