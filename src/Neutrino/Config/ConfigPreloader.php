<?php

namespace Neutrino\Config;

use Neutrino\PhpPreloader\Exceptions\DirConstantException;
use Neutrino\PhpPreloader\Exceptions\FileConstantException;
use Neutrino\PhpPreloader\Factory;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;

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

    /**
     * @throws \Neutrino\PhpPreloader\Exceptions\DirConstantException
     * @throws \Neutrino\PhpPreloader\Exceptions\FileConstantException
     * @throws \Exception
     */
    public function compile()
    {
        $outputFile = BASE_PATH . '/bootstrap/compile/config.php';
        try {
            $r = $this->preloader->prepareOutput($outputFile);

            $nodes = [
                new Assign(
                    new Variable('config'),
                    new Array_([], ['kind' => Array_::KIND_SHORT])
                )
            ];

            foreach (glob(BASE_PATH . '/config/*.php') as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);

                if ($name === 'compile') {
                    continue;
                }

                $this->returnConverter->setName($name);

                $stmts = $this->preloader->parse($file);
                $stmts = $this->preloader->traverse($stmts);

                $nodes = array_merge($nodes, $stmts);
            }

            $nodes[] = new Return_(
                new Variable('config')
            );

            fwrite($r, $this->preloader->prettyPrint($nodes));

        } catch (DirConstantException $e) {
            throw new DirConstantException(
                "Usage of __DIR__ constant is prohibited. Use BASE_PATH . '/path_to_dir' instead.\nin : $file",
                $e->getCode()
            );
        } catch (FileConstantException $e) {
            throw new FileConstantException(
                "Usage of __FILE__ constant is prohibited. Use BASE_PATH . '/path_to_file' instead.\nin : $file",
                $e->getCode()
            );
        } finally {
            if (isset($r) && is_resource($r)) {
                fclose($r);
            }
            if (isset($e)) {
                @unlink($outputFile);
            }
        }
    }
}
