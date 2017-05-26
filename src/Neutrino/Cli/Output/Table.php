<?php

namespace Neutrino\Cli\Output;

/**
 * Class Table
 *
 * @package Neutrino\Cli\Output
 */
class Table
{
    const NO_STYLE = 1;

    const NO_HEADER = 2;

    const STYLE_DEFAULT = 4;

    protected $output;

    protected $datas;

    protected $columns = [];

    protected $style;

    /**
     * Table constructor.
     *
     * @param ConsoleOutput $output
     * @param array         $datas
     * @param array         $headers
     * @param int           $style
     */
    public function __construct(
        ConsoleOutput $output,
        array $datas = [],
        array $headers = [],
        $style = self::STYLE_DEFAULT
    )
    {
        $this->output = $output;
        $this->datas = $datas;
        $this->style = $style;

        foreach ($headers as $header) {
            $this->columns[$header] = [];
        }
    }

    /**
     * @param array $datas
     *
     * @return $this
     */
    public function setDatas(array $datas)
    {
        $this->datas = $datas;

        return $this;
    }

    public function generateColumns()
    {
        foreach ($this->datas as $data) {
            foreach ($data as $column => $value) {
                if (!arr_has($this->columns, $column) || !arr_has($this->columns[$column], 'size')) {
                    $this->columns[$column] = [
                        'size' => max(Helper::strlenWithoutDecoration($column), Helper::strlenWithoutDecoration($value))
                    ];
                    continue;
                }

                $this->columns[$column]['size'] =
                    max($this->columns[$column]['size'], Helper::strlenWithoutDecoration($value));
            }
        }

        return $this;
    }

    protected function separator()
    {
        if (!$this->withStyle()) {
            return;
        }
        $line = '+';
        foreach ($this->columns as $column => $opts) {
            $line .= '-' . Helper::strPad('-', $opts['size'], '-') . '-+';
        }
        $this->output->write($line, true);
    }

    protected function header()
    {
        if (!$this->withHeader()) {
            return;
        }

        $closure = $this->withStyle() ? '|' : '';
        $line = $closure;
        foreach ($this->columns as $column => $opts) {
            $line .= ' ' . Helper::strPad(str_upper($column), $opts['size'], ' ') . ' ' . $closure;
        }
        $this->output->write($line, true);
    }

    public function display()
    {
        $this->generateColumns();

        if ($this->withHeader()) {
            $this->separator();

            $this->header();
        }

        $this->separator();

        $closure = $this->withStyle() ? '|' : '';
        foreach ($this->datas as $data) {
            $line = $closure;
            foreach ($this->columns as $column => $opts) {
                $line .= ' ' . Helper::strPad(arr_fetch($data, $column, ''), $opts['size'], ' ') . ' ' . $closure;
            }
            $this->output->write($line, true);
        }

        $this->separator();
    }

    protected function withHeader()
    {
        return !($this->style & self::NO_HEADER);
    }

    protected function withStyle()
    {
        return !($this->style & self::NO_STYLE);
    }
}
