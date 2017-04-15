<?php
namespace framework\core;

abstract class Auth
{
    private static $auth;
    private static $option;

    abstract public function check();

    abstract public function user();
    
    abstract public function login();
    
    abstract public function logout();
    
    public static function init()
    {
        if (self::$auth) return;
        $config = Config::get('auth');
        if ($config) {
            if (is_subclass_of($config['class'], __CLASS__)) {
                self::$auth = new $config['class'];
            }
        }
    }
}
Auth::init();
