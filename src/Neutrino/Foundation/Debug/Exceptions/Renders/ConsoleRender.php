<?php


namespace Neutrino\Foundation\Debug\Exceptions\Renders;

use Neutrino\Constants\Services;
use Neutrino\Cli\Output\Block;
use Neutrino\Debug\Exceptions\Helper;
use Neutrino\Foundation\Debug\Exceptions\RenderInterface;
use Phalcon\Logger;

/**
 * Class ConsoleRender
 * @package Neutrino\Foundation\Debug\Exceptions
 */
class ConsoleRender implements RenderInterface
{
    /**
     * @param \Throwable|\Exception $throwable
     * @param \Phalcon\DiInterface  $container
     */
    public function render($throwable, $container = null)
    {
        if ($container && $container->has(Services\Cli::OUTPUT)) {
            /** @var \Neutrino\Cli\Output\Writer $output */
            $output = $container->getShared(Services\Cli::OUTPUT);

            $output->line('');

            $block = new Block($output, $this->getColoration($throwable), ['padding' => 4]);

            $block->draw(explode("\n", Helper::verbose($throwable)));
        } else {
            echo Helper::verbose($throwable);
        }
    }

    /**
     * @param \Throwable|\Exception $throwable
     *
     * @return string
     */
    protected function getColoration($throwable)
    {
        switch (Helper::logLevel($throwable)) {
            case Logger::ALERT:
            case Logger::CRITICAL:
            case Logger::EMERGENCY:
            case Logger::ERROR:
                return 'error';
            case Logger::WARNING:
                return 'warn';
            case Logger::NOTICE:
                return 'notice';
            case Logger::INFO:
                return 'info';
            default:
                return 'error';
        }
    }
}