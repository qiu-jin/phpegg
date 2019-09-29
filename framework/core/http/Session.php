<?php
namespace framework\core\http;

use framework\util\Arr;
use framework\core\Event;
use framework\core\Config;

class Session
{
    private static $init;
	// 会话启动设置，参考 https://www.php.net/manual/en/function.session-start.php
	private static $start_options = [];
	
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
            if (isset($config['start_options'])) {
                self::$start_options = $config['start_options'];
            }
            if (isset($config['save_handler'])) {
                session_set_save_handler(instance(...$config['save_handler']));
            }
			if (!empty($config['exit_close'])) {
				Event::on('exit', 'session_write_close');
			}
			if (isset($config['auto_start']) && !$config['auto_start']) {
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
		if (session_status() != PHP_SESSION_ACTIVE) {
			session_start(self::$start_options);
			Event::trigger('session');
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
			if ($value) {
				$_SESSION = array_replace_recursive($_SESSION, $name);
			} else {
				$_SESSION = $name + $_SESSION;
			}
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
        session_unset();
        session_destroy();
		if ($delete_cookie) {
		    $params = session_get_cookie_params();
		    Cookie::delete(session_name(), $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}
    }
    
    /*
     * 原生session函数魔术方法
     */
    public static function __callStatic($method, $params)
    {
        if (function_exists($func = "session_$method")) {
            return $func(...$params);
        }
        throw new \BadMethodCallException('Call to undefined method '.__CLASS__."::$method");
    }
}
Session::__init();
