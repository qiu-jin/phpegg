<?php
namespace framework\core\http;

class Cookie
{
    private static $init;
    private static $options = [
        
    ];
    
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('cookie');
        if ($config) {
            
        }
    }
    
    public static function get($name = null, $default = null)
    {
        if ($name === null) {
            return $_COOKIE;
        }
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
    }
    
    public static function set($name, $value)
    {
        $_COOKIE[$name] = $value;
        self::setHeader($name, $value, $expire, $path, $domain, $secure, $httponly)
    }
    
    public static function delete($name)
    {
        unset($_COOKIE[$name]);
        self::setHeader($name, $value, $expire, $path, $domain, $secure, $httponly)
    }
    
    public static function clear()
    {
        foreach ($_COOKIE as $key => $val) {
            self::setHeader($name, $value, $expire, $path, $domain, $secure, $httponly)
        }
        $_COOKIE = [];
    }
    
    private static function setHeader($name, $value, $expire, $path, $domain, $secure, $httponly)
    {
        $str = urlencode($name).'=';
        if ($value === null) {
            $str .= 'null; expires='.gmdate('D, d-M-Y H:i:s T', time()-31536001).'; max-age=-31536001';
        } else {
            $str .= urlencode($value).'; expires='.gmdate('D, d-M-Y H:i:s T', $expire).'; max-age=';
        }
        if ($path) {
            $str .= '; path='.$path;
        }
        if ($domain) {
            $str .= '; domain='.$domain;
        }
        if ($secure) {
            $str .= '; secure';
        }
        if ($httponly) {
            $str .= '; httponly';
        }
        Response::header('Set-Cookie', $str);
    }
}
Cookie::init();
