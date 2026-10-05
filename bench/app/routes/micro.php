<?php

$this->getDI()->getShared('micro.router')->addGet('/hello', function () {
    return 'Hello';
});
