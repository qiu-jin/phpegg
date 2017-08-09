<?php
namespace framework\core;

class Container
{
    private static $init;
    private static $container;
    //key表示支持的模型名，value表示模型类的namespace层数，为0时不限制
    private static $model_provider = [
        'model'     => 1,
        'logic'     => 1,
        'service'   => 1
    ];
    //key表示支持的驱动类型名，value表示驱动类实例是否默认缓存
    private static $driver_provider = [
        'db'        => true,
        'rpc'       => true,
        'cache'     => true,
        'storage'   => true,
        'search'    => true,
        'data'      => true,
        'queue'     => false,
        'email'     => false,
        'sms'       => false,
        'geoip'     => false
    ];
    //key表示容器名，value表示容器类名
    private static $class_provider = [];

    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('app.container');
        if ($config) {
            isset($config['model_provider'])  && self::$model_provider  += $config['model_provider'];
            isset($config['class_provider'])  && self::$class_provider  += $config['class_provider'];
            isset($config['driver_provider']) && self::$driver_provider += $config['driver_provider'];
        }
        Hook::add('exit', __CLASS__.'::free');
    }
    
    /*
     * 获取容器实例
     */
    public static function get($name)
    {
        if (isset(self::$container[$name])) {
            return self::$container[$name];
        }
        $prefix = strtok($name, '.');
        if (isset(self::$driver_provider[$prefix])) {
            return self::load($prefix, strtok('.'));
        } elseif (isset(self::$model_provider[$prefix])) {
            if ($prefix !== $name) {
                return self::model($name);
            }
            return self::$container[$name] = new ModelGetter($name, self::$model_provider[$prefix]);
        } elseif (isset(self::$class_provider[$name])) {
            return self::$container[$name] = new self::$class_provider[$name];
        }
    }
    
    /*
     * 获取容器实例
     */
    public static function has($name)
    {
        return isset(self::$container[$name]);
    }
    
    /*
     * 设置容器类
     */
    public static function bind($name, $class)
    {
        self::$class_provider[$name] = $class;
    }
    
    /*
     * 获取驱动实例，不缓存实例
     */
    public static function make($name, $class, $config = null)
    {   
        return self::$container[$name] = $config ? new $class($config) : new $class();
    }
    
    /*
     * 获取驱动实例，缓存实例
     */
    public static function load($type, $name = null)
    {
        $index = $name ? "$type.$name" : $type;
        if (isset(self::$container[$index])) {
            return self::$container[$index];
        }
        if ($name) {
            $config = Config::get($index);
        } else {
            $null_name = true;
            list($name, $config) = Config::firstPair($type);
        }
        if (isset($config['driver'])) {
            self::$container[$index] =  self::driver($type, $config['driver'], $config);
            if (isset($null_name)) {
                self::$container["$type.$name"] = self::$container[$index];
            }
            return self::$container[$index];
        }
    }
    
    /*
     * 获取模型类实例
     */
    public static function model($name, $config = null)
    {
        if (isset(self::$container[$name])) {
            return self::$container[$name];
        }
        $class = 'app\\'.strtr($name, '.', '\\');
        if ((class_exists($class))) {
            return self::$container[$name] = $config ? new $class($config) : new $class();
        }
        throw new \Exception('Class not exists: '.$class);
    }
    
    public static function driver($type, $driver, $config = [])
    {
        $class = 'framework\driver\\'.$type.'\\'.ucfirst($driver);
        return new $class($config);
    }

    /*
     * 清理资源
     */
    public static function free()
    {
        self::$container = null;
    }
}
Container::init();

class ModelGetter
{
    protected $__depth;
    protected $__prefix;

    public function __construct($prefix, $depth)
    {
        $this->__depth = $depth - 1;
        $this->__prefix = $prefix;
    }
    
    public function __get($name)
    {
        if ($this->__depth === 0) {
            return $this->$name = Container::model($this->__prefix.'.'.$name);
        }
        return $this->$name = new self($this->__prefix.'.'.$name, $this->__depth);
    }
    
    public function __call($method, $param = [])
    {
        return Container::model($this->__prefix)->$method(...$param);
    }
}
