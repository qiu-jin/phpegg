<?php
namespace app\hook;

use framework\core\http\Request;
use framework\core\http\Session;

class VerifyCsrf
{
    public static function run()
    {
        if (Request::isPost()) {
            $token = Request::post('csrf_token');
            if (!$token || $token !== Session::get('csrf_token')) {
                return abort(500, '无效请求，Csrf token 验证失败。');
            }
        }
    }
}

