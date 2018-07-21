<?php
namespace framework\extend\view;

use framework\util\Str;
use framework\core\http\Request;
use framework\core\http\Session;

class Csrf
{
    protected $name = '_csrf_token';
    
    public static function token($name = null)
    {
        Session::set($name ?? self::$name, $token = Str::random());
        return $token;
    }
    
    public static function template($html = null)
    {
        return '<input type="hidden" name="'.self::$name.'" value="'.self::token($name).'" />';
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
