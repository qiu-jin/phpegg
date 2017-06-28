<?php

include '../../../framework/app.php';

framework\App::start('rest', ['sub_controller' => 'rest'])->run();