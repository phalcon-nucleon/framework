<?php

namespace Luxury\Cli\Output;

use Luxury\Support\Arr;
use Luxury\Support\Str;

/**
 * Class Table
 *
 * @package     Luxury\Cli\Output
 */
class Table
{
    protected $output;

    protected $datas;

    protected $columns = [];

    /**
     * Table constructor.
     *
     * @param array $datas
     */
    public function __construct(ConsoleOutput $output, array $datas = [])
    {
        $this->output = $output;
        $this->datas  = $datas;
    }

    protected function generateColumns()
    {
        foreach ($this->datas as $data) {
            foreach ($data as $column => $value) {
                if (!Arr::has($this->columns, $column)) {
                    $this->columns[$column] = [
                        'size' => max(strlen($column), strlen($value))
                    ];
                    continue;
                }

                $this->columns[$column]['size'] = max($this->columns[$column]['size'], strlen($value));
            }
        }
    }

    protected function separator()
    {
        $line = '+';
        foreach ($this->columns as $column => $opts) {
            $line .= '-' . str_pad('-', $opts['size'], '-') . '-+';
        }
        $this->output->write($line, true);
    }

    protected function header()
    {
        $line = '|';
        foreach ($this->columns as $column => $opts) {
            $line .= ' ' . str_pad(Str::upper($column), $opts['size'], ' ') . ' |';
        }
        $this->output->write($line, true);
    }

    public function display()
    {
        $this->generateColumns();

        $this->separator();

        $this->header();

        $this->separator();

        foreach ($this->datas as $data) {
            $line = '|';
            foreach ($data as $column => $value) {
                $line .= ' ' . str_pad($value, $this->columns[$column]['size'], ' ') . ' |';
            }
            $this->output->write($line, true);
        }

        $this->separator();
    }
}
