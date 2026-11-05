<?php

declare(strict_types=1);

namespace Test\View\Volt;

use PHPUnit\Framework\Attributes\DataProvider;
use Test\View\ViewTestCase;

/**
 * The Nucleon filters, compiled and rendered.
 */
final class FiltersTest extends ViewTestCase
{
    /**
     * @return iterable<string, array{string, string, array<string, mixed>}>
     */
    public static function filters(): iterable
    {
        yield 'merge' => ['{{ ([1, 2]|merge([3], x))|join(",") }}', '1,2,3,4', ['x' => [4]]];
        yield 'split' => ['{{ "a,b,c"|split(",")|join("-") }}', 'a-b-c', []];
        yield 'split limit' => ['{{ "a,b,c"|split(",", 2)|join("-") }}', 'a-b,c', []];
        yield 'split chars' => ['{{ "abc"|split|join("-") }}', 'a-b-c', []];
        yield 'split chunks' => ['{{ "abcde"|split("", 2)|join("-") }}', 'ab-cd-e', []];
        yield 'split variable separator' => ['{{ s|split(sep)|join("-") }}', 'a-b', ['s' => 'a;b', 'sep' => ';']];
        yield 'round' => ['{{ 1.5|round }}', '2', []];
        yield 'round precision' => ['{{ 1.256|round(2) }}', '1.26', []];
        yield 'round floor' => ['{{ 1.9|round("floor") }}', '1', []];
        yield 'round ceil' => ['{{ 1.1|round("ceil") }}', '2', []];
        yield 'round precision floor' => ['{{ 1.259|round(2, "floor") }}', '1.25', []];
        yield 'round precision ceil' => ['{{ x|round(1, "ceil") }}', '1.3', ['x' => 1.21]];
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function slices(): iterable
    {
        yield 'Nucleon: offset, length' => ['{{ [1, 2, 3, 4]|slice(0, 2)|join(",") }}', '1,2', true];
        yield 'Nucleon: offset' => ['{{ [1, 2, 3, 4]|slice(2)|join(",") }}', '3,4', true];
        yield 'Volt: start, inclusive end' => ['{{ [1, 2, 3, 4]|slice(0, 2)|join(",") }}', '1,2,3', false];
        yield 'Volt: string' => ['{{ "nucleon"|slice(0, 2) }}', 'nuc', false];
    }

    #[DataProvider('slices')]
    public function testSlice(string $template, string $expected, bool $nucleon): void
    {
        if ($nucleon) {
            $this->container(['filters' => ['slice' => \Neutrino\View\Engines\Volt\Compiler\Filters\SliceFilter::class]]);
        }

        $this->assertSame($expected, $this->renderString($template));
    }

    /**
     * @param array<string, mixed> $params
     */
    #[DataProvider('filters')]
    public function testFilter(string $template, string $expected, array $params): void
    {
        $this->assertSame($expected, $this->renderString($template, $params));
    }
}
