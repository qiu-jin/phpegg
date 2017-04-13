<?php
namespace framework\core;

abstract class Auth
{
    private static $auth;
    private static $option;
    
    public static function init()
    {
        $config = Config::get('auth');
        if ($config) {
            
        }
    }
    
    abstract public function user();

    abstract public function check();
    
    abstract public function login($data);
    
    abstract public function logout();
}
