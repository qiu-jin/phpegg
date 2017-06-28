<?php

include '../../../framework/app.php';

framework\App::start('standard', ['sub_controller' => 'standard'])->run('dump');