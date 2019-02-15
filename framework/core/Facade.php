<?php
namespace framework\core;

abstract class Facade
{
	// 实例
	private static $instances;
	// 提供者
	protected static $provider;
	
    /*
     * 魔术方法，执行Provider实例方法
     */
    public static function __callStatic($method, $params)
    {
		return (self::$instances[static::class] ?? 
				self::$instances[static::class] = Container::makeProviderInstance(static::$provider))->$method(...$params);
    }
}
