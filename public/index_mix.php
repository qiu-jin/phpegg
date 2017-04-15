<?php

define('APP_DEBUG', true);

include __DIR__.'/../framework/app.php';

$app = framework\App::start('test', 'mix');

$app->route('123', function ($id = 2) {

    //return load('queue', 'amqp')->producer('test')->push(time());

    return load('queue', 'amqp')->consumer('test')->get();

    //return cache('mfile')->get('test');
    
    //return cache('opcache')->set('test', $_SERVER, 30);
    //return db()->user->select('name')->find();
});

$app->run('print_r');