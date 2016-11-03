<?php

namespace Luxury\Foundation\Cli;

use Luxury\Cli\Output\Helper;
use Luxury\Cli\Task;
use Luxury\Support\Arr;

/**
 * Class HelperTask
 *
 * @package     Luxury\Foundation\Cli
 */
class HelperTask extends Task
{
    public function beforeExecuteRoute()
    {
    }

    public function mainAction()
    {
        $infos = Helper::getTaskInfos(
            $this->getArg('task'),
            $this->getArg('action') . $this->dispatcher->getActionSuffix()
        );

        $this->line($infos['description']);
        if (Arr::has($infos, 'arguments')) {
            foreach ($infos['arguments'] as $argument) {
                $this->line('param: ' . $argument);
            }
        }
        if (Arr::has($infos, 'options')) {
            foreach ($infos['options'] as $option) {
                $this->line('opts: ' . $option);
            }
        }
    }
}
