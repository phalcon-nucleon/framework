<?php

namespace Neutrino\PhpPreloader;

use Neutrino\PhpPreloader\Visitors\DirConstVisitor;
use Neutrino\PhpPreloader\Visitors\FileConstVisitor;
use Neutrino\PhpPreloader\Visitors\UseRemoverVisitor;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

/**
 * Class Factory
 *
 * @package Neutrino\PhpParser
 */
class Factory
{
    /**
     * @return \Neutrino\PhpPreloader\Preloader
     */
    public function create()
    {
        return new Preloader(
            $this->getPrinter(),
            $this->getParser(),
            $this->getTraverser()
        );
    }

    /**
     * @return \PhpParser\Parser
     */
    protected function getParser()
    {
        return (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @return \PhpParser\NodeTraverser
     */
    protected function getTraverser()
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new UseRemoverVisitor());
        $traverser->addVisitor(new DirConstVisitor());
        $traverser->addVisitor(new FileConstVisitor());

        return $traverser;
    }

    /**
     * @return \PhpParser\PrettyPrinter\Standard
     */
    protected function getPrinter()
    {
        return new PrettyPrinter();
    }
}
