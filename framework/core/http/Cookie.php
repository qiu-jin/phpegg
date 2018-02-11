<?php
namespace framework\core\http;

use framework\core\Config;
use framework\core\Container;

class Cookie
{
    private static $init;
    private static $cookie;
    private static $options = [
        'lifetime'  => 0,
        'path'      => '/',
        'domain'    => '',
        'secure'    => false,
        'httponly'  => false
    ];
    private static $serializer;
    private static $crypt_config;
    private static $crypt_except = ['PHPSESSID'];
    private static $crypt_handler;
    
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::flash('cookie')) {
            if (isset($config['options'])) {
                self::$options = $config['options'] + self::$options;
            }
            if (isset($config['crypt'])) {
                self::$crypt_config = $config['crypt'];
                if (isset($config['crypt_except'])) {
                    self::$crypt_except = $config['crypt_except'];
                }
            }
            if (isset($config['serializer'])) {
                self::$serializer = $config['serializer'];
            }
        }
    }
    
    public static function get($name, $default = null)
    {
        if (isset(self::$cookie[$name])) {
            return self::$cookie[$name];
        }
        if (isset($_COOKIE[$name])) {
            return self::$cookie[$name] = self::getValue($name);
        }
        return $default;
    }
    
    public static function set($name, $value, ...$options)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, ...$options);
    }
    
    public static function forever($name, $value)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, 31536001);
    }
    
    public static function delete($name)
    {
        unset($_COOKIE[$name]);
        unset(self::$cookie[$name]);
        self::setCookie($name, null);
    }
    
    public static function clear()
    {
        foreach ($_COOKIE as $key => $val) {
            self::setCookie($name, null);
        }
        $_COOKIE = [];
        self::$cookie = null;
    }
    
    public static function getAll()
    {
        if ($_COOKIE) {
            foreach ($_COOKIE as $name => $value) {
                if (!isset(self::$cookie[$name])) {
                    self::$cookie[$name] = self::getValue($name);
                }
            }
        }
        return self::$cookie;
    }
    
    protected static function setCookie(
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
            if (isset(self::$serializer)) {
                $value = (self::$serializer[0])($value);
            }
            if (isset(self::$crypt) && !in_array($name, self::$crypt_except, true)) {
                $value = self::getCryptHandler()->encrypt($value);
            }
            $expire = time() + $lifetime;
        }
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    
    protected static function getValue($name)
    {
        $value = $_COOKIE[$name];
        if (isset(self::$crypt) && !in_array($name, self::$crypt_except, true)) {
            $value = self::getCryptHandler()->decrypt($value);
        }
        if (isset(self::$serializer)) {
            $value = (self::$serializer[1])($value);
        }
        return $value;
    }
    
    protected static function getCryptHandler()
    {
        return self::$crypt_handler ?? self::$crypt_handler = Container::driver('crypt', self::$crypt_config);
    }
}
Cookie::init();
