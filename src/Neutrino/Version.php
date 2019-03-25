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
            4, // medium
            0, // minor
            1, // special
            0  // number
        ];
    }
}
