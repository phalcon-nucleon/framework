<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Table;
use Neutrino\Cli\Task;

/**
 * Class OpcacheTask
 *
 * @property-read \Neutrino\Opcache\Manager $opcache
 *
 * @package Neutrino\Foundation\Cli\Tasks
 */
class OpcacheTask extends Task
{
    /**
     * @description Opcache reset.
     */
    public function resetAction()
    {
        if ($this->opcache->reset()) {
            $this->info('Opcache has been reset');
        } else {
            $this->warn('Opcache reset fail. Check if Opcache is correctly loaded and enabled');
        }
    }

    /**
     * @description Output Opcache status.
     */
    public function statusAction()
    {
        $status = $this->opcache->status(false);

        if (empty($status)) {
            $this->warn('Opcache reset fail. Check if Opcache is correctly loaded and enabled');

            return;
        }

        $this->info('Global Status : ');

        $this->table([
            ['enable', $status['opcache_enabled']],
            ['full', $status['cache_full']],
            ['restart_pending', $status['restart_pending']],
            ['restart_in_progress', $status['restart_in_progress']],
        ], [], Table::NO_STYLE | Table::NO_HEADER);

        $this->info('Memory Status : ');

        $this->table([
            ['used', $status['memory_usage']['used_memory']],
            ['free', $status['memory_usage']['free_memory']],
            ['waste', $status['memory_usage']['wasted_memory']],
            ['wasted %', $status['memory_usage']['current_wasted_percentage']],
        ], [], Table::NO_STYLE | Table::NO_HEADER);

        $this->info('Interned Strings Status : ');

        $this->table([
            ['size', $status['interned_strings_usage']['buffer_size']],
            ['used', $status['interned_strings_usage']['used_memory']],
            ['free', $status['interned_strings_usage']['free_memory']],
            ['number', $status['interned_strings_usage']['number_of_strings']],
        ], [], Table::NO_STYLE | Table::NO_HEADER);

        $this->info('Statistics : ');

        $this->table([
            ['Scripts cached', $status['opcache_statistics']['num_cached_scripts']],
            ['Keys cached', $status['opcache_statistics']['num_cached_keys']],
            ['hits', $status['opcache_statistics']['hits']],
            ['hit rate', $status['opcache_statistics']['opcache_hit_rate']],
        ], [], Table::NO_STYLE | Table::NO_HEADER);
    }
}