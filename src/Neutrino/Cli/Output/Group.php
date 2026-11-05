<?php

declare(strict_types=1);

namespace Neutrino\Cli\Output;

/**
 * Two-column listing grouped by the prefix of the keys (`group:name`).
 */
class Group
{
    public const int NONE = 0;

    public const int SORT_ASC = 2;

    public const int SORT_DESC = 4;

    /**
     * @var array<string, array<string, string>>
     */
    protected array $groups = [];

    /**
     * @param array<string, string> $datas Key (possibly decorated) => description
     */
    public function __construct(
        protected Writer $output,
        protected array $datas = [],
        protected int $options = self::NONE,
    ) {}

    protected function generateGroupData(): void
    {
        $this->groups = [];

        foreach ($this->datas as $key => $data) {
            $washKey = Helper::removeDecoration((string) $key);
            $group = str_contains($washKey, ':') ? explode(':', $washKey)[0] : '_default';

            $this->groups[$group][(string) $key] = $data;
        }

        if ($this->options & self::SORT_DESC) {
            krsort($this->groups);
            foreach ($this->groups as &$group) {
                krsort($group);
            }
            unset($group);
        } elseif ($this->options & self::SORT_ASC) {
            ksort($this->groups);
            foreach ($this->groups as &$group) {
                ksort($group);
            }
            unset($group);
        }
    }

    public function display(): void
    {
        $this->generateGroupData();

        $tableOutput = new Table($this->output, [], [], Table::NO_STYLE | Table::NO_HEADER);

        // Every group first, so that the columns have the same width in all of them.
        foreach ($this->groups as $datas) {
            $tableOutput->setDatas(self::rows($datas))->generateColumns();
        }

        foreach ($this->groups as $group => $datas) {
            if ($group !== '_default') {
                $this->output->notice((string) $group);
            }
            $tableOutput->setDatas(self::rows($datas))->display();
        }
    }

    /**
     * @param array<string, string> $datas
     *
     * @return list<array{string, string}>
     */
    private static function rows(array $datas): array
    {
        $rows = [];
        foreach ($datas as $key => $value) {
            $rows[] = [(string) $key, $value];
        }

        return $rows;
    }
}
