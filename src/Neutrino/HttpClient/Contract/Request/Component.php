<?php

namespace Neutrino\HttpClient\Contract\Request;

use Neutrino\HttpClient\Request;

/**
 * Class Component
 *
 * @package     Neutrino\HttpClient\Contract\Request
 */
interface Component
{
    /**
     * @param \Neutrino\HttpClient\Request $request
     *
     * @return mixed
     */
    public function build(Request $request);
}
