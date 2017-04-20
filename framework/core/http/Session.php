<?php
namespace framework\core\http;

use framework\core\Config;

class Session
{
    private static $init;
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('session');
        if ($config) {
            if (isset($config['id'])) {
                session_id($config['id']);
            }
            if (isset($config['name'])) {
                session_name($config['name']);
            }
            if (isset($config['save_path'])) {
                session_save_path($config['save_path']);
            }
            if (isset($config['cache_limiter'])) {
                session_cache_limiter($config['cache_limiter']);
            }
            if (isset($config['cache_expire'])) {
                session_cache_expire($config['cache_expire']);
            }
            if (isset($config['ini_set'])) {
                $allow_set = ['cookie_domain', 'gc_maxlifetime', 'cookie_lifetime', 'cookie_secure', 'cookie_httponly', 'use_cookies'];
                foreach ($config['ini_set'] as $setk => $setv) {
                    if (in_array($setk, $allow_set, true)) {
                        ini_set('session.'.$setk, $setv);
                    }
                }
            }
            if (isset($config['save_handler'])) {
                session_save_handler(new $config['save_handler'][0]($config['save_handler'][1]));
            }
        }
        session_start();
    }
    
    public static function id()
    {
        return session_id();
    }
    
    public static function gen()
    {
        return session_regenerate_id();
    }
    
    public static function get($name = null, $default = null)
    {
        if ($name === null) {
            return $_SEESION;
        }
        return isset($_SEESION[$key]) ? $_SEESION[$key] : $default;
    }
    
    public static function set($name, $value)
    {
        $_SEESION[$name] = $value;
    }
    
    public static function delete($name)
    {
        if (isset($_SEESION[$name])) unset($_SEESION[$name]);
    }
    
    public static function clear()
    {
        $_SEESION = null;
        session_unset();
        session_destroy();
    }
}
Session::init();
