<?php

define('APP_DEBUG', true);

include '../../../framework/app.php';

framework\App::start(app\library\MyApp::class)->run();