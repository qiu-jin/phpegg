<?php
namespace framework\core;

use framework\App;
use framework\util\Arr;
use framework\exception\Exception;
use framework\exception\ErrorException;

class Error
{
    /*
     * 等级常量
     */
    const ERROR    = E_USER_ERROR;
    const WARNING  = E_USER_WARNING;
    const NOTICE   = E_USER_NOTICE;
    // 错误信息
    private static $errors;
	// 错误信息类型
    private static $error_info_types = [
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
     * 获取信息
     */
    public static function get($all = false)
    {
        return $all ? self::$errors : Arr::last(self::$errors);
    }
    
    /*
     * 触发错误
     */
    public static function trigger($message, $code = self::ERROR, $limit = 1)
    {
        $file = $line = $trace = null;
        $traces = debug_backtrace(0);
        if (isset($traces[$limit])) {
            $type = $class = $function = '';
            extract($traces[$limit]);
            $message = "$class$type$function() $message";
            if (\app\env\APP_DEBUG) {
                $trace = array_slice($traces, $limit);
            }
        }
        list($level, $prefix) = self::getErrorInfo($code);
        if ($code === self::ERROR) {
            throw new ErrorException("[$prefix] $message", $code, $file, $line, $trace);
        }
        self::record('error.user', $level, $code, "User Error: [$prefix] $message", $file, $line, $trace);
    }
    
    /*
     * set_error_handler 错误处理器
     */
    public static function errorHandler($code, $message, $file = null, $line = null)
    {
        if (error_reporting() & $code) {
            list($level, $prefix) = self::getErrorInfo($code);
            if (Config::env('STRICT_ERROR_MODE', true)) {
                throw new \ErrorException("[$prefix] $message", 0, $code, $file, $line);
            }
            self::record('error.error', $level, $code, "Error: [$prefix] $message", $file, $line, debug_backtrace(0));
            if ($code & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
                App::exit(3);
                self::respond();
            }
        }
    }
    
    /*
     * set_exception_handler 异常处理器
     */
    public static function exceptionHandler($e)
    {
        App::exit($e instanceof \ErrorException ? 3 : 4);
        self::record(
            'error.exception',
            Logger::ERROR,
            $e->getCode(),
            sprintf('Uncaught %s: %s', get_class($e), $e->getMessage()),
            $e->getFile(),
            $e->getLine(),
            \app\env\APP_DEBUG ? ($e instanceof ErrorException ? $e->getUserTrace() : $e->getTrace()) : null
        );
        self::respond();
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
            self::respond();
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
        //Event::trigger('app.error', compact('code', 'file', 'line', 'trace'));
        Logger::write($level, $message, $context);
    }
    
    /*
     * 响应错误
     */
    private static function respond()
    {
        App::abort(500, \app\env\APP_DEBUG ? self::$errors : null);
    }
    
    /*
     * 获取错误分类信息
     */
    private static function getErrorInfo($code)
    {
        return self::$error_info_types[$code] ?? [Logger::ERROR, 'UNKNOEN_ERROR'];
    }
}
