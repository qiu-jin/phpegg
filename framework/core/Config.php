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
        self::$configs = new \stdClass();
        self::setEnv();
        $config_name = defined('CONFIG_NAME') ? CONFIG_NAME : 'config';
        if (is_dir(APP_DIR.$config_name.'/')) {
            self::$path = APP_DIR.$config_name.'/';
        } elseif(is_file(APP_DIR.$config_name.'.php')) {
            self::load(include(APP_DIR.$config_name.'.php'));
        }
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
    
    private static function import($name)
    {
        if (!isset(self::$configs->$name)) {
            if (isset(self::$path)) {
                $file = self::$path.$name.'.php';
                if (is_file($file)) {
                    $config = include($file);
                    if (is_array($config)) {
                        self::$configs->$name = $config;
                        return;
                    }
                }
            }
            self::$configs->$name = [];
        }
    }
    
    private static function setEnv()
    {
        $envfile = APP_DIR.'.env';
        if (is_file($envfile)) {
            $env = parse_ini_file($envfile);
            if ($env) {
                foreach ($env as $k => $v) {
                    putenv("$k=$v");
                }
            }
        }
    }
}
Config::init();
