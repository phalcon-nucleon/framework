<?php

namespace Luxury\Cli\Output;

/**
 * Class ConsoleOutput
 *
 * @package Luxury\Cli\Output
 *
 * @author  Fabien Potencier <fabien@symfony.com>
 * @see     https://github.com/symfony/console/blob/3.0/Formatter/OutputFormatterStyle.php
 *
 * Console output stylised. Transformed from symfony/console.
 */
class ConsoleOutput
{
    /**
     * @var resource
     */
    private $stream;

    private static $availableForegroundColors = [
        'black'   => ['set' => 30, 'unset' => 39],
        'red'     => ['set' => 31, 'unset' => 39],
        'green'   => ['set' => 32, 'unset' => 39],
        'yellow'  => ['set' => 33, 'unset' => 39],
        'blue'    => ['set' => 34, 'unset' => 39],
        'magenta' => ['set' => 35, 'unset' => 39],
        'cyan'    => ['set' => 36, 'unset' => 39],
        'white'   => ['set' => 37, 'unset' => 39],
        'default' => ['set' => 39, 'unset' => 39],
    ];
    private static $availableBackgroundColors = [
        'black'   => ['set' => 40, 'unset' => 49],
        'red'     => ['set' => 41, 'unset' => 49],
        'green'   => ['set' => 42, 'unset' => 49],
        'yellow'  => ['set' => 43, 'unset' => 49],
        'blue'    => ['set' => 44, 'unset' => 49],
        'magenta' => ['set' => 45, 'unset' => 49],
        'cyan'    => ['set' => 46, 'unset' => 49],
        'white'   => ['set' => 47, 'unset' => 49],
        'default' => ['set' => 49, 'unset' => 49],
    ];
    private static $availableOptions          = [
        'bold'       => ['set' => 1, 'unset' => 22],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink'      => ['set' => 5, 'unset' => 25],
        'reverse'    => ['set' => 7, 'unset' => 27],
        'conceal'    => ['set' => 8, 'unset' => 28],
    ];

    public function info($str)
    {
        return $this->apply($str, 'green');
    }

    public function notice($str)
    {
        return $this->apply($str, 'yellow');
    }

    public function warn($str)
    {
        return $this->apply($str, 'yellow', null, ['reverse']);
    }

    public function error($str)
    {
        return $this->apply($str, 'white', 'red');
    }

    public function question($str)
    {
        return $this->apply($str, 'black', 'cyan');
    }

    /**
     * Applies the style to a given text.
     *
     * @param string $text The text to style
     *
     * @return string
     */
    public function apply($text, $foreground = null, $background = null, array $options = [])
    {
        if (!$this->hasColorSupport()) {
            return $text;
        }

        $setCodes   = [];
        $unsetCodes = [];

        if (null !== $foreground) {
            $setCodes[]   = self::$availableForegroundColors[$foreground]['set'];
            $unsetCodes[] = self::$availableForegroundColors[$foreground]['unset'];
        }
        if (null !== $background) {
            $setCodes[]   = self::$availableBackgroundColors[$background]['set'];
            $unsetCodes[] = self::$availableBackgroundColors[$background]['unset'];
        }
        if (!empty($options)) {
            foreach ($options as $option) {
                $setCodes[]   = self::$availableOptions[$option]['set'];
                $unsetCodes[] = self::$availableOptions[$option]['unset'];
            }
        }

        if (0 === count($setCodes)) {
            return $text;
        }

        return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }

    /**
     * @return resource
     */
    protected function getStream()
    {
        if ($this->stream == null) {
            $this->stream = $this->openOutputStream();
        }

        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function write($message, $newline)
    {
        if (false === @fwrite($this->getStream(), $message) || ($newline && (false === @fwrite($this->getStream(),
                        PHP_EOL)))
        ) {
            // should never happen
            throw new \RuntimeException('Unable to write output.');
        }

        fflush($this->getStream());
    }

    protected function hasColorSupport()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        return function_exists('posix_isatty') && @posix_isatty($this->stream);
    }

    /**
     * @return resource
     */
    private function openOutputStream()
    {
        if (!$this->hasStdoutSupport()) {
            return fopen('php://output', 'w');
        }

        return @fopen('php://stdout', 'w') ?: fopen('php://output', 'w');
    }

    /**
     * @return resource
     */
    private function openErrorStream()
    {
        return fopen($this->hasStderrSupport() ? 'php://stderr' : 'php://output', 'w');
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     *
     * @return bool
     */
    protected function hasStdoutSupport()
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDERR.
     *
     * @return bool
     */
    protected function hasStderrSupport()
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Checks if current executing environment is IBM iSeries (OS400), which
     * doesn't properly convert character-encodings between ASCII to EBCDIC.
     *
     * @return bool
     */
    private function isRunningOS400()
    {
        $checks = [
            function_exists('php_uname') ? php_uname('s') : '',
            getenv('OSTYPE'),
            PHP_OS,
        ];

        return false !== stripos(implode(';', $checks), 'OS400');
    }

    function __destruct()
    {
        if ($this->stream != null) {
            fclose($this->stream);
        }
    }

}
