<?php

namespace Neutrino\Debug {

    use Highlight\Highlighter;
    use Highlight\Renders\Html;
    use Highlight\Tokenizer\PHP;
    use Highlight\Tokenizer\SQL;

    if (!function_exists(__NAMESPACE__ . '\\human_mtime')) {
        /**
         * @internal
         *
         * Convert a microtime to it's human representation
         *
         * @param number $time
         * @param null $precision
         *
         * @return string
         */
        function human_mtime($time, $precision = null)
        {
            $units = [1 => 'ms', 2 => 'µs', 3 => 'ns'];

            foreach ($units as $idx => $unit) {
                $pow = (1000 ** $idx);

                $n = floor($time * $pow);

                if ($n > 0) {
                    $v = fmod($time * $pow, $pow);
                    $p = (int)($time * $pow / $pow) * $pow;
                    $i = (int)($v + $p);

                    $s = round($v + $p, max(0, is_null($precision) ? 4 - strlen($i) : $precision)) . ' ' . $unit;
                    break;
                }
            }

            if (empty($s)) {
                $s = round(fmod($time * (1000 ** 3), (1000 ** 3)), 3) . ' ns';
            }

            return trim($s, '.-');
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\human_bytes')) {
        /**
         * @internal
         *
         * Convert a bytes numbre to it's human representation
         *
         * @param number $bytes
         * @param int $precision
         *
         * @return string
         */
        function human_bytes($bytes, $precision = 2)
        {
            $units = ['', 'K', 'M', 'G', 'T'];
            $unit = 1024;
            $type = 'B';
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log($unit));
            $pow = min($pow, count($units) - 1);

            $bytes /= pow($unit, $pow);

            return round($bytes, $precision) . ' ' . (isset($units[$pow]) ? $units[$pow] : '') . $type;
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\sql_highlight')) {
        /**
         * @internal
         *
         * Simple SQL highlight
         *
         * @param string $sql
         *
         * @return string
         */
        function sql_highlight($sql)
        {
            SQL::$style = SQL::STYLE_EXPAND;
            return Highlighter::factory(SQL::class, Html::class)->highlight($sql);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\file_highlight')) {
        /**
         * @internal
         *
         * Highlight the file part of string represented by {path}\{file}
         *
         * @param string $file
         *
         * @return string
         */
        function file_highlight($file)
        {
            $file = str_replace(BASE_PATH . DIRECTORY_SEPARATOR, '', str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file));

            if (preg_match('!(\w+\.[a-z]{2,4})(?:\((\d+)\))$!', $file)) {
                return preg_replace('!(\w+\.[a-z]{2,4})(?:\((\d+)\))$!', '<b>$1</b> (line: $2)', $file);
            }

            return preg_replace('!(\w+\.[a-z]{2,4})$!', '<b>$1</b>', $file);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\func_highlight')) {
        /**
         * @internal
         *
         * Highlight the function part of string represented by {namespace}\{function}
         *
         * @param string $func
         *
         * @return string
         */
        function func_highlight($func)
        {
            return preg_replace(
                '!(.+)(->|::)(\w+)(?:(\(.+\)))?!',
                '<span class="red-text text-darken-4">$1</span>$2<span class="red-text text-darken-2">$3</span><span class="grey-text text-darken-1">$4</span>',
                $func
            );
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\php_highlight')) {
        /**
         * @internal
         *
         * Highlight php string
         *
         * @param string $str
         *
         * @return string
         */
        function php_highlight($str)
        {
            return Highlighter::factory(PHP::class, Html::class)->highlight($str);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\php_file_part_highlight')) {
        /**
         * @internal
         *
         * Highlight php file part
         *
         * @param     $file
         * @param     $line
         * @param int $expands
         *
         * @return string
         */
        function php_file_part_highlight($file, $line, $expands = 10)
        {
            $parts = explode("\n", file_get_contents($file));

            $start = max(0, $line - $expands - 1);
            $parts = array_slice($parts, $start, $expands * 2 + 1);

            $highlighted = php_highlight(implode("\n", $parts));

            $parts = explode("\n", $highlighted);

            foreach ($parts as $idx => $part) {
                $parts[$idx] = '<code class="line-number">' . ($idx+$start+1) .'</code>' . $part;
            }

            $parts[$line - $start - 1] = '<code class="line-sel">' . $parts[$line - $start - 1] . '</code>';

            return implode("\n", $parts);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\length')) {
        /**
         * @internal
         *
         * Get length of an array or a string
         *
         * @param array|string $var
         *
         * @return int|null
         */
        function length($var)
        {
            if(is_string($var)){
                return strlen($var);
            }
            if(is_array($var)){
                return count($var);
            }
            return null;
        }
    }
}
