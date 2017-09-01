<?php
namespace framework\core\http;

use framework\core\Config;

class Cookie
{
    private static $init;
    private static $crypt;
    private static $serialize;
    private static $unserialize;
    private static $cookie = [];
    private static $option = [
        'expire'    => 0,
        'path'      => '/',
        'domain'    => '',
        'secure'    => false,
        'httponly'  => false
    ];
    private static $crypt_except = ['PHPSESSID'];
    
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('cookie');
        if ($config) {
            if (isset($config['option'])) {
                self::$option = array_merge(self::$option, $config['option']);
            }
            if (isset($config['crypt'])) {
                self::$crypt = driver('crypt', $config['crypt']);
                if (isset($config['crypt_except'])) {
                    self::$crypt_except = $config['crypt_except'];
                }
            }
            if (isset($config['serializer'])) {
                list(self::$serialize, self::$unserialize) = $config['serializer'];
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
    
    public static function set($name, $value, ...$option)
    {
        self::$cookie[$name] = $value;
        self::setCookie($name, $value, ...$option);
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
    
    protected static function setCookie($name, $value, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        foreach (self::$option as $k => $v) {
            if (!isset($$k)) $$k = $v;
        }
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
