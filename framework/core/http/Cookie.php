<?php
namespace framework\core\http;

use framework\core\Config;

class Cookie
{
    private static $init;
    private static $crypt;
    private static $crypt_except;
    private static $serialize;
    private static $unserialize;
    private static $cookie = [];
    
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('cookie');
        if ($config) {
            if (isset($config['crypt'])) {
                self::$crypt = load('crypt', $config['crypt']);
                if (isset($config['crypt_except'])) {
                    self::$crypt_except = $config['crypt_except'];
                }
            }
            if (isset($config['serialize'])) {
                list(self::$serialize, self::$unserialize) = $config['serialize'];
            }
        }
    }
    
    public static function get($name = null, $default = null)
    {
        if ($name === null) {
            return self::getAll();
        }
        if (isset(self::$cookie[$name])) {
            return self::$cookie[$name];
        }
        if (isset($_COOKIE[$name])) {
            return self::$cookie[$name] = self::getValue($name);
        }
        return $default;
    }
    
    public static function set($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    
    public static function temp($name, $value)
    {
        self::$cookie[$name] = $value;
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
        self::$cookie = [];
    }
    
    public static function free()
    {
        self::$crypt = null;
        self::$crypt_except = null;
        self::$cookie = null;
    }
    
    protected static function getAll()
    {
        if ($_COOKIE) {
            foreach ($_COOKIE as $name => $value) {
                if (!isset(self::$cookie[$name])) {
                    self::$cookie[$name] = self::getValue($name);;
                }
            }
        }
        return self::$cookie;
    }
    
    protected static function getValue($name)
    {
        $value = $_COOKIE[$name];
        if (self::$crypt && !in_array($name, self::$crypt_except ,true)) {
            $value = self::$crypt->decrypt($value);
        }
        if (self::$unserialize) {
            $value = (self::$unserialize)($value);
        }
        return $value;
    }
    
    protected static function setCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        if ($value === null) { 
            $expire = time()-3600;
        } else {
            if (self::$serialize) {
                $value = (self::$serialize)($value);
            }
            if (self::$crypt && !in_array($name, self::$crypt_except ,true)) {
                $value = self::$crypt->encrypt($value);
            }
            if ($expire) {
                $expire = time()+$expire;
            }
        }
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}
Cookie::init();
