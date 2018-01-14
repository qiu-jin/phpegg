<?php
namespace framework\core;

use framework\App;

class Error
{
    const ERROR    = E_USER_ERROR;
    const WARNING  = E_USER_WARNING;
    const NOTICE   = E_USER_NOTICE;
    
    private static $error_level_info = [
        E_ERROR             => [Logger::CRITICAL    , 'E_ERROR'],
        E_WARNING           => [Logger::WARNING     , 'E_WARNING'],
        E_PARSE             => [Logger::ALERT       , 'E_PARSE'],
        E_NOTICE            => [Logger::NOTICE      , 'E_NOTICE'],
        E_CORE_ERROR        => [Logger::CRITICAL    , 'E_CORE_ERROR'],
        E_CORE_WARNING      => [Logger::WARNING     , 'E_CORE_WARNING'],
        E_COMPILE_ERROR     => [Logger::ALERT       , 'E_COMPILE_ERROR'],
        E_COMPILE_WARNING   => [Logger::WARNING     , 'E_COMPILE_WARNING'],
        E_USER_ERROR        => [Logger::ERROR       , 'E_USER_ERROR'],
        E_USER_WARNING      => [Logger::WARNING     , 'E_USER_WARNING'],
        E_USER_NOTICE       => [Logger::NOTICE      , 'E_USER_NOTICE'],
        E_STRICT            => [Logger::NOTICE      , 'E_STRICT'],
        E_RECOVERABLE_ERROR => [Logger::ERROR       , 'E_RECOVERABLE_ERROR'],
        E_DEPRECATED        => [Logger::NOTICE      , 'E_DEPRECATED'],
        E_USER_DEPRECATED   => [Logger::NOTICE      , 'E_USER_DEPRECATED'],
    ];
    // 保存错误信息
    private static $errors;
    
    /*
     * 获取错误信息
     */
    public static function get($all = false)
    {
        return $all ? self::$errors : end(self::$errors);
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
		if (($last_error = error_get_last())
            && ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))
        ) {
            App::exit(5);
            list($level, $prefix) = self::getErrorLevelInfo($last_error['type']);
            $message = "Fatal Error $prefix: $last_error[message]";
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
        App::abort(500, APP_DEBUG ? self::$errors : null);
    }
    
    /*
     * 记录错误
     */
    private static function record($name, $code, $level, $message, $file, $line, $trace = null)
    {
        $context = compact('file', 'line', 'trace');
        self::$errors[] = compact('level', 'message', 'context');
        Event::trigger($name, $code, $level, $message, $context);
        Logger::write($level, $message, $context);
    }
    
    /*
     * 获取错误分类信息
     */
    private static function getErrorLevelInfo($code)
    {
        return self::$error_level_info[$code] ?? [Logger::ERROR, 'UNKNOEN_ERROR'];
    }
}
