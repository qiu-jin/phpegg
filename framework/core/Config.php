<?php
namespace framework\core;

use framework\util\Arr;

class Config
{
    private static $init;
    //配置值缓存
    private static $config;
    //文件检查缓存
    private static $checked;
    //配置文件目录
    private static $config_dir;
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
		if (defined('ENV_FILE')) {
			__require(ENV_FILE);
		} elseif (file_exists(APP_DIR.'env.php')) {
			__require(APP_DIR.'env.php');
		}
		if (!defined('app\env\APP_DEBUG')) {
			define('app\env\APP_DEBUG', false);
		}
		if (!defined('app\env\STRICT_ERROR_MODE')) {
			define('app\env\STRICT_ERROR_MODE', true);
		}
        if ($config_file = self::env('CONFIG_FILE')) {
			self::$config = __require($config_file);
        }
        self::$config_dir = self::env('CONFIG_DIR');
    }
    
    /*
     * 获取环境变量值
     */
    public static function env($name, $default = null)
    {
        return defined($const = "app\\env\\$name") ? constant($const) : $default;
    }
	
    /*
     * 读取配置项值，不缓存值（仅支持顶级配置项）
     */
    public static function read($name)
    {
		if (strpos($name, '.') === false) {
			return self::$config[$name] ?? self::loadFile($name);
		}
    }
    
    /*
     * 获取配置项值
     */
    public static function get($name, $default = null)
    {
        $ns = explode('.', $name);
		$fn = array_shift($ns);
        if (!self::check($fn)) {
            return $default;
        }
        $value = self::$config[$fn];
		if ($ns) {
			return is_array($value) ? Arr::get($value, $ns, $default) : $default;
		}
        return $value;
    }
    
    /*
     * 检查配置项是否已设置
     */
    public static function has($name)
    {
        $ns = explode('.', $name);
		$fn = array_shift($ns);
        if (!self::check($fn)) {
            return false;
        }
        $value = self::$config[$fn];
		if ($ns) {
			return is_array($value) ? Arr::has($value, $ns, $default) : false;
		}
		return true;
    }
    
    /*
     * 设置配置项值
     */
    public static function set($name, $value)
    {
        $ns = explode('.', $name);
		$fn = array_shift($ns);
        if ($ns) {
            if (!self::check($fn) || !is_array(self::$config[$fn])) {
                self::$config[$fn] = [];
			}
            Arr::set(self::$config[$fn] , $ns, $value);
        } else {
            self::$config[$fn] = $value;
        }
    }

    /*
     * 检查配置是否导入
     */
    private static function check($name)
    {
        if (isset(self::$config[$name])) {
            return true;
        }
        if (!isset(self::$checked[$name])) {
	        self::$checked[$name] = true;
			$config = self::loadFile($name);
			if (isset($config)) {
				self::$config[$name] = $config;
				return true;
			}
        }
		return false;
    }
	
    /*
     * 从配置目录中读取子配置文件
     */
    private static function loadFile($name)
    {
        if (self::$config_dir && is_php_file($file = self::$config_dir."$name.php") && is_array($config = __require($file))) {
            return $config;
        }
    }
}
Config::__init();
