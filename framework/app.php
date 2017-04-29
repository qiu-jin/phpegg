<?php 
namespace framework;

use framework\core\Auth;
use framework\core\Hook;
use framework\core\Config;
use framework\core\http\Response;

abstract class App
{
    private static $app;
    private static $init;
    private static $exit;
    private static $runing;
    private static $error_handler;
    
    protected $config = [];
    protected $dispatch = [];
    
    abstract public function run(callable $return_handler);
    abstract public function dispatch();
    
    private function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        define('FW_DIR', __DIR__.'/');
        defined('ROOT_DIR')   || define('ROOT_DIR', dirname(__DIR__).'/');
        defined('APP_DIR')    || define('APP_DIR', dirname($_SERVER['DOCUMENT_ROOT']).'/');
        defined('APP_DEBUG')  || define('APP_DEBUG', false);
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
    
    public static function start($app = 'standard', array $config = null)
    {
        if (!self::$app) {
            self::init($name);
            if (static::class !== __CLASS__) {
                throw new \Exception('Illegal start call');
            }
            if (strpos($app, '\\') === false) {
                $app = 'framework\core\app\\'.ucfirst($app);
            }
            if (is_subclass_of($app, __CLASS__)) {
                if ($config === null) {
                    $config = Config::get('app');
                }
                self::$app = new $app($config);
            } else {
                throw new \Exception('Illegal app class :'.$app);
            }
            $dispatch = self::$app->dispatch();
            if ($dispatch) {
                self::$app->dispatch = $dispatch;
                if (isset(self::$app->config['auth_enable'])) {
                    Auth::passport();
                }
                Hook::listen('start');
                return self::$app;
            }
            self::abort(404);
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
    
    public static function abort($code = null, $message = null)
    {
        if (isset(self::$error_handler)) {
            self::$error_handler($code, $message);
        } elseif (is_callable([self::$app, 'error'])) {
            self::$app->error($code, $message);
        } else {
            Response::json(['error' => ['code' => $code, 'message' => $message]]);
        }
    }
    
    public static function setErrorHandler($handler)
    {
        self::$error_handler = $handler;
    }
    
    protected function runing()
    {
        if (self::$runing) {
            throw new \Exception('App is runing');
        }
        self::$runing = true;
    }
}
