<?php

define('APP_DEBUG', true);

include '../../../framework/app.php';

framework\App::start('Inline', [
    
    'controller_path' => 'controller/inline',

])->run();