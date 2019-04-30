<?php
namespace framework\core;

abstract class Facade
{
	// 实例
	private static $instances;
	// 提供者
	protected static $provider;
	
    /*
     * 魔术方法，执行实例方法
     */
    public static function __callStatic($method, $params)
    {
		return static::make()->$method(...$params);
    }
	
    /*
     * 清除实例
     */
    public static function clean($class = null)
    {
		if (static::class != __CLASS__) {
			return static::__callStatic('clean', ...func_get_args());
		}
		if ($class === null) {
			self::$instances = null;
		} elseif (isset(self::$instances[$class])) {
			unset(self::$instances[$class]);
		}
    }
	
    /*
     * 生成实例
     */
    protected static function make()
    {
		return self::$instances[static::class] ??
			   self::$instances[static::class] = Container::makeCustomProvider(static::$provider);
    }
}
