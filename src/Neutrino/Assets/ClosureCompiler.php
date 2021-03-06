<?php

namespace Neutrino\Assets;

use Neutrino\Assets\Exception\CompilatorException;
use Neutrino\Http\Standards\Method;
use Neutrino\HttpClient\Factory;
use Neutrino\HttpClient\Parser\JsonArray;

/**
 * Class JsCompiler
 *
 * Neutrino\Assets
 */
class ClosureCompiler implements AssetsCompilator
{

    /**
     * @param array $options
     *
     * @return mixed
     * @throws CompilatorException
     * @throws \Exception
     */
    public function compile(array $options)
    {
        $jsCode = $this->extractJsCode($options['compile']['directories']);
        $jsCode = $this->applyPrecompilation($jsCode, isset($options['precompilations']) ? $options['precompilations'] : []);

        $data = [
          'compilation_level' => $options['compile']['level'],
          'output_format' => 'json',
          'output_info' => ['warnings', 'errors', 'statistics', 'compiled_code'],
          'warning_level' => 'default',
        ];

        if (!empty($options['compile']['externs_url'])) {
            $data['externs_url'] = $options['compile']['externs_url'];
        }

        if (!empty($options['compile']['js_externs'])) {
            $data['js_externs'] = $this->extractJsCode($options['compile']['js_externs']);
        }

        $query = http_build_query(['js_code' => $jsCode]) . '&' . preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query($data));

        $request = Factory::makeRequest();
        $request
            ->setMethod(Method::POST)
            ->setUri('https://closure-compiler.appspot.com/compile')
            ->setParams($query)
            ->disableSsl();

        $response = $request->send();

        if (!$response->isOk()) {
            throw new CompilatorException(
                "Can't call closure compile api.\n" .
                "HTTP Status : {$response->getHeader()->get('Status')}\n" .
                "HTTP Erreur : [{$response->getErrorCode()}]: {$response->getError()}\n" .
                "HTTP Body   : {$response->getBody()}"
            );
        }

        $content = $response->parse(JsonArray::class)->getData();

        file_put_contents(BASE_PATH . '/' . $options['output_file'], $content['compiledCode']);

        unset($content['compiledCode']);

        return $content;
    }

    private function applyPrecompilation($content, array $precompilators)
    {
        foreach ($precompilators as $precompilator => $options) {
            if (is_string($options)) {
                $precompilator = $options;
                $options = [];
            }

            /** @var \Neutrino\Assets\Closure\Precompilation $precompilator */
            $precompilator = new $precompilator($options);

            $content = $precompilator->precompile($content);
        }

        return $content;
    }

    private function extractJsCode(array $directories)
    {
        $content = [];

        foreach ($directories as $directory) {
            foreach ($this->getDirFiles(BASE_PATH . '/' . $directory) as $item) {
                if (is_file($item)) {
                    $content[] = file_get_contents($item);
                }
            }
        }

        return implode(';', $content);
    }

    private function getDirFiles($path)
    {
        $files = [];

        $paths = glob($path . '/*');

        usort($paths, function ($a, $b) {
            if (is_file($a)) {
                return -1;
            }
            if (is_file($b)) {
                return 1;
            }
            return 0;
        });

        foreach ($paths as $item) {
            if (is_dir($item)) {
                $files = array_merge($files, $this->getDirFiles($item));
            } elseif (is_file($item)) {
                $files[] = $item;
            }
        }

        return $files;
    }
}
