<?php
namespace framework\core;

use framework\extend\logger\Writer;
use framework\extend\logger\Formatter;

class Logger
{
    use Writer;
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
    
    // 标示init方法是否已执行，防止重复执行
    private static $init;
    private static $writer;
    private static $configs;
    private static $handlers = [];
    private static $level_handler_name = [];
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        $configs = Config::get('logger');
        if ($configs) {
            foreach ($configs as $name => $config) {
                if (isset($config['driver'])) {
                    if (isset($config['level'])) {
                        foreach (array_unique($config['level']) as $lv) {
                            self::$level_handler_name[$lv][] = $name;
                        }
                        unset($config['level']);
                    }
                } else {
                    unset($configs[$name]);
                }
            }
            self::$configs = $configs;
        }
        Hook::add('exit', __CLASS__.'::free');
    }
    
    /*
     * 写入日志
     */
    public static function write($level, $message, $context = [])
    {
        if (isset(self::$level_handler_name[$level])) {
            foreach (self::$level_handler_name[$level] as $name) {
                self::getHandler($name)->write($level, $message, $context);
            }
        }
    }
 
    /*
     * 获取日志频道实例
     */
    public static function channel($name = null)
    {
        if ($name === null) {
            return self::$writer ?? self::$writer = new self();
        } elseif (isset(self::$configs[$name])) {
            return self::getHandler($name);
        }
        return null;
    }
    
    /*
     * 释放资源
     */
    public static function free()
    {
        self::$writer = null;
        self::$handlers = null;
    }
    
    /*
     * 获取日志处理器实例
     */
    private static function getHandler($name)
    {
        if (isset(self::$handlers[$name])) {
            return self::$handlers[$name];
        } else {
            $config = self::$configs[$name];
            $handler = Container::makeDriver('logger', $config);
            if (isset($config['format'])) {
                $handler->setFormatter(new Formatter($config['format']));
            }
            self::$handlers[$name] = $handler;
            return $handler;
        }
    }
}
Logger::init();
