<?php
namespace framework\core;

class Container
{
    protected static $init;
    protected static $providers = [
        // 驱动
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
            'logger'    => true,
            'geoip'     => true,
            'crypt'     => true,
            'captcha'   => true,
        ],
        // 模型
        'model'     => [
            'model'     => 1,
            'logic'     => 1,
            'service'   => 1
        ],
        'closure'   => [],
        'class'     => [],
        'alias'     => [],
    ];
    protected static $instances;

    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        if ($config = Config::get('container')) {
            foreach (array_keys(self::$providers) as $type) {
                if (isset($config[$type])) {
                    self::$providers[$type] = array_merge(self::$providers[$type], $config[$type]);
                }
            }
        }
        Hook::add('exit', __CLASS__.'::free');
    }
    
    public static function get($name)
    {
        return self::$instances[$name] ?? null;
    }
    
    public static function has($name)
    {
        return isset(self::$instances[$name]);
    }
    
    public static function set($name, object $value)
    {
        self::$instances[$name] = $value;
    }
    
    public static function delete($name)
    {
        if (isset(self::$instances[$name])) {
            unset(self::$instances[$name]);
        }
    }
    
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
    
    public static function make($name)
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }
        $params = explode('.', $name);
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$params[0]])) {
                return self::$instances[$name] = self::{'make'.ucfirst($type)}(...$params);
            }
        }
    }
    
    public static function driver($type, $name = null)
    {
        if (isset(self::$providers['driver'][$type])) {
            if (is_array($name)) {
                return self::makeDriverInstance($type, $name);
            }
            $index = $name ? "$type.$name" : $type;
            return self::$instances[$index] ?? self::$instances[$index] = self::makeDriver($type, $name);
        }
    }
    
    public static function model($name)
    {
        $ns = explode('.', $name);
        if (isset(self::$providers['model'][$ns[0]])) {
            return self::$instances[$name] ?? self::$instances[$name] = self::makeModel(...$ns);
        }
    }
    
    public static function hasProvider($name)
    {
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$name])) return true;
        }
        return false;
    }
    
    public static function getModel($type, ...$ns)
    {
        $index = implode('.', $ns);
        if (isset(self::$instances[$index])) {
            return self::$instances;
        }
        $depth = self::$providers['model'][$ns[0]];
        if (isset($ns[$depth])) {
            $class = 'app\\'.implode('\\', $ns);
            return new $class(); 
        } else {
            return self::makeModelNs($ns);
        }
    }
    
    protected static function makeModel($type, ...$ns)
    {
        $depth = self::$providers['model'][$type];
        if ($ns) {
            if (count($ns) === $depth) {
                $class = 'app\\'.$type.'\\'.implode('\\', $ns);
                return new $class(); 
            }
        } else {
            return self::makeModelNs($ns, $depth);
        }
    }

    protected static function makeDriver($type, $index = null)
    {
        if ($index) {
            return self::makeDriverInstance($type, Config::get("$type.$index"));
        } else {
            list($index, $config) = Config::firstPair($type);
            return self::$instances["$type.$index"] ?? self::makeDriverInstance($type, $config);
        }
    }

    protected static function makeClass($name)
    {
        $value = self::$providers['class'][$name];
        return new $value[0](...array_slice($value, 1));
    }

    protected static function makeClosure($name)
    {
        return self::$providers['closure'][$name]();
    }
    
    protected static function makeAlias($name)
    {
        return self::get(self::$providers['alias'][$name]);
    }
    
    protected static function makeModelNs($ns, $depth)
    {
        return new class($ns) {
            protected $__ns;
            protected $__depth;
            public function __construct($ns, $depth) {
                $this->__ns = $ns;
                $this->__depth = $depth--;
            }
            public function __get($name) {
                $this->__ns[] = $name;
                return $this->$name = $this->__depth < 1
                                    ? Container::model(...$this->__ns)
                                    : self($this->__ns, $this->__depth);
            }
        };
    }
    
    protected static function makeDriverInstance($type, $config)
    {
        $class = 'framework\driver\\'.$type.'\\'.ucfirst($config['driver']);
        return new $class($config);
    }
    
    public static function free()
    {
        self::$providers = null;
        self::$instances = null;
    }
}
Container::init();
