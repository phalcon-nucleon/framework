<?php

namespace Neutrino\Cli\Output;

use Neutrino\Support\Str;

/**
 * Class Group
 *
 * @package Neutrino\Cli\Output
 */
class Group
{
    const NONE = 0;

    const KEY_SORTED = 2;

    const KEY_REVERSE_SORTED = 4;

    protected $output;

    protected $datas;

    protected $options;

    protected $groups = [];

    /**
     * Group constructor.
     *
     * @param Writer $output
     * @param array  $datas
     * @param int    $options
     */
    public function __construct(
        Writer $output,
        array $datas = [],
        $options = self::NONE
    )
    {
        $this->output = $output;
        $this->datas = $datas;
        $this->options = $options;
    }

    protected function generateGroupData()
    {
        foreach ($this->datas as $key => $data) {
            $washKey = Helper::removeDecoration($key);
            if (Str::contains($washKey, ':')) {
                $keys = explode(':', $washKey);

                $group = $keys[0];

                $this->groups[$group][$key] = $data;
            } else {
                $this->groups['_default'][$key] = $data;
            }
        }

        if ($this->options & self::KEY_REVERSE_SORTED) {
            krsort($this->groups);
            foreach ($this->groups as &$group) {
                krsort($group);
            }
        } elseif ($this->options & self::KEY_SORTED) {
            ksort($this->groups);
            foreach ($this->groups as &$group) {
                ksort($group);
            }
        }
    }

    public function display()
    {
        $this->generateGroupData();

        $tableOutput = new Table($this->output, [], [], Table::NO_STYLE | Table::NO_HEADER);

        // Browser all data for generate correct columns length
        foreach ($this->groups as $group => $datas) {
            $table = [];
            foreach ($datas as $key => $value) {
                $table[] = [$key, $value];
            }
            $tableOutput->setDatas($table)->generateColumns();
        }

        // Display elements
        foreach ($this->groups as $group => $datas) {
            if ($group != '_default') {
                $this->output->notice($group);
            }
            $table = [];
            foreach ($datas as $key => $value) {
                $table[] = [$key, $value];
            }
            $tableOutput->setDatas($table)->display();
        }
    }
}
