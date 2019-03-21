<?php
namespace framework\core;

abstract class Facade
{
	private static $init;
	// 实例
	private static $instances;
	// 提供者
	protected static $provider;
	
    /*
     * 初始化
     */
    public static function __init()
    {
		if (static::class == __CLASS__) {
	        if (self::$init) {
	            return;
	        }
	        self::$init = true;
			if (Config::get('container.exit_clean')) {
				Event::on('exit', function () {
					self::$instances = null;
				});
			}
		}
		return static::__callStatic('__init', $params);
    }
	
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
			   self::$instances[static::class] = Container::makeCustom(static::$provider);
    }
}
