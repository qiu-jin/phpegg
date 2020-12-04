<?php
namespace framework\core;

use framework\driver\logger\Logger as LoggerDriver;

class Logger
{
    /*
     * 日志等级
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
    // 配置
    private static $configs;
    // 日志驱动处理器
    private static $handlers;
	// 空日志处理器
	private static $null_handler;
    // 分级日志处理器
    private static $level_handler;
    // 分组日志处理器
    private static $group_handlers;
    // 分级日志包含的处理器名集合
    private static $level_handler_names;
    // 分组日志包含的处理器名集合
    private static $group_handler_names;
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($configs = Config::read('logger')) {
            foreach ($configs as $name => $config) {
                if (isset($config['level'])) {
                    foreach ((array) $config['level'] as $lv) {
                        self::$level_handler_names[$lv][] = $name;
                    }
                }
                if (isset($config['group'])) {
                    foreach ((array) $config['group'] as $gp) {
                        self::$group_handler_names[$gp][] = $name;
                    }
                }
            }
            self::$configs = $configs;
        }
    }
	
    /*
     * 获取实例
     */
    public static function get($name = null)
    {
		return isset($name) ? self::getHandler($name) : self::getLevelHandler();
    }

    /*
     * 获取组实例
     */
    public static function group($name)
    {
		return is_array($name) ? self::makeGroupHandler($name) : self::getGroupHandler($name);
    }
	
    /*
     * 频道实例
     */
    public static function channel($name = null, $use_null_handler = false)
    {
		if (!isset($name)) {
			return self::getLevelHandler();
		}
		if (isset(self::$configs[$name])) {
			return self::getHandler($name);
		}
		if (isset(self::$group_handler_names[$name])) {
			return self::getGroupHandler($name);
		}
		if (is_array($name)) {
			return self::makeGroupHandler($name);
		}
		if ($use_null_handler) {
			return self::getNullHandler();
		}
    }
    
    /*
     * 分级写入日志
     */
    public static function write($level, $message, $context = null)
    {
        if (isset(self::$level_handler_names[$level])) {
            foreach (self::$level_handler_names[$level] as $n) {
                self::getHandler($n)->write($level, $message, $context);
            }
        }
    }
    
    /*
     * 分组写入日志
     */
    public static function groupWrite($group, $level, $message, $context = null)
    {
		$names = is_array($group) ? $group : (self::$group_handler_names[$group] ?? null);
        if ($names) {
            foreach ($names as $n) {
                self::getHandler($n)->write($level, $message, $context);
            }
        }
    }
    
    /*
     * 日志实例
     */
    private static function getHandler($name)
    {
        return self::$handlers[$name] ?? 
			   self::$handlers[$name] = Container::driver('logger', self::$configs[$name]);
    }
	
    /*
     * 空日志实例
     */
    private static function getNullHandler()
    {
        return self::$null_handler ?? self::$null_handler = new class () extends LoggerDriver {
            public function write($level, $message, $context = null) {}
        };
    }
	
    /*
     * 分组日志实例
     */
    private static function getGroupHandler($name)
    {
        return self::$group_handlers[$name] ?? 
			   self::$group_handlers[$name] = self::makeGroupHandler(self::$group_handler_names[$name]);
    }
    
    /*
     * 分级日志实例
     */
    private static function getLevelHandler()
    {
        return self::$level_handler ?? self::$level_handler = new class () extends LoggerDriver {
            public function write($level, $message, $context = null) {
                Logger::write($level, $message, $context);
            }
        };
    }
    
    /*
     * 组实例
     */
    private static function makeGroupHandler($names)
    {
        return new class ($names) extends LoggerDriver {
            private $names;
            public function __construct($names) {
                $this->names = $names;
            }
            public function write($level, $message, $context = null) {
                foreach ($this->names as $n) {
                    Logger::getHandler($n)->write($level, $message, $context);
                }
            }
        };
    }
}
Logger::__init();
