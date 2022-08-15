<?php
namespace framework\core;

use framework\util\Arr;

class Container
{
	// Provider类型常量
	const T_DRIVER	= 1;
	const T_MODEL 	= 1 << 1;
	const T_SERVICE = 1 << 2;
	const T_CLASS 	= 1 << 3;
	const T_CLOSURE	= 1 << 4;
	const T_ALIAS 	= 1 << 5;

    protected static $init;
    // 容器实例
    protected static $instances;
    // 容器提供者设置
    protected static $providers = [
        'db'        => [self::T_DRIVER, true/*是否应用于Getter, 驱动类型, 默认配置项*/],
		'rpc'       => [self::T_DRIVER, true],
		'cache'     => [self::T_DRIVER, true],
		'email'     => [self::T_DRIVER],
		'logger'    => [self::T_DRIVER],
		/*
        'storage'   => [self::T_DRIVER],
		'sms'       => [self::T_DRIVER],
		'data'      => [self::T_DRIVER],
        'crypt'     => [self::T_DRIVER],
        'queue'     => [self::T_DRIVER],
        'geoip'     => [self::T_DRIVER],
        'search'    => [self::T_DRIVER],
		'captcha'   => [self::T_DRIVER],
		*/
		/*
        'model'     => [self::T_MODEL],
		*/
        'service'   => [self::T_SERVICE, true/*, ...层数（默认为1）, ...基础名称空间 */],
		/*
		'class' 	=> [self::T_CLASS, false, [类全名, ...类初始化参数（可选）]],
		'closure' 	=> [self::T_CLOSURE, false, 匿名函数（函数执行返回实例）],
		'alias' 	=> [self::T_ALIAS, false, 真实provider名],
		*/
    ];
	// getter providers属性名
	protected static $getter_providers_name;
	// 公共 getter providers
	protected static $getter_common_providers = [];
	// 允许 getter property 访问 driver
	protected static $allow_driver_getter_property_access = [];
	
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
		if ($config = Config::get('container')) {
	        if (isset($config['providers'])) {
				self::$providers = $config['providers'] + self::$providers;
	        }
	        if (isset($config['getter_providers_name'])) {
				self::$getter_providers_name = $config['getter_providers_name'];
	        }
	        if (isset($config['getter_common_providers'])) {
				self::$getter_common_providers = $config['getter_common_providers'];
	        }
	        if (isset($config['allow_driver_getter_property_access'])) {
				self::$allow_driver_getter_property_access = $config['allow_driver_getter_property_access'];
	        }
			if (!empty($config['exit_event_clean'])) {
				Event::on('exit', function() {
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
     * 获取或设置规则
     */
    public static function provider($name, array $value = null)
    {
		if (isset($value)) {
			self::$providers[$name] = $value;
		} else {
			return self::$providers[$name] ?? null;
		}
    }
	
    /*
     * 获取或设置实例
     */
    public static function instance($name, object $value = null)
    {
		if (isset($value)) {
			self::$instances[$name] = $value;
		} else {
			return self::$instances[$name] ?? null;
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
     * 获取驱动实例
     */
    public static function driver($type, $name = null)
    {
        if (isset(self::$providers[$type])) {
			$pv = self::$providers[$type];
			if (self::$providers[$type][0] !== self::T_DRIVER) {
				throw new \Exception("容器非驱动类型: $type");
			}
            if (is_array($name)) {
                return self::makeDriverInstance($pv[2] ?? $type, $name);
            }
            $key = $name ? "$type.$name" : $type;
            return self::$instances[$key] ?? self::$instances[$key] = self::makeDriver($pv[1] ?? $type, $name ?? $pv[2] ?? null);
        }
		throw new \Exception("容器驱动不存在: $type");
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
     * 获取getter providers属性名
     */
    public static function getGetterDriversName()
    {
		return self::$getter_providers_name;
    }
	
    /*
     * 生成公共Provider实例
     */
    public static function makeGetterCommonProvider($name)
    {
		if (isset(self::$getter_common_providers[$name])) {
			return self::makeCustomProvider(self::$getter_common_providers[$name]);
		}
    }
	
    /*
     * 
     */
    public static function checkAllowDriverGetterPropertyAccess($name)
    {
		return in_array($name, self::$allow_driver_getter_property_access);
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
					return self::makeDriver($v[2] ?? $params[0], $v[3] ?? null);
				}
				break;
			/*case self::T_MODEL:
				break;*/
			case self::T_SERVICE:
				if ($c > 1) {
					$params[0] = $v[3] ?? "app\\$params[0]";
					return instance(implode('\\', $params));
				}
				break;
			case self::T_CLASS:
				if ($c == 1) {
					return instance(...$v[2]);
				}
				break;
			case self::T_CLOSURE:
				if ($c == 1) {
					return $v[2]();
				}
				break;
			case self::T_ALIAS:
				if ($c == 1) {
					return self::makeAlias($v[2]);
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
		$config = Config::get($type);
		$index = Arr::headKey($config);
        $key = "$type.$index";
		return self::$instances[$key] ?? self::$instances[$key] = self::makeDriverInstance($type, $config[$index]);
    }
	
    /*
     * 生成驱动实例
     */
    protected static function makeDriverInstance($type, $config)
    {
		if (isset($config['class'])) {
			$class = $config['class'];
		} elseif (isset($config['driver'])) {
			$class = "framework\driver\\$type\\".ucfirst($config['driver']);
		} else {
			throw new \Exception($type.'驱动没有设置实例');
		}
		return new $class($config);
    }
}
Container::__init();
