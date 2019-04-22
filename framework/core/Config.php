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
        $ns = explode('.', $name, 2);
        $fn = $ns[0];
        if (!self::check($fn)) {
            return $default;
        }
        $value = self::$config[$fn];
        return isset($ns[1]) ? Arr::get($value, $ns[1], $default) : $value;
    }
    
    /*
     * 检查配置项是否已设置
     */
    public static function has($name)
    {
        $ns = explode('.', $name, 2);
        $fn = $ns[0];
        if (!self::check($fn)) {
            return false;
        }
		return isset($ns[1]) ? Arr::has(self::$config[$fn], $ns[1]) : true;
    }
    
    /*
     * 设置配置项值
     */
    public static function set($name, $value)
    {
        $ns = explode('.', $name, 2);
        if (isset($ns[1])) {
            $fn = $ns[0];
            if (!self::check($fn)) {
                self::$config[$fn] = [];
            }
            Arr::set(self::$config[$fn], $ns[1], $value);
        } else {
            self::$config[$name] = $value;
        }
    }
    
    /*
     * 读取配置项值，不缓存（仅支持顶级配置项）
     */
    public static function read($name)
    {
        return self::$config[$name] ?? self::load($name);
    }
    
    /*
     * 获取配置项首个键值对（仅支持顶级配置项）
     */
    public static function headKv($name)
    {
        if (self::check($name)) {
            return Arr::headKv(self::$config[$name]);
        }
    }
    
    /*
     * 获取配置项随机键值对（仅支持顶级配置项）
     */
    public static function randomKv($name)
    {
        if (self::check($name)) {
            return Arr::randomKv(self::$config[$name]);
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
        if (isset(self::$checked[$name])) {
            return false;
        }
        self::$checked[$name] = true;
        return self::$dir && (self::$config[$name] = self::load($name));
    }
    
    /*
     * 从配置目录中读取子配置文件
     */
    private static function load($name)
    {
        if (is_php_file($file = self::$dir."$name.php") && is_array($return = __include($file))) {
            return $return;
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
    private static function loadFile($file)
    {
        if (is_array($config = __include($file))) {
            self::$config = $config;
        }
    }
}
Config::__init();
