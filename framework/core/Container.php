<?php
namespace framework\core;

class Container
{
    protected static $init;
    protected static $container;
    protected static $providers = [
        //key表示支持的驱动类型名
        'driver'    => [
            'db'        => true,
            'rpc'       => true,
            'cache'     => true,
            'storage'   => true,
            'search'    => true,
            'data'      => true,
            'queue'     => true,
            'email'     => true,
            'sms'       => true,
            'geoip'     => true,
            'crypt'     => true,
            'captcha'   => true,
        ],
        //key表示支持的模型名，value表示模型类的namespace层数
        'model'     => [
            'model'     => 1,
            'logic'     => 1,
            'service'   => 1
        ],
        'closure'   => [],
        'class'     => [],
        'alias'     => [],
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
     * 生成实例
     */
    public static function make($name)
    {
        if (isset(self::$container[$name])) {
            return self::$container[$name];
        }
        $params = explode('.', $name);
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$params[0]])) {
                return self::$container[$params[0]] = self::{'get'.ucfirst($type)}(...$params);
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
            return self::getDriver($type, $name);
        }
        return self::$container[$name ? "$type.$name" : $type] = self::getDriver($type, $name);
    }
    
    public static function model($name)
    {
        return self::$container[$name] = self::getModel(...explode('.', $name));
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
                if ($value instanceof \Closure) {
                    self::$providers['closure'][$name] = $value;
                }
                break;
        }
        throw new Exception("Bind illegal type to $name");
    }
    
    protected static function getModel(...$params)
    {
        $depth = self::$providers['model'][$params[0]];
        if ($depth > 0) {
            return count($params) === $depth + 1 ? self::makeModel($params) : self::makeModelNs($params, $depth);
        }
    }
    
    /*
     * 生成驱动实例
     */
    protected static function getDriver($type, $name = null)
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
    
    /*
     * 获取模型类实例
     */
    protected static function makeModel($params)
    {
        $class = 'app\\'.implode('\\', $params);
        return new $class();
    }
    
    /*
     * 获取模型
     */
    protected static function makeModelNs($ns, $depth)
    {
        return new class($ns, $depth) extends Container{
            protected $__ns;
            protected $__depth;
            public function __construct($ns, $depth)
            {
                $this->__ns = $ns;
                $this->__depth = $depth - 1;
            }
            public function __get($name)
            {
                $this->__ns[] = $name;
                $model = implode('.', $this->__ns);
                if (isset(self::$container[$model])) {
                    return $this->$name = self::$container[$model];
                }
                $this->$name = $this->__depth < 1 ? self::makeModel($this->__ns) : new self($this->__ns, $this->__depth);
                return self::$container[$model] = $this->$name;
            }
        };
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
