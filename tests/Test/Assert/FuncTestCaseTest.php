<?php
namespace Test\Assert;

use Luxury\Foundation\Kernelize;
use Luxury\Interfaces\Kernelable;
use Phalcon\Kernel;
use Phalcon\Mvc\Application;
use Test\TestCase\TestCase;

/**
 * Trait FuncTestCaseTest
 *
 * @package Test\Assert
 */
class FuncTestCaseTest extends TestCase
{

    public function setUp()
    {
        global $config;

        $config['application']['baseUri'] = '/';

        parent::setUp();

        $this->app->useImplicitView(false);
    }

    public function testAssertController()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        $this->assertController('Stub');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertControllerFail()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        $this->assertController('Blablabla');
    }

    public function testAssertAction()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        $this->assertAction('return');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertActionFail()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        $this->assertAction('Blablabla');
    }

    public function testResponseContentContains()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        $this->assertResponseContentContains('return');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testResponseContentContainsFail()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        $this->assertResponseContentContains('redirect');
    }

    public function testAssertRedirectTo()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/redirect');

        $this->assertRedirectTo('/');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertRedirectToFail()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/redirect');

        // THEN
        $this->assertRedirectTo('/wrong');
    }

    public function testAssertResponseCode()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/redirect');

        $this->assertResponseCode(302);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertResponseCodeFail()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        // THEN
        $this->assertResponseCode(302);
    }

    public function testAssertHeaders()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/redirect');

        $this->assertHeader([
            'Location' => '/',
            'Status'   => '302 Found',
        ]);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertHeadersFail()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/redirect');

        $this->assertHeader([
            'Location' => '/return',
        ]);
    }

    public function testAssertForwarded()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/forwarded');

        $this->assertDispatchIsForwarded();
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertForwardedFail()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/redirect');

        $this->assertDispatchIsForwarded();
    }

    public function testCheckExtension()
    {
        // GIVEN
        // WHEN
        $this->checkExtension('phalcon');
    }

    public function testCheckExtensionArray()
    {
        // GIVEN
        // WHEN
        $this->checkExtension([
            'phalcon',
            'xdebug'
        ]);
    }

    /**
     * @expectedException \PHPUnit_Framework_SkippedTestError
     */
    public function testCheckExtensionStringFail()
    {
        // GIVEN
        // WHEN
        $this->checkExtension('phalconista');
    }

    /**
     * @expectedException \PHPUnit_Framework_SkippedTestError
     */
    public function testCheckExtensionArrayFail()
    {
        // GIVEN
        // WHEN
        $this->checkExtension([
            'phalconista'
        ]);
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testNoRedirect()
    {
        // GIVEN
        // WHEN
        $this->dispatch('/return');

        // THEN
        $this->assertRedirectTo('/');
    }

    public function testStaticKernel()
    {
        $this->assertInstanceOf(Application::class, static::staticKernel());
        $this->assertInstanceOf(Kernelable::class, static::staticKernel());
    }
}
