<?php
namespace framework\core;

class Container
{
    private static $init;
    protected static $container;
    protected static $providers = [
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
        //key表示支持的模型名，value表示模型类的namespace层数，为0时不限制
        'model'  => [
            'model'     => 1,
            'logic'     => 1,
            'service'   => 1
        ],
        //key表示容器名，value表示容器类名
        'class'  => [],
        'closure'=> [],
        'alias'  => [],
    ];

    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('container');
        if ($config) {
            foreach (array_keys(self::$providers) as $type) {
                $key = $type."_provider";
                if (isset($config[$key])) {
                    self::$providers[$type] += $config[$key];
                }
            }
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
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$prefix])) {
                return self::$container[$name] = self::{'get'.ucfirst($type)}($name);
            }
        }
    }
    
    /*
     * 
     */
    public static function has($name)
    {
        return isset(self::$container[$name]);
    }
    
    /*
     * 
     */
    public static function set($name, object $object)
    {
        self::$container[$name] = $object;
    }
    
    /*
     * 设置器类
     */
    public static function bind($name, $class)
    {
        self::$providers['class'][$name] = $class;
    }
    
    /*
     * 
     */
    public static function make($name, $class, ...$config)
    {   
        return self::$container[$name] = new $class(...$config);
    }
    
    protected static function setAlias($alias, $name)
    {
        self::$providers['alias'][$alias] = $name;
    }

    protected static function setClosure($name, callable $closure)
    {
        self::$providers['closure'][$name] = $closure;
    }
    
    /*
     * 
     */
    public static function driver($type, $name = null)
    {
        if ($name) {
            $config = is_array($name) ? $name : Config::get("$type.$name");
        } else {
            list($index, $config) = Config::firstPair($type);
        }
        if (isset($config['driver'])) {
            $class = 'framework\driver\\'.$type.'\\'.ucfirst($config['driver']);

            if (isset($index)) {
                return self::$container["$type.$index"] = new $class($config);
            }
            return new $class($config);
        }
    }
    
    /*
     * 获取模型类实例
     */
    public static function model($name)
    {
        $class = 'app\\'.strtr($name, '.', '\\');
        return new $class();
    }

    /*
     * 清理资源
     */
    public static function free()
    {
        self::$container = null;
    }
    
    protected static function getDriver($name)
    {
        return self::driver(...explode('.', $name));
    }
    
    protected static function getModel($name)
    {
        if (strpos('.', $name)) {
            return self::model($name);
        }
        //7.0后可使用匿名类
        /*
        return new class($name, self::$providers['model'][$name]) {
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
        };
        */
        return new ModelGetter($name, self::$providers['model'][$name]);
    }

    protected static function getClass($name)
    {
        return new self::$providers['class'][$name];
    }

    protected static function getClosure($name)
    {
        return self::$providers['closure'][$name]();
    }
    
    protected static function getAlias($name)
    {
        return self::get(self::$providers['alias'][$name]);
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
