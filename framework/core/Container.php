<?php
namespace framework\core;

class Container
{
    protected static $init;
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
            'geoip'     => false,
            'crypt'     => false,
            'captcha'   => false,
        ],
        //key表示支持的模型名，value表示模型类的namespace层数，为0时不限制
        'model'  => [
            'model'     => 1,
            'logic'     => 1,
            'service'   => 1
        ],
        'class'  => [
            //
        ],
        'closure'=> [
            //
        ],
        'alias'  => [
            //
        ],
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
                if (isset($config[$type])) {
                    self::$providers[$type] = array_merge(self::$providers[$type], $config[$type]);
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
        return self::$container[$name] ?? null;
    }
    
    /*
     * 
     */
    public static function has($name)
    {
        return isset(self::$container[$name]);
    }
    
    /*
     * 设置容器实例
     */
    public static function set($name, object $value)
    {
        self::$container[$name] = $value;
    }

    /*
     * 绑定容器
     */
    public static function bind($name, $value)
    {
        switch (gettype($value)) {
            case 'array':
                self::$providers['class'][$name] = $value;
                break;
            case 'string':
                if (strcasecmp($name, $value) !== 0) {
                    self::$providers['alias'][$name] = $value;
                    break;
                }
                throw new Exception("Alias same name");
            case 'object':
                if (is_callable($value)) {
                    self::$providers['closure'][$name] = $value;
                }
                break;
        }
    }
    
    /*
     * 生成实例
     */
    public static function make($name)
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
    
    public static function exists($name)
    {
        if (isset(self::$container[$name])) {
            return true;
        }
        $prefix = strtok($name, '.');
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$prefix])) return true;
        }
        return false;
    }
    
    public static function driver($type, $name = null)
    {
        if (is_array($name)) {
            return self::makeDriver($type, $name);
        }
        return self::$container[$name ? "$type.$name" : $type] = self::makeDriver($type, $name);
    }
    
    public static function model($name)
    {
        return self::$container[$name] = self::makeModel($name);
    }
    
    /*
     * 生成驱动实例
     */
    public static function makeDriver($type, $name = null)
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
    public static function makeModel($name)
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
        return self::makeDriver(...explode('.', $name));
    }
    
    protected static function getModel($name)
    {
        $depth = self::$providers['model'][$name];
        if ($depth > 0 && $depth === substr_count($name, '.')) {
            return self::makeModel($name);
        }
        return new class($name, $depth) {
            protected $__prev;
            protected $__depth;
            protected $__prefix;
            public function __construct($prefix, $depth, $prev = null)
            {
                $this->__prev = $prev;
                $this->__depth = $depth - 1;
                $this->__prefix = $prefix;
            }
            public function __get($name)
            {
                $this->__prev = null;
                if ($this->__depth === 0) {
                    return $this->$name = Container::model($this->__prefix.'.'.$name);
                }
                return $this->$name = new self($this->__prefix.'.'.$name, $this->__depth, $this);
            }
            public function __call($method, $param = [])
            {
                if ($this->__depth > 0) {
                    $model = Container::model($this->__prefix);
                    $this->__prev->{substr(strstr($this->__prefix, '.'), 1)} = $model;
                    $this->__prev = null;
                    return $model->$method(...$param);
                }
                throw new Exception("Call to undefined method $method()");
            }
        };
    }

    protected static function getClass($name)
    {
        $value = self::$providers['class'][$name];
        return new $value[0](...array_slice($value, 1));
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
