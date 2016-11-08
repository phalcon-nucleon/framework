<?php
namespace Test\Support;

use Luxury\Support\Traits\Macroable;
use Test\TestCase\TestCase;

/**
 * Trait MacroableTest
 *
 * @package Support
 */
class MacroableTest extends TestCase
{
    /**
     * @var Macroable
     */
    private $macroable;

    public function setUp()
    {
        parent::setUp();
        
        $this->macroable = $this->createObjectForTrait();
    }

    private function createObjectForTrait()
    {
        return $this->getObjectForTrait(Macroable::class);
    }

    public function testRegisterMacro()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertEquals('Taylor', $macroable::{__CLASS__}());
    }

    public function testRegisterMacroAndCallWithoutStatic()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertEquals('Taylor', $macroable->{__CLASS__}());
    }

    public function testRegisterMacroAndCallWithoutStaticCallable()
    {
        $obj       = new StubMacroable;
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, $obj);
        $this->assertEquals('Taylor', $macroable->{__CLASS__}());
    }

    public function testNotFoundMethod()
    {
        $method = __CLASS__;

        $this->setExpectedException(\BadMethodCallException::class,
            "Method {$method} does not exist.");

        $this->macroable->{$method}();
    }

    public function testNotFoundMethodStatic()
    {
        $macroable = $this->macroable;
        $this->setExpectedException(\BadMethodCallException::class,
            "Method " . __CLASS__ . " does not exist.");

        $this->assertEquals('Taylor', $macroable::{__CLASS__}());
    }

    public function testWhenCallingMacroClosureIsBoundToObject()
    {
        TestMacroable::macro('tryInstance', function () {
            return $this->protectedVariable;
        });
        TestMacroable::macro('tryStatic', function () {
            return static::getProtectedStatic();
        });
        $instance = new TestMacroable;
        $result   = $instance->tryInstance();
        $this->assertEquals('instance', $result);
        $result = TestMacroable::tryStatic();
        $this->assertEquals('static', $result);

        $method = __METHOD__;
        TestMacroable::macro($method, [$instance, 'func']);

        $this->assertEquals(123, TestMacroable::$method());
    }
}

class TestMacroable
{
    use Macroable;
    protected $protectedVariable = 'instance';

    protected static function getProtectedStatic()
    {
        return 'static';
    }

    public function func()
    {
        return 123;
    }
}

class StubMacroable
{
    function __invoke()
    {
        return 'Taylor';
    }
}