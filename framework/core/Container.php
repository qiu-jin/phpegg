<?php
namespace framework\core;

class Container
{
	// Provider类型常量
	const T_DRIVER	= 1;
	const T_MODEL 	= 2;
	const T_CLASS 	= 3;
	const T_CLOSURE	= 4;
	const T_ALIAS 	= 5;

    protected static $init;
    // 容器实例
    protected static $instances;
    // 容器提供者设置
    protected static $providers = [
        'db'        => [self::T_DRIVER],
        'sms'       => [self::T_DRIVER],
        'rpc'       => [self::T_DRIVER],
        'data'      => [self::T_DRIVER],
        'cache'     => [self::T_DRIVER],
        'crypt'     => [self::T_DRIVER],
        'queue'     => [self::T_DRIVER],
        'email'     => [self::T_DRIVER],
        'geoip'     => [self::T_DRIVER],
        'search'    => [self::T_DRIVER],
        'logger'    => [self::T_DRIVER],
        'captcha'   => [self::T_DRIVER],
        'storage'   => [self::T_DRIVER],
        'model'     => [self::T_MODEL, ['app\model', 1]],
        'logic'     => [self::T_MODEL, ['app\logic', 1]],
        'service'   => [self::T_MODEL, ['app\service', 1]],
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
		$config = Config::read('container');
        if (isset($config['providers'])) {
			self::$providers = $config['providers'] + self::$providers;
        }
    }
    
    /*
     * 获取实例
     */
    public static function get($name)
    {
        return self::$instances[$name] ?? null;
    }
    
    /*
     * 检查实例
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
     * 删除实例
     */
    public static function delete($name)
    {
        if (isset(self::$instances[$name])) {
            unset(self::$instances[$name]);
        }
    }
	
    /*
     * 清除实例
     */
    public static function clean()
    {
        self::$instances = null;
    }
    
    /*
     * 添加规则
     */
    public static function bind($name, $type, $value = null)
    {
        self::$providers[$name] = [$type, $value];
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
		if (isset(self::$providers[$params[0]])) {
			return self::$instances[$name] = self::makeProvider($params);
		}
    }
    
    /*
     * 获取驱动实例
     */
    public static function driver($type, $name = null)
    {
        if (isset(self::$providers[$type])) {
            if (is_array($name)) {
                return self::makeDriverInstance($type, $name);
            }
            $key = $name ? "$type.$name" : $type;
            return self::$instances[$key] ?? self::$instances[$key] = self::makeDriverProvider($type, $name);
        }
    }
	
    /*
     * 获取实例规则
     */
    public static function getProvider($name)
    {
		return self::$providers[$name] ?? null;
    }
	
    /*
     * 生成自定义Provider实例
     */
    public static function makeCustomProvider($provider)
    {
        if (is_string($provider)) {
            return self::make($provider);
        } elseif (is_array($provider)) {
            return instance(...$provider);
        } elseif ($provider instanceof \Closure) {
            return $provider();
        }
		throw new \Exception("Invalid provider type");
    }
	
    /*
     * 生成Provider实例
     */
    protected static function makeProvider($params)
    {
		$c = count($params);
		$v = self::$providers[$params[0]];
		switch ($v[0]) {
			case self::T_DRIVER:
				if ($c == 1 || $c == 2) {
					return self::makeDriverProvider(...$params);
				}
				break;
			case self::T_MODEL:
				if ($c - 1 == $v[1][1] ?? 1) {
					return instance($v[1][0].'\\'.implode('\\', array_slice($params, 1)));
				}
				break;
			case self::T_CLASS:
				if ($c == 1) {
					return instance(...$v[1]);
				}
				break;
			case self::T_CLOSURE:
				if ($c == 1) {
					return $v[1]();
				}
				break;
			case self::T_ALIAS:
				if ($c == 1) {
					$a = explode('.', $v[1]);
					if (isset(self::$providers[$a[0]])) {
						if (self::$providers[$a[0]][0] != self::T_ALIAS) {
							return self::makeProvider($a);
						}
						throw new \Exception("Alias Provider的源不允许为Alias");
					}
				}
				break;
			default:
			    throw new \Exception("无效的Provider类型: $v[0]");
		}
		throw new \Exception("出成Provider实例失败");
    }

    /*
     * 生成驱动实例
     */
    protected static function makeDriverProvider($type, $index = null)
    {
        if ($index) {
            return self::makeDriverInstance($type, Config::get("$type.$index"));
        }
        list($index, $config) = Config::headKv($type);
        $key = "$type.$index";
        return self::$instances[$key] ?? self::$instances[$key] = self::makeDriverInstance($type, $config);
    }
	
    /*
     * 生成驱动实例
     */
    protected static function makeDriverInstance($type, $config)
    {
		if (isset($config['class'])) {
			$class = $config['class'];
		} else {
			$class = (self::$providers[$type][1] ?? "framework\driver\\$type").'\\'.ucfirst($config['driver']);
		}
		return new $class($config);
    }
}
Container::__init();
