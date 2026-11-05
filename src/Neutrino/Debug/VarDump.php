<?php

declare(strict_types=1);

namespace Neutrino\Debug;

use BackedEnum;
use Neutrino\Constants\Services;
use Neutrino\Support\Reflection;
use Phalcon\Di\Di;
use ReflectionProperty;
use UnitEnum;

/**
 * Dumps variables: as foldable HTML, or as text in the console. Also the Volt function `dump()`.
 *
 * Objects already dumped are shown as a link to their first dump (`#id`), which also stops the recursion.
 * Arrays are dumped up to {@see self::MAX_DEPTH} levels (a recursive array is cut there).
 */
final class VarDump
{
    public const int MAX_DEPTH = 64;

    private static int $uid = 0;

    private static bool $assetsOutput = false;

    /** @var array<int, int> Ids of the dumped objects, by `spl_object_id()` */
    private array $ids = [];

    private int $depth = 0;

    private function __construct(private readonly bool $html) {}

    /**
     * Dumps the variables: as text in the console, as HTML otherwise.
     */
    public static function dump(mixed ...$vars): void
    {
        if (PHP_SAPI === 'cli') {
            foreach ($vars as $var) {
                echo self::text($var), "\n";
            }

            return;
        }

        self::startSession();

        foreach ($vars as $var) {
            $id = 'nuc-dump-' . ++self::$uid;

            echo self::assets() . "<pre class='nuc-dump' id='$id'>" . self::html($var) . "</pre><script>nucDumper('$id')</script>";
        }
    }

    /**
     * The HTML dump of a variable, without the CSS and the JavaScript of the page.
     */
    public static function html(mixed $var): string
    {
        return (new self(true))->value($var);
    }

    /**
     * The text dump of a variable.
     */
    public static function text(mixed $var): string
    {
        return (new self(false))->value($var);
    }

    private function value(mixed $var): string
    {
        if ($this->depth >= self::MAX_DEPTH) {
            return $this->html ? '<span>** MAX DUMP LVL **</span>' : '** MAX DUMP LVL **';
        }

        $this->depth++;

        try {
            return match (true) {
                $var === null                => $this->scalar('const', 'null'),
                is_bool($var)                => $this->scalar('const', $var ? 'true' : 'false'),
                is_int($var)                 => $this->scalar('integer', (string) $var),
                is_float($var)               => $this->scalar('double', var_export($var, true)),
                is_string($var)              => $this->string($var),
                is_array($var)               => $this->array($var),
                $var instanceof UnitEnum     => $this->enum($var),
                is_object($var)              => $this->object($var),
                is_resource($var)            => $this->resource($var),
                default                      => $this->scalar('unknown', get_debug_type($var)),
            };
        } finally {
            $this->depth--;
        }
    }

    private function scalar(string $type, string $value): string
    {
        return $this->html ? '<code class="nuc-' . $type . '">' . htmlspecialchars($value) . '</code>' : $value;
    }

    private function string(string $var): string
    {
        if (!$this->html) {
            return '"' . addcslashes($var, "\"\\\0..\37") . '"';
        }

        return '<span class="nuc-sep">"</span><code class="nuc-string" title="' . strlen($var) . ' characters">'
            . htmlspecialchars($var, ENT_QUOTES | ENT_SUBSTITUTE) . '</code><span class="nuc-sep">"</span>';
    }

    private function enum(UnitEnum $var): string
    {
        $name = $var::class . '::' . $var->name . ($var instanceof BackedEnum ? ' = ' . var_export($var->value, true) : '');

        return $this->html ? '<code class="nuc-const" title="' . htmlspecialchars($var::class) . '">' . htmlspecialchars($name) . '</code>' : $name;
    }

    /**
     * @param array<mixed> $var
     */
    private function array(array $var): string
    {
        $items = [];

        foreach ($var as $key => $value) {
            $items[] = [$this->value($key), $value, '=>'];
        }

        return $this->compound('array', 'array:' . count($var), '[', ']', $items, null);
    }

    private function object(object $var): string
    {
        $class = get_debug_type($var);
        $objectId = spl_object_id($var);

        if (isset($this->ids[$objectId])) {
            return $this->reference($class, $this->ids[$objectId]);
        }

        $id = $this->ids[$objectId] = ++self::$uid;
        $items = [];
        $dumped = [];

        foreach (Reflection::properties($var) as $property) {
            $dumped[$property->getName()] = true;
            $items[] = $this->property($var, $property);
        }

        // Dynamic properties.
        foreach (get_object_vars($var) as $name => $value) {
            if (!isset($dumped[$name])) {
                $items[] = [$this->key('+', (string) $name, 'public ' . $name), $value, ':'];
            }
        }

        return $this->compound('object', $class, '{', '}', $items, $id);
    }

