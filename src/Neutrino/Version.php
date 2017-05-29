<?php

namespace Neutrino;

class Version extends \Phalcon\Version
{
    /**
     * @inheritdoc
     */
    protected static function _getVersion()
    {
        return [
            1, // major
            0, // medium
            0, // minor
            1, // special
            null  // number
        ];
    }

    /**
     * @inheritdoc
     */
    public static function get()
    {
        return str_replace(' ', '-', parent::get());
    }
}
