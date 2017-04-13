<?php 
namespace framework;

use framework\core\Hook;
use framework\core\Config;

class App
{
    private static $app;
    private static $init;
    private static $exit;
    private static $runing;
    
    public static function init($name)
    {
        if (self::$init) return;
        self::$init = true;
        define('APP_NAME', $name);
        define('FW_DIR', __DIR__.'/');
        defined('APP_DEBUG')  || define('APP_DEBUG', false);
        defined('ROOT_DIR')   || define('ROOT_DIR', dirname(__DIR__).'/');
        defined('APP_DIR')    || define('APP_DIR', ROOT_DIR.'app/'.APP_NAME.'/');
        defined('VENDOR_DIR') || define('VENDOR_DIR', ROOT_DIR.'vendor/');
        defined('RUNTIME_DIR')|| define('RUNTIME_DIR', APP_DIR.'runtime/');        
        require(FW_DIR.'common.php');
        require(FW_DIR.'core/config.php');
        require(FW_DIR.'core/loader.php');
        require(FW_DIR.'core/error.php');
        require(FW_DIR.'core/hook.php');   
        register_shutdown_function(function () {
            Hook::listen('exit');
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            Hook::listen('close');
        });
        Hook::listen('init');
    }
    
    public static function start($name, $app = 'standard',array $config = null)
    {
        if (!self::$app) {
            self::init($name);
            if (in_array($app, ['cli', 'inline', 'jsonrpc', 'mix', 'rest', 'standard'])) {
                $class = 'framework\core\app\\'.ucfirst($app);
                self::$app = $config ? new $class($config) : new $class(Config::get('app'));
            } elseif (class_exists($app)) {
                self::$app = $config ? new $app($config) : new $app(Config::get('app'));
            } else {
                throw new \Exception('App start error');
            }
            define('APP_MODE', $app);
            Hook::listen('start');
            return self::$app;
        }
    }

    public static function load($type = null, $name = null)
    {
        if ($type) {
            if ($name) {
                if (is_array($name)) {
                    $config = $name;
                } else {
                    $config = Config::get($type.'.'.$name);
                }
            } else {
                $config = Config::first_value($type);
            }
            if (isset($config['driver'])) {
                return driver($type, $config['driver'], $config);
            }
            return null;
        } else {
            return self::$app;
        }
    }
    
    public static function exit($status = 1)
    {
        if ($status === 0) {
            return (bool) self::$exit;
        } elseif ($status === 1) {
            if (!self::$exit) {
                self::$exit = 1;
                exit;
            }
        } else {
            self::$exit = $status;
        }
    }
    
    public static function runing()
    {
        if (self::$runing) {
            return true;
        }
        self::$runing = true;
        return false;
    }
}
