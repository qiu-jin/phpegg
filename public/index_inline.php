<?php

define('APP_DEBUG', true);

include __DIR__.'/../framework/app.php';

framework\App::start('test', 'inline')->run(function ($return) {
    var_dump($return);
    framework\App::exit();
});