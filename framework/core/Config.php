<?php
namespace framework\core;

class Config
{
    //配置文件路径
    private static $path;
    //配置值缓存
    private static $configs;
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$configs) return;
        self::loadEnv();
        self::$configs = new \stdClass();
        $path = APP_DIR.self::env('CONFIG_PATH', 'config');
        if (is_dir($path)) {
            self::$path = "$path/";
        } elseif(is_file("$path.php")) {
            self::load("$path.php");
        }
    }
    
    /*
     * 获取环境变量值
     */
    public static function env($name, $default = null)
    {
        $value = getenv($name);
        return $value === false ? $default : $value;
    }
    
    /*
     * 获取配置项值
     */
    public static function get($name, $default = null)
    {
        if (strpos($name, '.')) {
            $namepath = explode('.', $name);
            $name = array_shift($namepath);
            self::import($name);
            $value = self::$configs->$name;
            foreach ($namepath as $path) {
                if (isset($value[$path])) {
                    $value = $value[$path];
                } else {
                    return $default;
                }
            }
            return $value;
        } else {
            self::import($name);
            return self::$configs->$name;
        }
        return $default;
    }
    
    /*
     * 检查配置项是否已设置
     */
    public static function has($name)
    {
        if (strpos($name, '.')) {
            $namepath = explode('.', $name);
            $name = array_shift($namepath);
            self::import($name);
            $value = self::$configs->$name;
            foreach ($namepath as $path) {
                if (isset($value[$path])) {
                    $value = $value[$path];
                } else {
                    return false;
                }
            }
            return true;
        } else {
            self::import($name);
            return bool(self::$configs->$name);
        }
    }
    
    /*
     * 设置配置项值
     */
    public static function set($name, $value)
    {
        if (strpos($name, '.')) {
            $namepath = explode('.', $name);
            $name = array_shift($namepath);
            self::import($name);
            $val =& self::$configs->$name;
            foreach ($namepath as $path) {
                if (!isset($val[$path])) {
                    $val[$path] = [];
                }
                $val =& $val[$path];
            }
            $value = $val;
        } else {
            self::$configs->$name = $value;
        }
    }
    
    /*
     * 设置配置项第一个值，不支持多级配置项
     */
    public static function first($name)
    {
        self::import($name);
        return reset(self::$configs->$name);
    }
    
    /*
     * 设置配置项随机值，不支持多级配置项
     */
    public static function random($name)
    {
        self::import($name);
        return self::$configs->$name[array_rand($configs->$name)];
    }
    
    /*
     * 设置配置项第一个键值对，不支持多级配置项
     */
    public static function firstPair($name)
    {
        self::import($name);
        $value = reset(self::$configs->$name);
        return [key(self::$configs->$name), $value];
    }
    
    /*
     * 导入配置
     */
    private static function load($path)
    {
        $configs = __require($path);
        if ($configs && is_array($configs)) {
            foreach ($configs as $name => $value) {
                self::$configs->$name = $value;
            }
        }
    }
    
    /*
     * 从文件中导入配置
     */
    private static function import($name)
    {
        if (!isset(self::$configs->$name)) {
            if (isset(self::$path)) {
                $file = self::$path.$name.'.php';
                if (is_file($file)) {
                    $config = __require($file);
                    if (is_array($config)) {
                        self::$configs->$name = $config;
                        return;
                    }
                }
            }
            self::$configs->$name = [];
        }
    }
    
    /*
     * 导入环境配置
     */
    private static function loadEnv()
    {
        $file = APP_DIR.'.env';
        if (is_file($file)) {
            $env = parse_ini_file($file);
            if ($env) {
                foreach ($env as $k => $v) {
                    putenv("$k=$v");
                }
            }
        }
    }
}
Config::init();
