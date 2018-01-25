<?php 
namespace framework;

use framework\core\Error;
use framework\core\Event;
use framework\core\Config;

abstract class App
{
    // 版本号
    const VERSION = '1.0.0';
    // 是否命令行环境
    const IS_CLI  = PHP_SAPI === 'cli';
    // 内置支持的应用模式
    const MODES   = ['Standard', 'Rest', 'Inline', 'View', 'Micro', 'Jsonrpc', 'Grpc', 'Graphql', 'Cli'];
    
    // 应用实例容器
    private static $app;
    // 标示boot方法是否已执行，防止重复执行
    private static $boot;
    /* 标示退出状态
     * 0 未标识退出
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
    
    // 应用配置项
    protected $config;
    // 应用调度结果
    protected $dispatch;
    
    /*
     * 应用调度
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
        if ($config) {
            $this->config = $config + $this->config;
        }
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
        if ($return_handler === null || $return_handler($return) === true) {
            $this->response($return);
        }
        self::$exit = 2;
    }
    
    /*
     * 应用环境初始化
     */
    public static function boot()
    {
        if (self::$boot) {
            return;
        }
        self::$boot = true;
        define('FW_DIR', __DIR__.'/');
        defined('APP_DEBUG')|| define('APP_DEBUG', false);
        defined('ROOT_DIR') || define('ROOT_DIR', dirname(__DIR__).'/');
        if (!defined('APP_DIR')) {
            if (self::IS_CLI) {
                exit('APP_DIR constant not defined');
            }
            define('APP_DIR', dirname($_SERVER['DOCUMENT_ROOT']).'/');
        }
        require FW_DIR.'common.php';
        require FW_DIR.'core/Config.php';
        require FW_DIR.'core/Loader.php';
        set_error_handler(function (...$e) {
            Error::errorHandler(...$e);
        });
        set_exception_handler(function ($e) {
            Error::exceptionHandler($e);
        });
        register_shutdown_function(function () {
            try {
                if (!isset(self::$exit)) {
                    Error::fatalHandler();
                }
                Event::trigger('exit');
            } catch (\Throwable $e) {
                Error::exceptionHandler($e);
            }
            self::$app = null;
            Event::trigger('flush');
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            Event::trigger('close');
        });
        Event::trigger('boot');
    }
    
    /*
     * 启动应用，应用调度成功返回一个应用实例，否则调用abort终止应用
     */
    public static function start($app = 'Standard', array $config = null)
    {
        if (self::$app) {
            return;
        }
        define('APP_MODE', $app);
        if (in_array($app, self::MODES, true)) {
            $class = 'framework\core\app\\'.$app;
        } elseif (is_subclass_of($app, __CLASS__)) {
            $class = $app;
        } else{
            throw new \RuntimeException("Illegal app class: $app");
        }
        if ($config === null) {
            $config = Config::get('app');
        } else {
            Config::set('app', $config);
        }
        self::$app = new $class($config);
        Event::trigger('dispatch', self::$app->dispatch = self::$app->dispatch());
        if (self::$app->dispatch !== false) {
            return self::$app;
        }
        self::abort(404);
    }
    
    /*
     * 退出应用
     */
    public static function exit(int $status = 1)
    {
        if ($status === 1) {
            if (!isset(self::$exit)) {
                self::$exit = 1;
                exit;
            }
        } else {
            self::$exit = $status;
        }
    }
    
    /*
     * 是否已退出应用
     */
    public static function isExit()
    {
        return isset(self::$exit);
    }
    
    /*
     * 异常退出应用
     */
    public static function abort($code = null, $message = null)
    {
        if ((self::$error_handler === null || self::$error_handler($code, $message) === true) && isset(self::$app)) {
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
    public static function getDispatch($name = null)
    {
        return $name === null ? self::$app->dispatch : (self::$app->dispatch[$name] ?? null);
    }
    
    /*
     * 设置应用错误处理器，由abort方法调用
     */
    public static function setErrorHandler(callable $handler)
    {
        self::$error_handler = $handler;
    }
    
    /*
     * 获取控制器类名
     */
    protected function getControllerClass($controller, $check = false)
    {
        $class = "app\\{$this->config['controller_ns']}\\$controller";
        if (isset($this->config['controller_suffix'])) {
            $class .= $this->config['controller_suffix'];
        }
        if (class_exists($class, false)) {
            return $class;
        }
        $file = APP_DIR.strtr($this->config['controller_ns'], '\\', '/')."/$controller.php";
        if (!$check || (preg_match('/^\w+(\\\\\w+)*$/', $controller) && is_php_file($file))) {
            __include($file);
            return $class;
        }
    }
}
App::boot();
