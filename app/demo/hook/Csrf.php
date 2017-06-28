<?php
namespace app\hook;

use framework\core\View;
use framework\core\http\Request;

class Csrf
{
    private static $except = [];
    
    public static function check()
    {
        if (Request::isPost()) {
            $token = Request::post('csrf_token');
            if (!$token || $token != Request::session('csrf_token')) {
                return View::failure('无效请求，Csrf token 验证失败。');
            }
        }
    }
}

