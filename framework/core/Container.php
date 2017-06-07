<?php
namespace framework\core;

use framework\extend\misc;

abstract class Container
{
    private static $_init;
    private static $_class_map = [];
    private static $_connection_map = [];
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$_init) return;
        self::$_init = true;
        Hook::add('exit', __CLASS__.'::free');
    }
    
    public function __get($name)
    {
        if (in_array($name, ['cache', 'db', 'rpc', 'storage'])) {
            if (isset($this->connections[$name])) {
                $config = $this->connections[$name];
                return $this->$name = is_array($config) ? self::connect($name, $config) : self::load($name, $config);
            }
            return $this->$name = return self::connect($name);
        } elseif (in_array($name, ['email', 'geoip', 'sms', 'queue'])) {
            return $this->$name = $this->load($name);
        } else {
            return new ContainerChain($name);
        }
    }
    
    public static function get($name, $type = 'model')
    {
        if (isset(self::$_class_map[$type][$name])) {
            return self::$_class_map[$type][$name];
        } else {
            $class = 'App\\'.strtolower($type).'\\'.strtr($name, '.', '\\');
            if ((class_exists($class))) {
                return self::$_class_map[$type][$name] = new $class();
            }
        }
    }
    
    public static function load($type, $name = null)
    {   
        if ($name) {
            $config = is_array($name) ? $name : Config::get($type.'.'.$name);
        } else {
            $config = Config::first($type);
        }
        if (isset($config['driver'])) {
            return driver($type, $config['driver'], $config);
        }
    }
    
    public static function connect($type, $name = null)
    {
        if (!isset(self::$_connection_map[$type][$name])) {
            if ($name === null) {
                $null_name = true;
                list($name, $config) = Config::firstPair($type);
            } else {
                $config = Config::get($type.'.'.$name);
            }
            if (isset($config['driver'])) {
                self::$_connection_map[$type][$name] = driver($type, $config['driver'], $config);
                if (isset($null_name)) {
                    self::$_connection_map[$type][null] = self::$_connection_map[$type][$name];
                }
            }
        }
        return self::$_connection_map[$type][$name];
    }

    public static function free()
    {
        self::$_class_map = null;
        self::$_connection_map = null;
    }
}
Container::init();
