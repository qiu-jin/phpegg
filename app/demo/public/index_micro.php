<?php

define('APP_DEBUG', true);

include '../../../framework/app.php';

$app = framework\App::start('Micro');

$app->route('user/*', function ($id) {

    return $this->db->user->get($id);
    
});

if (isset($_GET['c']) && isset($_GET['a'])) {
    
    $app->default($_GET['c'], $_GET['a']);
}

$app->run('dd');
