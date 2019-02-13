<?php
namespace framework\core;

class Container
{
    protected static $init;
    // 容器实例
    protected static $instances;
    // 容器提供者设置
    protected static $providers = [
        // 驱动（驱动名称空间）
        'driver'    => [
            'db'        => 'framework\driver\db',
            'sms'       => 'framework\driver\sms',
            'rpc'       => 'framework\driver\rpc',
            'data'      => 'framework\driver\data',
            'cache'     => 'framework\driver\cache',
            'crypt'     => 'framework\driver\crypt',
            'queue'     => 'framework\driver\queue',
            'email'     => 'framework\driver\email',
            'geoip'     => 'framework\driver\geoip',
            'search'    => 'framework\driver\search',
            'logger'    => 'framework\driver\logger',
            'captcha'   => 'framework\driver\captcha',
            'storage'   => 'framework\driver\storage',
        ],
        // 模型（模型名称空间与模型层级）
        'model'     => [
            'model'     => ['app\model', 1],
            'logic'     => ['app\logic', 1],
            'service'   => ['app\service', 1],
        ],
        // 匿名函数（执行匿名函数返回一个实例）
        'closure'   => [],
        // 类（类配置，第一个元素是类名，其余为类实例化参数）
        'class'     => [],
        // 别名（别名指向非别名类型提供者）
        'alias'     => [],
    ];

    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::read('container')) {
            foreach ($config as $type => $value) {
                if (isset(self::$providers[$type])) {
                    self::$providers[$type] = $value + self::$providers[$type];
                }
            }
        }
        Event::on('exit', [__CLASS__, 'clean']);
    }
    
    /*
     * 获取已有实例
     */
    public static function get($name)
    {
        return self::$instances[$name] ?? null;
    }
    
    /*
     * 检查已有实例存在
     */
    public static function has($name)
    {
        return isset(self::$instances[$name]);
    }
    
    /*
     * 设置实例
     */
    public static function set($name, object $value)
    {
        self::$instances[$name] = $value;
    }
    
    /*
     * 删除已有实例
     */
    public static function delete($name)
    {
        if (isset(self::$instances[$name])) {
            unset(self::$instances[$name]);
        }
    }
    
    /*
     * 绑定实例生成规则
     */
    public static function bind($type, $name, $value)
    {
        self::$providers[$type][$name] = $value;
    }
    
    /*
     * 生成实例
     */
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
    
    /*
     * 获取模型实例
     */
    public static function model($name)
    {
        $ns = explode('.', $name);
        if (isset(self::$providers['model'][$ns[0]])) {
            return self::$instances[$name] ?? self::$instances[$name] = self::makeModel(...$ns);
        }
    }
    
    /*
     * 获取驱动实例
     */
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
    
    /*
     * 生成别名规则实例
     */
    public static function makeAlias($name)
    {
        $params = explode('.', self::$providers['alias'][$name]);
        if (($type = self::getProviderType($params[0])) !== 'alias') {
            return self::{"make$type"}(...$params);
        }
    }

    /*
     * 生成类定义规则实例
     */
    public static function makeClass($name)
    {
        $value = self::$providers['class'][$name];
        return new $value[0](...array_slice($value, 1));
    }

    /*
     * 生成匿名函数规则实例
     */
    public static function makeClosure($name)
    {
        return is_object($object = self::$providers['closure'][$name]()) ? $object : null;
    }
    
    /*
     * 生成模型实例
     */
    public static function makeModel($type, ...$ns)
    {
        $provider = self::$providers['model'][$type];
        $class = (is_array($provider) ? $provider[0] : $provider).'\\'.implode('\\', $ns);
        return new $class();
    }

    /*
     * 生成驱动实例
     */
    public static function makeDriver($type, $index = null)
    {
        if ($index) {
            return self::makeDriverInstance($type, Config::get("$type.$index"));
        }
        list($index, $config) = Config::headKv($type);
        $key = "$type.$index";
        return self::$instances[$key] ?? self::$instances[$key] = self::makeDriverInstance($type, $config);
    }
    
    /*
     * 生成驱动实例（不缓存）
     */
    public static function makeDriverInstance($type, $config)
    {
        $class = $config['class'] ?? self::$providers['driver'][$type].'\\'.ucfirst($config['driver']);
        return new $class($config);
    }
    
    /*
     * 获取实例规则类型
     */
    public static function getProviderType($name)
    {
        foreach (self::$providers as $type => $provider) {
            if (isset($provider[$name])) {
                return $type;
            }
        }
    }
    
    /*
     * 获取实例规则
     */
    public static function getProviderValue($type, $name)
    {
        return self::$providers[$type][$name] ?? null;
    }
    
    /*
     * 清除已存实例
     */
    public static function clean()
    {
        self::$instances = null;
    }
}
Container::__init();
