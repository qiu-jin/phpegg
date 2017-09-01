<?php
namespace framework\core;

class Config
{
    private static $init;
    //配置文件目录
    private static $dir;
    //缓存文件检查
    private static $checked = [];
    //配置值缓存
    private static $configs = [];
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        self::loadEnvFile();
        if ($dir = self::env('CONFIG_DIR')) {
            self::$dir = $dir;
        } elseif ($file = self::env('CONFIG_FILE')) {
            self::loadSingleFile("$file.php");
        }
    }
    
    /*
     * 获取环境变量值
     */
    public static function env($name, $default = null)
    {
        $const = 'APP\ENV\\'.$name;
        return defined($const) ? constant($const) : $default;
    }
    
    /*
     * 获取配置项值
     */
    public static function get($name, $default = null)
    {
        $prefix = strtok($name, '.');
        if (self::check($prefix)) {
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
        return $default;
    }
    
    /*
     * 检查配置项是否已设置
     */
    public static function has($name)
    {
        $prefix = strtok($name, '.');
        if (self::check($prefix)) {
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
        return false;
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
     * 设置配置项第一个值，不支持多级配置项
     */
    public static function first($name)
    {
        return self::check($name) ? reset(self::$configs[$name]) : false;
    }
    
    /*
     * 设置配置项随机值，不支持多级配置项
     */
    public static function random($name)
    {
        return self::check($name) ? self::$configs[$name][array_rand(self::$configs[$name])] : false;
    }
    
    /*
     * 设置配置项第一个键值对，不支持多级配置项
     */
    public static function firstPair($name)
    {
        return self::check($name) ? [key(self::$configs[$name]), reset(self::$configs[$name])] : false;
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
        return self::$dir && self::loadFile($name);
    }
    
    /*
     * 从目录中导入文件
     */
    private static function loadFile($name)
    {
        $file = self::$dir.$name.'.php';
        if (is_php_file($file)) {
            $config = __include($file);
            if (is_array($config)) {
                self::$configs[$name] = $config;
                return true;
            }
        }
        return false;
    }
    
    /*
     * 导入环境配置文件
     */
    private static function loadEnvFile()
    {
        $file = defined('APP_ENV_FILE') ? APP_ENV_FILE : APP_DIR.'env.php';
        if (is_php_file($file)) {
            __include($file);
        }
    }
    
    /*
     * 导入单文件配置
     */
    private static function loadSingleFile($path)
    {
        $configs = __include($path);
        if (is_array($configs)) {
            self::$configs = $configs;
        }
    }
}
Config::init();
