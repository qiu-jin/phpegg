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
        if ($configs = Config::get('logger')) {
            foreach ($configs as $name => $config) {
				self::$handlers[$name] = null;
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
        }
    }
	
    /*
     * 写入emergency日志
     */
    public static function emergency($message, $context = null)
    {
       self::write(__FUNCTION__, $message, $context);
    }
    
    /*
     * 写入alert日志
     */
    public static function alert($message, $context = null)
    {
        self::write(__FUNCTION__, $message, $context);
    }
    
    /*
     * 写入critical日志
     */
    public static function critical($message, $context = null)
    {
        self::write(__FUNCTION__, $message, $context);
    }
    
    /*
     * 写入error日志
     */
    public static function error($message, $context = null)
    {
        self::write(__FUNCTION__, $message, $context);
    }
    
    /*
     * 写入warning日志
     */
    public static function warning($message, $context = null)
    {
        self::write(__FUNCTION__, $message, $context);
    }
    
    /*
     * 写入notice日志
     */
    public static function notice($message, $context = null)
    {
        self::write(__FUNCTION__, $message, $context);
    }
    
    /*
     * 写入info日志
     */
    public static function info($message, $context = null)
    {
        self::write(__FUNCTION__, $message, $context);
    }
    
    /*
     * 写入debug日志
     */
    public static function debug($message, $context = null)
    {
		self::write(__FUNCTION__, $message, $context);
    }
	
    /*
     * 写入分级日志
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
     * 写入分组日志
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
     * 获取实例
     */
    public static function get($name = null)
    {
		return self::getHandler($name ?? key(self::$handlers));
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
    public static function channel($name = null, $enable_null_handler = false)
    {
		if (!isset($name)) {
			return self::getLevelHandler();
		}
		if (array_key_exists($name, self::$handlers)) {
			return self::getHandler($name);
		}
		if (isset(self::$group_handler_names[$name])) {
			return self::getGroupHandler($name);
		}
		if (is_array($name)) {
			return self::makeGroupHandler($name, $enable_null_handler);
		}
		if ($enable_null_handler) {
			return self::getNullHandler();
		}
    }
    
    /*
     * 日志实例
     */
    private static function getHandler($name, $enable_null_handler = false)
    {
		if (isset(self::$handlers[$name])) {
			return self::$handlers[$name];
		} elseif (array_key_exists($name, self::$handlers)) {
			return self::$handlers[$name] = Container::driver('logger', $name);
		} elseif ($enable_null_handler) {
			return self::getNullHandler();
		}
		throw new \Exception("日志处理器实例不存在: $name");
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
     * 分组日志实例
     */
    private static function getGroupHandler($name)
    {		
		if (isset(self::$group_handlers[$name])) {
			return self::$group_handlers[$name];
		} elseif (isset(self::$group_handler_names[$name])) {
			return self::$group_handlers[$name] = self::makeGroupHandler(self::$group_handler_names[$name]);
		}
		throw new \Exception("分组日志实例不存在: $name");
    }
    
    /*
     * 生成组实例
     */
    private static function makeGroupHandler($names, $enable_null_handler = false)
    {
        return new class ($names, $enable_null_handler) extends LoggerDriver {
            private $names;
			private $enable_null_handler;
            public function __construct($names, $enable_null_handler) {
                $this->names = $names;
				$this->enable_null_handler = $enable_null_handler;
            }
            public function write($level, $message, $context = null) {
                foreach ($this->names as $n) {
                    Logger::getHandler($n, $this->enable_null_handler)->write($level, $message, $context);
                }
            }
        };
    }
}
Logger::__init();
