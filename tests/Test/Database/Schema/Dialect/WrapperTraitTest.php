<?php

namespace Test\Database\Schema\Dialect;

use Neutrino\Database\Schema\Dialect\WrapperTrait;
use Phalcon\Db\DialectInterface;
use Test\TestCase\TestCase;

/**
 * Class TestWrapperTrait
 *
 * @package Test\Database\Schema\Dialect
 */
class WrapperTraitTest extends TestCase
{
    public function dataDialectInterfaceMethods()
    {
        $tests = [];

        $reflection = new \ReflectionClass(DialectInterface::class);

        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            if ($method->isFinal() || !$method->isPublic()) {
                continue;
            }

            $arguments = [];

            $parameters = $method->getParameters();
            foreach ($parameters as $parameter) {
                if (($class = $parameter->getClass())) {
                    $arguments[] = $this->createMock($class->getName());
                } elseif ($parameter->isArray()) {
                    $arguments[] = [];
                } elseif ($parameter->isCallable()) {
                    $arguments[] = function () {
                    };
                } elseif (!$parameter->allowsNull()) {
                    $arguments[] = '';
                } else {
                    $arguments[] = null;
                }
            }

            $tests[$method->getName()] = [
                'name'      => $method->getName(),
                'arguments' => $arguments
            ];
        }

        return $tests;
    }

    /**
     * @dataProvider dataDialectInterfaceMethods
     *
     * @param $method
     * @param $arguments
     */
    public function testDialectInterfaceMethodsMapping($method, $arguments)
    {
        $dialect = $this->createMock(DialectInterface::class);

        $dialect
            ->expects($this->once())
            ->method($method)
            ->with(...$arguments);

        $wrapper = $this->getMockForTrait(
            WrapperTrait::class,
            [$dialect]
        );

        $wrapper->$method(...$arguments);
    }

    public function testMagicCallMapping()
    {
        $methods = array_keys($this->dataDialectInterfaceMethods());
        $methods[] = 'test';

        $dialect = $this->getMockBuilder(DialectInterface::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->setMethods($methods)
            ->getMock();

        $dialect
            ->expects($this->once())
            ->method('test')
            ->with(null, 123, 'abc');

        $wrapper = $this->getMockForTrait(
            WrapperTrait::class,
            [$dialect]
        );

        $wrapper->test(null, 123, 'abc');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testBadMethodCallException()
    {
        $wrapper = $this->getMockForTrait(
            WrapperTrait::class,
            [$this->createMock(DialectInterface::class)]
        );

        $wrapper->test(null, 123, 'abc');
    }
}
