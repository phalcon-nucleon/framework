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

    public function __construct(Writer $output, $style, $options = [])
    {
        $this->output  = $output;
        $this->style   = $style;
        $this->options = $options;
    }

    public function draw($lines = [])
    {
        $maxlen = 0;
        foreach ($lines as $line) {
            $maxlen = max($maxlen, strlen($line));
        }

        $padding = Arr::get($this->options, 'padding', 4);

        $this->output->{$this->style}(str_repeat(' ', $maxlen + $padding));

        $pad = str_repeat(' ', $padding / 2);

        foreach ($lines as $line) {
            $this->output->{$this->style}($pad . str_pad($line, $maxlen, ' ', STR_PAD_RIGHT) . $pad);
        }

        $this->output->{$this->style}(str_repeat(' ', $maxlen + 4));
    }
}
