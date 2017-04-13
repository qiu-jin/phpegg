<?php
namespace framework\core;

use framework\extend\db\Table;

class Model
{
    private static $_init;
    private static $_models = [];
    private static $_connections = [];
    private static $_connection_name_index = [];
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$_init) return;
        self::$_init = true;
        Hook::add('exit', __CLASS__.'::clear');
    }
    
    public function __get($name)
    {
        if (empty($this->connections[$name])) {
            return $this->$name = self::connect($name);
        } else {
            return $this->$name = self::connect($name, $this->connections[$name]);
        }
    }
    
    public static function get($name)
    {   
        if (isset(self::$_models[$name])) {
            return self::$_models[$name];
        } else {
            $class = 'app\\'.APP_NAME.'\model\\'.strtr($name, '.', '\\');
            if ((class_exists($class))) {
                return self::$_models[$name] = new $class();
            }
        }
        return null;
    }
    
    public static function table($name, $db = null)
    {
        list($db_name, $table_name) = explode('.', $name);
        $class = 'app\\'.APP_NAME.'\table\\'.$db_name.'\\'.ucfirst($table_name);
        if ((class_exists($class))) {
            return new $class($db);
        } else {
            return new Table($table_name, $db);
        }
    }
    
    public static function connect($type, $name = null)
    {
        if (is_array($name)) {
            $config = $name;
            unset($name);
        } else {
            if (isset(self::$_connection_name_index[$type][$name])) {
                return self::$_connections[self::$_connection_name_index[$type][$name]];
            } else {
                if (is_string($name)) {
                    $config = Config::get($type.'.'.$name);
                } elseif (is_null($name)) {
                    $null_name = true;
                    list($name, $config) = Config::first_pair($type);
                }
            }
        }
        if (isset($config['driver'])) {
            if (isset($name)) {
                $_connection_name_index[$type][$name] = count(self::$_connections)-1;
                if (isset($null_name)) {
                    $_connection_name_index[$type][null] = $_connection_name_index[$type][$name];
                }
            }
            return self::$_connections[] = driver($type, $config['driver'], $config);
        }
        return null;
    }

    public static function clear()
    {
        self::$_models = null;
        if (self::$_connections) {
            self::$_connections = null;
            self::$_connection_name_index = null;
        }
    }
}
Model::init();
