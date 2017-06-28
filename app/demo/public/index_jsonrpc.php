<?php

include '../../../framework/app.php';

framework\App::start('jsonrpc', ['sub_controller' => 'jsonrpc'])->run('dump');