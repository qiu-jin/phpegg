<?php
namespace framework\core;

trait ContainerGetter
{
    protected $connections;
    
    public function __get($name)
    {
        if (isset($this->connections[$name])) {
            $config = $this->connections[$name];
            $type = isset($config['type']) ? $config['type'] : $name;
            return $this->$name = Container::load($type, $config);
        } elseif (in_array($name, Container::CONN_TYPE)) {
            return $this->$name = Container::load($name);
        }
    }
}

abstract class Container
{
    const CONN_TYPE = [
        'cache', 'db', 'rpc', 'storage', 'search', 'data', 'queue', 'email', 'sms', 'geoip'
    ];
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

    public static function get($name, $type = null)
    {
        if (isset(self::$_class_map[$type][$name])) {
            return self::$_class_map[$type][$name];
        } else {
            $class = 'App\\'.strtolower($type).'\\'.strtr($name, '.', '\\');
            if ((class_exists($class))) {
                return self::$_class_map[$type][$name] = new $class();
            }
            throw new \Exception('Class not exists: '.$class);
        }
    }
    
    public static function make($type, $name = null)
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
    
    public static function load($type, $name = null)
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
