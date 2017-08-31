<?php
namespace framework\core;

use framework\App;

class Error
{
    const ERROR    = E_USER_ERROR;
    const WARNING  = E_USER_WARNING;
    const NOTICE   = E_USER_NOTICE;
    
    // 标示init方法是否已执行，防止重复执行
    private static $init;
    // 保存错误信息
    private static $error;
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        error_reporting(-1);
        set_error_handler(__CLASS__.'::errorHandler');
        set_exception_handler(__CLASS__.'::exceptionHandler');
        register_shutdown_function(__CLASS__.'::fatalHandler');
    }
    
    /*
     * 获取错误信息
     */
    public static function get($all = false)
    {
        return $all ? self::$error : end(self::$error);
    }
    
    /*
     * 设置错误信息
     */
    public static function set($message, $code = E_USER_ERROR, $limit = 1)
    {
        $file = null;
        $line = null;
        $type = null;
        $class = null;
        $function = null;
        $level = self::getErrorLevelInfo($code)[0];
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (isset($traces[$limit])) {
            extract($traces[$limit]);
            $message = "$class$type$function() $message";
        }
        if (Config::env('STRICT_ERROR_MODE')) {
            throw new \ErrorException($message, $code, $code, $file, $line);
        } else {
            self::record($level, $message, $file, $line);
        }
    }
    
    /*
     * set_error_handler 错误处理器
     */
    public static function errorHandler($code, $message, $file = null, $line = null)
    {
        if (error_reporting() & $code) {
            if (Config::env('STRICT_ERROR_MODE')) {
                throw new \ErrorException($message, $code, $code, $file, $line);
            } else {
                list($level, $prefix) = self::getErrorLevelInfo($code);
                $message = $prefix.': '.$message;
                self::record($level, $message, $file, $line);
                if ($level === Logger::CRITICAL || $level === Logger::ALERT || $level === Logger::ERROR ) {
                    App::finish(3);
                    self::response();
                    return false;
                }
            }
        }
    }
    
    /*
     * set_exception_handler 异常处理器
     */
    public static function exceptionHandler($e)
    {
        App::finish(4);
        $level = Logger::ERROR;
        $message = 'Uncaught Exception '.get_class($e).': '.$e->getMessage();
        self::record($level, $message, $e->getFile(), $e->getLine());
        self::response();
    }
    
    /*
     * register_shutdown_function 致命错误处理器
     */
    public static function fatalHandler()
    {
        if (!App::finish(0)) {
    		$last_error = error_get_last();
    		if ($last_error && ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
                App::finish(5);
                list($level, $prefix) = self::getErrorLevelInfo($last_error['type']);
                $message = 'Fatal Error '.$prefix.': '.$last_error['message'];
                self::record($level, $message, $last_error['file'], $last_error['line']);
                self::response();
    		}
        }
        self::$error = null;
    }
    
    /*
     * 记录错误
     */
    private static function record($level, $message, $file, $line, $trace = null)
    {
        self::$error[] = ['level' => $level, 'message' => $message, 'file' => $file, 'line' => $line, 'trace' => $trace];
        Logger::write($level, $message, ['file' => $file, 'line' => $line]);
    }
    
    /*
     * 响应错误给客户端
     */
    private static function response()
    {
        App::abort(500, APP_DEBUG ? self::$error : null);
    }
    
    /*
     * 获取错误分类信息
     */
    private static function getErrorLevelInfo($code)
    {
        switch ($code) {
            case E_ERROR:
                return [Logger::CRITICAL, 'E_ERROR'];
            case E_WARNING:
                return [Logger::WARNING, 'E_WARNING'];
            case E_PARSE:
                return [Logger::ALERT, 'E_PARSE'];
            case E_NOTICE:
                return [Logger::NOTICE, 'E_NOTICE'];
            case E_CORE_ERROR:
                return [Logger::CRITICAL, 'E_CORE_ERROR'];
            case E_CORE_WARNING:
                return [Logger::WARNING, 'E_CORE_WARNING'];
            case E_COMPILE_ERROR:
                return [Logger::ALERT, 'E_COMPILE_ERROR'];
            case E_COMPILE_WARNING:
                return [Logger::WARNING, 'E_COMPILE_WARNING'];
            case E_USER_ERROR:
                return [Logger::ERROR, 'E_USER_ERROR'];
            case E_USER_WARNING:
                return [Logger::WARNING, 'E_USER_WARNING'];
            case E_USER_NOTICE:
                return [Logger::NOTICE, 'E_USER_NOTICE'];
            case E_STRICT:
                return [Logger::NOTICE, 'E_STRICT'];
            case E_RECOVERABLE_ERROR:
                return [Logger::ERROR, 'E_RECOVERABLE_ERROR'];
            case E_DEPRECATED:
                return [Logger::NOTICE, 'E_DEPRECATED'];
            case E_USER_DEPRECATED:
                return [Logger::NOTICE, 'E_USER_DEPRECATED'];
            default:
                return [Logger::ERROR, 'UNKNOEN_ERROR'];
        }
    }
}
Error::init();
