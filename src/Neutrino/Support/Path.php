<?php

namespace Neutrino\Support;

/**
 * Class Path
 *
 * @package Neutrino\Support
 */
class Path
{
    /**
     * @param $path
     *
     * @return string
     */
    public static function normalize($path)
    {
        if (empty($path)) {
            return '';
        }

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        $parts = explode('/', $path);

        $safe = [];
        foreach ($parts as $idx => $part) {
            if (($idx == 0 && empty($part))) {
                $safe[] = '';
            } elseif (trim($part) == "" || $part == '.') {
            } elseif ('..' == $part) {
                if (null === array_pop($safe) || empty($safe)) {
                    $safe[] = '';
                }
            } else {
                $safe[] = $part;
            }
        }

        if (count($safe) === 1 && $safe[0] === '') {
            return DIRECTORY_SEPARATOR;
        }

        return implode(DIRECTORY_SEPARATOR, $safe);
    }

    public static function findRelative($frompath, $topath)
    {
        $frompath = str_replace(DIRECTORY_SEPARATOR, '/', $frompath);
        $topath = str_replace(DIRECTORY_SEPARATOR, '/', $topath);

        $from = explode(DIRECTORY_SEPARATOR, self::normalize($frompath)); // Folders/File
        $to = explode(DIRECTORY_SEPARATOR, self::normalize($topath)); // Folders/File
        $relpath = '';

        $i = 0;
        // Find how far the path is the same
        while (isset($from[$i]) && isset($to[$i])) {
            if ($from[$i] != $to[$i]) {
                break;
            }
            $i++;
        }
        $j = count($from) - 1;
        // Add '..' until the path is the same
        while ($i <= $j) {
            if (!empty($from[$j])) {
                $relpath .= '..' . '/';
            }
            $j--;
        }
        // Go to folder from where it starts differing
        while (isset($to[$i])) {
            if (!empty($to[$i])) {
                $relpath .= $to[$i] . '/';
            }
            $i++;
        }

        // Strip last separator
        return substr($relpath, 0, -1);
    }
}
