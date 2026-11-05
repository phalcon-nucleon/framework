<?php

$this->getDI()->getShared('router')->addTask('hello', \Bench\Tasks\HelloTask::class);
