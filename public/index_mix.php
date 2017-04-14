<?php

define('APP_DEBUG', true);

include __DIR__.'/../framework/app.php';

$app = framework\App::start('test', 'mix');

$app->route('123', function ($id = 2) {
    
    return db()->user->select('name')->find();
});

$app->run('print_r');