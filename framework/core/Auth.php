<?php
namespace framework\core;

abstract class Auth
{
    private static $auth;
    private static $pass;
    private static $cache;

    abstract protected function check();

    abstract protected function fail();

    abstract protected function user();
    
    abstract protected function login(...$params);
    
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
            self::$pass = $config['pass'];
            if ($config['cache_pass']) {
                self::$cache = cache($config['cache_pass']);
            }
        }
        Hook::add('exit', __CLASS__.'::clear');
    }
    
    public static function passport($call = null)
    {
        if (self::$pass) {
            if (self::$cache) {
                if (self::$cache->has($call)) {
                    return true;
                }
            }
            foreach (self::$pass as $pass) {
                if (stripos($call, $pass) === 0) {
                    if (self::$cache) {
                        self::$cache->set($call, 1);
                    }
                    return true;
                }
            }
        }
        return self::$auth->check() || self::$auth->fail();
    }
    
    public static function clear()
    {
        self::$auth = null;
        self::$pass = null;
        self::$cache = null;
    }
    
    public static function __callStatic($method, $params = [])
    {
        return self::$auth->$method(...$params);
    }
}
Auth::init();
