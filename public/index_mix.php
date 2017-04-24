<?php

define('APP_DEBUG', true);

include __DIR__.'/../framework/app.php';

$app = framework\App::start('test', 'mix');

if (isset($_GET['c']) && isset($_GET['a'])) {
    $app->query($_GET['c'], $_GET['a']);
} else {
    $app->route('/user/([0-9])', function ($id) {
        return db()->user->select('name')->get($id);
    });
}
$app->run('print_r');