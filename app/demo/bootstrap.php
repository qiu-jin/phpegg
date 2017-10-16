<?php
// 无模式应用示例的bootstrap文件

define('APP_DEBUG', true);

include '../../../framework/app.php';

framework\App::boot();

$that = (new class() {
    use Getter;
}); 

framework\core\Hook::add('exit', function () {
    global $ret;
    Response::json($ret, false);
});
