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
            0, // major
            2, // medium
            0, // minor
            4, // special
            0  // number
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
