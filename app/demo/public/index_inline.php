<?php

include '../../../framework/app.php';

framework\App::start('inline', ['sub_controller' => 'inline'])->run('dump');