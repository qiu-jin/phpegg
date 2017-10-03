<?php
namespace app\hook;

use framework\core\http\Request;
use framework\core\http\Response;

class Redirect
{
    public static function run()
    {
        if (!Request::isHttps()) {
        	Response::redirect('https://'.Request::url(), true);
        }
    }
}

