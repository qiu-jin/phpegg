<?php
namespace framework\core;

abstract class Container
{
    private static $_init;
    private static $_class_map = [];
    private static $_connection_map = [];
    private static $_default_connections = [
        'cache', 'db', 'rpc', 'storage', 'search', 'data', 'queue', 'email', 'sms', 'geoip'
    ];
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$_init) return;
        self::$_init = true;
        Hook::add('exit', __CLASS__.'::free');
    }
    
    public function __get($name)
    {
        if (isset($this->connections[$name])) {
            if (in_array(self::$_default_connections[$name])) {
                return $this->$name = self::handler($name, $this->connections[$name]);
            }
            if (isset($this->connections[$name]['type'])) {
                return $this->$name = self::handler($this->connections[$name]['type'], $this->connections[$name]['config']);
            }
        } elseif (isset(self::$_default_connections[$name])) {
            return $this->$name = self::handler($name);
        }
        throw new \Exception('Illegal attr: '.$name);
    }
    
    public static function get($name, $type = null)
    {
        if ($type === null && strpos('app\\', __NAMESPACE__)) {
            $type = strstr(substr(__NAMESPACE__, 4), '\\');
        }
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
    
    private static function handler($type, $config = null)
    {
        if (is_array($config)) {
            return self::load($type, $config);
        }
        return self::$_connection_names[$name] ? self::connect($type, $config) : self::load($type, $config);
    }
}
Container::init();
