<?php 
namespace framework;

use framework\core\Hook;
use framework\core\Error;
use framework\core\Config;

abstract class App
{
    // 是否为命令行应用
    const IS_CLI  = PHP_SAPI === 'cli';
    // 版本号
    const VERSION = '1.0.0';
    // 应用实例容器
    private static $app;
    // 标示boot方法是否已执行，防止重复执行
    private static $boot;
    /* 标示退出状态
     * 0 未标示
     * 1 用户强制退出，使用exit
     * 2 请求完成并退出
     * 3 错误退出
     * 4 异常退出
     * 5 致命错误退出
     */
    private static $exit;
    // 标示run方法知否在执行，防止重复执行
    private static $runing;
    // 设置错误处理器
    private static $error_handler;
    // 内置支持的应用模式
    private static $modes = ['Standard', 'Rest', 'Inline', 'View', 'Micro', 'Jsonrpc', 'Grpc', 'Graphql', 'Cli'];
    
    // 应用配置项
    protected $config;
    // 应用调度结果
    protected $dispatch;
    
    /*
     * 应用调度方法，调度失败返回false
     */
    abstract protected function dispatch();
    
    /*
     * 调用应用
     */
    abstract protected function call();
    
    /*
     * 错误处理
     */
    abstract protected function error($code, $message);
    
    /*
     * 响应处理
     */
    abstract protected function response($return);
    
    /*
     * 构造函数，合并配置项
     */
    private function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /*
     * 运行应用
     */
    public function run(callable $return_handler = null)
    {
        if (self::$runing) {
            throw new \RuntimeException('App is runing');
        }
        self::$runing = true;
        $return = $this->call();
        if (!$return_handler || $return_handler($return)) {
            $this->response($return);
        }
        self::$exit = 2;
    }
    
    /*
     * 应用环境初始化
     */
    public static function boot()
    {
        if (self::$boot) return;
        self::$boot = true;
        define('FW_DIR', __DIR__.'/');
        defined('APP_DEBUG')|| define('APP_DEBUG', false);
        defined('ROOT_DIR') || define('ROOT_DIR', dirname(__DIR__).'/');
        if (!defined('APP_DIR')) {
            if (empty($_SERVER['DOCUMENT_ROOT'])) {
                exit('APP_DIR constant not defined');
            }
            define('APP_DIR', dirname($_SERVER['DOCUMENT_ROOT']).'/');
        }
        require FW_DIR.'common.php';
        require FW_DIR.'core/Config.php';
        require FW_DIR.'core/Loader.php';
        require FW_DIR.'core/Hook.php';
        Hook::listen('boot');
        set_error_handler(function (...$e) {
            Error::errorHandler(...$e);
        });
        set_exception_handler(function ($e) {
            Error::exceptionHandler($e);
        });
        register_shutdown_function(function () {
            try {
                if (!self::$exit) {
                    Error::fatalHandler();
                }
                self::$app = null;
                Hook::listen('exit');
            } catch (\Throwable $e) {
                exit("Uncaught ShutdownException: ".$e->getMessage());
            }
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            Hook::listen('close');
        });
    }
    
    /*
     * 启动应用，应用调度成功返回一个应用实例，否则调用abort终止应用
     */
    public static function start($app = 'Standard', array $config = null)
    {
        if (self::$app) return;
        self::boot();
        if (static::class !== __CLASS__) {
            throw new \RuntimeException('Illegal start call');
        }
        if (in_array($app, self::$modes, true)) {
            $class = 'framework\core\app\\'.$app;
        } elseif (is_subclass_of($app, __CLASS__)) {
            $class = $app;
        } else{
            throw new \RuntimeException("Illegal app class: $app");
        }
        define('APP_MODE', $app);
        if ($config === null) {
            $config = Config::get('app');
        } else {
            Config::set('app', $config);
        }
        self::$app = new $class($config);
        self::$app->dispatch = self::$app->dispatch();
        Hook::listen('start', self::$app->dispatch);
        if (self::$app->dispatch) {
            return self::$app;
        }
        self::abort(404);
    }
    
    /*
     * 退出应用
     */
    public static function exit($status = 1)
    {
        if ($status === 0) {
            return self::$exit;
        } elseif ($status === 1) {
            if (!self::$exit) {
                self::$exit = 1;
                exit;
            }
        } else {
            self::$exit = $status;
        }
    }
    
    /*
     * 异常退出应用
     */
    public static function abort($code = null, $message = null)
    {
        if (isset(self::$error_handler)) {
            (self::$error_handler)($code, $message);
        } elseif (isset(self::$app)) {
            self::$app->error($code, $message);
        }
        self::exit();
    }
    
    /*
     * 返回应用实例
     */
    public static function instance()
    {
        return self::$app;
    }
    
    /*
     * 获取调度信息
     */
    public static function getDispatch()
    {
        return self::$app->dispatch;
    }
    
    /*
     * 设置应用错误处理器，由abort方法调用
     */
    public static function setErrorHandler(callable $handler)
    {
        self::$error_handler = $handler;
    }
}