    /**
     * @return array{0: string, 1: mixed, 2: string, 3?: string}
     */
    private function property(object $var, ReflectionProperty $property): array
    {
        $name = $property->getName();
        [$modifier, $visibility] = match (true) {
            $property->isPrivate()   => ['-', 'private'],
            $property->isProtected() => ['#', 'protected'],
            default                  => ['+', 'public'],
        };
        $title = $visibility . ($property->isStatic() ? ' static' : '') . ($property->isReadOnly() ? ' readonly' : '') . ' ' . $name;

        if ($property->isStatic()) {
            return [$this->key($modifier, '::' . $name, $title), $property->getValue(), ':'];
        }

        if (!$property->isInitialized($var)) {
            return [$this->key($modifier, $name, $title), null, ':', $this->scalar('unknown', 'uninitialized')];
        }

        return [$this->key($modifier, $name, $title), $property->getValue($var), ':'];
    }

    /**
     * @param resource $var
     */
    private function resource(mixed $var): string
    {
        $type = get_resource_type($var);
        $label = 'resource(@' . (int) $var . ' ' . $type . ')';
        $meta = match ($type) {
            'stream'  => stream_get_meta_data($var),
            'process' => proc_get_status($var),
            default   => [],
        };

        $items = [];
        foreach ($meta as $key => $value) {
            $items[] = [$this->value($key), $value, '=>'];
        }

        return $this->compound('resource', $label, '{', '}', $items, $items === [] ? null : ++self::$uid);
    }

    private function key(string $modifier, string $name, string $title): string
    {
        if (!$this->html) {
            return $modifier . $name;
        }

        return '<code class="nuc-key" title="' . htmlspecialchars($title) . '"><small class="nuc-modifier">' . $modifier . '</small> ' . htmlspecialchars($name) . '</code>';
    }

    /**
     * An array, an object or a resource, with its items.
     *
     * @param list<array{0: string, 1: mixed, 2: string, 3?: string}> $items key, value, separator and the dump of the value
     *                                                                     when already done
     */
    private function compound(string $type, string $label, string $open, string $close, array $items, ?int $id): string
    {
        if (!$this->html) {
            if ($items === []) {
                return $label . ' ' . $open . $close;
            }

            $indent = str_repeat('  ', $this->depth);
            $rows = [];
            foreach ($items as $item) {
                [$key, $value, $separator] = $item;
                $rows[] = $indent . $key . ($separator === ':' ? ': ' : ' => ') . ($item[3] ?? $this->value($value));
            }

            return $label . ($id === null ? '' : ' #' . $id) . ' ' . $open . "\n" . implode("\n", $rows) . "\n" . substr($indent, 2) . $close;
        }

        $dump = '<code class="nuc-' . $type . '"' . ($type === 'object' ? ' title="' . htmlspecialchars($label) . '"' : '') . '>'
            . htmlspecialchars($type === 'object' ? self::shortClass($label) : $label) . '</code> <span class="nuc-closure">' . $open . '</span>';

        if ($items !== []) {
            $target = $id === null ? '' : ' data-target="nuc-ref-' . $id . '"';
            $dump .= '<span class="nuc-toggle nuc-toggle-' . ($type === 'object' ? 'object' : 'array') . '"' . $target . '>' . ($id === null ? '' : '#' . $id) . '</span>';
            $dump .= '<ul class="nuc-' . ($type === 'object' ? 'object' : 'array') . '"' . ($id === null ? '' : ' id="nuc-ref-' . $id . '"') . '>';

            foreach ($items as $item) {
                [$key, $value, $separator] = $item;
                $child = is_array($value) || (is_object($value) && !$value instanceof UnitEnum) || is_resource($value);
                $dump .= '<li class="nuc-' . str_replace(' ', '-', gettype($value)) . ($child ? ' nuc-close' : '') . '">'
                    . $key . ($separator === ':' ? ': ' : ' <span class="nuc-sep">=></span> ') . ($item[3] ?? $this->value($value)) . '</li>';
            }

            $dump .= '</ul>';
        }

        return $dump . '<span class="nuc-closure nuc-close">' . $close . '</span>';
    }

    /**
     * An object already dumped: a link to its dump.
     */
    private function reference(string $class, int $id): string
    {
        if (!$this->html) {
            return $class . ' {#' . $id . '}';
        }

        return '<code class="nuc-object" title="' . htmlspecialchars($class) . '">' . htmlspecialchars(self::shortClass($class)) . '</code> <span class="nuc-closure">{</span>'
            . '<span class="nuc-toggle nuc-toggle-object" data-target="nuc-ref-' . $id . '">#' . $id . '</span><span class="nuc-closure nuc-close">}</span>';
    }

    private static function shortClass(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }

