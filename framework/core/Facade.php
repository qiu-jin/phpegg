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
     * 生成实例
     */
    protected static function make()
    {
		return self::$instances[static::class] ??
			   self::$instances[static::class] = Container::makeCustomProvider(static::$provider);
    }
}
