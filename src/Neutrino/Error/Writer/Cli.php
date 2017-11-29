<?php

namespace Neutrino\Error\Writer;

use Neutrino\Cli\Output\Block;
use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Di;
use Phalcon\Logger as PhalconLogger;

/**
 * Class Cli
 *
 * @package     Neutrino\Error\Writer
 */
class Cli implements Writable
{
    /**
     * @inheritdoc
     */
    public function handle(Error $error)
    {
        $di = Di::getDefault();

        if ($di && $di->has(Services\Cli::OUTPUT)) {
            $output = $di->getShared(Services\Cli::OUTPUT);

            $block = new Block($output, $this->getColoration($error), ['padding' => 4]);

            $block->draw(explode("\n", Helper::format($error)));
        } else {
            echo Helper::format($error);
        }
    }

    /**
     * @param \Neutrino\Error\Error $error
     *
     * @return string
     */
    protected function getColoration(Error $error)
    {
        if($error->isException){
            return 'error';
        }

        switch (Helper::getLogType($error->code)){
            case PhalconLogger::ALERT:
            case PhalconLogger::CRITICAL:
            case PhalconLogger::EMERGENCY:
            case PhalconLogger::ERROR:
                return 'error';
            case PhalconLogger::WARNING:
                return 'warn';
            case PhalconLogger::NOTICE:
                return 'notice';
            case PhalconLogger::INFO:
                return 'info';
            default:
                return 'error';
        }
    }
}
