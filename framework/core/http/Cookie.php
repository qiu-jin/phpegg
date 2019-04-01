<?php
namespace framework\core\http;

use framework\core\Event;
use framework\core\Config;
use framework\core\Container;

class Cookie
{
    private static $init;
    // cookie设置项
    private static $options = [
        'lifetime'  => 0,
        'path'      => '',
        'domain'    => '',
        'secure'    => false,
        'httponly'  => false
    ];
	// 设置值
    private static $set_cookie;
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::read('cookie')) {
            if (isset($config['options'])) {
                self::$options = $config['options'] + self::$options;
            }
        }
		class_exists(Response::class);
		Event::on('response', [__CLASS__, 'flush']);
    }
    
    /*
     * 获取所有
     */
    public static function all()
    {
		return self::$_COOKIE;
    }
    
    /*
     * 获取
     */
    public static function get($name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }
    
    /*
     * 是否存在
     */
    public static function has($name)
    {
        return isset($_COOKIE[$name]);
    }
    
    /*
     * 设置
     */
    public static function set($name, $value, ...$options)
    {
        $_COOKIE[$name] = $value;
		self::$set_cookie[] = func_get_args();
    }
    
    /*
     * 设置永久
     */
    public static function forever($name, $value, ...$options)
    {
        $_COOKIE[$name] = $value;
		self::$set_cookie[] = array_merge([$name, $value, 315360000], $options);
    }
    
    /*
     * 删除
     */
    public static function delete($name, ...$options)
    {
        unset(self::$_COOKIE[$name]);
		self::$set_cookie[] = array_merge([$name, null, null], $options);
    }
    
    /*
     * 底层设置
     */
    public static function setCookie(
        $name, $value, $lifetime = null, $path = null, $domain = null, $secure = null, $httponly = null
    ) {
        if ($value === null) { 
            $expire = time() - 3600;
        } else {
			$lifetime = $lifetime ?? self::$options['lifetime'];
            $expire = $lifetime ? time() + $lifetime : 0;
        }
        return setcookie(
			$name, $value, $expire, 
			$path ?? self::$options['path'], 
			$domain ?? self::$options['domain'], 
			$secure ?? self::$options['secure'], 
			$httponly ?? self::$options['httponly']
		);
    }
	
    /*
     * 刷新输出
     */
    public static function flush()
    {
		if (self::$set_cookie) {
			foreach (self::$set_cookie as $v) {
				self::setCookie(...$v);
			}
			self::$set_cookie = null;
		}
    }
}
Cookie::__init();
