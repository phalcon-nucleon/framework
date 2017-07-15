<?php

namespace Neutrino\Dotconst;

use Neutrino\Dotconst\Exception\InvalidFileException;

/**
 * Class Compile
 *
 * @package Neutrino\Dotconst
 */
class Compile
{
    /**
     * Compile loaded & parsed ini files to php files.
     *
     * @param $path
     * @param $compilePath
     *
     * @throws \Neutrino\Dotconst\Exception\InvalidFileException
     */
    public static function compile($path, $compilePath)
    {
        $constants = Loader::fromFiles($path);

        $r = fopen($compilePath . '/consts.php', 'w');

        if ($r === false) {
            throw new InvalidFileException('Can\'t create file : ' . $compilePath);
        }

        fwrite($r, "<?php" . PHP_EOL);

        foreach ($constants as $const => $value) {
            fwrite($r, "define('$const', " . var_export($value, true) . ");" . PHP_EOL);
        }

        fclose($r);
    }
}
