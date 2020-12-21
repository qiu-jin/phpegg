<?php
namespace framework\core;

use framework\util\Arr;

class Container
{
	// Provider类型常量
	const T_CLASS 	= 1;
	const T_DRIVER	= 1 << 1;
	const T_MODEL 	= 1 << 2;
	const T_CLOSURE	= 1 << 3;
	const T_ALIAS 	= 1 << 4;

    protected static $init;
    // 容器实例
    protected static $instances;
    // 容器提供者设置
    protected static $providers = [
        'db'        => [self::T_DRIVER/*，驱动类名称空间（可选） */],
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
        'model'     => [self::T_MODEL, 1/* 模型层数, 模型类名称空间（可选） */],
        'logic'     => [self::T_MODEL, 1],
        'service'   => [self::T_MODEL, 1],
		/*
		'class_provider' 	=> [self::T_CLASS, [类全名, ...类初始化参数（可选）]],
		'closure_provider' 	=> [self::T_CLOSURE, 匿名函数（函数执行返回实例）],
		'alias_provider' 	=> [self::T_ALIAS, 真实provider名],
		*/
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
	        if (isset($config['providers'])) {
				self::$providers = $config['providers'] + self::$providers;
	        }
			if (!empty($config['exit_event_clean'])) {
				Event::on('exit', function () {
					Container::clean();
					if (class_exists(Facade::class, false)) {
						Facade::clean();
					}
				});
			}
		}
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
		throw new \Exception("容器提供者不存在: $name");
    }
	
    /*
     * 添加规则
     */
    public static function bind($name, ...$params)
    {
        self::$providers[$name] = $params;
    }
	
    /*
     * 获取规则
     */
    public static function getProvider($name)
    {
		return self::$providers[$name] ?? null;
    }
	
    /*
     * 设置实例
     */
    public static function setInstance($name, object $instance)
    {
        self::$instances[$name] = $instance;
    }
	
    /*
     * 清除实例
     */
    public static function clean()
    {
		self::$instances = null;
    }
    
    /*
     * 获取驱动实例
     */
    public static function driver($type, $name = null)
    {
        if (isset(self::$providers[$type])) {
			if (self::$providers[$type][0] !== self::T_DRIVER) {
				throw new \Exception("容器非驱动类型: $type");
			}
            if (is_array($name)) {
                return self::makeDriverInstance($type, $name);
            }
            $key = $name ? "$type.$name" : $type;
            return self::$instances[$key] ?? self::$instances[$key] = self::makeDriver($type, $name);
        }
		throw new \Exception("容器驱动不存: $type");
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
		throw new \Exception("无效的自定义Provider类型");
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
				if ($c <= 2) {
					return self::makeDriver(...$params);
				}
				break;
			case self::T_MODEL:
				if ($c == ($v[1] ?? 1) + 1) {
					$params[0] = $v[2] ?? "app\\$params[0]";
					return instance(implode('\\', $params));
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
					return self::makeAlias($v[1]);
				}
				break;
			default:
			    throw new \Exception("无效的Provider类型: $v[0]");
		}
		throw new \Exception('生成Provider实例失败: '.implode('.', $params));
    }
	
    /*
     * 生成别名实例
     */
    protected static function makeAlias($name)
    {
		if (isset(self::$instances[$name])) {
			return self::$instances[$name];
		}
		$p = explode('.', $name);
		if (isset(self::$providers[$p[0]])) {
			if (self::$providers[$p[0]][0] == self::T_ALIAS) {
				throw new \Exception("别名实例源不能仍为alias类型: $name");
			}
			return self::$instances[$name] = self::makeProvider($p);
		}
		throw new \Exception("别名实例源不存在: $name");
    }
	
    /*
     * 生成驱动实例
     */
    protected static function makeDriver($type, $index = null)
    {
        if ($index) {
            return self::makeDriverInstance($type, Config::get("$type.$index"));
        }
        list($index, $config) = Arr::headKv(Config::get($type));
        $key = "$type.$index";
		return self::$instances[$key] ?? self::$instances[$key] = self::makeDriverInstance($type, $config);
    }
	
    /*
     * 生成驱动实例
     */
    protected static function makeDriverInstance($type, $config)
    {
		$class = $config['class'] ?? (self::$providers[$type][1] ?? "framework\driver\\$type").'\\'.ucfirst($config['driver']);
		return new $class($config);
    }
}
Container::__init();
