<?php 
namespace framework;

use framework\core\Auth;
use framework\core\Hook;
use framework\core\Config;
use framework\core\http\Response;

abstract class App
{
    private static $app;
    private static $boot;
    private static $exit;
    private static $runing;
    private static $error_handler;
    
    protected $config = [];
    protected $dispatch = [];
    
    abstract public function run(callable $return_handler);
    
    abstract protected function dispatch();
    
    private function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public static function boot()
    {
        if (self::$boot) return;
        self::$boot = true;
        define('FW_DIR', __DIR__.'/');
        defined('ROOT_DIR') || define('ROOT_DIR', dirname(__DIR__).'/');
        defined('APP_DIR')  || define('APP_DIR', dirname($_SERVER['DOCUMENT_ROOT']).'/');
        require(FW_DIR.'common.php');
        require(FW_DIR.'core/config.php');
        require(FW_DIR.'core/loader.php');
        require(FW_DIR.'core/error.php');
        require(FW_DIR.'core/hook.php');
        register_shutdown_function(function () {
            self::$app = null;
            Hook::listen('exit');
            function_exists('fastcgi_finish_request') && fastcgi_finish_request();
            Hook::listen('close');
        });
        Hook::listen('init');
    }
    
    public static function start($app = 'standard', array $config = null)
    {
        if (!self::$app) {
            self::boot();
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
                if (isset(self::$app->config['enable_auth'])) {
                    Auth::passport($dispatch['call']);
                }
                Hook::listen('start');
                return self::$app;
            }
            self::abort(404);
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
    
    public static function setErrorHandler(callable $handler)
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
