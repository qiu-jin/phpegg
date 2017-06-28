<?php

define('APP_DEBUG', true);

include __DIR__.'/../../framework/app.php';

framework\App::init('test');

framework\core\Hook::add('exit', function () {
    global $return;
    if ($return) {
        framework\App::exit(2);
        dump($return);
    }
});
