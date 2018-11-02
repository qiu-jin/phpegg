<?php
namespace framework\core\http;

use framework\core\Config;
use framework\core\Container;

class Cookie
{
    private static $init;
    private static $cookie;
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
    private static $except_cookie_names;
    
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::flash('cookie')) {
            if (isset($config['options'])) {
                self::$options = $config['options'] + self::$options;
            }
            if (empty($config['serializer'])) {
                self::$cookie = $_COOKIE;
            } else {
                self::$raw_cookie = $_COOKIE;
                self::$serializer = $config['serializer'];
            }
        }
        self::$except_cookie_names = $config['except_cookie_names'] ?? [ini_get('session.name')];
    }
    
    public static function all()
    {
        if (self::$raw_cookie) {
            foreach (self::$raw_cookie as $k => $v) {
                if (!isset(self::$cookie[$k])) {
                    self::$cookie[$k] = self::unserializeValue($k, $v);
                }
            }
        }
        return self::$cookie;
    }
    
    public static function get($name, $default = null)
    {
        if (isset(self::$cookie[$name])) {
            return self::$cookie[$name];
        } elseif (isset($raw_cookie[$name])) {
            return self::$cookie[$name] = self::unserializeValue($name, $raw_cookie[$name]);
        }
        return $default;
    }
    
    public static function has($name)
    {
        return isset(self::$cookie[$name]) || isset($raw_cookie[$name]);
    }
    
    public static function set($name, $value, ...$options)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, ...$options);
    }
    
    public static function forever($name, $value)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, 315360000);
    }
    
    public static function delete($name)
    {
        unset(self::$cookie[$name]);
        unset(self::$raw_cookie[$name]);
        self::setCookie($name, null);
    }
    
    public static function clean($except = true)
    {
        if (self::$raw_cookie) {
            foreach (array_keys(self::$raw_cookie) as $name) {
                if (!in_array($name, self::$except_cookie_names, true)) {
                    self::setCookie($name, null);
                }
            }
        }
        self::$cookie = self::$raw_cookie = null;
    }
    
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
            if (self::$serializer && !in_array($name, self::$except_cookie_names)) {
                $value = (self::$serializer[0])($value);
            }
            $expire = $lifetime === 0 ? 0 : time() + $lifetime;
        }
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    
    protected static function unserializeValue($name, $value)
    {
        return in_array($name, self::$except_cookie_names) ? $value : (self::$serializer[1])($value);
    }
}
Cookie::__init();
