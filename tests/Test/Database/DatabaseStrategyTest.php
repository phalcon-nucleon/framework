<?php

namespace Test\Database;

use Neutrino\Database\DatabaseStrategy;
use Neutrino\Debug\Reflexion;
use Phalcon\Db\AdapterInterface;
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Index;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\Reference;
use Phalcon\Db\ReferenceInterface;

class DatabaseStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testProxy()
    {
        $mapInterfaces = [
          ColumnInterface::class => Column::class,
          IndexInterface::class => Index::class,
          ReferenceInterface::class => Reference::class,
        ];

        $db = Reflexion::getReflectionClass(DatabaseStrategy::class)->newInstanceWithoutConstructor();

        Reflexion::set($db, 'adapter', $watcher = new StubDbWatcher());

        $methods = Reflexion::getReflectionMethods(AdapterInterface::class);

        foreach ($methods as $method) {
            $parameters = [];

            foreach ($method->getParameters() as $parameter) {
                if ($parameter->isArray()) {
                    $parameters[] = [1, 2, 3];
                } elseif ($class = $parameter->getClass()) {
                    $class = $class->getName();
                    if(isset($mapInterfaces[$class])){
                        $class = $mapInterfaces[$class];
                    }
                    $parameters[] = Reflexion::getReflectionClass($class)->newInstanceWithoutConstructor();
                } else {
                    $parameters[] = null;
                }
            }

            $parameters[] = 4;
            $parameters[] = 5;
            $parameters[] = 6;

            $db->{$method->getName()}(...$parameters);

            $last = array_pop($watcher->watched);

            $this->assertEquals($last['name'], $method->getName());
            $this->assertEquals($last['args'], $parameters);
        }
    }

    public function testProxyMagic()
    {
        $db = Reflexion::getReflectionClass(DatabaseStrategy::class)->newInstanceWithoutConstructor();

        Reflexion::set($db, 'adapter', $watcher = new StubDbWatcher());

        $db->testMethod(1, 2, 3, 4);

        $last = array_pop($watcher->watched);

        $this->assertEquals($last['name'], 'testMethod');
        $this->assertEquals($last['args'], [1, 2, 3, 4]);
    }
}

class StubDbWatcher
{
    public $watched;

    public function __call($name, $arguments)
    {
        $this->watched[] = [
          'name' => $name, 'args' =>  $arguments
        ];
    }
}
