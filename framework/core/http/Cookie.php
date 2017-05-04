<?php
namespace framework\core\http;

class Cookie
{
    private static $init;
    private static $crypt;
    private static $crypt_except;
    
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('cookie');
        if ($config) {
            if (isset($config['crypt'])) {
                self::$crypt = load('crypt', $config['crypt']);
                $names = array_keys($_COOKIE);
                if (isset($config['crypt_except']) {
                    self::$crypt_except = $config['crypt_except'];
                    $names = array_diff($names, self::$crypt_except);
                }
                if ($names) {
                    foreach ($names as $name) {
                        $_COOKIE[$name] = self::$crypt->decrypt($_COOKIE[$name]);
                    }
                }
            }
        }
    }
    
    public static function get($name = null, $default = null)
    {
        if ($name === null) {
            return $_COOKIE;
        }
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
    }
    
    public static function set($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        $_COOKIE[$name] = $value;
        self::setCookie($name, $value, $expire, $path, $domain, $secure, $httponly)
    }
    
    public static function forever($name, $value)
    {
        $_COOKIE[$name] = $value;
        self::setCookie($name, $value, $expire, $path, $domain, $secure, $httponly)
    }
    
    public static function delete($name)
    {
        unset($_COOKIE[$name]);
        self::setCookie($name, null);
    }
    
    public static function clear()
    {
        foreach ($_COOKIE as $key => $val) {
            self::setCookie($name, null);
        }
        $_COOKIE = [];
    }
    
    protected static function setCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        if ($this->option['use_set_cookie']) {
            return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
        $str = urlencode($name).'=';
        if ($value === null) {
            $str .= 'null; expires='.gmdate('D, d-M-Y H:i:s T', time()-31536001).'; max-age=-31536001';
        } else {
            if (self::$crypt && !in_array($name, self::$crypt_except ,true)) {
                $value = self::$crypt->encrypt($value);
            }
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
