<?php

namespace Bench\Controllers;

use Neutrino\Http\Controller;

class IndexController extends Controller
{
    public function helloAction()
    {
        return $this->response->setContent('Hello');
    }
}
