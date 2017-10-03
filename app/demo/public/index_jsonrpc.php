<?php

include '../../../framework/app.php';

framework\App::start('Jsonrpc', [

    'batch_max_num' => 1000,
        
])->run();
