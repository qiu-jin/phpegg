<?php 
namespace framework;

use framework\core\Error;
use framework\core\Event;
use framework\core\Config;
use framework\core\http\Request;

abstract class App
{
    // 框架版本号
    const FW_VERSION = '1.0.0';
    // 内核版本号
    const CORE_VERSION = '1.0.0';
    // 是否命令行环境
    const IS_CLI = PHP_SAPI === 'cli';
    // 内置应用模式
    const MODES = ['Standard', 'Rest', 'Micro', 'Inline', 'Jsonrpc', 'Grpc', 'Cli'];
    // 应用实例
    private static $app;
    // boot标示，防止重复执行
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
    // runing标示，防止重复执行
    private static $runing;
	// 请求路径
	private static $path;
    // 错误处理器
    private static $error_handler;
    // 返回值处理器
    private static $return_handler;
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
    abstract protected function respond($return);
    
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
     * 应用环境初始化
     */
    public static function boot()
    {
        if (self::$boot) {
            return;
        }
        self::$boot = true;
        define('FW_DIR', __DIR__.'/');
		if (!defined('ROOT_DIR')) {
			define('ROOT_DIR', dirname(__DIR__).'/');
		}
        if (!defined('APP_DIR')) {
            if (self::IS_CLI) {
                define('APP_DIR', dirname(realpath($_SERVER['argv'][0]), 2).'/');
            } else {
                define('APP_DIR', dirname($_SERVER['DOCUMENT_ROOT']).'/');
            }
        }
		// 检测是否安装eggcore扩展
        if (extension_loaded('eggcore')) {
            eggcore_boot(self::VERSION, self::CORE_VERSION);
        } else {
            require FW_DIR.'common.php';
            require FW_DIR.'core/Config.php';
            require FW_DIR.'core/Loader.php';
        }
        set_error_handler(function(...$e) {
            Error::errorHandler(...$e);
        });
        set_exception_handler(function($e) {
            Error::exceptionHandler($e);
        });
        register_shutdown_function(function() {
            try {
	            if (!isset(self::$exit)) {
	                Error::fatalHandler();
	            }
                Event::trigger('exit');
				self::$app = null;
				Event::trigger('flush');
	            if (function_exists('fastcgi_finish_request')) {
	                fastcgi_finish_request();
	            }
	            Event::trigger('close');
            } catch (\Throwable $e) {
                Error::exceptionHandler($e);
            }
        });
        Event::trigger('boot');
    }
    
    /*
     * 启动应用，应用调度成功返回一个应用实例，否则调用abort终止应用
     */
    public static function start($app = 'Standard', $config = 'app')
    {
        if (self::$app) {
            return;
        }
        if (in_array($app, self::MODES)) {
            $app = "framework\core\app\\$app";
        } elseif (!is_subclass_of($app, __CLASS__)) {
            throw new \RuntimeException("Illegal app class: $app");
        }
		return self::$app = new $app(is_array($config) ? $config : Config::read($config));
    }
	
    /*
     * 返回应用实例
     */
    public static function instance()
    {
        return self::$app;
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
		if (!$this->dispatch()) {
			return self::abort(404);
		}
        $return = $this->call();
        self::$exit = 2;
        $handler = $return_handler ?? self::$return_handler;
        if ($handler === null || $handler($return) === true) {
            $this->respond($return);
        }
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
    public static function abort(...$params)
    {
        if (isset(self::$app) && (self::$error_handler === null || self::$error_handler(...$params) === true)) {
            self::$app->error(...$params);
        }
        self::exit();
    }
    
    /*
     * 设置应用错误处理器，由abort方法调用
     */
    public static function setErrorHandler(callable $handler)
    {
        self::$error_handler = $handler;
    }
    
    /*
     * 设置返回值处理器，由run方法调用
     */
    public static function setReturnHandler(callable $handler)
    {
        self::$return_handler = $handler;
    }
	
    /*
     * 设置路径
     */
    public static function setPath($path)
    {
		self::$path = trim($path, '/');
    }
	
    /*
     * 获取路径
     */
    public static function getPath()
    {
		return self::$path ?? trim(rawurldecode(Request::path()), '/');
    }
	
    /*
     * 获取路径数组
     */
    public static function getPathArr()
    {
        return ($path = self::getPath()) ? explode('/', $path) : [];
    }
	
    /*
     * 获取配置值
     */
    public static function getConfig($name = null, $default = null)
    {
		return $name === null ? self::$app->config : (self::$app->config[$name] ?? $default);
    }
	
    /*
     * 获取调度信息
     */
    public static function getDispatch($name = null, $default = null)
    {
        return $name === null ? self::$app->dispatch : (self::$app->dispatch[$name] ?? $default);
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
            __require($file);
            return $class;
        }
    }
	
    /*
     * 绑定键值参数
     */
    protected function bindMethodKvParams($reflection, $params, $check = false)
    {
        if ($reflection->getnumberofparameters() > 0) {
            foreach ($reflection->getParameters() as $param) {
                if (isset($params[$param->name])) {
                    $new_params[] = $params[$param->name];
                } elseif($param->isDefaultValueAvailable()) {
                    $new_params[] = $param->getdefaultvalue();
                } elseif ($check) {
                    return false;
                } else {
                    break;
                }
            }
        }
        return $new_params ?? [];
    }
}
App::boot();
