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
            ['enable', $this->formatBool($status['opcache_enabled'])],
            ['full', $this->formatBool($status['cache_full'])],
            ['restart_pending', $this->formatBool($status['restart_pending'])],
            ['restart_in_progress', $this->formatBool($status['restart_in_progress'])],
        ], [], Table::NO_STYLE | Table::NO_HEADER);

        $this->info('Memory Status : ');

        $this->table([
            ['used', $this->formatOctet($status['memory_usage']['used_memory'], 'm'), '(' . $status['memory_usage']['used_memory'] . ')'],
            ['free', $this->formatOctet($status['memory_usage']['free_memory'], 'm'), '(' . $status['memory_usage']['free_memory'] . ')'],
            ['waste', $this->formatOctet($status['memory_usage']['wasted_memory'], 'm'), '(' . $status['memory_usage']['wasted_memory'] . ')'],
            ['wasted %', $status['memory_usage']['current_wasted_percentage']],
        ], [], Table::NO_STYLE | Table::NO_HEADER);

        $this->info('Interned Strings Status : ');

        $this->table([
            ['size', $this->formatOctet($status['interned_strings_usage']['buffer_size'], 'm'), '(' . $status['interned_strings_usage']['buffer_size'] . ')'],
            ['used', $this->formatOctet($status['interned_strings_usage']['used_memory'], 'm'), '(' . $status['interned_strings_usage']['used_memory'] . ')'],
            ['free', $this->formatOctet($status['interned_strings_usage']['free_memory'], 'm'), '(' . $status['interned_strings_usage']['free_memory'] . ')'],
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

    private function formatOctet($value, $unit, $precision = 2)
    {
        $units = ['o', 'k', 'm', 'g', 't', 'p', 'e', 'z', 'y'];

        $unit = strtolower($unit);

        foreach ($units as $u) {
            if ($u === $unit) {
                break;
            }

            $value = $value / 1024;
        }

        return round($value, $precision) . ' ' . strtoupper($unit) . 'b';
    }

    private function formatBool($value, $true = 'yes', $false = 'no')
    {
        return $value ? $true : $false;
    }
}
