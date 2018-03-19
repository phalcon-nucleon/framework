<?php

namespace Test\Assets;

use Neutrino\Assets\ClosureCompiler;
use Neutrino\Debug\Reflexion;
use Neutrino\Http\Standards\Method;
use Neutrino\HttpClient\Provider\Curl;
use Neutrino\HttpClient\Response;
use Test\TestCase\TestCase;

class ClosureCompilerTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $dir = BASE_PATH . '/resources_assets_js';

        @mkdir($dir);
        @file_put_contents($dir.'/app.js', "$('#someid .test')");
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        $dir = BASE_PATH . '/resources_assets_js';
        @unlink(BASE_PATH.'/public/js_app.js');
        @unlink($dir.'/app.js');
        @rmdir($dir);
    }

    private function getOptions(){
        return
          $options = [
            'compile' => [
              'directories' => [
                'resources_assets_js'
              ],
              'level' => 'ADVANCED_OPTIMIZATIONS',
              'externs_url' => [
                'http://code.jquery.com/jquery-3.3.1.js',
              ]
            ],
            'precompilations' => [
              \Neutrino\Assets\Closure\JqueryIdPrecompilation::class,
              \Neutrino\Assets\Closure\DebugPrecompilation::class => [
                'debug' => APP_DEBUG
              ],
              \Neutrino\Assets\Closure\GlobalClosurePrecompilation::class => [
                'window' => 'window',
                'document' => 'document',
                'jQuery' => 'jQuery',
              ]
            ],
            'output_file' => 'public/js_app.js'
          ];
    }

    /**
     * @expectedException \Neutrino\Assets\Exception\CompilatorException
     */
    public function testApiError()
    {
        $options = $this->getOptions();

        Reflexion::set(Curl::class, 'isAvailable', true);
        $curl = $this->mockService(Curl::class, Curl::class, false);
        $curl->expects($this->once())->method('setMethod')->with(Method::POST)->willReturnSelf();
        $curl->expects($this->once())->method('setHeader')->with('Content-type', 'application/x-www-form-urlencoded')->willReturnSelf();
        $curl->expects($this->once())->method('setUri')->with('https://closure-compiler.appspot.com/compile')->willReturnSelf();
        $curl->expects($this->once())->method('setParams')->willReturnSelf();
        $curl->expects($this->once())->method('disableSsl')->willReturnSelf();

        $curl->expects($this->once())->method('send')->willReturn($response = new Response());

        $response->setCode(400);

        $compiler = new ClosureCompiler();

        $compiler->compile($options);
    }

    public function testCompile()
    {
        $options = $this->getOptions();

        $jsCode = <<<JS
(function(window,document,jQuery){
  /**
* @param {...*} _arg
*/
function debug(_arg){
    console.log.apply(console, arguments);
}/**
* @param {string} id
* @return {(*|Window|Document|Element|Array<Element>|string|NodeList)}
*/
function jQuerySelectorSpeedhack(id) {
    return jQuery(document.getElementById(id))
}jQuerySelectorSpeedhack('someid').find('.test')
})(window,document,jQuery);
JS;

        $data = [
          'js_code' => $jsCode,
          'compilation_level' => 'ADVANCED_OPTIMIZATIONS',
          'output_format' => 'json',
          'output_info' => ['warnings', 'errors', 'statistics', 'compiled_code'],
          'warning_level' => 'default',
          'externs_url' => [
            'http://code.jquery.com/jquery-3.3.1.js',
          ]
        ];

        $query = http_build_query($data);
        $query = preg_replace('/%5B[0-9]+%5D/simU', '', $query);

        Reflexion::set(Curl::class, 'isAvailable', true);
        $curl = $this->mockService(Curl::class, Curl::class, false);
        $curl->expects($this->once())->method('setMethod')->with(Method::POST)->willReturnSelf();
        $curl->expects($this->once())->method('setHeader')->with('Content-type', 'application/x-www-form-urlencoded')->willReturnSelf();
        $curl->expects($this->once())->method('setUri')->with('https://closure-compiler.appspot.com/compile')->willReturnSelf();
        $curl->expects($this->once())->method('setParams')->with($query)->willReturnSelf();
        $curl->expects($this->once())->method('disableSsl')->willReturnSelf();

        $curl->expects($this->once())->method('send')->willReturn($response = new Response());

        $response->setCode(200);
        $response->setBody(json_encode([
          'compiledCode' => 'compiledCode'
        ]));

        $compiler = new ClosureCompiler();

        $compiler->compile($options);

        $this->assertEquals('compiledCode', file_get_contents(BASE_PATH . '/public/js_app.js'));
    }
}
