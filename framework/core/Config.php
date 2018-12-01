<?php
namespace framework\core;

use framework\util\Arr;

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
        $ns = explode('.', $name);
        $fn = $ns[0];
        if (!self::check($fn)) {
            return $default;
        }
        unset($ns[0]);
        $value = self::$configs[$fn];
        return $ns ? Arr::get($value, $ns, $default) : $value;
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
        unset($ns[0]);
        return $ns ? Arr::has(self::$configs[$fn], $ns) : true;
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
                self::$configs[$fn] = [];
            }
            unset($ns[0]);
            Arr::set(self::$configs[$fn], $ns, $value);
        } else {
            self::$configs[$name] = $value;
        }
    }
    
    /*
     * 获取配置项值，不缓存（仅支持顶级配置项）
     */
    public static function flash($name)
    {
        return self::$configs[$name] ?? self::load($name);
    }
    
    /*
     * 获取配置项首个键值对（仅支持顶级配置项）
     */
    public static function headKv($name)
    {
        if (self::check($name)) {
            return Arr::headKv(self::$configs[$name]);
        }
    }
    
    /*
     * 获取配置项随机键值对（仅支持顶级配置项）
     */
    public static function randomKv($name)
    {
        if (self::check($name)) {
            return Arr::randomKv(self::$configs[$name]);
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
