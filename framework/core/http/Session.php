<?php
namespace framework\core\http;

use framework\util\Arr;
use framework\util\Str;
use framework\core\Event;
use framework\core\Config;

class Session
{
    private static $init;
	// 只读
	private static $read_only = false;
	
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::read('session')) {
			if (isset($config['ini_set'])) {
                foreach ($config['ini_set'] as $k => $v) {
                    ini_set("session.$k", $v);
                }
			}
            if (isset($config['cookie_params'])) {
                session_set_cookie_params(...$config['cookie_params']);
            }
            if (isset($config['save_handler'])) {
                session_set_save_handler(instance(...$config['save_handler']));
            }
            if (isset($config['read_only'])) {
                self::$read_only = $config['read_only'];
            }
            if (isset($config['auto_start']) && $config['auto_start'] === false) {
                return;
            }
        }
		self::start();
    }
	
    /*
     * 开始
     */
    public static function start()
    {
        session_start();
		if (self::$read_only) {
			session_write_close();
		}
    }
    
    /*
     * 获取所有
     */
    public static function all()
    {
        return $_SESSION;
    }
    
    /*
     * 获取
     */
    public static function get($name, $default = null)
    {
        return Arr::get($_SESSION, $name, $default);
    }
    
    /*
     * 获取
     */
    public static function has($name)
    {
        return Arr::has($_SESSION, $name);
    }

    /*
     * 设置
     */
    public static function set($name, $value = null)
    {
        if (is_array($name)) {
            $_SESSION = array_replace_recursive($_SESSION, $name);
        } else {
            Arr::set($_SESSION, $name, $value);
        }
    }
    
    /*
     * 删除
     */
    public static function delete($name)
    {
        Arr::delete($_SESSION, $name);
    }
	
    /*
     * 获取并删除
     */
    public static function pull($name, $default = null)
    {
		return Arr::pull($_SESSION, $name, $default);
	}
    
    /*
     * 清除所有
     */
    public static function clean($delete_cookie = false)
    {
        $_SESSION = [];
        session_destroy();
		if ($delete_cookie) {
		    extract(session_get_cookie_params());
		    Cookie::delete(session_name(), $path, $domain, $secure, $httponly);
		}
    }
    
    /*
     * 原生session函数魔术方法
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
		$func = 'session_'.Str::snakeCase($method);
        if (function_exists($func)) {
            return $func(...$params);
        }
        throw new \BadMethodCallException('Call to undefined method '.__CLASS__."::$method");
    }
}
Session::__init();
