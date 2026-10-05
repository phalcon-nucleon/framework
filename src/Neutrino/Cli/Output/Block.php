<?php

declare(strict_types=1);

namespace Neutrino\Cli\Output;

/**
 * Block of padded lines, all drawn with one Writer style (info, notice, warn, error, question).
 */
class Block
{
    private const int MAX_WIDTH = 100;

    /**
     * @param 'line'|'info'|'notice'|'warn'|'error'|'question' $style
     * @param array{padding?: int}                             $options
     */
    public function __construct(
        protected Writer $output,
        protected string $style,
        protected array $options = [],
    ) {}

    /**
     * @param iterable<string> $lines Lines may contain "\n"; lines longer than 100 characters are split.
     */
    public function draw(iterable $lines = []): void
    {
        $rows = [];
        foreach ($lines as $line) {
            foreach (explode("\n", $line) as $part) {
                array_push($rows, ...($part === '' ? [''] : str_split($part, self::MAX_WIDTH)));
            }
        }

        $width = $rows === [] ? 0 : max(array_map('strlen', $rows));
        $padding = $this->options['padding'] ?? 4;
        $pad = str_repeat(' ', intdiv($padding, 2));

        $this->output->{$this->style}(str_repeat(' ', $width + $padding));

        foreach ($rows as $row) {
            $this->output->{$this->style}($pad . str_pad($row, $width) . $pad);
        }

        $this->output->{$this->style}(str_repeat(' ', $width + $padding));
    }
}
