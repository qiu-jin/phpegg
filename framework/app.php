<?php 
namespace framework;

use framework\core\Hook;
use framework\core\Config;
use framework\core\Exception;

abstract class App
{
    // 应用实例容器
    private static $app;
    // 标示boot方法是否已执行，防止重复执行
    private static $boot;
    /* 标示退出状态
     * 0 未标示
     * 1 请求自然完成并退出
     * 2 用户强制退出，使用exit
     * 3 错误退出，Error::errorHandler调用
     * 4 异常退出，Error::exceptionHandler调用
     * 5 致命错误退出，Error::fatalHandler调用
     */
    private static $exit;
    // 标示run方法知否在执行，防止重复执行
    private static $runing;
    // 设置错误处理器
    private static $error_handler;
    // 应用配置项
    protected $config = [];
    // 应用调度结果
    protected $dispatch = [];
    
    /*
     * 运行应用
     */
    abstract public function run(callable $return_handler);
    
    /*
     * 应用调度方法，调度成功返回数组，失败返回false
     */
    abstract protected function dispatch();
    
    /*
     * 构造函数，合并配置项
     */
    private function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /*
     * 应用环境初始化
     */
    public static function boot()
    {
        if (self::$boot) return;
        self::$boot = true;
        define('FW_DIR', __DIR__.'/');
        define('APP_TIME', microtime(true));
        define('APP_VERSION', '2.0.0');
        defined('APP_DEBUG')|| define('APP_DEBUG', false);
        defined('ROOT_DIR') || define('ROOT_DIR', dirname(__DIR__).'/');
        defined('APP_DIR')  || define('APP_DIR', dirname($_SERVER['DOCUMENT_ROOT']).'/');
        require(FW_DIR.'common.php');
        require(FW_DIR.'core/Config.php');
        require(FW_DIR.'core/Loader.php');
        require(FW_DIR.'core/Error.php');
        require(FW_DIR.'core/Hook.php');
        register_shutdown_function(function () {
            var_dump(self::$exit);exit;
            
            self::$app = null;
            Hook::listen('exit');
            function_exists('fastcgi_finish_request') && fastcgi_finish_request();
            Hook::listen('close');
        });
        Hook::listen('boot');
    }
    
    /*
     * 启动应用，应用调度成功返回一个应用实例，否则调用abort终止应用
     */
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
            self::$app->dispatch = self::$app->dispatch();
            Hook::listen('start', self::$app->dispatch);
            if (self::$app->dispatch) {
                return self::$app;
            }
            self::abort(404);
        }
    }
    
    /*
     * 退出应用，为兼容PHP5.6使用finish替换exit方法名
     */
    public static function finish($status = 2)
    {
        if ($status === 0) {
            return (bool) self::$exit;
        } elseif ($status === 2) {
            if (!self::$exit) {
                self::$exit = 2;
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
            self::$error_handler($code, $message);
        } elseif (is_callable([self::$app, 'error'])) {
            self::$app->error($code, $message);
        }
        self::finish();
    }
    
    /*
     * 返回应用实例
     */
    public static function instance()
    {
        return self::$app;
    }
    
    
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
    
    /*
     * 设置或检查run方法是否已经调用
     */
    protected function runing()
    {
        if (self::$runing) {
            throw new \Exception('App is runing');
        }
        self::$runing = true;
    }
}
