<?php

namespace Neutrino\Cli\Output;

use Neutrino\Support\Arr;

/**
 * Class Block
 *
 * @package     Neutrino\Cli\Output
 */
class Block
{
    protected $output;

    protected $style;

    protected $options;

    /**
     * Block constructor.
     *
     * @param \Neutrino\Cli\Output\Writer $output
     * @param string                      $style Output function used to display block (notice, info, warn, ...)
     * @param array                       $options
     */
    public function __construct(Writer $output, $style, $options = [])
    {
        $this->output  = $output;
        $this->style   = $style;
        $this->options = $options;
    }

    public function draw($lines = [])
    {
        $maxlen = 0;
        $rows = [];

        $_lines = [];
        foreach ($lines as $line) {
            $_lines = array_merge($_lines, explode(PHP_EOL, $line));
        }

        $lines = $_lines;

        foreach ($lines as $line) {
            $len = strlen($line);

            if ($len > 100) {
                $parts = str_split($line, 100);
                $rows = array_merge($rows, $parts);
                $maxlen = max($maxlen, 100);
            } else {
                $maxlen = max($maxlen, $len);
                $rows[] = $line;
            }
        }

        $padding = Arr::get($this->options, 'padding', 4);

        $this->output->{$this->style}(str_repeat(' ', $maxlen + $padding));

        $pad = str_repeat(' ', $padding / 2);

        foreach ($rows as $line) {
            $this->output->{$this->style}($pad . str_pad($line, $maxlen, ' ', STR_PAD_RIGHT) . $pad);
        }

        $this->output->{$this->style}(str_repeat(' ', $maxlen + 4));
    }
}