    /**
     * Starts the session before the first output, which would prevent it from starting later in the request.
     */
    private static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            return;
        }

        $di = Di::getDefault();

        if ($di !== null && $di->has(Services::SESSION)) {
            $di->getShared(Services::SESSION);
        }
    }

    /**
     * The CSS and the JavaScript of the dumps, output once per request.
     */
    private static function assets(): string
    {
        if (self::$assetsOutput) {
            return '';
        }

        self::$assetsOutput = true;

        return '<style>pre.nuc-dump{margin:0 0 5px;padding:5px;background:#232525;color:#eee;line-height:1.5;font:12px monospace;text-align:left;word-wrap:break-word;white-space:pre-wrap;word-break:break-all;position:relative;z-index:99999}pre.nuc-dump code,pre.nuc-dump code.nuc-key{color:#a69730}pre.nuc-dump ul{margin:0;padding:0;list-style-type:none;position:relative}pre.nuc-dump ul::before{content:" ";display:block;position:absolute;width:0;top:0;bottom:0;left:2px;border-left:1px dotted rgba(255,255,255,.15)}pre.nuc-dump ul li{margin:0 0 0 15px;padding:0;list-style-type:none}pre.nuc-dump small{font-size:80%}pre.nuc-dump li.nuc-close>ul{display:none}pre.nuc-dump li.nuc-open>ul{display:inherit}pre.nuc-dump .nuc-toggle{padding:0 2px;cursor:pointer;color:#919292;border-radius:2px}pre.nuc-dump .nuc-toggle:hover{color:#fefefe}pre.nuc-dump .nuc-open .nuc-toggle::after{font:10px sans-serif;content:" ▼"}pre.nuc-dump .nuc-close .nuc-toggle::after{font:10px sans-serif;content:" ►"}pre.nuc-dump .nuc-parent:after{content:""!important}pre.nuc-dump .nuc-toggle-object:hover{background:rgba(255,255,255,.2)}pre.nuc-dump .nuc-hover{background:#8b18a7!important;color:#fefefe!important}pre.nuc-dump .nuc-modifier{color:#c16b2a}pre.nuc-dump code.nuc-const{color:#CC7832}pre.nuc-dump code.nuc-resource{color:#00b0ff}pre.nuc-dump code.nuc-double,pre.nuc-dump code.nuc-float,pre.nuc-dump code.nuc-integer{color:#90caf9}pre.nuc-dump code.nuc-string{color:#52b33b}pre.nuc-dump code.nuc-string.nuc-truncate{cursor:pointer}pre.nuc-dump .nuc-closure,pre.nuc-dump .nuc-sep{color:#ef6c00}pre.nuc-dump code.nuc-string.nuc-truncate::after{color:#d800ff;font-weight:700;line-height:11px;content:\' >\'}pre.nuc-dump code.nuc-string.nuc-truncate.nuc-open::after{content:\' <\'}pre.nuc-dump code.nuc-array{color:#CC7832}pre.nuc-dump code.nuc-object{color:#00b0ff}</style>'
            . '<script>window.nucDumper=window.nucDumper||function(f){function g(a,b){var c=a.parentNode;return c.id===b?!0:"PRE"===c.tagName?!1:g(c,b)}function h(a){var b;var c=a.querySelectorAll(".nuc-parent");var d=0;for(b=c.length;d<b;d++)c[d].classList.remove("nuc-parent");c=a.querySelectorAll("[data-target]");d=0;for(b=c.length;d<b;d++)a=c[d],g(a,a.dataset.target)&&a.classList.add("nuc-parent")}function k(a,b){if(a===b)return!1;var c=a.parentElement;return c===b?!0:c?k(c,b):!1}function e(a){a&&a.querySelector("ul")&&(a.classList.toggle("nuc-close"),a.classList.toggle("nuc-open"))}function l(a){a=a.target;var b=a.tagName,c=a.classList;"CODE"===a.tagName&&c.contains("nuc-truncate")?(c.toggle("nuc-open"),a.innerText=c.contains("nuc-open")?a.dataset.a:a.dataset.a.substr(0,117)):"SPAN"===b&&a.hasAttribute("data-target")?(b=f.getElementById(a.getAttribute("data-target")),k(a,b)||(b.parentNode===a.parentNode?e(b.parentElement):((b=b.parentElement)&&b.querySelector("ul")&&(b.classList.add("nuc-close"),b.classList.remove("nuc-open")),a.parentNode.insertBefore(f.getElementById(a.getAttribute("data-target")),a.nextSibling),e(a.parentElement),h(this)))):"SPAN"===b&&c.contains("nuc-toggle")&&(a=a.parentElement,"LI"===a.tagName&&e(a))}return function(a){a=f.getElementById(a);for(var b=a.querySelectorAll("code.nuc-string"),c,d=0,e=b.length;d<e;d++)c=b[d],120<c.innerText.length&&(c.classList.add("nuc-truncate"),c.dataset.a=c.innerText,c.innerText=c.innerText.substr(0,117));a.addEventListener("click",l);a.addEventListener("mouseover",function(a){a=a.target;a.classList.contains("nuc-parent")&&(a.classList.add("nuc-hover"),document.getElementById(a.dataset.target).previousElementSibling.classList.add("nuc-hover"))});a.addEventListener("mouseout",function(a){a=a.target;a.classList.contains("nuc-parent")&&(a.classList.remove("nuc-hover"),document.getElementById(a.dataset.target).previousElementSibling.classList.remove("nuc-hover"))});h(a)}}(document);</script>';
    }
}
