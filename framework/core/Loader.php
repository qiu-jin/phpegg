<?php 
namespace framework\core;

class Loader
{
    private static $init;
    private static $cache = [];
    private static $class_map = [];
    private static $class_psr = [];
    private static $class_alias = [];
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        self::import(VENDOR_DIR.'autoload');
        if ($config = Config::get('loader')) {
            foreach ($config as $type => $rules) {
                self::add($rules, $type);
            }
        }
        spl_autoload_register(__CLASS__.'::autoload', true, true);
    }
    
    public static function add(array $rules, $type = 'psr')
    {
        if (count($rules) > 0) {
            switch ($type) {
                case 'psr':
                    self::$class_psr = self::$class_psr+$rules;
                    break;
                case 'map':
                    self::$class_map = self::$class_map+$rules;
                    break;
                case 'alias':
                    self::$class_alias = self::$class_alias+$rules;
                    break;
                case 'files':
                    foreach ($rules as $name) {
                        self::import($name);
                    }
                    break;
            }
        }
    }
    
    public static function clear($type = null)
    {
        switch ($type) {
            case 'psr':
                self::$class_psr = [];
                return;
            case 'map':
                self::$class_map = [];
                return;
            case 'alias':
                self::$class_alias = [];
                return;
            case null:
                self::$class_map = [];
                self::$class_psr = [];
                self::$class_alias = [];
                return;
        }
    }
    
    public static function import($name, $ignore = true, $cache = false)
    {
        if ($name{0} !== '/') {
            $name = ROOT_DIR.$name;
        }
        if ($cache) {
            $realname = realpath($name);
            if (isset(self::$cache[$realname])) return;
            self::$cache[$realname] = true;
        }
        if ($ignore) {
            if (is_file($name.'.php')) {
                include($name.'.php');
            }
        } else {
            require($name.'.php');
        }
    }

    private static function autoload($class)
    {
        $fn = strtok($class, '\\');
        if (strcasecmp($fn, 'framework') === 0 || strcasecmp($fn, 'app') === 0) {
            $path = strtr($class, '\\', '/');
            self::import(ROOT_DIR.strtolower(dirname($path)).'/'.basename($path));
        } elseif(isset(self::$class_psr[$fn])) {
            self::import(self::$class_psr[$fn].strtr($class, '\\', '/'));
        } elseif(isset(self::$class_map[$class])) {
            self::import(self::$class_map[$class]);
        } elseif (isset(self::$class_alias[$class])) {
            $class = self::$class_alias[$class];
            class_alias($class, self::$class_alias[$class]);
        } 
    }
}
Loader::init();
