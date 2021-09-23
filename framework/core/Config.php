<?php
namespace framework\core;

use framework\util\Arr;

class Config
{
    private static $init;
    //配置文件目录
    private static $dir;
    //配置值缓存
    private static $config;
    //文件检查缓存
    private static $checked;
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        __require(defined('ENV_FILE') ? ENV_FILE : APP_DIR.'env.php');
        if ($file = self::env('CONFIG_FILE')) {
			self::$config = __require($file);
        }
        self::$dir = self::env('CONFIG_DIR');
    }
    
    /*
     * 获取环境变量值
     */
    public static function env($name, $default = null)
    {
        return defined($const = "app\\env\\$name") ? constant($const) : $default;
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
			if (is_array($value)) {
				return Arr::get($value, $ns, $default);
			}
			return $default;
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
			if (is_array($value)) {
				return Arr::has($value, $ns, $default);
			}
			return false;
		}
		return true;
    }
    
    /*
     * 设置配置项值
     */
    public static function set($name, $value)
    {
        $ns = explode('.', $name);
		$fn = array_shift($fn);
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
     * 读取配置项值，不缓存值（仅支持顶级配置项）
     */
    public static function read($name, $use_cache = true)
    {
		if (strpos($name, '.') === false) {
			if ($use_cache && isset(self::$config[$name])) {
				return self::$config[$name];
			} elseif (self::$dir) {
				return self::loadFile($name);
			}
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
        if (self::$dir && !isset(self::$checked[$name])) {
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
        if (is_php_file($file = self::$dir."$name.php") && is_array($config = __require($file))) {
            return $config;
        }
    }
}
Config::__init();
