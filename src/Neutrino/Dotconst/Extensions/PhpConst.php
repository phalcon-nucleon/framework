<?php

namespace Neutrino\Dotconst\Extensions;

/**
 * Class PhpConst
 *
 * @package Neutrino\Dotconst\Extensions
 */
class PhpConst extends Extension
{
    protected $identifier = 'php/const:([\w:\\\\]+)(?:@(.+))?';

    /**
     * @param string $value
     * @param string $basePath
     *
     * @return string
     */
    public function parse($value, $basePath)
    {
        $match = $this->match($value);

        return constant($match[1]) . (isset($match[2]) ? $match[2] : '');
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

        if (isset($match[2])) {
            return "{$match[1]} . '{$match[2]}'";
        }

        return $match[1];
    }
}
