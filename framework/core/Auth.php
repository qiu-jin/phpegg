<?php
namespace framework\core;

abstract class Auth
{
    private static $auth;
    private static $cache;
    private static $except;

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
        if (isset($config['class']) && is_subclass_of($config['class'], __CLASS__)) {
            self::$auth = new $config['class']($config);
        } else {
            throw new \Exception('Illegal auth class');
        }
        if (isset($config['except'])) {
            self::$pexcept = $config['except'];
            if ($config['cache_except']) {
                self::$cache = cache($config['cache_except']);
            }
        }
        Hook::add('exit', __CLASS__.'::clear');
    }
    
    public static function passport($call = null)
    {
        if (self::$except) {
            if (self::$cache) {
                if (self::$cache->has($call)) {
                    return true;
                }
            }
            foreach (self::$except as $except) {
                if (stripos($call, $except) === 0) {
                    if (self::$cache) {
                        self::$cache->set($call, 1);
                    }
                    return true;
                }
            }
        }
        return self::$auth->check() || self::$auth->faildo();
    }
    
    public static function clear()
    {
        self::$auth = null;
        self::$cache = null;
        self::$except = null;
    }
    
    public static function __callStatic($method, $params = [])
    {
        return self::$auth->$method(...$params);
    }
}
Auth::init();
