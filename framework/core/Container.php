<?php
namespace framework\core;

abstract class Container
{
    private static $_init;
    private static $_class_map = [];
    private static $_connection_map = [];
    private static $_connection_names = [
        'cache'     => true,
        'db'        => true,
        'rpc'       => true,
        'storage'   => true,
        'queue'     => false,
        'email'     => false,
        'sms'       => false,
        'geoip'     => false,
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
        /*
        if (isset($this->connections[$name])) {
            
        } elseif (isset(self::$_connection_names[$name])) {
            return $this->$name = self::$_connection_names[$name] ? self::connect($name) : self::load($name);
        }
        */
        if (isset(self::$_connection_names[$name])) {
            if (self::$_connection_names[$name]) {
                if (isset($this->connections[$name])) {
                    $config = $this->connections[$name];
                    return $this->$name = is_array($config) ? self::connect($name, $config) : self::load($name, $config);
                }
                return $this->$name = return self::connect($name);
            }
            return $this->$name = self::load($name);
        }
        return new ContainerChain($name);
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

class ContainerChain 
{
    private $ns;
    private $type;
    
    public function __construct($type)
    {
        $this->type = $type;
    }
    
    public function __get($class)
    {
        $this->ns[] = $class;
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if ($this->ns) {
            return Container::get(implode('.', $this->ns), $type)->$method(...$params);
        }
    }
}
