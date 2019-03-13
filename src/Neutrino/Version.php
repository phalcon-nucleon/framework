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
            3, // medium
            1, // minor
            4, // special
            1  // number
        ];
    }
}
