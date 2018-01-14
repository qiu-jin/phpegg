<?php
namespace framework\core;

class Logger
{
    /*
     * 日志等级常量
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    
    private static $init;
    private static $handlers = [];
    private static $level_handler_name = [];
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($configs = Config::get('logger')) {
            foreach ($configs as $name => $config) {
                if (isset($config['level']) && $name !== null) {
                    foreach (array_unique($config['level']) as $lv) {
                        self::$level_handler_name[$lv][] = $name;
                    }
                }
            }
        }
    }
    
    /*
     * 写入日志
     */
    public static function write($level, $message, $context = null)
    {
        if (isset(self::$level_handler_name[$level])) {
            foreach (self::$level_handler_name[$level] as $name) {
                self::channel($name)->write($level, $message, $context);
            }
        }
    }
 
    /*
     * 获取日志频道实例
     */
    public static function channel($name = null)
    {
        if (isset(self::$handlers[$name])) {
            return self::$handlers[$name];
        }
        if ($name !== null) {
            return self::$handlers[$name] = Container::driver('logger', $name);
        }
        return self::$handlers[null] = new class () extends \framework\driver\logger\Logger {
            public function __construct() {}
            public function write($level, $message, $context = null) {
                Logger::write($level, $message, $context);
            }
        };
    }
}
Logger::init();
