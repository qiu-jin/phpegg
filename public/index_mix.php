<?php

define('APP_DEBUG', true);

include __DIR__.'/../framework/app.php';

$app = framework\App::start('test', 'mix');

if (isset($_GET['c']) && isset($_GET['a'])) {
    $app->query($_GET['c'], $_GET['a']);
} else {
    $app->route('/user/([0-9]+)', function ($id) {
        
        //return db()->exec("INSERT INTO user SET name='qiujin' , email='qiu-jin@qq.com'");
        
        //return db()->user->where('id', 15)->update(['email' => 'jin.qiu@goland.cn']);
        
        //return db()->user->insert(['name' => '邱琎', 'email' => 'jin.qiu@goland.cn']);
        
        return db()->user->get(18);
    });
}
$app->run('dump');