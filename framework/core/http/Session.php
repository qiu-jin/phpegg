<?php
namespace framework\core\http;

use framework\core\Config;

class Session
{
    private static $init;
    
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $config = Config::get('session');
        if ($config) {
            foreach ($config as $key => $value) {
                if (in_array($key, ['id', 'name', 'save_path', 'cache_expire', 'cache_limiter'], true)) {
                    call_user_func("session_$key", $value);
                }
            }
            if (isset($config['cookie_params'])) {
                session_set_cookie_params(...$config['cookie_params']);
            }
            if (isset($config['save_handler'])) {
                session_set_save_handler(new $config['save_handler'][0]($config['save_handler'][1]));
            }
        }
        session_start();
    }
    
    public static function id()
    {
        return session_id();
    }
    
    public static function regen()
    {
        return session_regenerate_id();
    }
    
    public static function get($name = null, $default = null)
    {
        return $name === null ? $_SESSION : $_SESSION[$name] ?? $default;
    }
    
    public static function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }
    
    public static function delete($name)
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }
    
    public static function clear()
    {
        $_SESSION = [];
    }
    
    public static function destroy()
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
    }
}
Session::init();
