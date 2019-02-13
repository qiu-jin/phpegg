<?php
namespace framework\core\http;

use framework\core\Config;
use framework\core\Container;

class Cookie
{
    private static $init;
	// 值
    private static $cookie;
	// 原始值
    private static $raw_cookie;
    // cookie设置项
    private static $options = [
        'lifetime'  => 0,
        'path'      => '',
        'domain'    => '',
        'secure'    => false,
        'httponly'  => false
    ];
    // cookie序列化处理器
    private static $serializer;
    // 排除部分系统自处理的cookie，如PHPSESSID
    private static $serialize_except;
    
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
            if (empty($config['serializer'])) {
                self::$cookie = $_COOKIE;
            } else {
                self::$raw_cookie = $_COOKIE;
                self::$serializer = $config['serializer'];
				self::$serialize_except = $config['serialize_except'] ?? [ini_get('session.name')];
            }
        } else {
            self::$cookie = $_COOKIE;
        }
    }
    
    /*
     * 获取所有
     */
    public static function all()
    {
		if (isset(self::$raw_cookie)) {
	        foreach (self::$raw_cookie as $k => $v) {
	            if (!isset(self::$cookie[$k])) {
	                self::$cookie[$k] = self::unserialize($k, $v);
	            }
	        }
		}
		return self::$cookie;
    }
    
    /*
     * 获取
     */
    public static function get($name, $default = null)
    {
        if (isset(self::$cookie[$name])) {
            return self::$cookie[$name];
        } elseif (isset($raw_cookie[$name])) {
            return self::$cookie[$name] = self::unserialize($name, $raw_cookie[$name]);
        }
        return $default;
    }
    
    /*
     * 是否存在
     */
    public static function has($name)
    {
        return isset(self::$cookie[$name]) || isset($raw_cookie[$name]);
    }
    
    /*
     * 设置
     */
    public static function set($name, $value, ...$options)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, ...$options);
    }
    
    /*
     * 设置永久
     */
    public static function forever($name, $value, ...$options)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, 315360000, ...$options);
    }
    
    /*
     * 删除
     */
    public static function delete($name, ...$options)
    {
        unset(self::$cookie[$name]);
        unset(self::$raw_cookie[$name]);
        self::setCookie($name, null, null, , ...$options);
    }
    
    /*
     * 底层设置
     */
    public static function setCookie(
        $name, $value, $lifetime = null, $path = null, $domain = null, $secure = null, $httponly = null
    ) {
        foreach (self::$options as $k => $v) {
            if (!isset($$k)) {
                $$k = $v;
            }
        }
        if ($value === null) { 
            $expire = time() - 3600;
        } else {
            if (self::$serializer && !in_array($name, self::$serialize_except)) {
                $value = (self::$serializer[0])($value);
            }
            $expire = $lifetime === 0 ? 0 : time() + $lifetime;
        }
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    
    /*
     * 反序列化
     */
    protected static function unserialize($name, $value)
    {
        return in_array($name, self::$serialize_except) ? $value : (self::$serializer[1])($value);
    }
}
Cookie::__init();
