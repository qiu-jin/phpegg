<?php
namespace framework\core;

class Config
{
    private static $init;
    //配置文件目录
    private static $dir;
    //配置值缓存
    private static $configs;
    //缓存文件检查
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
        self::loadEnv();
        if ($file = self::env('CONFIG_FILE')) {
            self::loadFile($file);
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
        if (!self::check($prefix = strtok($name, '.'))) {
            return $default;
        }
        $value = self::$configs[$prefix];
        while ($tok = strtok('.')) {
            if (isset($value[$tok])) {
                $value = $value[$tok];
            } else {
                return $default;
            }
        }
        return $value;
    }
    
    /*
     * 检查配置项是否已设置
     */
    public static function has($name)
    {
        if (!self::check($prefix = strtok($name, '.'))) {
            return false;
        }
        $value = self::$configs[$prefix];
        while ($tok = strtok('.')) {
            if (isset($value[$tok])) {
                $value = $value[$tok];
            } else {
                return false;
            }
        }
        return true;
    }
    
    /*
     * 设置配置项值
     */
    public static function set($name, $value)
    {
        if (strpos($name, '.')) {
            $prefix = strtok($name, '.');
            if (!self::check($prefix)) {
                self::$configs[$prefix] = [];
            }
            $val =& self::$configs[$prefix];
            while ($tok = strtok('.')) {
                if (!isset($val[$tok])) {
                    $val[$tok] = [];
                }
                $val =& $val[$tok];
            }
            $val = $value;
        } else {
            self::$configs[$name] = $value;
        }
    }
    
    /*
     * 获取配置项值，不缓存，不支持多级配置项
     */
    public static function flash($name)
    {
        return self::$configs[$name] ?? self::load($name);
    }
    
    /*
     * 设置配置项第一个值，不支持多级配置项
     */
    public static function first($name)
    {
        return self::check($name) ? current(self::$configs[$name]) : null;
    }
    
    /*
     * 设置配置项随机值，不支持多级配置项
     */
    public static function random($name)
    {
        return self::check($name) ? self::$configs[$name][array_rand(self::$configs[$name])] : null;
    }
    
    /*
     * 设置配置项第一个键值对，不支持多级配置项
     */
    public static function firstPair($name)
    {
        if (self::check($name)) {
            foreach (self::$configs[$name] as $key => $value) {
                return [$key, $value];
            }
        }
    }
    
    /*
     * 检查配置是否导入
     */
    private static function check($name)
    {
        if (isset(self::$configs[$name])) {
            return true;
        }
        if (isset(self::$checked[$name])) {
            return false;
        }
        self::$checked[$name] = true;
        return self::$dir && (self::$configs[$name] = self::load($name));
    }
    
    /*
     * 从配置目录中导入子配置文件
     */
    private static function load($name)
    {
        if (is_php_file($file = self::$dir."$name.php") && is_array($config = __include($file))) {
            return $config;
        }
    }
    
    /*
     * 导入环境配置文件
     */
    private static function loadEnv()
    {
        if (is_php_file($file = defined('ENV_FILE') ? ENV_FILE : APP_DIR.'env.php')) {
            __include($file);
        }
    }
    
    /*
     * 导入单文件配置
     */
    private static function loadFile($path)
    {
        if (is_array($configs = __include($path))) {
            self::$configs = $configs;
        }
    }
}
Config::__init();
