<?php
namespace framework\core;

class Config
{
    private static $path;
    private static $configs;
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$configs) return;
        self::loadEnv();
        self::$configs = new \stdClass();
        $path = APP_DIR.self::getEnv('CONFIG_PATH', 'config');
        if (is_dir($path)) {
            self::$path = "$path/";
        } elseif(is_file("$path.php")) {
            self::load(__require("$path.php"));
        }
    }
    
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
    
    public static function first_value($name)
    {
        self::import($name);
        return reset(self::$configs->$name);
    }
    
    public static function first_pair($name)
    {
        self::import($name);
        $value = reset(self::$configs->$name);
        return [key(self::$configs->$name), $value];
    }
    
    public static function load(array $configs)
    {
        if ($configs) {
            foreach ($configs as $name => $value) {
                self::$configs->$name = $value;
            }
        }
    }
    
    public static function getEnv($name, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $value : $default;
    }
    
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
}
Config::init();
