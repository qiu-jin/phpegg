<?php
namespace framework\core\http;

use framework\util\Str;
use framework\core\Config;

class Session
{
    private static $init;
    
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::flash('session')) {
            if (isset($config['cookie_params'])) {
                session_set_cookie_params(...$config['cookie_params']);
            }
            if (isset($config['save_handler'])) {
                session_set_save_handler(instance(...$config['save_handler']));
            }
            foreach (['id', 'name', 'save_path', 'cache_expire', 'cache_limiter'] as $k) {
                if (isset($config[$k])) {
                    ('session_'.$config[$k])($value);
                }
            }
        }
        if (empty($config['disable_auto_start'])) {
            session_start();
        }
    }
    
    public static function __callStatic($method, $params)
    {
        if (function_exists($function = 'session_'.Str::toSnake($method))) {
            return $function(...$params);
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    public static function get($name = null, $default = null)
    {
        return $name === null ? $_SESSION : ($_SESSION[$name] ?? $default);
    }
    
    public static function has($name)
    {
        return isset($_SESSION[$name]);
    }

    public static function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }
    
    public static function delete($name)
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }
    
    public static function clear()
    {
        session_unset();
        if ($_SESSION) {
            $_SESSION = [];
        }
        session_destroy();
    }
}
Session::init();
