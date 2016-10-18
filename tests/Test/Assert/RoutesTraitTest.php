<?php

namespace Test\Assert;

use Luxury\Test\Helpers\RoutesTrait;
use Phalcon\Http\Request\Method;
use Test\TestCase\TestCase;

/**
 * Trait RoutesTestCaseTest
 *
 * @package Test\Assert
 */
class RoutesTraitTest extends TestCase
{
    use RoutesTrait;

    public function testAssertRoute_Ok()
    {
        $this->assertRoute('', Method::GET, true, 'Stub', 'index');
    }

    public function testAssertRoute_Ok_WithParams()
    {
        $this->assertRoute('/parameted/param_1', Method::GET, true, 'Stub', 'index', ['tags' => 'param_1']);
    }

    public function testAssertRoute_Ok_WithParams_2()
    {
        $this->assertRoute(
            '/parameted/param_1/123',
            Method::GET,
            true,
            'Stub',
            'index',
            ['tags' => 'param_1', 'page' => '123']
        );
    }

    public function testAssertRoute_Ok_Whitout_GivenController()
    {
        $this->assertRoute('', Method::GET, true);
    }

    public function testAssertRoute_Ok_Fail()
    {
        $this->assertRoute('/fail', Method::GET, false);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertRoute_WrongMethod()
    {
        $this->assertRoute('', Method::POST, true, 'Stub', 'wrong');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertRoute_WrongController()
    {
        $this->assertRoute('', Method::GET, true, 'Wrong', 'index');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertRoute_WrongAction()
    {
        $this->assertRoute('', Method::GET, true, 'Stub', 'wrong');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertRoute_WrongParams()
    {
        $this->assertRoute('/parameted/1.2.3.4', Method::GET, true, 'Stub', 'index', ['tags' => 'param_1']);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertRoute_WrongParams_2()
    {
        $this->assertRoute('/parameted/abc123/zyx', Method::GET, true, 'Stub', 'index', ['tags' => 'param_1']);
    }
}
