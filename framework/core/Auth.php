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
        if (self::$auth) return;
        $config = Config::get('auth');
        if (is_subclass_of($config['class'], __CLASS__)) {
            self::$auth = (new $config['class']($config));
        } else {
            throw new Exception('Illegal auth class');
        }
        Hook::add('exit', __CLASS__.'::free');
    }
    
    public static function run()
    {
        if (!self::$auth->check()) {
            self::$auth->faildo();
            App::exit();
        }
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
