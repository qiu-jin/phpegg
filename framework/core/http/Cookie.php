<?php
namespace framework\core\http;

use framework\core\Event;
use framework\core\Config;

class Cookie
{
    private static $init;
    // cookie设置项
    private static $options = [
		// 有效期
        'lifetime'  => 0,
		// 有效路径
        'path'      => '',
		// 有效域名
        'domain'    => '',
		// 启用安全传输
        'secure'    => false,
		// httponly设置
        'httponly'  => false
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
        if ($config = Config::read('cookie')) {
			self::$options = $config + self::$options;
        }
		Event::trigger('cookie');
    }
    
    /*
     * 获取所有
     */
    public static function all()
    {
		return $_COOKIE;
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
		Response::cookie($name, $value, ...$options);
    }
    
    /*
     * 永久设置
     */
    public static function forever($name, $value, ...$options)
    {
		self::set($name, $value, 315360000, ...$options);
    }
    
    /*
     * 删除
     */
    public static function delete($name, ...$options)
    {
		if (isset(self::$_COOKIE[$name])) {
	        unset(self::$_COOKIE[$name]);
			Response::cookie($name, null, null, ...$options);
		}
    }
    
    /*
     * 高级设置
     */
    public static function setCookie(
        $name, $value, $lifetime = null, $path = null, $domain = null, $secure = null, $httponly = null
    ) {
        if ($value === null) { 
            $expire = time() - 3600;
        } else {
			$lft = $lifetime ?? self::$options['lifetime'];
            $expire = $lft ? time() + $lft : 0;
        }
        return setcookie(
			$name, $value, $expire, 
			$path ?? self::$options['path'], 
			$domain ?? self::$options['domain'], 
			$secure ?? self::$options['secure'], 
			$httponly ?? self::$options['httponly']
		);
    }
}
Cookie::__init();
