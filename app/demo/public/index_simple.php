<?php

include '../../../framework/app.php';

$app = framework\App::start('simple');

$app->dispatch($_GET['c'], $_GET['a']);

$app->route('/user/([0-9]+)', function ($id) {
    return db()->user->get($id);
});

$app->setErrorHandler(function ($code, $message) {
    
});

$app->run('dump');