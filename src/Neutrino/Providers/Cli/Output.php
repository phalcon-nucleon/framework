<?php

namespace Neutrino\Providers\Cli;

use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Providers\Provider;

/**
 * Class Output
 *
 * @package     Neutrino\Providers\Cli
 */
class Output extends Provider
{
    protected $name = Services\Cli::OUTPUT;

    protected $shared = true;

    /**
     * Return the service to register
     *
     * Called when the services container tries to resolve the service
     *
     * @return mixed
     */
    protected function register()
    {
        return new Writer($this->application->isQuiet());
    }
}
