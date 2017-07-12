<?php
namespace framework\core;

class Container
{
    private static $init;
    private static $model_map = [];
    //key表示支持的模型名，value表示模型类的namespace层数，为0时不限制
    private static $model_type = [
        'model'     => 1,
        'logic'     => 1,
        'service'   => 1
    ];
    private static $connection_map = [];
    //key表示支持的驱动类型名，value表示驱动类实例是否默认缓存
    private static $connection_type = [
        'cache'     => true,
        'db'        => true,
        'rpc'       => true,
        'storage'   => true,
        'search'    => true,
        'data'      => true,
        'queue'     => false,
        'email'     => false,
        'sms'       => false,
        'geoip'     => false
    ];

    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('app.container');
        if ($config) {
            if (isset($config['model_type'])) {
                self::$model_type = array_merge(self::$model_type, $config['model_type']);
            }
            if (isset($config['connection_type'])) {
                self::$connection_type = array_merge(self::$connection_type, $config['connection_type']);
            }
        }
        Hook::add('exit', __CLASS__.'::free');
    }

    /*
     * 类加载时调用此初始方法
     */
    public static function get($name)
    {
        if (isset(self::$model_map[$name])) {
            return self::$model_map[$name];
        } else {
            $class = 'app\\'.strtr($name, '.', '\\');
            if ((class_exists($class))) {
                return self::$model_map[$name] = new $class();
            }
            throw new \Exception('Class not exists: '.$class);
        }
    }
    
    /*
     * 获取驱动实例，不缓存实例
     */
    public static function make($type, $name = null)
    {   
        if ($name) {
            $config = is_array($name) ? $name : Config::get($type.'.'.$name);
        } else {
            $config = Config::first($type);
        }
        if (isset($config['driver'])) {
            return  self::driver($type, $config['driver'], $config);
        }
    }
    
    /*
     * 获取驱动实例，缓存实例
     */
    public static function load($type, $name = null)
    {
        if (!isset(self::$connection_map[$type][$name])) {
            if ($name === null) {
                $null_name = true;
                list($name, $config) = Config::firstPair($type);
            } else {
                $config = Config::get($type.'.'.$name);
            }
            if (isset($config['driver'])) {
                self::$connection_map[$type][$name] =  self::driver($type, $config['driver'], $config);
                if (isset($null_name)) {
                    self::$connection_map[$type][null] = self::$connection_map[$type][$name];
                }
            }
        }
        return self::$connection_map[$type][$name];
    }
    
    public static function driver($type, $driver, $config = [])
    {
        $class = 'framework\driver\\'.$type.'\\'.ucfirst($driver);
        return new $class($config);
    }
    
    public static function getModelType($name)
    {
        return isset(self::$model_type[$name]) ? self::$model_type[$name] : null;
    }
    
    public static function getConnectionType($name)
    {
        return isset(self::$connection_type[$name]) ? self::$connection_type[$name] : null;
    }

    /*
     * 清理资源
     */
    public static function free()
    {
        self::$model_map = null;
        self::$connection_map = null;
    }
}
Container::init();
