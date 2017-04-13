<?php
namespace framework\core;

use framework\App;

class Error
{
    private static $init;
    private static $error;
    
    //run this method in last line when load class
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        APP_DEBUG ? error_reporting(-1) : error_reporting(0);
        set_error_handler(__CLASS__.'::errorHandler');
        set_exception_handler(__CLASS__.'::exceptionHandler');
        register_shutdown_function(__CLASS__.'::fatalHandler');
    }
    
    public static function trace($message, $level = 'error', $limit = 0)
    {
        $file = null;
        $line = null;
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (isset($traces[$limit])) {
            $file = $traces[$limit]['file'];
            $line = $traces[$limit]['line'];
        }
        self::record($level, $message, $file, $line);
    }
    
    public static function errorHandler($code, $message, $file = null, $line = null)
    {
        list($level, $prefix) = self::getErrorCodeInfo($code);
        $message = $prefix.': '.$message;
        self::record($level, $message, $file, $line);
        if ($level === Logger::CRITICAL || $level === Logger::ALERT || $level === Logger::ERROR ) {
            App::exit(2);
            self::response();
            return false;
        }
    }
    
    public static function exceptionHandler($e)
    {
        App::exit(3);
        $level = Logger::ERROR;
        if ($e instanceof Exception) {
            $name = Exception::class.'\\'.$e->getName();
        } else {
            $name = get_class($e);
        }
        $message = 'Uncaught Exception '.$name.': '.$e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        self::record($level, $message, $file, $line);
        self::response();
    }
    
    public static function fatalHandler()
    {
        if (!App::exit(0)) {
    		$last_error = error_get_last();
    		if (isset($last_error) && ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
                App::exit(4);
                list($level, $prefix) = self::getErrorCodeInfo($last_error['type']);
                $message = 'Fatal Error '.$prefix.': '.$last_error['message'];
                self::record($level, $message, $last_error['file'], $last_error['line']);
                self::response();
    		} else {
                Logger::write(Logger::WARNING, 'unknown exit');
    		}
        }
        self::$error = null;
    }
    
    private static function record($level, $message, $file, $line)
    {
        self::$error[] = ['level' => $level, 'message' => $message, 'file' => $file, 'line' => $line];
        Logger::write($level, $message, ['file' => $file, 'line' => $line]);
    }
    
    private static function response()
    {
        $app = App::load();
        if (is_callable([$app, 'error'])) {
            $app->error(null, APP_DEBUG ? self::$error : null);
        } elseif (APP_DEBUG) {
            dump(self::$error);
        }
    }
    
    private static function getErrorCodeInfo($code)
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
