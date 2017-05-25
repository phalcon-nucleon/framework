<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Task;
use Neutrino\Config\Loader;

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

        self::generateCache();
    }

    public static function generateCache()
    {
        $config = Loader::raw(BASE_PATH, ['compile']);

        $handle = fopen(BASE_PATH . '/bootstrap/compile/config.php', 'w');

        if ($handle === false) {
            throw new \Exception;
        }

        fwrite($handle, '<?php' . PHP_EOL);

        fwrite($handle, 'return ' . var_export($config, true) . ';' . PHP_EOL);

        fclose($handle);
    }
}
