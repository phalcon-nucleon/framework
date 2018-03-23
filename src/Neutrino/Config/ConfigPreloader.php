<?php

namespace Neutrino\Config;

use Neutrino\PhpPreloader\Exceptions\DirConstantException;
use Neutrino\PhpPreloader\Exceptions\FileConstantException;
use Neutrino\PhpPreloader\Factory;

/**
 * Class ConfigPreloader
 *
 * @package Neutrino\Config
 */
class ConfigPreloader
{
    private $preloader;
    private $returnConverter;

    public function __construct()
    {
        $this->preloader = (new Factory())->create();
        $this->returnConverter = new ReturnConverter('config');
        $this->preloader->getTraverser()->addVisitor($this->returnConverter);
    }

    public function compile(){

        try {

            $r = $this->preloader->prepareOutput(BASE_PATH . '/bootstrap/compile/config.php');

            fwrite($r, "\$config = [];\n");

            foreach (glob(BASE_PATH . '/config/*.php') as $file) {
                try {
                    $name = pathinfo($file, PATHINFO_FILENAME);

                    if ($name === 'compile') {
                        continue;
                    }

                    $this->returnConverter->setName($name);

                    fwrite($r, $this->preloader->getCode($file) . "\n");
                } catch (DirConstantException $e) {
                    $this->block([
                        "Usage of __DIR__ constant is prohibited. Use BASE_PATH . '/path' instead.",
                        "in : $file"
                    ], 'error');
                } catch (FileConstantException $e) {
                    $this->block([
                        "Usage of __FILE__ constant is prohibited. Use BASE_PATH . '/path' instead.",
                        "in : $file"
                    ], 'error');
                } catch (\Exception $e) {
                    $this->block([$e->getMessage()], 'error');
                }
            }

            fwrite($r, "return \$config;\n");
        } finally {
            if (isset($r) && is_resource($r)) {
                fclose($r);
            }
            if (isset($e)) {
                @unlink(BASE_PATH . '/bootstrap/compile/config.php');
            }
        }
    }
}
