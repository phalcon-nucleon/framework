<?php

namespace Neutrino\Dotconst\Extensions;

use Neutrino\Dotconst\Helper;
use Neutrino\Support\Path;

/**
 * Class PhpDir
 *
 * @package Neutrino\Dotconst\Extensions
 */
class PhpDir extends Extension
{
    protected $identifier = 'php/dir(?::(/[\w\-. ]+))?(?:@(.+))?';

    /**
     * @param string $value
     * @param string $basePath
     *
     * @return string
     */
    public function parse($value, $basePath)
    {
        $match = $this->match($value);

        return Helper::normalizePath($basePath . DIRECTORY_SEPARATOR . (isset($match[1]) ? $match[1] : '') . (isset($match[2]) ? $match[2] : ''));
    }

    /**
     * @param string $value
     * @param string $basePath
     * @param string $compilePath
     *
     * @return string
     */
    public function compile($value, $basePath, $compilePath)
    {
        $match = $this->match($value);

        return "__DIR__ . '" . addslashes('/' . Path::findRelative($compilePath, $basePath) . '/' . (isset($match[1]) ? $match[1] : '')) . "'";
    }
}
