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
            1, // medium
            0, // minor
            2, // special
            null  // number
        ];
    }
}
