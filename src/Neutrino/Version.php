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
            2, // medium
            3, // minor
            4, // special
            0  // number
        ];
    }
}
