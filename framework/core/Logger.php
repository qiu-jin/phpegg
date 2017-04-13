<?php
namespace framework\core;

use \framework\extend\logger\Formatter;

class Logger
{
    use \framework\extend\logger\Writer;
    
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    
    private static $init;
    private static $writer;
    private static $configs;
    private static $handlers = [];
    private static $level_handler_name = [];
    
    //run this method in last line when load class
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
        Hook::add('exit', __CLASS__.'::clear');
    }
    
    public static function write($level, $message, $context = [])
    {
        if (isset(self::$level_handler_name[$level])) {
            foreach (self::$level_handler_name[$level] as $name) {
                self::getHandler($name)->write($level, $message, $context);
            }
        }
    }
 
    public static function channel($name = null)
    {
        if ($name === null) {
            return self::$writer ? self::$writer : self::$writer = new self();
        } elseif (isset(self::$configs[$name])) {
            return self::getHandler($name);
        }
        return null;
    }
    
    public static function clear()
    {
        self::$writer = null;
        self::$configs = null;
        self::$handlers = null;
        self::$level_handler_name = null;
    }
    
    private static function getHandler($name)
    {
        if (isset(self::$handlers[$name])) {
            return self::$handlers[$name];
        } else {
            return self::$handlers[$name] = driver('logger', self::$configs[$name]['driver'], self::$configs[$name]);
        }
    }
}
Logger::init();
