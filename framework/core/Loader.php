<?php 
namespace framework\core;

class Loader
{
    private static $init;
    private static $cache = [];
    private static $class_map = [];
    private static $class_prefix = [
        'app' => APP_DIR,
        'framework' => FW_DIR
    ];
    private static $class_alias = [
        'App' => 'framework\App',
        'Request' => 'framework\core\http\Request',
        'Response' => 'framework\core\http\Response',
    ];
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        self::import('vendor/autoload');
        if ($config = Config::get('loader')) {
            foreach ($config as $type => $rules) {
                self::add($rules, $type);
            }
        }
        spl_autoload_register(__CLASS__.'::autoload', true, true);
    }
    
    public static function add(array $rules, $type = 'prefix')
    {
        if (count($rules) > 0) {
            switch ($type) {
                case 'prefix':
                    self::$class_prefix = self::$class_prefix+$rules;
                    return;
                case 'map':
                    self::$class_map = self::$class_map+$rules;
                    return;
                case 'alias':
                    self::$class_alias = self::$class_alias+$rules;
                    return;
                case 'files':
                    foreach ($rules as $name) {
                        self::import($name);
                    }
                    return;
            }
        }
    }
    
    public static function import($name, $ignore = true, $cache = false)
    {
        if ($name{0} !== '/') {
            $name = ROOT_DIR.$name;
        }
        if ($cache) {
            $realname = realpath($name);
            if (isset(self::$cache[$realname])) {
                return;
            }
            self::$cache[$realname] = true;
        }
        $file = $name.'.php';
        if (!$ignore || is_file($file)) {
            __require($file);
        }
    }

    private static function autoload($class)
    {
        $fn = strstr($class, '\\', true);
        if(isset(self::$class_prefix[$fn])) {
            self::import(self::$class_prefix[$fn].strstr(strtr($class, '\\', '/'), '/'));
        } elseif(isset(self::$class_map[$class])) {
            self::import(self::$class_map[$class]);
        } elseif (isset(self::$class_alias[$class])) {
            class_alias(self::$class_alias[$class], $class);
        }
    }
}
Loader::init();
