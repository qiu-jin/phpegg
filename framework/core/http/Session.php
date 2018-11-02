<?php
namespace framework\core\http;

use framework\util\Str;
use framework\util\Arr;
use framework\core\Config;

class Session
{
    private static $init;
    // 可用初始函数
    private static $init_functions = [
        'id', 'name', 'save_path', 'cache_expire', 'cache_limiter', 'module_name'
    ];
    
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::flash('session')) {
            if ($set = Arr::pull($config, 'ini_set')) {
                foreach ($set as $k => $v) {
                    ini_set("session.$k", $v);
                }
            }
            if ($v = Arr::pull($config, 'cookie_params')) {
                session_set_cookie_params(...$v);
            }
            if ($v = Arr::pull($config, 'save_handler')) {
                session_set_save_handler(instance(...$v));
            }
            foreach ($config as $k => $v) {
                if (in_array($k, self::$init_functions)) {
                    ("session_$k")($v);
                }
            }
            if (Arr::pull($config, 'auto_start') === false) {
                return;
            }
        }
        session_start();
    }
    
    public static function all()
    {
        return $_SESSION;
    }
    
    public static function get($name, $default = null)
    {
        return Arr::get($_SESSION, $name, $default);
    }
    
    public static function has($name)
    {
        return Arr::has($_SESSION, $name);
    }

    public static function set($name, $value = null)
    {
        if (is_array($name)) {
            $_SESSION = array_replace_recursive($_SESSION, $name);
        } else {
            Arr::set($_SESSION, $name, $value);
        }
    }
    
    public static function delete($name)
    {
        Arr::delete($_SESSION, $name);
    }
    
    public static function clean()
    {
        session_unset();
        $_SESSION = [];
        session_destroy();
    }
    
    /*
     * Session 函数魔术方法
     * session_​abort
     * session_​cache_​expire
     * session_​cache_​limiter
     * session_​commit
     * session_​create_​id
     * session_​decode
     * session_​destroy
     * session_​encode
     * session_​gc
     * session_​get_​cookie_​params
     * session_​id
     * session_​is_​registered
     * session_​module_​name
     * session_​name
     * session_​regenerate_​id
     * session_​register_​shutdown
     * session_​register
     * session_​reset
     * session_​save_​path
     * session_​set_​cookie_​params
     * session_​set_​save_​handler
     * session_​start
     * session_​status
     * session_​unregister
     * session_​unset
     * session_​write_​close
    */
    public static function __callStatic($method, $params)
    {
        if (function_exists($function = 'session_'.Str::toSnake($method))) {
            return $function(...$params);
        }
        throw new \BadMethodCallException('Call to undefined method '.__CLASS__."::$method");
    }
}
Session::__init();
