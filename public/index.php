<?php

define('APP_DEBUG', true);

include __DIR__.'/../framework/app.php';

framework\App::start('test', 'inline')->run(function (&$return) {
    output(print_r($return, true));
    App::exit();
});
