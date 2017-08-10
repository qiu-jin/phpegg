<?php
namespace framework\core;

class Container
{
    private static $init;
    private static $container;
    /*
    private static $provider = [
        //key表示支持的模型名，value表示模型类的namespace层数，为0时不限制
        'model'  => [
            'model'     => 1,
            'logic'     => 1,
            'service'   => 1
        ],
        //key表示支持的驱动类型名，value表示驱动类实例是否默认缓存
        'driver' => [
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
        ],
        //key表示容器名，value表示容器类名
        'class'  => [],
        'alias'  => [],
        'closure'=> []
    ];
    */
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
    private static $alias_provider = [];
    private static $closure_provider = [];
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('container');
        /*
        if ($config) {
            foreach (array_keys(self::$providers) as $type) {
                $key = $type."_provider";
                if (isset($config[$key])) {
                    self::$providers[$type] += $config[$key];
                }
            }
        }
        */
        if ($config) {
            isset($config['model_provider'])  && self::$model_provider  += $config['model_provider'];
            isset($config['class_provider'])  && self::$class_provider  += $config['class_provider'];
            isset($config['alias_provider'])  && self::$alias_provider  += $config['alias_provider'];
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
        /*
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$name])) {
                return self::{'get'.ucfirst($type)}($provider[$name]);
            }
        }
        */
        if (isset(self::$alias_provider[$name])) {
            $alias = $name;
            $name = self::$alias_provider[$name];
            if (isset(self::$container[$name])) {
                return self::$container[$alias] = self::$container[$name];
            }
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
        } elseif (isset(self::$closure_provider[$name])) {
            return self::$container[$name] = self::$class_provider[$name]();
        }
        //throw new \Exception('Container not exists: '.$name);
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
    public static function set($name, object $object)
    {
        self::$container[$name] = $object;
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
    public static function make($name, $class, ...$config)
    {   
        return self::$container[$name] = new $class(...$config);
    }
    
    /*
     * 设置容器类
    public static function setAlias($alias, $name)
    {
        self::$alias_provider[$alias] = $name;
    }

    public static function setClosure($name, callable $closure)
    {
        self::$closure_provider[$name] = $closure;
    }
    
    public static function getClass($name)
    {
        return new self::$providers['class'][$name];
    }
    
    public static function getAlias($name)
    {
        return self::get(self::$providers['alias'][$name]);
    }

    public static function getClosure($name)
    {
        return self::$providers['closure'][$name]();
    }
    
    public static function getDriver($name)
    {
        return self::load($name);
    }
    
    public static function getModel($name)
    {
        return new ModelGetter($name, self::$providers['model'][$name]);
    }
    */
    
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

//7.0后改成匿名类
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
