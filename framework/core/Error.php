<?php
namespace framework\core;

use framework\App;

class Error
{
    const ERROR    = E_USER_ERROR;
    const WARNING  = E_USER_WARNING;
    const NOTICE   = E_USER_NOTICE;
    // 保存错误信息
    private static $errors;
    private static $error_info = [
        E_ERROR             => [Logger::CRITICAL ,'E_ERROR'],
        E_WARNING           => [Logger::WARNING  ,'E_WARNING'],
        E_PARSE             => [Logger::ALERT    ,'E_PARSE'],
        E_NOTICE            => [Logger::NOTICE   ,'E_NOTICE'],
        E_CORE_ERROR        => [Logger::CRITICAL ,'E_CORE_ERROR'],
        E_CORE_WARNING      => [Logger::WARNING  ,'E_CORE_WARNING'],
        E_COMPILE_ERROR     => [Logger::ALERT    ,'E_COMPILE_ERROR'],
        E_COMPILE_WARNING   => [Logger::WARNING  ,'E_COMPILE_WARNING'],
        E_USER_ERROR        => [Logger::ERROR    ,'E_USER_ERROR'],
        E_USER_WARNING      => [Logger::WARNING  ,'E_USER_WARNING'],
        E_USER_NOTICE       => [Logger::NOTICE   ,'E_USER_NOTICE'],
        E_STRICT            => [Logger::NOTICE   ,'E_STRICT'],
        E_RECOVERABLE_ERROR => [Logger::ERROR    ,'E_RECOVERABLE_ERROR'],
        E_DEPRECATED        => [Logger::NOTICE   ,'E_DEPRECATED'],
        E_USER_DEPRECATED   => [Logger::NOTICE   ,'E_USER_DEPRECATED'],
    ];
    
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
        $file = $line = $trace = null;
        $traces = debug_backtrace(APP_DEBUG ? DEBUG_BACKTRACE_PROVIDE_OBJECT : DEBUG_BACKTRACE_IGNORE_ARGS);
        if (isset($traces[$limit])) {
            $type = $class = $function = '';
            extract($traces[$limit]);
            $message = "$class$type$function() $message";
            if (APP_DEBUG) {
                $trace = array_slice($traces, $limit);
            }
        }
        list($level, $prefix) = self::getErrorInfo($code);
        self::record('error.user', $level, $code, "User Error: [$prefix] $message", $file, $line, $trace);
        if (Config::env('STRICT_ERROR_MODE')) {
            self::response();
        }
    }
    
    /*
     * set_error_handler 错误处理器
     */
    public static function errorHandler($code, $message, $file = null, $line = null)
    {
        if (error_reporting() & $code) {
            list($level, $prefix) = self::getErrorInfo($code);
            if (Config::env('STRICT_ERROR_MODE')) {
                throw new \ErrorException("[$prefix] $message", $code, $code, $file, $line);
            } else {
                self::record('error.error', $level, $code, "Error $prefix: $message", $file, $line);
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
        self::record(
            'error.exception',
            Logger::ERROR,
            $e->getCode(),
            sprintf('Uncaught %s: %s', $e instanceof Exception ? $e->getClass() : get_class($e), $e->getMessage()),
            $e->getFile(),
            $e->getLine(),
            APP_DEBUG ? $e->getTrace() : null
        );
        self::response();
    }
    
    /*
     * register_shutdown_function 致命错误处理器
     */
    public static function fatalHandler()
    {
		if (($error = error_get_last())
            && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))
        ) {
            App::exit(5);
            list($level, $prefix) = self::getErrorInfo($error['type']);
            $message = "Fatal Error: [$prefix] $error[message]";
            self::record('error.fatal', $level, $error['type'], $message, $error['file'], $error['line']);
            self::response();
		} else {
            App::exit(0);
            self::record('error.fatal', Logger::NOTICE, 0, 'Unknown exit', null, null);
		}
    }
    
    /*
     * 记录错误
     */
    private static function record($name, $level, $code, $message, $file, $line, $trace = null)
    {
        $context = compact('code', 'file', 'line', 'trace');
        self::$errors[] = compact('level', 'message', 'context');
        Event::trigger($name, $level, $code, $message, $context);
        Logger::write($level, $message, $context);
    }
    
    /*
     * 响应错误给客户端
     */
    private static function response()
    {
        App::abort(500, APP_DEBUG ? self::$errors : null);
    }
    
    /*
     * 获取错误分类信息
     */
    private static function getErrorInfo($code)
    {
        return self::$error_info[$code] ?? [Logger::ERROR, 'UNKNOEN_ERROR'];
    }
}
