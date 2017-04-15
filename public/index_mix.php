<?php

define('APP_DEBUG', true);

include __DIR__.'/../framework/app.php';

$app = framework\App::start('test', 'mix');

$app->route('123', function ($id = 2) {

    return load('queue', 'kafka')->producer('test')->push(time());

    return load('queue', 'amqp')->consumer('test')->pop();

    //return cache('mfile')->get('test');
    
    //return cache('opcache')->set('test', $_SERVER, 30);
    //return db()->user->select('name')->find();
});

$app->run('print_r');