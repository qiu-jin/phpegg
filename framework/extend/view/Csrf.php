<?php
namespace framework\extend\view;

use framework\util\Hash;
use framework\core\http\Request;
use framework\core\http\Session;

class Csrf
{
    protected $name = '_csrf_token';
    
    public static function token($name = null)
    {
        Session::set($name ?? self::$name, $token = hash('md5', Hash::salt()));
        return $token;
    }
    
    public static function render($name = null)
    {
        if ($name === null) {
            $name = self::$name;
        }
        return '<input type="hidden" name="'.$name.'" value="'.self::token($name).'" />';
    }
    
    public static function verify($name = null, $value = null)
    {
        if ($name === null) {
            $name = self::$name;
        }
        if ($value === null) {
            $value = Request::post($name);
        }
        return $value && $value === Session::get($name);
    }
}
