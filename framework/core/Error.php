<?php
namespace framework\core;

use framework\App;

class Error
{
    const ERROR    = E_USER_ERROR;
    const WARNING  = E_USER_WARNING;
    const NOTICE   = E_USER_NOTICE;
    // 保存错误信息
    private static $error;
    
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
    public static function set($message, $code = self::ERROR, $limit = 1)
    {
        $file = $line = $type = $class = $function = null;
        $level = self::getErrorLevelInfo($code)[0];
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (isset($traces[$limit])) {
            extract($traces[$limit]);
            $message = "$class$type$function() $message";
        }
        if (Config::env('STRICT_ERROR_MODE')) {
            throw new \ErrorException($message, $code, $code, $file, $line);
        } else {
            self::record('error.user', $code, $level, $message, $file, $line);
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
                self::record('error.error', $code, $level, $message, $file, $line);
                if ($level === Logger::CRITICAL || $level === Logger::ALERT || $level === Logger::ERROR ) {
                    App::exit(3);
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
        App::exit(4);
        $level = Logger::ERROR;
        $name  = $e instanceof Exception ? ($e->getClass() ?? 'CoreException') : get_class($e);
        $message = 'Uncaught '.$name.': '.$e->getMessage();
        self::record('error.exception', $e->getCode(), $level, $message, $e->getFile(), $e->getLine());
        self::response();
    }
    
    /*
     * register_shutdown_function 致命错误处理器
     */
    public static function fatalHandler()
    {
		$last_error = error_get_last();
		if ($last_error && ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
            App::exit(5);
            list($level, $prefix) = self::getErrorLevelInfo($last_error['type']);
            $message = 'Fatal Error '.$prefix.': '.$last_error['message'];
            self::record('error.fatal', $last_error['type'], $level, $message, $last_error['file'], $last_error['line']);
            self::response();
		} else {
            App::exit(0);
            self::record('error.fatal', Logger::WARNING, 0, 'Unknown exit', null, null);
		}
    }
    
    /*
     * 响应错误给客户端
     */
    private static function response()
    {
        App::abort(500, APP_DEBUG ? self::$error : null);
    }
    
    /*
     * 记录错误
     */
    private static function record($type, $code, $level, $message, $file, $line, $trace = null)
    {
        $context = compact('file', 'line', 'trace');
        self::$error[] = compact('level', 'message', 'context');
        Event::trigger($type, $code, $level, $message, $context);
        Logger::write($level, $message, $context);
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
