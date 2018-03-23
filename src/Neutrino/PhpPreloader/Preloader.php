<?php

namespace Neutrino\PhpPreloader;

use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract as PrettyPrinter;
use RuntimeException;

/**
 * Class AbstractPreloader
 *
 * @package Neutrino\PhpParser
 */
class Preloader
{
    /**
     * The printer.
     *
     * @var \PhpParser\PrettyPrinterAbstract
     */
    protected $printer;

    /**
     * The parser.
     *
     * @var \PhpParser\Parser
     */
    protected $parser;

    /**
     * The traverser.
     *
     * @var \PhpParser\NodeTraverserInterface
     */
    protected $traverser;

    /**
     * AbstractPreloader constructor.
     *
     * @param \PhpParser\PrettyPrinterAbstract  $printer
     * @param \PhpParser\Parser                 $parser
     * @param \PhpParser\NodeTraverserInterface $traverser
     */
    public function __construct(PrettyPrinter $printer, Parser $parser, NodeTraverserInterface $traverser)
    {
        $this->printer = $printer;
        $this->parser = $parser;
        $this->traverser = $traverser;
    }

    /**
     * Prepare the output file and directory.
     *
     * @param string $file
     * @param bool   $strict Use strict type. Only for PHP7
     *
     * @return resource
     */
    public function prepareOutput($file, $strict = false)
    {
        if ($strict && PHP_VERSION_ID < 70000) {
            throw new RuntimeException('Strict mode requires PHP 7 or greater.');
        }

        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new RuntimeException("Unable to create directory $dir.");
        }

        $r = fopen($file, 'w');

        if (!$r) {
            throw new RuntimeException("Unable to open $file for writing.");
        }

        if ($strict) {
            fwrite($r, "<?php declare(strict_types=1);\n");
        } else {
            fwrite($r, "<?php\n");
        }

        return $r;
    }

    /**
     * Get a pretty printed string of code from a file while applying visitors.
     *
     * @param string $file
     *
     * @return string
     */
    public function getCode($file)
    {
        $stmts = $this->parse($file);
        $stmts = $this->traverse($stmts);
        $content = $this->prettyPrint($stmts);

        return $content;
    }

    /**
     * Parses PHP code into a node tree.
     *
     * @param string $file
     *
     * @return null|\PhpParser\Node[]
     * @throws \RuntimeException
     */
    public function parse($file)
    {
        if (!is_string($file) || empty($file)) {
            throw new RuntimeException('Invalid filename provided.');
        }

        if (!is_readable($file)) {
            throw new RuntimeException("Cannot open $file for reading.");
        }

        $content = php_strip_whitespace($file);

        return $this->parser->parse($content);
    }

    /**
     * Traverses an array of nodes using the registered visitors.
     *
     * @param \PhpParser\Node[] $stmts
     *
     * @return \PhpParser\Node[]
     */
    public function traverse(array $stmts)
    {
        return $this->traverser->traverse($stmts);
    }

    /**
     * Pretty prints an array of statements.
     *
     * @param \PhpParser\Node[] $stmts
     *
     * @return string
     */
    public function prettyPrint(array $stmts)
    {
        return $this->printer->prettyPrint($stmts);
    }

    /**
     * @return \PhpParser\PrettyPrinterAbstract
     */
    public function getPrinter()
    {
        return $this->printer;
    }

    /**
     * @return \PhpParser\Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @return \PhpParser\NodeTraverserInterface
     */
    public function getTraverser()
    {
        return $this->traverser;
    }
}
