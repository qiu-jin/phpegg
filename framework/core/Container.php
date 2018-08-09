<?php
namespace framework\core;

class Container
{
    protected static $init;
    // 容器实例
    protected static $instances;
    // 容器提供者
    protected static $providers = [
        // 驱动
        'driver'    => [
            'db'        => 'framework\driver\db',
            'rpc'       => 'framework\driver\rpc',
            'cache'     => 'framework\driver\cache',
            'storage'   => 'framework\driver\storage',
            'search'    => 'framework\driver\search',
            'data'      => 'framework\driver\data',
            'queue'     => 'framework\driver\queue',
            'email'     => 'framework\driver\email',
            'sms'       => 'framework\driver\sms',
            'geoip'     => 'framework\driver\geoip',
            'crypt'     => 'framework\driver\crypt',
            'captcha'   => 'framework\driver\captcha',
            'logger'    => 'framework\driver\logger',
        ],
        // 模型
        'model'     => [
            'model'     => ['app\model', 1],
            'logic'     => ['app\logic', 1],
            'service'   => ['app\service', 1],
        ],
        // 闭包
        'closure'   => [],
        // 类
        'class'     => [],
        // 别名
        'alias'     => [],
    ];

    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::flash('container')) {
            foreach ($config as $type => $value) {
                if (isset(self::$providers[$type])) {
                    self::$providers[$type] = $value + self::$providers[$type];
                }
            }
        }
        Event::on('exit', [__CLASS__, 'clean']);
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
    
    public static function bind($type, $name, $value)
    {
        self::$providers[$type][$name] = $value;
    }
    
    public static function make($name)
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }
        $params = explode('.', $name);
        if ($type = self::getProviderType($params[0])) {
            return self::$instances[$name] = self::{"make$type"}(...$params);
        }
    }
    
    public static function model($name)
    {
        $ns = explode('.', $name);
        if (isset(self::$providers['model'][$ns[0]])) {
            return self::$instances[$name] ?? self::$instances[$name] = self::makeModel(...$ns);
        }
    }
    
    public static function driver($type, $name = null)
    {
        if (isset(self::$providers['driver'][$type])) {
            if (is_array($name)) {
                return self::makeDriverInstance($type, $name);
            }
            $key = $name ? "$type.$name" : $type;
            return self::$instances[$key] ?? self::$instances[$key] = self::makeDriver($type, $name);
        }
    }
    
    public static function makeAlias($name)
    {
        $params = explode('.', self::$providers['alias'][$name]);
        if (($type = self::getProviderType($params[0])) !== 'alias') {
            return self::{"make$type"}(...$params);
        }
    }

    public static function makeClass($name)
    {
        $value = self::$providers['class'][$name];
        return new $value[0](...array_slice($value, 1));
    }

    public static function makeClosure($name)
    {
        return is_object($object = self::$providers['closure'][$name]()) ? $object : null;
    }
    
    public static function makeModel($type, ...$ns)
    {
        $class = self::$providers['model'][$type][0].'\\'.implode('\\', $ns);
        return new $class();
    }

    public static function makeDriver($type, $index = null)
    {
        if ($index) {
            return self::makeDriverInstance($type, Config::get("$type.$index"));
        }
        list($index, $config) = Config::firstKv($type);
        $key = "$type.$index";
        return self::$instances[$key] ?? self::$instances[$key] = self::makeDriverInstance($type, $config);
    }
    
    public static function makeDriverInstance($type, $config)
    {
        if ($config['driver'] === 'custom') {
            $class = $config['class'];
        } else {
            $class = self::$providers['driver'][$type].'\\'.ucfirst($config['driver']);
        }
        return new $class($config);
    }
    
    public static function getProviderType($name)
    {
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$name])) {
                return $type;
            }
        }
    }
    
    public static function getProviderValue($type, $name)
    {
        return self::$providers[$type][$name] ?? null;
    }
    
    public static function clean()
    {
        self::$instances = null;
    }
}
Container::__init();
