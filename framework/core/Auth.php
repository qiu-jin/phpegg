<?php
namespace framework\core;

abstract class Auth
{
    private static $auth;
    private static $pass;

    abstract protected function check();

    abstract protected function fail();

    abstract protected function user();
    
    abstract protected function login();
    
    abstract protected function logout();
    
    private function __construct(){}
    
    public static function init()
    {
        if (self::$auth) return;
        $config = Config::get('auth');
        if (isset($config['class']) && is_subclass_of($config['class'], __CLASS__)) {
            self::$auth = new $config['class']($config);
        } else {
            throw new \Exception('Illegal auth class');
        }
        if (isset($config['pass'])) {
            $this->pass = $config['pass'];
        }
    }
    
    public static function checkCall($call)
    {
        if ($this->pass) {
            foreach ($this->pass as $pass) {
                if (stripos($call, $pass) === 0) return;
            }
        }
        return self::$auth->check() || self::$auth->fail();
    }
    
    public static function __callStatic($method, $params = [])
    {
        return self::$auth->$method(...$params);
    }
}
Auth::init();
