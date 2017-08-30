<?php
namespace framework\core;

abstract class Auth
{
    private static $auth;

    abstract protected function id();

    abstract protected function user();

    abstract protected function check();

    abstract protected function faildo();

    abstract protected function login();
    
    abstract protected function logout();
    
    private function __construct(){}
    
    public static function init()
    {
        
    }
    
    public static function passport($call = null)
    {
        return self::$auth->check() || self::$auth->faildo();
    }
    
    public static function free()
    {
        self::$auth = null;
    }
    
    public static function __callStatic($method, $params = [])
    {
        return self::$auth->$method(...$params);
    }
}
Auth::init();
