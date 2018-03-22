<?php

namespace Neutrino\Foundation\Cli\Tasks;

use ClassPreloader\Exceptions\DirConstantException;
use ClassPreloader\Exceptions\FileConstantException;
use ClassPreloader\Parser\DirVisitor;
use ClassPreloader\Parser\FileVisitor;
use ClassPreloader\Parser\NodeTraverser;
use Neutrino\Cli\Task;
use Neutrino\PhpParser\UseRemover;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

/**
 * Class ConfigCacheTask
 *
 * @package     Neutrino\Foundation\Cli\Tasks
 */
class ConfigCacheTask extends Task
{

    /**
     * Configuration cache.
     *
     * @description Cache the configuration.
     *
     * @throws \Exception
     */
    public function mainAction()
    {
        $this->info('Generating configuration cache');

        $this->compileConfig();
    }

    public function compileConfig()
    {
        try {
            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());
            $traverser->addVisitor(new UseRemover());
            $traverser->addVisitor(new DirVisitor(true));
            $traverser->addVisitor(new FileVisitor(true));
            $printer = new PrettyPrinter();
            $r = $this->prepareOutput();

            foreach (glob(BASE_PATH . '/config/*.php') as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);

                if ($name === 'compile') {
                    continue;
                }

                $parsed = $parser->parse(php_strip_whitespace($file));
                $parsed = $traverser->traverse($parsed);

                $last = $parsed[count($parsed) - 1];

                if (!($last instanceof Return_)) {
                    throw new \Exception('Last statement must be the return of config.');
                }

                $exprAssign = new Assign(
                    new ArrayDimFetch(
                        new Variable('config'),
                        new String_($name)
                    ),
                    $last->expr
                );

                $parsed[count($parsed) - 1] = $exprAssign;

                fwrite($r, $printer->prettyPrint($parsed) . "\n");
            }

            $this->endOutput($r);
        } catch (DirConstantException $e) {
            $this->block(["Usage of __DIR__ constant is prohibited. Use BASE_PATH . '/path' instead."], 'error');
        } catch (FileConstantException $e) {
            $this->block(["Usage of __FILE__ constant is prohibited. Use BASE_PATH . '/path' instead."], 'error');
        } catch (\Exception $e) {
            $this->block([$e->getMessage()], 'error');
        } finally {
            if (isset($e)) {
                if (isset($r) && is_resource($r)) {
                    fclose($r);
                }
                @unlink(BASE_PATH . '/bootstrap/compile/config.php');
            }
        }
    }

    public function prepareOutput()
    {
        $handle = fopen(BASE_PATH . '/bootstrap/compile/config.php', 'w');

        fwrite($handle, "<?php\n\$config = [];\n");

        return $handle;
    }

    public function endOutput($handle)
    {
        fwrite($handle, "return \$config;\n");

        fclose($handle);
    }
}
