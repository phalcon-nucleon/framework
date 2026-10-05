<?php

declare(strict_types=1);

namespace Neutrino\Cli\Output;

/**
 * Text table.
 */
class Table
{
    public const int NO_STYLE = 1;

    public const int NO_HEADER = 2;

    public const int STYLE_DEFAULT = 4;

    /**
     * Columns, with their width.
     *
     * @var array<int|string, array{size?: int}>
     */
    protected array $columns = [];

    /**
     * @param array<array<int|string, scalar|null>> $datas
     * @param list<string>                          $headers
     */
    public function __construct(
        protected Writer $output,
        protected array $datas = [],
        array $headers = [],
        protected int $style = self::STYLE_DEFAULT,
    ) {
        foreach ($headers as $header) {
            $this->columns[$header] = [];
        }
    }

    /**
     * @param array<array<int|string, scalar|null>> $datas
     */
    public function setDatas(array $datas): static
    {
        $this->datas = $datas;

        return $this;
    }

    public function generateColumns(): static
    {
        foreach ($this->datas as $data) {
            foreach ($data as $column => $value) {
                $size = Helper::strlenWithoutDecoration((string) $value);

                $this->columns[$column] = ['size' => max(
                    $this->columns[$column]['size'] ?? Helper::strlenWithoutDecoration((string) $column),
                    $size,
                )];
            }
        }

        return $this;
    }

    public function display(): void
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
                $line .= ' ' . Helper::strPad((string) ($data[$column] ?? ''), $opts['size'] ?? 0, ' ') . ' ' . $closure;
            }
            $this->output->write($line, true);
        }

        $this->separator();
    }

    protected function separator(): void
    {
        if (!$this->withStyle()) {
            return;
        }

        $line = '+';
        foreach ($this->columns as $opts) {
            $line .= '-' . str_repeat('-', $opts['size'] ?? 0) . '-+';
        }
        $this->output->write($line, true);
    }

    protected function header(): void
    {
        $closure = $this->withStyle() ? '|' : '';
        $line = $closure;
        foreach ($this->columns as $column => $opts) {
            $line .= ' ' . Helper::strPad(mb_strtoupper((string) $column), $opts['size'] ?? 0, ' ') . ' ' . $closure;
        }
        $this->output->write($line, true);
    }

    protected function withHeader(): bool
    {
        return !($this->style & self::NO_HEADER);
    }

    protected function withStyle(): bool
    {
        return !($this->style & self::NO_STYLE);
    }
}
