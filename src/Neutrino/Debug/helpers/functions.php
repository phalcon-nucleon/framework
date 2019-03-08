<?php

namespace Neutrino\Debug {

    use Highlight\Highlighter;
    use Highlight\Languages\PHP;
    use Highlight\Languages\SQL;
    use Highlight\Renders\Html;
    use Highlight\Token;

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
            return Highlighter::factory(SQL::class, Html::class, [
                'format' => SQL::FORMAT_NESTED,
                'inlineStyle' => true,
                'styles' => ['pre' => '', 'function' => 'color:#fdd835;font-style:italic']
            ])->highlight($sql);
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

            if (preg_match('!(\w+\.[a-zA-Z0-9]{2,4})(?:\((\d+)\))$!', $file)) {
                return preg_replace('!(\w+\.[a-zA-Z0-9]{2,4})(?:\((\d+)\))$!', '<b>$1</b> (line: $2)', $file);
            }

            return preg_replace('!(\w+\.[a-zA-Z0-9]{2,4})$!', '<b>$1</b>', $file);
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
            return Highlighter::factory(PHP::class, Html::class, [
                'withLineNumber' => false,
                'inlineStyle'    => true,
                'noPre'          => true,
                'context'        => 'php',
                'styles'         => [
                    Token::TOKEN_NAMESPACE => 'color:#880000',
                    Token::TOKEN_FUNCTION  => 'color:#880000;font-weight:bold',
                    Token::TOKEN_KEYWORD   => 'color:#bf360c;',
                    Token::TOKEN_VARIABLE  => 'color:#880000',
                    Token::TOKEN_STRING    => 'color:#2e7d32'
                ]])
                ->highlight($func);
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
            return Highlighter::factory(PHP::class, Html::class, [
                    'withLineNumber' => true,
                    'lineOffset'     => $line - $expands - 1,
                    'lineLimit'      => $expands * 2 + 1,
                    'lineSelected'   => $line
                ])
                ->highlight(file_get_contents($file));
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
